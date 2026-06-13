# QR Code Studio — Developer Overview

QR Code Studio is an open-source QR generator from Decision Science Corp. The browser app at `/` is pure client-side; the **REST API** at `/api/v1/` mirrors the same validation and generation logic server-side for automation and agents.

## Quick links

| Resource | URL |
|----------|-----|
| **agents.md** | [Agent integration guide](agents) |
| **REST API** | [API reference](api) |
| **OpenAPI** | [/api/v1/openapi.json](/api/v1/openapi.json) |
| **PHP SDK** | [sdk/php](../../sdk/php/) in repo |
| **Python SDK** | [sdk/python](../../sdk/python/) in repo |
| **SMCP plugin** | [smcp_plugin/qr_studio](../../smcp_plugin/) |

## Licenses

- **Source code** (PHP, JS app logic we authored, SDKs, SMCP): **GNU AGPL v3**
- **Documentation and non-code assets**: **CC BY-SA 4.0**

See [Licensing](licensing) for third-party vendored components.

## Base URL

```
https://qr.decisionsciencecorp.com
```

## Typical flow

1. `POST /api/v1/normalize.php` — validate URL/email/phone, get encoded payload + typo suggestions
2. `POST /api/v1/generate.php` — return PNG or SVG as base64

Optional API key via `X-API-Key` or `Authorization: Bearer` when `QR_STUDIO_API_KEY` is configured on the server (bypasses public rate limit).

## Repository

https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com
