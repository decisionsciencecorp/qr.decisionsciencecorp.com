# QR Code Studio — Agent Integration (`agents.md`)

Canonical agent onboarding for [QR Code Studio](https://qr.decisionsciencecorp.com/). Live docs: https://qr.decisionsciencecorp.com/dev/docs.php?path=agents

## Start here

| Priority | What | Where |
|----------|------|-------|
| 1 | **This guide** | `agents.md` (repo root) · [live DEV docs](https://qr.decisionsciencecorp.com/dev/docs.php?path=agents) |
| 2 | **REST API** | [API reference](https://qr.decisionsciencecorp.com/dev/docs.php?path=api) · [OpenAPI JSON](https://qr.decisionsciencecorp.com/api/v1/openapi.json) |
| 3 | **SMCP plugin** | `smcp_plugin/qr_studio/cli.py --describe` |
| 4 | **SDKs** | `sdk/php/` · `sdk/python/` |

## What agents should use

**Prefer the SMCP plugin** when your host supports Sanctum MCP plugins — it wraps normalize + generate with `--describe` discovery.

**Otherwise** call the REST API directly or use the PHP/Python SDK in this repo.

## Endpoints (summary)

```
GET  /api/v1/health.php
POST /api/v1/normalize.php   { "content": "...", "type": "auto" }
POST /api/v1/generate.php    { "content": "...", "format": "png", "ecl": "M" }
GET  /api/v1/openapi.json
```

### Normalize example

```bash
curl -sS -X POST 'https://qr.decisionsciencecorp.com/api/v1/normalize.php' \
  -H 'Content-Type: application/json' \
  -d '{"content":"hello@gnail.com","type":"email"}'
```

### Generate example

```bash
curl -sS -X POST 'https://qr.decisionsciencecorp.com/api/v1/generate.php' \
  -H 'Content-Type: application/json' \
  -d '{"content":"https://decisionsciencecorp.com","format":"png","ecl":"M"}'
```

## Authentication

Public by default (~120 req/min per IP). Optional `QR_STUDIO_API_KEY` on server → `X-API-Key` or `Authorization: Bearer`.

## SMCP commands

`health` · `normalize` · `generate` · `tool-help`

```bash
python3 smcp_plugin/qr_studio/cli.py --describe
```

## Licensing

- Code (SDK, SMCP, API): **AGPL-3.0**
- This documentation: **CC BY-SA 4.0**

See `COPYING.md` and `LICENSE-CC-BY-SA-4.0.md`.

## DEV hub

https://qr.decisionsciencecorp.com/dev/index.php
