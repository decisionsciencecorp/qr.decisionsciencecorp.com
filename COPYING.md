# Licensing — QR Code Studio

Decision Science Corp publishes this project under a **dual-license** model.

## Source code → GNU AGPL v3

All **software** in this repository is licensed under the **GNU Affero General Public License v3.0** (AGPL-3.0). See [`LICENSE`](LICENSE).

That includes, without limitation:

- `public/app.js`, `public/api/`, `public/includes/` (our PHP)
- `public/dev/*.php` (documentation renderer)
- `sdk/php/`, `sdk/python/`
- `smcp_plugin/`
- `tests/` (test code)

If you modify and deploy this software over a network, AGPL-3.0 requires you to offer corresponding source to users who interact with it remotely.

## Everything else → CC BY-SA 4.0

**Non-code** creative works are licensed under **Creative Commons Attribution-ShareAlike 4.0 International** (CC BY-SA 4.0). See [`LICENSE-CC-BY-SA-4.0.md`](LICENSE-CC-BY-SA-4.0.md).

That includes:

- Markdown documentation under `public/dev/docs/`, `docs/`, and root `agents.md`
- Static marketing copy and diagrams we authored (not third-party logos)
- Screenshots and design artifacts committed as documentation assets

## Third-party components (retain upstream licenses)

| Component | Location | License |
|-----------|----------|---------|
| kazuhikoarase `qrcode-generator` | `public/vendor/qrcode*.js` | MIT |
| Parsedown | `public/includes/lib/Parsedown.php` | MIT |
| PHP QR Code (t0k4rt/phpqrcode) | `public/includes/lib/phpqrcode/` | LGPL-3.0 |
| Inter font files | `public/fonts/` | SIL Open Font License 1.1 |

Vendored libraries remain under their upstream terms; our AGPL applies to our integration and original code.

## Attribution

**QR Code Studio** — © Decision Science Corp.  
When redistributing documentation under CC BY-SA 4.0, credit **Decision Science Corp** and link to `https://qr.decisionsciencecorp.com/`.
