<?php

declare(strict_types=1);

$rootDirectory = dirname(__DIR__, 2);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($requestPath) && preg_match('#^/show/([0-9]+)/?$#', $requestPath, $matches) === 1) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/show.php';
    require $rootDirectory . '/show.php';
    return true;
}

$filePath = realpath($rootDirectory . $requestPath);
if (
    is_string($filePath)
    && str_starts_with($filePath, $rootDirectory)
    && is_file($filePath)
) {
    return false;
}

require $rootDirectory . '/index.php';
return true;
