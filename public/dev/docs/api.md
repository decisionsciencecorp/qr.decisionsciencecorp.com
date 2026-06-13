# REST API Reference

Base URL: `https://qr.decisionsciencecorp.com`

OpenAPI machine contract: [/api/v1/openapi.json](/api/v1/openapi.json)

All JSON responses include `success: true|false`. Errors return `error` and optional `error_code`.

## Authentication

Optional. When `QR_STUDIO_API_KEY` is set on the server:

- Header `X-API-Key: <key>` **or**
- Header `Authorization: Bearer <key>`

Without a key, requests are public with per-IP rate limiting.

## CORS

`Access-Control-Allow-Origin: *` on API routes. `OPTIONS` preflight returns `204`.

---

## GET `/api/v1/health.php`

Service metadata and endpoint index.

**Response 200**

```json
{
  "success": true,
  "service": "qr-code-studio",
  "version": "1.0.0",
  "license": { "code": "AGPL-3.0", "docs": "CC-BY-SA-4.0" },
  "endpoints": { "health": "/api/v1/health.php", "normalize": "...", "generate": "...", "openapi": "..." },
  "documentation": "/dev/index.php"
}
```

---

## POST `/api/v1/normalize.php`

Validate and normalize QR payload (same rules as the browser app).

**Body**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `content` | string | yes | URL, email, phone, or text |
| `type` | string | no | `auto` (default), `url`, `email`, `tel`, `text` |

**Response 200**

```json
{
  "success": true,
  "encoded": "mailto:hello@gnail.com",
  "type": "email",
  "suggestion": "mailto:hello@gmail.com",
  "suggestion_reason": "common provider typo"
}
```

**Response 422** — validation failed (`error_code`: `validation`)

---

## POST `/api/v1/generate.php`

Generate a QR code image.

**Body** — all normalize fields plus:

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `format` | string | `png` | `png` or `svg` |
| `ecl` | string | `M` | `L`, `M`, `Q`, `H` |
| `cell_size` | int | 8 | Module size in pixels (2–20) |
| `margin` | int | 4 | Quiet zone modules (0–10) |
| `logo_base64` | string | — | Optional center logo (max 2MB decoded) |
| `logo_size_pct` | int | 22 | Logo size 10–30% of image |

When `logo_base64` is set, effective ECL is at least **Q**.

**Response 200**

```json
{
  "success": true,
  "encoded": "https://example.com",
  "type": "url",
  "format": "png",
  "mime_type": "image/png",
  "data_base64": "<base64>",
  "width": 264,
  "height": 264,
  "ecl_requested": "M",
  "ecl_effective": "M"
}
```

---

## Error codes

| `error_code` | HTTP | Meaning |
|--------------|------|---------|
| `invalid_json` | 400 | Body is not JSON |
| `validation` | 400/422 | Invalid field or content |
| `unauthorized` | 401 | Bad/missing API key |
| `rate_limited` | 429 | Public rate limit exceeded |
| `method_not_allowed` | 405 | Wrong HTTP method |
| `generation` | 500 | QR render failure |

---

## SDKs

- [PHP SDK](sdk-php) — `sdk/php/QrStudioClient.php`
- [Python SDK](sdk-python) — `sdk/python/qr_studio/client.py`

## Agents

See [agents.md](agents) for SMCP and integration patterns.
