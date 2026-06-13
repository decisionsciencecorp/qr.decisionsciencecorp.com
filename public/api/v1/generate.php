<?php
/**
 * QR Code Studio API v1 — generate QR image
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/api_helpers.php';
require_once dirname(__DIR__, 2) . '/includes/qr_normalize.php';
require_once dirname(__DIR__, 2) . '/includes/qr_generate.php';

api_json_begin();
api_rate_limit();
api_optional_auth();
api_require_method('POST');

$body = api_read_json_body();
$content = api_string($body['content'] ?? null, 'content');
$type = api_optional_string($body['type'] ?? 'auto') ?? 'auto';
$format = strtolower(api_optional_string($body['format'] ?? 'png') ?? 'png');
$ecl = strtoupper(api_optional_string($body['ecl'] ?? 'M') ?? 'M');
$cellSize = api_optional_int($body['cell_size'] ?? null, 2, 20, 8);
$margin = api_optional_int($body['margin'] ?? null, 0, 10, 4);
$logoBase64 = api_optional_string($body['logo_base64'] ?? null, 3_000_000);
$logoSizePct = api_optional_int($body['logo_size_pct'] ?? null, 10, 30, 22);

$normalized = QrNormalize::process($content, $type);
if (!empty($normalized['error'])) {
    api_error($normalized['error'], 422, 'validation');
}
$payload = $normalized['encoded'] ?? '';
if ($payload === '') {
    api_error('Could not encode content.', 422, 'validation');
}

try {
    $rendered = QrGenerate::render($payload, $format, $ecl, $cellSize, $margin, $logoBase64, $logoSizePct);
} catch (InvalidArgumentException $e) {
    api_error($e->getMessage(), 422, 'validation');
} catch (Throwable $e) {
    api_error('QR generation failed: ' . $e->getMessage(), 500, 'generation');
}

api_success([
    'encoded' => $payload,
    'type' => $normalized['type'] ?? $type,
    'format' => $format,
    'mime_type' => $rendered['mime_type'],
    'data_base64' => $rendered['data_base64'],
    'width' => $rendered['width'],
    'height' => $rendered['height'],
    'ecl_requested' => $ecl,
    'ecl_effective' => $rendered['ecl_effective'],
    'suggestion' => $normalized['suggestion'] ?? null,
    'suggestion_reason' => $normalized['suggestion_reason'] ?? null,
]);
