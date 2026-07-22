<?php

declare(strict_types=1);

function phpuploader_project_root(): string
{
    return dirname(__DIR__, 2);
}

function phpuploader_initialize_page(string $baseDir): array
{
    $context = phpuploader_initialize_app($baseDir);
    $context['logger']->access(null, 'page_view', 'success');

    return $context;
}

function phpuploader_initialize_app(string $baseDir): array
{
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once $baseDir . '/src/Core/ConfigLoader.php';
    \PHPUploader\Core\ConfigLoader::requireConfig($baseDir);
    require_once $baseDir . '/src/Core/Logger.php';
    require_once $baseDir . '/src/Core/ResponseHandler.php';
    require_once $baseDir . '/src/Core/SecurityUtils.php';
    require_once $baseDir . '/src/Model/init.php';
    require_once $baseDir . '/src/Model/FileRepository.php';

    $configInstance = new \PHPUploader\Config();
    $config = $configInstance->index();

    if (!$configInstance->validateSecurityConfig()) {
        throw new RuntimeException('設定ファイルのセキュリティ設定が不完全です。config.php を確認してください。');
    }

    $initInstance = new \PHPUploader\Model\Init($config);
    $db = $initInstance->initialize();
    $logger = new \PHPUploader\Core\Logger(
        $config['logDirectoryPath'],
        $config['logLevel'],
        $db
    );
    $responseHandler = new \PHPUploader\Core\ResponseHandler($logger);

    return [
        'config' => $config,
        'db' => $db,
        'logger' => $logger,
        'responseHandler' => $responseHandler,
    ];
}

function phpuploader_app_base_path(?array $config = null): string
{
    $configuredBaseUrl = phpuploader_configured_public_base_url($config ?? []);

    if ($configuredBaseUrl !== null) {
        return phpuploader_base_path_from_url($configuredBaseUrl);
    }

    return phpuploader_detect_app_base_path();
}

function phpuploader_detect_app_base_path(): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

    if ($scriptDirectory === '/' || $scriptDirectory === '.' || $scriptDirectory === '') {
        return '/';
    }

    return rtrim($scriptDirectory, '/') . '/';
}

function phpuploader_configured_public_base_url(array $config): ?string
{
    $configuredBaseUrl = trim((string)($config['publicBaseUrl'] ?? ''));

    return $configuredBaseUrl === '' ? null : $configuredBaseUrl;
}

function phpuploader_base_path_from_url(string $baseUrl): string
{
    $path = parse_url($baseUrl, PHP_URL_PATH);

    if (!is_string($path) || $path === '' || $path === '/') {
        return '/';
    }

    return '/' . trim($path, '/') . '/';
}

function phpuploader_absolute_url(string $path, string $appBasePath, ?array $config = null): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $configuredBaseUrl = phpuploader_configured_public_base_url($config ?? []);
    if ($configuredBaseUrl !== null && preg_match('#^https?://#i', $configuredBaseUrl) === 1) {
        return phpuploader_join_public_base_url($configuredBaseUrl, $path, $appBasePath);
    }

    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return $path;
    }

    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $scheme = $forwardedProto === 'https' || (
        $forwardedProto === '' && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    )
        ? 'https'
        : 'http';

    $urlPath = $path;
    if ($urlPath === '' || $urlPath[0] !== '/') {
        $urlPath = $appBasePath . ltrim($urlPath, '/');
    }

    return $scheme . '://' . $host . '/' . ltrim($urlPath, '/');
}

function phpuploader_join_public_base_url(string $baseUrl, string $path, string $appBasePath): string
{
    $baseUrl = rtrim($baseUrl, '/') . '/';
    $relativePath = $path;

    if ($relativePath === '') {
        return $baseUrl;
    }

    if ($relativePath[0] === '/') {
        $normalizedBasePath = phpuploader_base_path_from_url($baseUrl);
        if ($normalizedBasePath !== '/' && str_starts_with($relativePath, $normalizedBasePath)) {
            $relativePath = substr($relativePath, strlen($normalizedBasePath));
        } elseif ($appBasePath !== '/' && str_starts_with($relativePath, $appBasePath)) {
            $relativePath = substr($relativePath, strlen($appBasePath));
        } else {
            $relativePath = ltrim($relativePath, '/');
        }
    }

    return $baseUrl . ltrim($relativePath, '/');
}

function phpuploader_request_uri(string $appBasePath): string
{
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? $appBasePath);

    if ($requestUri === '' || $requestUri[0] !== '/') {
        return $appBasePath;
    }

    return $requestUri;
}

function phpuploader_trim_description(string $description): string
{
    if (mb_strlen($description, 'UTF-8') <= 160) {
        return $description;
    }

    return mb_substr($description, 0, 157, 'UTF-8') . '...';
}

function phpuploader_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function phpuploader_render_error(Throwable $error, string $baseDir, ?object $logger = null): void
{
    if ($logger !== null && method_exists($logger, 'error')) {
        $logger->error('Application Error: ' . $error->getMessage(), [
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
        ]);
    } else {
        $logMessage = date('Y-m-d H:i:s') .
            ' [CRITICAL] ' . $error->getMessage() .
            ' in ' . $error->getFile() .
            ' on line ' . $error->getLine() .
            PHP_EOL;
        @file_put_contents($baseDir . '/logs/critical.log', $logMessage, FILE_APPEND | LOCK_EX);
    }

    http_response_code(500);
    $errorMessage = phpuploader_escape($error->getMessage());
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="UTF-8"><title>エラー</title></head>';
    echo '<body><h1>システムエラー</h1>';
    echo '<p>' . $errorMessage . '</p>';
    echo '<p><a href="./index.php">トップページに戻る</a></p>';
    echo '</body></html>';
}
