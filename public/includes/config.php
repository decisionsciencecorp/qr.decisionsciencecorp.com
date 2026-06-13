<?php
/**
 * QR Code Studio — runtime config (AGPL-3.0)
 *
 * Optional API key: set QR_STUDIO_API_KEY in the PHP-FPM pool / server env for
 * authenticated high-volume clients. When empty, the API is public with IP
 * rate limiting only.
 */
declare(strict_types=1);

return [
    'service_name' => 'qr-code-studio',
    'api_version' => '1.0.0',
    'base_url' => 'https://qr.decisionsciencecorp.com',
    'api_key' => getenv('QR_STUDIO_API_KEY') ?: '',
    'rate_limit_per_minute' => 120,
    'rate_limit_burst' => 30,
];
