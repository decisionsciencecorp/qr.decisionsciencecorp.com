# QR Code Studio

Tiny QR-code generator at **[qr.decisionsciencecorp.com](https://qr.decisionsciencecorp.com)**.

Turn any URL, email address, or phone number into a scannable QR code. Validation
is forgiving — it suggests `https://` when you forget the scheme, and proposes
fixes for common email-domain typos (`gnail.com` → `gmail.com`, `.con` → `.com`)
so you don't print 500 stickers pointing at a dead address.

Pure client-side, no servers, no tracking. Generation happens in your browser.

---

## Layout

```
public/                 docroot served by nginx (multihost pattern)
├── index.html          single-page app
├── styles.css          DSC-themed dark UI (Inter, accent #3b82f6)
├── app.js              validation, autocorrect, QR rendering
├── assets/images/      logo + favicon
├── fonts/              self-hosted Inter family
└── vendor/
    ├── qrcode.js       MIT — kazuhikoarase/qrcode-generator
    └── qrcode_UTF8.js  MIT — UTF-8 string support helper
db/                     empty placeholder (.gitkeep)
                        — keeps multihost deploy.sh happy; this app has no DB
```

No PHP, no database. The `db/` directory exists only so the multihost deploy
pipeline (which assumes every site has one) has a valid rsync source.

## Deploy

Multihost-compatible. The standard `/root/sites/<domain>.env` →
`/root/sync.sh <domain>` → `/root/deploy.sh` pipeline mirrors `public/` to the
vhost docroot. See `docs/DEPLOY.md` for the env file template.

## Development

Open `public/index.html` directly in a browser, or serve it locally:

```bash
cd public && python3 -m http.server 8000
# → http://localhost:8000
```

There is no build step. Edit, refresh, done.

## Design parity

Visual identity tracks `decisionsciencecorp.com` — same Inter typography, same
`#0a0a0a` / `#3b82f6` palette, same card chrome. Treat updates to the parent
site's `styles.css` palette as upstream when refreshing this app.

## License & credits

App code: © Decision Science Corp.
QR engine: [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator)
by Kazuhiko Arase, MIT licensed (see `public/vendor/QRCODE-LICENSE.txt`).
