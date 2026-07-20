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

$_GET['page'] = 'download';

require __DIR__ . '/index.php';
