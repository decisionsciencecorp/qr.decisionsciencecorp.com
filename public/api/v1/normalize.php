<?php
/**
 * QR Code Studio API v1 — normalize content
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/api_helpers.php';
require_once dirname(__DIR__, 2) . '/includes/qr_normalize.php';

api_json_begin();
api_rate_limit();
api_optional_auth();
api_require_method('POST');

$body = api_read_json_body();
$content = api_string($body['content'] ?? null, 'content');
$type = api_optional_string($body['type'] ?? 'auto') ?? 'auto';

$result = QrNormalize::process($content, $type);
if (!empty($result['error'])) {
    api_error($result['error'], 422, 'validation');
}

$response = [
    'encoded' => $result['encoded'] ?? '',
    'type' => $result['type'] ?? $type,
    'suggestion' => $result['suggestion'] ?? null,
    'suggestion_reason' => $result['suggestion_reason'] ?? null,
];
api_success($response);
