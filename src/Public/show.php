<?php

declare(strict_types=1);

if (empty($_GET['id'])) {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('#^/([0-9]+)$#', $pathInfo, $matches) === 1) {
        $_GET['id'] = $matches[1];
    }
}

if (empty($_GET['id'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_string($requestPath) && preg_match('#/show/([0-9]+)/?$#', $requestPath, $matches) === 1) {
        $_GET['id'] = $matches[1];
    }
}

$baseDir = dirname(__DIR__, 2);

require_once $baseDir . '/src/Bootstrap/render_page.php';

render_phpuploader_page($baseDir, 'download', $baseDir . '/src/View/show/header.php');
