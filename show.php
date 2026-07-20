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

$baseDir = __DIR__;

require_once $baseDir . '/app/bootstrap/render_page.php';

render_phpuploader_page($baseDir, 'download', $baseDir . '/app/views/show/header.php');
