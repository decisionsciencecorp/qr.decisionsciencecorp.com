# QR Code Studio — Agent Integration (`agents.md`)

This page is the canonical **agent onboarding** guide for QR Code Studio. The same file lives at the repository root as [`agents.md`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/agents.md).

## Start here

| Priority | What | Where |
|----------|------|-------|
| 1 | **This guide** | `/dev/docs.php?path=agents` |
| 2 | **REST API** | [API reference](api) · [OpenAPI JSON](/api/v1/openapi.json) |
| 3 | **SMCP plugin** | [SMCP docs](smcp) · `smcp_plugin/qr_studio/cli.py --describe` |
| 4 | **SDKs** | [PHP](sdk-php) · [Python](sdk-python) |

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

Response includes `encoded`, `type`, and optional `suggestion` / `suggestion_reason` for typos.

### Generate example

```bash
curl -sS -X POST 'https://qr.decisionsciencecorp.com/api/v1/generate.php' \
  -H 'Content-Type: application/json' \
  -d '{"content":"https://decisionsciencecorp.com","format":"png","ecl":"M"}'
```

Decode `data_base64` to bytes for the image file.

## Authentication

Public by default with IP rate limiting (~120 requests/minute). When the operator sets `QR_STUDIO_API_KEY` on the server, send:

```
X-API-Key: <key>
```

or

```
Authorization: Bearer <key>
```

## Logo overlay (API)

Pass `logo_base64` (raw base64 or `data:image/png;base64,...`) and `logo_size_pct` (10–30). The API bumps error correction to at least **Q** when a logo is present.

## Content types

| `type` | Encoded payload |
|--------|-----------------|
| `url` | `https://...` (scheme inferred if missing) |
| `email` | `mailto:user@domain` |
| `tel` | `tel:+15550100123` |
| `text` | literal string |

## SMCP commands

| Command | Purpose |
|---------|---------|
| `health` | Service health |
| `normalize` | Validate / autocorrect |
| `generate` | PNG or SVG as base64 |
| `tool-help` | Intent → recommended command |

Run discovery:

```bash
python3 smcp_plugin/qr_studio/cli.py --describe
```

## Licensing for agents

- **Code you execute from this repo** (SDK, SMCP): AGPL-3.0 — network use requires source offer.
- **This documentation**: CC BY-SA 4.0 — attribute Decision Science Corp.

## Human UI

The public studio at `/` works offline in the browser. Agents should use `/api/v1/` for deterministic automation.

## Support

- DEV hub: `/dev/index.php`
- GitHub issues: https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/issues
