<?php
/**
 * QR Code Studio — API helpers (AGPL-3.0)
 */
declare(strict_types=1);

function qr_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

function api_json_begin(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function api_success(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $code = 400, ?string $errorCode = null): void
{
    http_response_code($code);
    $payload = ['success' => false, 'error' => $message];
    if ($errorCode !== null) {
        $payload['error_code'] = $errorCode;
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_error('Request body must be valid JSON.', 400, 'invalid_json');
    }
    return $data;
}

function api_require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        api_error('Method not allowed.', 405, 'method_not_allowed');
    }
}

function api_optional_auth(): void
{
    $cfg = qr_config();
    $expected = (string) ($cfg['api_key'] ?? '');
    if ($expected === '') {
        return;
    }
    $provided = '';
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $provided = (string) $_SERVER['HTTP_X_API_KEY'];
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        $provided = $m[1];
    }
    if ($provided === '' || !hash_equals($expected, $provided)) {
        api_error('Invalid or missing API key.', 401, 'unauthorized');
    }
}

function api_rate_limit(): void
{
    $cfg = qr_config();
    if (!empty($cfg['api_key'])) {
        return;
    }
    $limit = (int) ($cfg['rate_limit_per_minute'] ?? 120);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $bucket = sys_get_temp_dir() . '/qrstudio_rl_' . md5($ip . date('YmdHi'));
    $count = is_file($bucket) ? (int) file_get_contents($bucket) : 0;
    if ($count >= $limit) {
        api_error('Rate limit exceeded. Try again in a minute or use an API key.', 429, 'rate_limited');
    }
    file_put_contents($bucket, (string) ($count + 1));
}

function api_string(mixed $v, string $field, int $maxLen = 4096): string
{
    if (!is_string($v) || trim($v) === '') {
        api_error("Field \"{$field}\" is required.", 400, 'validation');
    }
    $s = trim($v);
    if (strlen($s) > $maxLen) {
        api_error("Field \"{$field}\" exceeds maximum length ({$maxLen}).", 400, 'validation');
    }
    return $s;
}

function api_optional_string(mixed $v, int $maxLen = 4096): ?string
{
    if ($v === null || $v === '') {
        return null;
    }
    if (!is_string($v)) {
        return null;
    }
    $s = trim($v);
    if ($s === '' || strlen($s) > $maxLen) {
        return null;
    }
    return $s;
}

function api_optional_int(mixed $v, int $min, int $max, int $default): int
{
    if ($v === null || $v === '') {
        return $default;
    }
    if (!is_numeric($v)) {
        return $default;
    }
    $n = (int) $v;
    return max($min, min($max, $n));
}
