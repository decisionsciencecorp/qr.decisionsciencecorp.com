<?php
/**
 * QR Code Studio DEV docs browser (AGPL-3.0)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/markdown.php';

$requestedPath = $_GET['path'] ?? 'index';
$requestedPath = trim((string) $requestedPath, '/');
$requestedPath = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $requestedPath);
if ($requestedPath === '') {
    $requestedPath = 'index';
}

$docsDir = __DIR__ . '/docs';
$docsPath = $docsDir . '/' . $requestedPath . '.md';

if (!is_file($docsPath)) {
    http_response_code(404);
    $title = 'Documentation Not Found';
    $description = 'The requested DEV documentation page does not exist.';
    $content = '<div class="dev-prose"><h2>404 — Not found</h2><p>'
        . htmlspecialchars($description, ENT_QUOTES)
        . '</p><p><a class="btn btn-primary" href="index.php">← DEV home</a></p></div>';
    $toc = '';
} else {
    $markdown = file_get_contents($docsPath);
    if ($markdown === false) {
        http_response_code(500);
        echo 'Unable to read documentation.';
        exit;
    }
    $title = DevMarkdown::title($markdown);
    $plain = DevMarkdown::strip($markdown);
    $description = $plain !== '' ? substr($plain, 0, 160) : 'QR Code Studio developer documentation';
    $html = DevMarkdown::parse($markdown);
    $html = DevMarkdown::addHeadingIds($html);
    $content = '<div class="dev-prose">' . $html . '</div>';
    $toc = DevMarkdown::toc($markdown);
}

$breadcrumb = '';
$parts = explode('/', $requestedPath);
$current = '';
foreach ($parts as $i => $part) {
    $current .= ($current === '' ? '' : '/') . $part;
    $label = ucwords(str_replace('-', ' ', $part));
    if ($i === count($parts) - 1) {
        $breadcrumb .= '<li class="dev-crumb dev-crumb--active">' . htmlspecialchars($label, ENT_QUOTES) . '</li>';
    } else {
        $breadcrumb .= '<li class="dev-crumb"><a href="docs.php?path=' . htmlspecialchars($current, ENT_QUOTES) . '">'
            . htmlspecialchars($label, ENT_QUOTES) . '</a></li>';
    }
}

$templatePath = __DIR__ . '/docs-template.html';
$template = file_get_contents($templatePath);
if ($template === false) {
    http_response_code(500);
    echo 'Template missing.';
    exit;
}

$replacements = [
    '{{DOCS_TITLE}}' => htmlspecialchars($title, ENT_QUOTES),
    '{{DOCS_DESCRIPTION}}' => htmlspecialchars($description, ENT_QUOTES),
    '{{DOCS_PATH}}' => htmlspecialchars($requestedPath, ENT_QUOTES),
    '{{DOCS_CONTENT}}' => $content,
    '{{DOCS_TOC}}' => $toc,
    '{{DOCS_BREADCRUMB}}' => $breadcrumb,
];

echo str_replace(array_keys($replacements), array_values($replacements), $template);
