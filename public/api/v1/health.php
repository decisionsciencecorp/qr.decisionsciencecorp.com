<?php
/**
 * QR Code Studio API v1 — health
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/api_helpers.php';

api_json_begin();
api_rate_limit();

$cfg = qr_config();
api_success([
    'service' => $cfg['service_name'],
    'version' => $cfg['api_version'],
    'license' => ['code' => 'AGPL-3.0', 'docs' => 'CC-BY-SA-4.0'],
    'endpoints' => [
        'health' => '/api/v1/health.php',
        'normalize' => '/api/v1/normalize.php',
        'generate' => '/api/v1/generate.php',
        'openapi' => '/api/v1/openapi.json',
    ],
    'documentation' => '/dev/index.php',
]);
