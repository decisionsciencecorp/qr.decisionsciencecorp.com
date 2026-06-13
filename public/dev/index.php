<?php
/**
 * QR Code Studio DEV hub (AGPL-3.0)
 */
declare(strict_types=1);

$docs = [
    ['path' => 'agents', 'title' => 'agents.md — Agent integration', 'badge' => 'Start here'],
    ['path' => 'api', 'title' => 'REST API reference', 'badge' => 'API'],
    ['path' => 'sdk-php', 'title' => 'PHP SDK', 'badge' => 'SDK'],
    ['path' => 'sdk-python', 'title' => 'Python SDK', 'badge' => 'SDK'],
    ['path' => 'smcp', 'title' => 'SMCP plugin', 'badge' => 'Agents'],
    ['path' => 'licensing', 'title' => 'Licensing', 'badge' => 'Legal'],
    ['path' => 'index', 'title' => 'Developer overview', 'badge' => 'Guide'],
];

$openapiUrl = '/api/v1/openapi.json';
$agentsRepoUrl = 'https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/agents.md';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEV — QR Code Studio</title>
    <meta name="description" content="Developer documentation for QR Code Studio — API, SDKs, SMCP plugin, and agent integration.">
    <meta name="robots" content="index, follow">
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="../fonts/inter.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="dev.css">
</head>
<body class="dev-body">
    <nav class="navbar dev-navbar" id="navbar">
        <div class="nav-container">
            <a href="../index.html" class="nav-logo" aria-label="QR Code Studio">
                <img src="../assets/images/logo-white.svg" alt="" class="nav-logo-img" aria-hidden="true">
                <span class="nav-logo-text">QR Code Studio</span>
            </a>
            <span class="dev-pill" aria-label="Developer section">DEV</span>
            <ul class="nav-menu" id="nav-menu">
                <li><a href="../index.html" class="nav-link">Studio</a></li>
            </ul>
        </div>
    </nav>

    <header class="dev-hero">
        <div class="container">
            <p class="hero-eyebrow">Developer documentation</p>
            <h1 class="dev-hero__title">Build on QR Code Studio</h1>
            <p class="dev-hero__lead">
                Public REST API, PHP and Python SDKs, and an SMCP plugin for agents.
                Source code is <strong>AGPL-3.0</strong>; docs and creative assets are <strong>CC BY-SA 4.0</strong>.
            </p>
            <div class="dev-hero__actions">
                <a class="btn btn-primary" href="docs.php?path=agents">Read agents.md</a>
                <a class="btn btn-secondary" href="docs.php?path=api">API reference</a>
                <a class="btn btn-secondary" href="<?= htmlspecialchars($openapiUrl, ENT_QUOTES) ?>">OpenAPI JSON</a>
            </div>
        </div>
    </header>

    <main class="container dev-main">
        <section class="dev-card dev-card--featured">
            <h2 class="dev-card__title">For AI agents</h2>
            <p>
                Start with <a href="docs.php?path=agents"><code>agents.md</code></a> — integration patterns,
                rate limits, and the SMCP tool surface. The same file lives in the
                <a href="<?= htmlspecialchars($agentsRepoUrl, ENT_QUOTES) ?>">GitHub repo root</a>.
            </p>
            <ul class="dev-quicklinks">
                <li><a href="docs.php?path=api">REST API</a> — <code>POST /api/v1/normalize.php</code>, <code>generate.php</code></li>
                <li><a href="docs.php?path=smcp">SMCP plugin</a> — <a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/smcp_plugin">GitHub</a> · <code>cli.py --describe</code></li>
                <li><a href="docs.php?path=sdk-php">PHP SDK</a> — <a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/php">GitHub</a></li>
                <li><a href="docs.php?path=sdk-python">Python SDK</a> — <a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/python">GitHub</a></li>
                <li><a href="<?= htmlspecialchars($openapiUrl, ENT_QUOTES) ?>">openapi.json</a> — machine-readable contract</li>
            </ul>
        </section>

        <section class="dev-grid">
            <?php foreach ($docs as $doc): ?>
            <a class="dev-doc-card" href="docs.php?path=<?= htmlspecialchars($doc['path'], ENT_QUOTES) ?>">
                <span class="dev-doc-card__badge"><?= htmlspecialchars($doc['badge'], ENT_QUOTES) ?></span>
                <h3 class="dev-doc-card__title"><?= htmlspecialchars($doc['title'], ENT_QUOTES) ?></h3>
                <span class="dev-doc-card__arrow" aria-hidden="true">→</span>
            </a>
            <?php endforeach; ?>
        </section>

        <section class="dev-card">
            <h2 class="dev-card__title">Repository</h2>
            <p>
                <a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com">github.com/decisionsciencecorp/qr.decisionsciencecorp.com</a>
                — AGPL-3.0 code, CC BY-SA 4.0 documentation.
            </p>
            <ul class="dev-quicklinks">
                <li><a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/php">PHP SDK</a></li>
                <li><a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/python">Python SDK</a></li>
                <li><a href="https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/smcp_plugin">SMCP plugin</a></li>
            </ul>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="../index.html" class="footer-brand-link">
                        <img src="../assets/images/logo-white.svg" alt="" class="footer-logo-img" aria-hidden="true">
                        <span class="footer-brand-text">QR Code Studio</span>
                    </a>
                </div>
                <ul class="footer-list footer-list--inline">
                    <li><a class="footer-link footer-link--dev" href="index.php">DEV</a></li>
                    <li><a class="footer-link" href="docs.php?path=agents">agents.md</a></li>
                    <li><a class="footer-link" href="docs.php?path=api">API</a></li>
                    <li><a class="footer-link" href="docs.php?path=licensing">Licensing</a></li>
                </ul>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright">&copy; <span id="year"></span> Decision Science Corp</p>
            </div>
        </div>
    </footer>
    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>
