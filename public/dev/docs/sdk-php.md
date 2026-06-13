# PHP SDK

**GitHub:** [sdk/php](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/php) · [`QrStudioClient.php`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/sdk/php/QrStudioClient.php) (AGPL-3.0)

## Requirements

- PHP 8.1+
- `curl` extension

## Install

Copy [`QrStudioClient.php`](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/blob/main/sdk/php/QrStudioClient.php) into your project, clone [sdk/php](https://github.com/decisionsciencecorp/qr.decisionsciencecorp.com/tree/main/sdk/php), or add the repo as a git submodule.

## Usage

```php
<?php
require_once 'sdk/php/QrStudioClient.php';

$client = new QrStudioClient('https://qr.decisionsciencecorp.com');
// Optional: $client = new QrStudioClient($baseUrl, $apiKey);

$health = $client->health();
$norm = $client->normalize('hello@gnail.com', 'email');
$gen = $client->generate('https://decisionsciencecorp.com', [
    'format' => 'png',
    'ecl' => 'M',
]);

$pngBytes = base64_decode($gen['data_base64']);
file_put_contents('qr.png', $pngBytes);
```

## Methods

| Method | API endpoint |
|--------|----------------|
| `health()` | `GET /api/v1/health.php` |
| `normalize($content, $type = 'auto')` | `POST /api/v1/normalize.php` |
| `generate($content, $options = [])` | `POST /api/v1/generate.php` |

`generate()` options: `type`, `format`, `ecl`, `cell_size`, `margin`, `logo_base64`, `logo_size_pct`.

## Errors

Throws `QrStudioException` on HTTP or API errors (`success: false`).

## License

AGPL-3.0 — see [Licensing](licensing).
