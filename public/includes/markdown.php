<?php
/**
 * QR Code Studio — markdown renderer for DEV docs (AGPL-3.0)
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/Parsedown.php';

final class DevMarkdown
{
    private static ?Parsedown $parser = null;

    public static function parse(string $markdown, string $docsBase = '/dev/docs.php?path='): string
    {
        $markdown = self::rewriteInternalLinks($markdown, $docsBase);
        if (self::$parser === null) {
            self::$parser = new Parsedown();
            self::$parser->setSafeMode(true);
        }
        return self::$parser->text($markdown);
    }

    public static function strip(string $markdown): string
    {
        $text = preg_replace('/```[\s\S]*?```/', '', $markdown) ?? $markdown;
        $text = preg_replace('/`[^`]+`/', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[#*_>\-]+/', '', $text) ?? $text;
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    public static function title(string $markdown, string $fallback = 'Documentation'): string
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            return trim($m[1]);
        }
        return $fallback;
    }

    public static function toc(string $markdown): string
    {
        if (!preg_match_all('/^(#{2,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER)) {
            return '';
        }
        $html = '<nav class="dev-toc" aria-label="On this page"><p class="dev-toc__label">On this page</p><ul>';
        foreach ($matches as $m) {
            $level = strlen($m[1]);
            $title = trim($m[2]);
            $id = self::slug($title);
            $class = $level === 2 ? 'dev-toc__h2' : 'dev-toc__h3';
            $html .= '<li class="' . $class . '"><a href="#' . htmlspecialchars($id, ENT_QUOTES) . '">'
                . htmlspecialchars($title, ENT_QUOTES) . '</a></li>';
        }
        $html .= '</ul></nav>';
        return $html;
    }

    public static function addHeadingIds(string $html): string
    {
        return preg_replace_callback(
            '/<h([2-3])>(.*?)<\/h\1>/',
            static function (array $m): string {
                $plain = strip_tags($m[2]);
                $id = self::slug($plain);
                return '<h' . $m[1] . ' id="' . htmlspecialchars($id, ENT_QUOTES) . '">' . $m[2] . '</h' . $m[1] . '>';
            },
            $html
        ) ?? $html;
    }

    private static function slug(string $text): string
    {
        $s = strtolower(trim($text));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
        return trim($s, '-') ?: 'section';
    }

    private static function rewriteInternalLinks(string $markdown, string $docsBase): string
    {
        return preg_replace_callback(
            '/\]\((?!https?:\/\/|mailto:|tel:)([^)]+)\)/',
            static function (array $m) use ($docsBase): string {
                $path = trim($m[1], '/');
                if (str_ends_with($path, '.md')) {
                    $path = substr($path, 0, -3);
                }
                $path = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $path) ?? $path;
                return '](' . $docsBase . urlencode($path) . ')';
            },
            $markdown
        ) ?? $markdown;
    }
}
