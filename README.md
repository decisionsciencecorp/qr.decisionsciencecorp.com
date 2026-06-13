# QR Code Studio

Open-source QR generator at **[qr.decisionsciencecorp.com](https://qr.decisionsciencecorp.com)**.

Turn any URL, email, or phone number into a scannable QR code — in the browser or via REST API. Validation suggests `https://` when you forget the scheme and fixes common typos (`gnail.com` → `gmail.com`) before you print stickers.

**Licenses:** source code **AGPL-3.0** · documentation **CC BY-SA 4.0** — see [`COPYING.md`](COPYING.md).

---

## For developers & agents

| Resource | Link |
|----------|------|
| **DEV hub** | https://qr.decisionsciencecorp.com/dev/index.php |
| **agents.md** | [`agents.md`](agents.md) · [live docs](https://qr.decisionsciencecorp.com/dev/docs.php?path=agents) |
| **API** | `/api/v1/` · [OpenAPI](https://qr.decisionsciencecorp.com/api/v1/openapi.json) |
| **PHP SDK** | `sdk/php/QrStudioClient.php` — [GitHub](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/php) |
| **Python SDK** | `sdk/python/` — [GitHub](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/python) |
| **SMCP plugin** | `smcp_plugin/qr_studio/cli.py --describe` — [GitHub](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/smcp_plugin) |

---

## Layout

```
public/                     nginx docroot (multihost)
├── index.html              browser studio (client-side QR)
├── app.js, styles.css
├── api/v1/                 REST API (PHP)
├── dev/                    DEV docs browser (markdown → HTML)
├── includes/               PHP shared logic + vendored libs
└── vendor/                 MIT qrcode.js
sdk/php/                    PHP client
sdk/python/                 Python client
smcp_plugin/                Sanctum MCP CLI
agents.md                   agent integration (canonical)
db/                         deploy placeholder (.gitkeep)
```

## API quick start

```bash
curl -sS https://qr.decisionsciencecorp.com/api/v1/health.php

curl -sS -X POST https://qr.decisionsciencecorp.com/api/v1/normalize.php \
  -H 'Content-Type: application/json' \
  -d '{"content":"hello@gnail.com"}'

curl -sS -X POST https://qr.decisionsciencecorp.com/api/v1/generate.php \
  -H 'Content-Type: application/json' \
  -d '{"content":"https://decisionsciencecorp.com","format":"png"}'
```

## Deploy

Multihost: `/root/sites/qr.decisionsciencecorp.com.env` → cron `sync.sh`. See `docs/DEPLOY.md`.

Requires PHP with **GD** extension for PNG generation API.

## Development

```bash
cd public && php -S 127.0.0.1:8765
# Studio → http://127.0.0.1:8765/
# DEV    → http://127.0.0.1:8765/dev/index.php
```

```bash
python3 -m pytest tests/ -q
python3 smcp_plugin/qr_studio/cli.py --describe
```

## Credits

- Browser QR: [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) (MIT)
- Server QR: [phpqrcode](https://github.com/t0k4rt/phpqrcode) (LGPL-3.0)
- © Decision Science Corp
