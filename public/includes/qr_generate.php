<?php
/**
 * QR Code Studio — server-side QR generation (AGPL-3.0)
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

final class QrGenerate
{
    private const ECL_MAP = [
        'L' => QR_ECLEVEL_L,
        'M' => QR_ECLEVEL_M,
        'Q' => QR_ECLEVEL_Q,
        'H' => QR_ECLEVEL_H,
    ];

    private const ECL_RANK = ['L' => 0, 'M' => 1, 'Q' => 2, 'H' => 3];

    /**
     * @return array{data_base64:string,mime_type:string,width:int,height:int,ecl_effective:string}
     */
    public static function render(
        string $payload,
        string $format,
        string $ecl,
        int $cellSize,
        int $margin,
        ?string $logoBase64,
        int $logoSizePct
    ): array {
        $format = strtolower($format);
        if (!in_array($format, ['png', 'svg'], true)) {
            throw new InvalidArgumentException('format must be png or svg');
        }
        if ($format === 'png' && !function_exists('imagecreatefrompng')) {
            throw new InvalidArgumentException('PNG generation requires PHP GD extension; use format=svg or enable GD');
        }

        $eclKey = strtoupper($ecl);
        if (!isset(self::ECL_MAP[$eclKey])) {
            $eclKey = 'M';
        }
        $effective = $eclKey;
        if ($logoBase64 !== null && $logoBase64 !== '' && (self::ECL_RANK[$effective] ?? 0) < self::ECL_RANK['Q']) {
            $effective = 'Q';
        }
        $level = self::ECL_MAP[$effective];

        if ($format === 'png') {
            return self::renderPng($payload, $level, $effective, $cellSize, $margin, $logoBase64, $logoSizePct);
        }
        return self::renderSvg($payload, $level, $effective, $cellSize, $margin, $logoBase64, $logoSizePct);
    }

    /** @return array{data_base64:string,mime_type:string,width:int,height:int,ecl_effective:string} */
    private static function renderPng(
        string $payload,
        int $level,
        string $effective,
        int $cellSize,
        int $margin,
        ?string $logoBase64,
        int $logoSizePct
    ): array {
        $tmp = tempnam(sys_get_temp_dir(), 'qrstudio_');
        if ($tmp === false) {
            throw new RuntimeException('Failed to allocate temp file');
        }
        $pngPath = $tmp . '.png';
        rename($tmp, $pngPath);

        try {
            QRcode::png($payload, $pngPath, $level, max(1, $cellSize), max(0, $margin), false, 0xFFFFFF, 0x0A0A0A);
            $img = imagecreatefrompng($pngPath);
            if ($img === false) {
                throw new RuntimeException('PNG generation failed');
            }
            $width = imagesx($img);
            $height = imagesy($img);

            if ($logoBase64 !== null && $logoBase64 !== '') {
                self::overlayLogo($img, $width, $logoBase64, $logoSizePct);
            }

            ob_start();
            imagepng($img);
            $bytes = ob_get_clean();
            imagedestroy($img);
            if ($bytes === false) {
                throw new RuntimeException('PNG encode failed');
            }

            return [
                'data_base64' => base64_encode($bytes),
                'mime_type' => 'image/png',
                'width' => $width,
                'height' => $height,
                'ecl_effective' => $effective,
            ];
        } finally {
            if (is_file($pngPath)) {
                unlink($pngPath);
            }
        }
    }

    /** @return array{data_base64:string,mime_type:string,width:int,height:int,ecl_effective:string} */
    private static function renderSvg(
        string $payload,
        int $level,
        string $effective,
        int $cellSize,
        int $margin,
        ?string $logoBase64,
        int $logoSizePct
    ): array {
        $tmp = tempnam(sys_get_temp_dir(), 'qrstudio_');
        if ($tmp === false) {
            throw new RuntimeException('Failed to allocate temp file');
        }
        $svgPath = $tmp . '.svg';
        rename($tmp, $svgPath);

        try {
            QRcode::svg($payload, $svgPath, $level, max(1, $cellSize), max(0, $margin), false, 0xFFFFFF, 0x0A0A0A);
            $svg = file_get_contents($svgPath);
            if ($svg === false) {
                throw new RuntimeException('SVG generation failed');
            }

            if (preg_match('/width="(\d+)"/', $svg, $m)) {
                $width = (int) $m[1];
            } else {
                $width = 0;
            }
            if (preg_match('/height="(\d+)"/', $svg, $m)) {
                $height = (int) $m[1];
            } else {
                $height = $width;
            }

            if ($logoBase64 !== null && $logoBase64 !== '' && $width > 0) {
                $svg = self::injectSvgLogo($svg, $width, $logoBase64, $logoSizePct);
            }

            return [
                'data_base64' => base64_encode($svg),
                'mime_type' => 'image/svg+xml',
                'width' => $width,
                'height' => $height,
                'ecl_effective' => $effective,
            ];
        } finally {
            if (is_file($svgPath)) {
                unlink($svgPath);
            }
        }
    }

    private static function overlayLogo(\GdImage $img, int $canvasSize, string $logoBase64, int $logoSizePct): void
    {
        $raw = self::decodeLogo($logoBase64);
        if ($raw === null) {
            return;
        }
        $logo = @imagecreatefromstring($raw);
        if ($logo === false) {
            return;
        }

        $pct = max(10, min(30, $logoSizePct)) / 100;
        $logoSide = (int) round($canvasSize * $pct);
        $padSide = (int) round($logoSide * 1.14);
        $cx = (int) ($canvasSize / 2);
        $cy = (int) ($canvasSize / 2);
        $padX = (int) ($cx - $padSide / 2);
        $padY = (int) ($cy - $padSide / 2);
        $radius = max(4, (int) round($padSide * 0.12));

        $white = imagecolorallocate($img, 255, 255, 255);
        self::filledRoundRect($img, $padX, $padY, $padSide, $padSide, $radius, $white);

        $dstX = (int) ($cx - $logoSide / 2);
        $dstY = (int) ($cy - $logoSide / 2);
        imagecopyresampled($img, $logo, $dstX, $dstY, 0, 0, $logoSide, $logoSide, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
    }

    private static function injectSvgLogo(string $svg, int $size, string $logoBase64, int $logoSizePct): string
    {
        $pct = max(10, min(30, $logoSizePct)) / 100;
        $logoSide = $size * $pct;
        $padSide = $logoSide * 1.14;
        $cx = $size / 2;
        $cy = $size / 2;
        $padX = $cx - $padSide / 2;
        $padY = $cy - $padSide / 2;
        $radius = max(4, $padSide * 0.12);
        $imgX = $cx - $logoSide / 2;
        $imgY = $cy - $logoSide / 2;

        $dataUri = 'data:image/png;base64,' . preg_replace('/\s+/', '', $logoBase64);
        $overlay = sprintf(
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="%.2f" fill="#ffffff"/>'
            . '<image href="%s" x="%.2f" y="%.2f" width="%.2f" height="%.2f"/>',
            $padX, $padY, $padSide, $padSide, $radius,
            htmlspecialchars($dataUri, ENT_QUOTES, 'UTF-8'),
            $imgX, $imgY, $logoSide, $logoSide
        );

        if (str_contains($svg, '</svg>')) {
            return str_replace('</svg>', $overlay . '</svg>', $svg);
        }
        return $svg . $overlay;
    }

    private static function decodeLogo(string $logoBase64): ?string
    {
        $b64 = $logoBase64;
        if (preg_match('/^data:image\/[a-z+]+;base64,(.+)$/i', $b64, $m)) {
            $b64 = $m[1];
        }
        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) > 2 * 1024 * 1024) {
            return null;
        }
        return $raw;
    }

    private static function filledRoundRect(\GdImage $img, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        $r = min($r, (int) ($w / 2), (int) ($h / 2));
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }
}
