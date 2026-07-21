<?php

/**
 * PHP Uploader Ver.2.0 - メインエントリーポイント
 *
 * 簡易フレームワーク with モダンPHP対応
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);
$logger = null;

require_once $baseDir . '/src/Lib/page_helpers.php';

try {
    $pageContext = phpuploader_initialize_page($baseDir);
    $config = $pageContext['config'];
    $db = $pageContext['db'];
    $logger = $pageContext['logger'];
    $responseHandler = $pageContext['responseHandler'];

    $repository = new \PHPUploader\Model\FileRepository($db);
    $fileData = $repository->fetchAllPublic();

    $appBasePath = phpuploader_app_base_path($config);
    $escapedAppBasePath = phpuploader_escape($appBasePath);
    $siteTitle = (string)($config['title'] ?? 'PHP Uploader');
    $ogTitle = $siteTitle;
    $ogDescription = 'ブラウザからファイルをアップロードして共有できるPHP Uploaderです。';
    $ogType = 'website';
    $ogUrl = phpuploader_absolute_url(phpuploader_request_uri($appBasePath), $appBasePath, $config);
    $ogImageUrl = phpuploader_absolute_url($appBasePath . 'image/cover.png', $appBasePath, $config);
    $escapeMeta = static fn (string $value): string => phpuploader_escape($value);
    $pageHeaderPath = $baseDir . '/src/View/index/header.php';

    $csrfToken = \PHPUploader\Core\SecurityUtils::generateCSRFToken();
    $statusMessage = $_GET['deleted'] ?? null;

    $escapedCsrfToken = phpuploader_escape($csrfToken);
    $escapedMaxFileSize = phpuploader_escape((string)($config['maxFileSize'] ?? ''));
    $escapedChunkSize = phpuploader_escape((string)($config['chunkSize'] ?? 50));
    $escapedExtensionList = phpuploader_escape(implode(', ', (array)($config['extension'] ?? [])));
    $escapedMaxComment = phpuploader_escape((string)($config['maxComment'] ?? ''));
    $escapedVersion = phpuploader_escape((string)($config['version'] ?? 'dev'));

    $fileDataJson = json_encode(
        $fileData,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    if ($fileDataJson === false) {
        $fileDataJson = '[]';
    }

    $chunkSizeJson = json_encode((float)($config['chunkSize'] ?? 50));
    if ($chunkSizeJson === false) {
        $chunkSizeJson = '50';
    }

    require $baseDir . '/src/View/common/header.php';
    require $baseDir . '/src/View/index/page.php';
    require $baseDir . '/src/View/common/footer.php';
} catch (Throwable $error) {
    phpuploader_render_error($error, $baseDir, $logger);
}
