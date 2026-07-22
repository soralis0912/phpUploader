<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);
$logger = null;

require_once $baseDir . '/src/Lib/page_helpers.php';

try {
    $fileId = filter_var(
        $_GET['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $fileId = is_int($fileId) ? $fileId : 0;

    $pageContext = phpuploader_initialize_page($baseDir);
    $config = $pageContext['config'];
    $db = $pageContext['db'];
    $logger = $pageContext['logger'];
    $responseHandler = $pageContext['responseHandler'];

    $repository = new \PHPUploader\Model\FileRepository($db);
    $downloadFile = $fileId > 0 ? $repository->findDetailById($fileId) : null;

    if ($downloadFile === null) {
        http_response_code(404);
    }

    $appBasePath = phpuploader_app_base_path($config);
    $escapedAppBasePath = phpuploader_escape($appBasePath);
    $siteTitle = (string)($config['title'] ?? 'PHP Uploader');
    $ogTitle = $siteTitle;
    $ogDescription = 'PHP Uploaderで共有されたファイルです。';
    $ogType = 'website';

    if ($downloadFile !== null) {
        $downloadFileName = trim((string)($downloadFile['origin_file_name'] ?? ''));
        $downloadFileComment = trim((string)($downloadFile['comment'] ?? ''));
        $downloadFileSize = isset($downloadFile['size'])
            ? number_format(((int)$downloadFile['size']) / 1024 / 1024, 1) . 'MB'
            : '';

        if ($downloadFileName !== '') {
            $ogTitle = $downloadFileName . ' | ' . $siteTitle;
        }

        $ogDescription = $downloadFileComment !== ''
            ? $downloadFileComment
            : 'PHP Uploaderで共有されたファイルです。';

        if ($downloadFileSize !== '') {
            $ogDescription .= ' サイズ: ' . $downloadFileSize;
        }
    }

    $ogDescription = phpuploader_trim_description($ogDescription);
    $ogUrl = phpuploader_absolute_url(phpuploader_request_uri($appBasePath), $appBasePath, $config);
    $ogImageUrl = phpuploader_absolute_url($appBasePath . 'image/cover.png', $appBasePath, $config);
    $escapeMeta = static fn (string $value): string => phpuploader_escape($value);
    $pageHeaderPath = $baseDir . '/src/View/show/header.php';
    $pageScripts = ['show-page.js'];

    $csrfToken = \PHPUploader\Core\SecurityUtils::generateCSRFToken();
    $escapedCsrfToken = phpuploader_escape($csrfToken);

    $downloadView = null;
    if ($downloadFile !== null) {
        $downloadView = [
            'fileId' => (int)$downloadFile['id'],
            'fileName' => phpuploader_escape((string)$downloadFile['origin_file_name']),
            'comment' => phpuploader_escape((string)($downloadFile['comment'] ?? '')),
            'fileSize' => phpuploader_escape(number_format(((int)$downloadFile['size']) / 1024 / 1024, 1)),
            'downloadCount' => (int)$downloadFile['count'],
            'uploadedAt' => phpuploader_escape(date('Y/m/d H:i', (int)$downloadFile['input_date'])),
            'hasDownloadKey' => (bool)($downloadFile['has_download_key'] ?? false),
            'hasDeleteKey' => (bool)($downloadFile['has_delete_key'] ?? false),
        ];
    }

    require $baseDir . '/src/View/common/header.php';
    require $baseDir . '/src/View/show/page.php';
    require $baseDir . '/src/View/common/footer.php';
} catch (Throwable $error) {
    phpuploader_render_error($error, $baseDir, $logger);
}
