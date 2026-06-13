<?php
/**
 * QR Code Studio PHP SDK (AGPL-3.0-or-later)
 *
 * @copyright Decision Science Corp
 * @license AGPL-3.0-or-later
 */
declare(strict_types=1);

final class QrStudioException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, $httpStatus);
    }
}

final class QrStudioClient
{
    public function __construct(
        private string $baseUrl = 'https://qr.decisionsciencecorp.com',
        private ?string $apiKey = null,
        private int $timeoutSeconds = 30,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /** @return array<string,mixed> */
    public function health(): array
    {
        return $this->request('GET', '/api/v1/health.php');
    }

    /** @return array<string,mixed> */
    public function normalize(string $content, string $type = 'auto'): array
    {
        return $this->request('POST', '/api/v1/normalize.php', [
            'content' => $content,
            'type' => $type,
        ]);
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function generate(string $content, array $options = []): array
    {
        $body = array_merge(['content' => $content], $options);
        return $this->request('POST', '/api/v1/generate.php', $body);
    }

    /**
     * @param array<string,mixed>|null $jsonBody
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new QrStudioException('curl_init failed');
        }

        $headers = ['Accept: application/json'];
        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($this->apiKey !== null && $this->apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new QrStudioException('HTTP request failed: ' . $err, $status);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new QrStudioException('Invalid JSON response', $status);
        }

        if (empty($data['success'])) {
            throw new QrStudioException(
                (string) ($data['error'] ?? 'API error'),
                $status,
                isset($data['error_code']) ? (string) $data['error_code'] : null,
            );
        }

        return $data;
    }
}
