<?php

declare(strict_types=1);

$rootDirectory = dirname(__DIR__, 2);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($requestPath) && preg_match('#^/show/([0-9]+)/?$#', $requestPath, $matches) === 1) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/show.php';
    require $rootDirectory . '/src/Public/show.php';
    return true;
}

if (is_string($requestPath) && preg_match('#^/(?:app/)?api/([a-z]+)\.php$#', $requestPath, $matches) === 1) {
    $_SERVER['SCRIPT_NAME'] = '/api/' . $matches[1] . '.php';
    require $rootDirectory . '/src/Public/api/' . $matches[1] . '.php';
    return true;
}

if (is_string($requestPath) && preg_match('#^/(index|show|download|delete)\.php$#', $requestPath, $matches) === 1) {
    $_SERVER['SCRIPT_NAME'] = '/' . $matches[1] . '.php';
    require $rootDirectory . '/src/Public/' . $matches[1] . '.php';
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

if ($requestPath === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require $rootDirectory . '/src/Public/index.php';
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
