/* QR Code Studio — validation, autocorrect, QR rendering.
 * Pure client-side. Depends on globals: `qrcode` (vendor/qrcode.js).
 */
(function () {
    'use strict';

    /* ───────────────────────────  Constants  ─────────────────────────── */

    // Common email domains we autocorrect against.
    var KNOWN_EMAIL_DOMAINS = [
        'gmail.com', 'googlemail.com',
        'yahoo.com', 'yahoo.co.uk', 'ymail.com',
        'hotmail.com', 'hotmail.co.uk',
        'outlook.com', 'live.com', 'msn.com',
        'icloud.com', 'me.com', 'mac.com',
        'aol.com',
        'proton.me', 'protonmail.com',
        'fastmail.com', 'pm.me',
        'zoho.com', 'gmx.com', 'mail.com',
        'duck.com',
        'comcast.net', 'verizon.net', 'att.net', 'sbcglobal.net',
        'decisionsciencecorp.com'
    ];

    // Common TLD typos → correct TLD.
    var TLD_FIXES = {
        'con': 'com', 'cmo': 'com', 'cm': 'com', 'om': 'com', 'comm': 'com', 'cim': 'com',
        'og': 'org', 'or': 'org', 'orgg': 'org',
        'ent': 'net', 'nt': 'net', 'nett': 'net',
        'ed': 'edu', 'edy': 'edu',
        'gv': 'gov',
        'ko': 'co', // .co is real, only fix obvious mistypes
        'iio': 'io', 'oi': 'io',
        'cu': 'us'
    };

    // Common false-typo TLDs that look wrong but are real — never autocorrect.
    var REAL_SHORT_TLDS = new Set([
        'co', 'io', 'ai', 'us', 'uk', 'ca', 'au', 'de', 'fr', 'jp', 'cn', 'br',
        'in', 'mx', 'es', 'it', 'nl', 'se', 'no', 'fi', 'dk', 'pl', 'ru', 'ch',
        'app', 'dev', 'tech', 'tv', 'me', 'cc', 'tv', 'xyz', 'site', 'shop',
        'org', 'net', 'com', 'edu', 'gov', 'mil', 'biz', 'info'
    ]);

    /* ───────────────────────────  DOM refs  ──────────────────────────── */

    var $content        = document.getElementById('content');
    var $banner         = document.getElementById('suggestion-banner');
    var $suggestionText = document.getElementById('suggestion-text');
    var $accept         = document.getElementById('suggestion-accept');
    var $dismiss        = document.getElementById('suggestion-dismiss');
    var $encoded        = document.getElementById('encoded-value');
    var $errorRow       = document.getElementById('error-row');
    var $errorText      = document.getElementById('error-text');
    var $stage          = document.getElementById('qr-stage');
    var $canvas         = document.getElementById('qr-canvas');
    var $previewMeta    = document.getElementById('preview-meta');
    var $downloadPng    = document.getElementById('download-png');
    var $downloadSvg    = document.getElementById('download-svg');
    var $ecl            = document.getElementById('ecl');
    var $cellSize       = document.getElementById('cell-size');
    var $margin         = document.getElementById('margin');
    var $year           = document.getElementById('year');
    var $logoFile       = document.getElementById('logo-file');
    var $logoPreview    = document.getElementById('logo-preview');
    var $logoPreviewImg = document.getElementById('logo-preview-img');
    var $logoRemove     = document.getElementById('logo-remove');
    var $logoUploadText = document.getElementById('logo-upload-text');
    var $logoSize       = document.getElementById('logo-size');
    var $logoSizeField  = document.getElementById('logo-size-field');
    var $logoSizeValue  = document.getElementById('logo-size-value');

    var ECL_RANK = { L: 0, M: 1, Q: 2, H: 3 };
    var LOGO_MAX_BYTES = 2 * 1024 * 1024;

    /* ───────────────────────────  State  ─────────────────────────────── */

    var state = {
        type: 'auto',           // auto | url | email | tel | text
        currentSuggestion: null, // string | null
        lastValidPayload: null,  // render snapshot for downloads
        logo: null               // { image, dataUrl, name, sizePct } | null
    };

    /* ───────────────────────────  Helpers  ───────────────────────────── */

    function debounce(fn, ms) {
        var t = null;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    /** Damerau-Levenshtein distance (1 = adjacent transposition). */
    function dlDistance(a, b) {
        a = String(a); b = String(b);
        var m = a.length, n = b.length;
        if (!m) return n;
        if (!n) return m;
        // Cap large inputs early — domain names are short.
        if (Math.abs(m - n) > 4) return Math.abs(m - n) + 99;
        var d = [];
        for (var i = 0; i <= m; i++) d[i] = [i];
        for (var j = 0; j <= n; j++) d[0][j] = j;
        for (i = 1; i <= m; i++) {
            for (j = 1; j <= n; j++) {
                var cost = a.charAt(i - 1) === b.charAt(j - 1) ? 0 : 1;
                d[i][j] = Math.min(
                    d[i - 1][j] + 1,           // deletion
                    d[i][j - 1] + 1,           // insertion
                    d[i - 1][j - 1] + cost     // substitution
                );
                if (i > 1 && j > 1 &&
                    a.charAt(i - 1) === b.charAt(j - 2) &&
                    a.charAt(i - 2) === b.charAt(j - 1)) {
                    d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + 1);
                }
            }
        }
        return d[m][n];
    }

    /** Closest known domain within distance `maxDist`, or null. */
    function nearestDomain(domain, candidates, maxDist) {
        var best = null;
        var bestDist = maxDist + 1;
        for (var i = 0; i < candidates.length; i++) {
            var c = candidates[i];
            if (c === domain) return null; // already exact
            var dist = dlDistance(domain, c);
            // Reject suggestion if user's input is itself a substring extension
            // of an unrelated domain (e.g., don't suggest gmail.com for amail.com
            // which is plausibly real).
            if (dist < bestDist) {
                bestDist = dist;
                best = c;
            }
        }
        return best;
    }

    /** Apply TLD-only corrections (e.g. .con → .com). */
    function fixTld(domain) {
        var idx = domain.lastIndexOf('.');
        if (idx < 0 || idx === domain.length - 1) return null;
        var tld = domain.substring(idx + 1).toLowerCase();
        if (TLD_FIXES[tld]) {
            return domain.substring(0, idx + 1) + TLD_FIXES[tld];
        }
        return null;
    }

    /** Heuristic: input looks like a URL (has a TLD-ish suffix or scheme). */
    function looksLikeUrl(s) {
        if (/^[a-z][a-z0-9+\-.]*:\/\//i.test(s)) return true;
        if (/^[a-z][a-z0-9+\-.]*:[^/]/i.test(s)) return true; // mailto:, tel:, etc.
        // domain.tld[/path] — at least one dot, no spaces, looks domain-y.
        if (/\s/.test(s)) return false;
        if (!/\./.test(s)) return false;
        return /^[\w\-]+(\.[\w\-]+)+(\/.*)?$/.test(s);
    }

    function looksLikeEmail(s) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s);
    }

    function looksLikePhone(s) {
        // strip formatting; keep + and digits
        var digits = s.replace(/[\s\-().]/g, '');
        return /^\+?\d{6,16}$/.test(digits);
    }

    /* ───────────────────────  Type detection / encoding  ──────────────── */

    /**
     * Returns { encoded, type, suggestion?, error? }.
     * `encoded` is the actual QR payload. `suggestion` (if present) is a
     * cleaner version of the raw input the user can opt into.
     */
    function processInput(raw, forcedType) {
        var trimmed = raw.trim();
        if (!trimmed) return { error: null, type: forcedType || 'auto' };

        var type = forcedType || 'auto';

        if (type === 'auto') {
            if (/^mailto:/i.test(trimmed) || looksLikeEmail(trimmed.replace(/^mailto:/i, ''))) {
                type = 'email';
            } else if (/^tel:/i.test(trimmed) || looksLikePhone(trimmed)) {
                type = 'tel';
            } else if (looksLikeUrl(trimmed)) {
                type = 'url';
            } else {
                type = 'text';
            }
        }

        switch (type) {
            case 'email':   return processEmail(trimmed);
            case 'tel':     return processPhone(trimmed);
            case 'url':     return processUrl(trimmed);
            case 'text':    return { encoded: trimmed, type: 'text' };
            default:        return { encoded: trimmed, type: 'text' };
        }
    }

    function processUrl(raw) {
        var s = raw.replace(/^\s+|\s+$/g, '');

        // Strip any user-typed mailto/tel scheme that landed in URL slot.
        if (/^mailto:/i.test(s)) {
            return processEmail(s.replace(/^mailto:/i, ''));
        }
        if (/^tel:/i.test(s)) {
            return processPhone(s.replace(/^tel:/i, ''));
        }

        // If no scheme, infer https:// silently (this is the friendly behavior).
        var hadScheme = /^[a-z][a-z0-9+\-.]*:\/\//i.test(s);
        var encoded = hadScheme ? s : 'https://' + s;

        // Try to parse with URL — gives us cheap validation + lets us inspect host.
        var parsed = null;
        try { parsed = new URL(encoded); }
        catch (e) {
            return { error: 'That doesn\'t look like a valid URL. Try something like ' +
                'example.com or https://example.com/path.', type: 'url' };
        }

        var host = parsed.hostname.toLowerCase();
        if (!host || !/\./.test(host) && host !== 'localhost') {
            return { error: 'A URL needs a domain (e.g. example.com).', type: 'url' };
        }

        // Look for TLD typo we can suggest.
        var fixed = fixTld(host);
        var suggestion = null;
        if (fixed && fixed !== host) {
            var clone = new URL(encoded);
            clone.hostname = fixed;
            suggestion = clone.toString();
        }

        return {
            encoded: encoded,
            type: 'url',
            suggestion: suggestion,
            suggestionReason: suggestion ? 'TLD typo' : null
        };
    }

    function processEmail(raw) {
        var s = raw.replace(/^mailto:/i, '').trim();

        if (!/@/.test(s)) {
            return { error: 'An email address needs an @ — try name@example.com.', type: 'email' };
        }
        if (!looksLikeEmail(s)) {
            return { error: 'That email looks incomplete. Use the form name@example.com.', type: 'email' };
        }

        var atIdx = s.lastIndexOf('@');
        var local = s.substring(0, atIdx);
        var domain = s.substring(atIdx + 1).toLowerCase();

        // 1. Try TLD fix first (cheap, deterministic).
        var tldFixed = fixTld(domain);
        var fixedDomain = tldFixed || domain;

        // 2. Then try near-match against known domains, distance ≤2.
        var suggestion = null;
        var reason = null;

        if (tldFixed) {
            suggestion = local + '@' + tldFixed;
            reason = 'TLD typo';
        }

        var nearest = nearestDomain(fixedDomain, KNOWN_EMAIL_DOMAINS, 2);
        if (nearest && nearest !== fixedDomain) {
            // Don't replace short real TLDs (e.g. domain `co` is real).
            // We only autocorrect to provider-style domains.
            suggestion = local + '@' + nearest;
            reason = 'common provider typo';
        }

        return {
            encoded: 'mailto:' + s,
            type: 'email',
            suggestion: suggestion ? 'mailto:' + suggestion : null,
            suggestionReason: reason
        };
    }

    function processPhone(raw) {
        var s = raw.replace(/^tel:/i, '').trim();
        // Keep + and digits; strip the rest.
        var digits = s.replace(/[\s\-().]/g, '');

        if (!/^\+?\d{6,16}$/.test(digits)) {
            return { error: 'Use digits only, optionally with a leading + and country code (e.g. +1 555 010 0123).', type: 'tel' };
        }

        return { encoded: 'tel:' + digits, type: 'tel' };
    }

    /* ───────────────────────  QR rendering  ──────────────────────────── */

    /** Build the QR matrix and render to canvas. Returns { matrix, moduleCount, qr }. */
    function buildQr(payload, ecl) {
        // typeNumber 0 = auto-pick smallest that fits
        var qr = qrcode(0, ecl);
        qr.addData(payload);
        qr.make();
        return qr;
    }

    function renderToCanvas(qr, cellSize, margin, logo, logoSizePct) {
        var moduleCount = qr.getModuleCount();
        var size = (moduleCount + margin * 2) * cellSize;
        $canvas.width = size;
        $canvas.height = size;
        var ctx = $canvas.getContext('2d');
        // Background (white)
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);
        // Modules (black)
        ctx.fillStyle = '#0a0a0a';
        for (var r = 0; r < moduleCount; r++) {
            for (var c = 0; c < moduleCount; c++) {
                if (qr.isDark(r, c)) {
                    ctx.fillRect(
                        (margin + c) * cellSize,
                        (margin + r) * cellSize,
                        cellSize,
                        cellSize
                    );
                }
            }
        }
        if (logo && logo.image) {
            drawCenterLogo(ctx, size, logo.image, logoSizePct);
        }
    }

    function roundRectPath(ctx, x, y, w, h, r) {
        r = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    /** White pad + centered logo — sized as a fraction of the full canvas. */
    function drawCenterLogo(ctx, canvasSize, image, sizePct) {
        var pct = Math.max(10, Math.min(30, sizePct || 22)) / 100;
        var logoSide = canvasSize * pct;
        var padSide = logoSide * 1.14;
        var cx = canvasSize / 2;
        var cy = canvasSize / 2;
        var padX = cx - padSide / 2;
        var padY = cy - padSide / 2;
        var radius = Math.max(4, padSide * 0.12);

        ctx.fillStyle = '#ffffff';
        roundRectPath(ctx, padX, padY, padSide, padSide, radius);
        ctx.fill();

        var imgX = cx - logoSide / 2;
        var imgY = cy - logoSide / 2;
        ctx.drawImage(image, imgX, imgY, logoSide, logoSide);
    }

    function buildSvgString(qr, cellSize, margin, logo, logoSizePct) {
        var moduleCount = qr.getModuleCount();
        var size = (moduleCount + margin * 2) * cellSize;
        var rects = [];
        for (var r = 0; r < moduleCount; r++) {
            for (var c = 0; c < moduleCount; c++) {
                if (qr.isDark(r, c)) {
                    rects.push(
                        '<rect x="' + (margin + c) * cellSize +
                        '" y="' + (margin + r) * cellSize +
                        '" width="' + cellSize +
                        '" height="' + cellSize + '"/>'
                    );
                }
            }
        }

        var logoMarkup = '';
        if (logo && logo.dataUrl) {
            var pct = Math.max(10, Math.min(30, logoSizePct || 22)) / 100;
            var logoSide = size * pct;
            var padSide = logoSide * 1.14;
            var padX = (size - padSide) / 2;
            var padY = (size - padSide) / 2;
            var imgX = (size - logoSide) / 2;
            var imgY = (size - logoSide) / 2;
            var radius = Math.max(4, padSide * 0.12);
            logoMarkup =
                '<rect x="' + padX + '" y="' + padY + '" width="' + padSide +
                '" height="' + padSide + '" rx="' + radius + '" fill="#ffffff"/>' +
                '<image href="' + escapeAttr(logo.dataUrl) + '" x="' + imgX +
                '" y="' + imgY + '" width="' + logoSide + '" height="' + logoSide +
                '" preserveAspectRatio="xMidYMid meet"/>';
        }

        return [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size + '" width="' + size + '" height="' + size + '" shape-rendering="crispEdges">',
            '<rect width="' + size + '" height="' + size + '" fill="#ffffff"/>',
            '<g fill="#0a0a0a">',
            rects.join(''),
            '</g>',
            logoMarkup,
            '</svg>'
        ].join('');
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    }

    function effectiveEcl(selected, hasLogo) {
        if (!hasLogo) return selected || 'M';
        // Logos obscure modules — never go below Q when a center image is present.
        return (ECL_RANK[selected] || 0) < ECL_RANK.Q ? 'Q' : selected;
    }

    function logoSizePct() {
        return Math.max(10, Math.min(30, parseInt($logoSize.value, 10) || 22));
    }

    function syncLogoUi() {
        var hasLogo = !!(state.logo && state.logo.image);
        $logoPreview.hidden = !hasLogo;
        $logoSizeField.hidden = !hasLogo;
        if (hasLogo) {
            $logoPreviewImg.src = state.logo.dataUrl;
            $logoUploadText.textContent = 'Replace image';
        } else {
            $logoPreviewImg.removeAttribute('src');
            $logoUploadText.textContent = 'Upload image';
            $logoFile.value = '';
        }
        $logoSizeValue.textContent = logoSizePct() + '%';
    }

    function clearLogo() {
        state.logo = null;
        syncLogoUi();
        update();
    }

    function loadLogoFile(file) {
        if (!file) return;
        if (!/^image\/(png|jpeg|gif|webp|svg\+xml)$/i.test(file.type)) {
            showError('Logo must be PNG, JPEG, GIF, WebP, or SVG.');
            return;
        }
        if (file.size > LOGO_MAX_BYTES) {
            showError('Logo is too large — keep it under 2 MB.');
            return;
        }

        var reader = new FileReader();
        reader.onload = function () {
            var dataUrl = reader.result;
            var img = new Image();
            img.onload = function () {
                state.logo = {
                    image: img,
                    dataUrl: dataUrl,
                    name: file.name,
                    sizePct: logoSizePct()
                };
                syncLogoUi();
                // Bump error correction so branded codes stay scannable.
                if ((ECL_RANK[$ecl.value] || 0) < ECL_RANK.Q) {
                    $ecl.value = 'Q';
                }
                clearError();
                update();
            };
            img.onerror = function () {
                showError('Could not load that image — try a different file.');
            };
            img.src = dataUrl;
        };
        reader.onerror = function () {
            showError('Could not read that file.');
        };
        reader.readAsDataURL(file);
    }

    /* ─────────────────────────  Wiring  ──────────────────────────────── */

    function setStatus(state_str) {
        $stage.setAttribute('data-state', state_str);
        // Toggle the canvas + empty placeholder visibility ourselves rather
        // than leaning on `[hidden]` + descendant selectors — the global
        // `[hidden] { display:none !important }` would otherwise win.
        var ready = state_str === 'ready';
        $canvas.hidden = !ready;
        document.getElementById('qr-empty').hidden = ready;
    }

    function showError(msg) {
        $errorRow.hidden = false;
        $errorText.textContent = msg;
    }

    function clearError() { $errorRow.hidden = true; }

    function hideSuggestion() {
        $banner.hidden = true;
        $banner.removeAttribute('data-active-suggestion');
        $banner.removeAttribute('data-active-source');
        state.currentSuggestion = null;
    }

    function showSuggestion(suggestionRaw, reason, original) {
        var displaySuggestion = suggestionRaw.replace(/^mailto:/, '');
        var displayOriginal   = original.replace(/^mailto:/, '');
        $suggestionText.innerHTML =
            'Did you mean <strong>' + escapeHtml(displaySuggestion) + '</strong>?' +
            (reason ? ' <span style="opacity:0.75">(' + escapeHtml(reason) + ')</span>' : '') +
            '<br><span style="font-size:0.83rem;color:var(--text-muted)">You typed: ' + escapeHtml(displayOriginal) + '</span>';
        $banner.hidden = false;
        $banner.setAttribute('data-active-suggestion', suggestionRaw);
        state.currentSuggestion = suggestionRaw;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function setDownloadEnabled(on) {
        $downloadPng.disabled = !on;
        $downloadSvg.disabled = !on;
    }

    function update() {
        var raw = $content.value;
        var result = processInput(raw, state.type === 'auto' ? null : state.type);

        if (!raw.trim()) {
            // Empty state.
            clearError();
            hideSuggestion();
            $encoded.textContent = '—';
            $previewMeta.textContent = 'Type to generate.';
            setStatus('empty');
            setDownloadEnabled(false);
            state.lastValidPayload = null;
            return;
        }

        if (result.error) {
            clearError(); // keep suggestion area clean even on error
            hideSuggestion();
            showError(result.error);
            $encoded.textContent = '—';
            $previewMeta.textContent = 'Fix the issues above to generate a code.';
            setStatus('error');
            setDownloadEnabled(false);
            state.lastValidPayload = null;
            return;
        }

        clearError();

        if (result.suggestion) {
            showSuggestion(result.suggestion, result.suggestionReason, result.encoded);
        } else {
            hideSuggestion();
        }

        $encoded.textContent = result.encoded;

        // Render QR.
        var hasLogo = !!(state.logo && state.logo.image);
        var ecl = effectiveEcl($ecl.value || 'M', hasLogo);
        var cellSize = Math.max(2, Math.min(20, parseInt($cellSize.value, 10) || 8));
        var margin = Math.max(0, Math.min(16, parseInt($margin.value, 10) || 4));
        var logoPct = logoSizePct();

        try {
            var qr = buildQr(result.encoded, ecl);
            renderToCanvas(qr, cellSize, margin, state.logo, logoPct);
            setStatus('ready');
            setDownloadEnabled(true);
            state.lastValidPayload = {
                encoded: result.encoded,
                type: result.type,
                qr: qr,
                cellSize: cellSize,
                margin: margin,
                logo: state.logo,
                logoSizePct: logoPct,
                ecl: ecl
            };
            var meta = humanType(result.type) +
                ' · ' + qr.getModuleCount() + '×' + qr.getModuleCount() +
                ' · ECL ' + ecl;
            if (hasLogo) meta += ' · logo ' + logoPct + '%';
            $previewMeta.textContent = meta;
        } catch (e) {
            showError('Couldn\'t encode that — payload may be too long for a single QR code.');
            setStatus('error');
            setDownloadEnabled(false);
            state.lastValidPayload = null;
        }
    }

    function humanType(t) {
        return ({
            url: 'Web link',
            email: 'Email',
            tel: 'Phone number',
            text: 'Plain text'
        })[t] || 'QR';
    }

    /* ─────────────────────────  Listeners  ───────────────────────────── */

    var debouncedUpdate = debounce(update, 120);

    $content.addEventListener('input', debouncedUpdate);
    $ecl.addEventListener('change', update);
    $cellSize.addEventListener('input', debouncedUpdate);
    $margin.addEventListener('input', debouncedUpdate);
    $logoFile.addEventListener('change', function () {
        loadLogoFile($logoFile.files && $logoFile.files[0]);
    });
    $logoRemove.addEventListener('click', clearLogo);
    $logoSize.addEventListener('input', function () {
        $logoSizeValue.textContent = logoSizePct() + '%';
        if (state.logo) {
            state.logo.sizePct = logoSizePct();
            debouncedUpdate();
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('.type-pill'), function (pill) {
        pill.addEventListener('click', function () {
            Array.prototype.forEach.call(document.querySelectorAll('.type-pill'), function (p) {
                p.classList.remove('is-active');
                p.setAttribute('aria-checked', 'false');
            });
            pill.classList.add('is-active');
            pill.setAttribute('aria-checked', 'true');
            state.type = pill.getAttribute('data-type');
            update();
            $content.focus();
        });
    });

    $accept.addEventListener('click', function () {
        if (!state.currentSuggestion) return;
        // Strip mailto: when stuffing into the textarea — the field is for the
        // human-readable address; processEmail re-adds the scheme on encoding.
        var v = state.currentSuggestion.replace(/^mailto:/, '');
        $content.value = v;
        hideSuggestion();
        update();
        $content.focus();
    });

    $dismiss.addEventListener('click', function () {
        hideSuggestion();
        $content.focus();
    });

    $downloadPng.addEventListener('click', function () {
        if (!state.lastValidPayload) return;
        var link = document.createElement('a');
        link.download = filenameFor(state.lastValidPayload, 'png');
        link.href = $canvas.toDataURL('image/png');
        link.click();
    });

    $downloadSvg.addEventListener('click', function () {
        if (!state.lastValidPayload) return;
        var p = state.lastValidPayload;
        var svg = buildSvgString(p.qr, p.cellSize, p.margin, p.logo, p.logoSizePct);
        var blob = new Blob([svg], { type: 'image/svg+xml' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.download = filenameFor(p, 'svg');
        link.href = url;
        link.click();
        // Release the blob URL after the download starts (defer one frame so
        // Safari has time to kick off the download).
        setTimeout(function () { URL.revokeObjectURL(url); }, 250);
    });

    function filenameFor(p, ext) {
        var raw = p.encoded;
        // Build a filesystem-safe slug.
        var slug = raw
            .replace(/^https?:\/\//, '')
            .replace(/^mailto:/, '')
            .replace(/^tel:/, '')
            .replace(/[^A-Za-z0-9._-]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 48) || 'qrcode';
        return 'qr-' + slug + '.' + ext;
    }

    /* ─────────────────────────  Init  ────────────────────────────────── */

    $year.textContent = new Date().getFullYear();
    syncLogoUi();
    update();
})();
