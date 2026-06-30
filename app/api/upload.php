<?php

/**
 * ファイルアップロードAPI
 *
 * セキュリティ強化版のアップロード処理
 */

// 出力バッファリング開始

declare(strict_types=1);

ob_start();

// エラー表示設定（デバッグ用）
ini_set('display_errors', '0');
ini_set('log_errors', '1'); // ログファイルにエラーを記録
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
set_time_limit(300);

// ヘッダー設定
header('Content-Type: application/json; charset=utf-8');

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getConfiguredChunkSizeBytes(array $config): int
{
    $chunkSizeMb = $config['chunkSize'] ?? 50;

    if (!is_numeric($chunkSizeMb) || (float)$chunkSizeMb <= 0) {
        $chunkSizeMb = 50;
    }

    return (int)round((float)$chunkSizeMb * 1024 * 1024);
}

function parsePostedInteger(mixed $value): ?int
{
    if (!is_scalar($value)) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
        return null;
    }

    return (int)$value;
}

function getUploadedFileErrors(array $file): array
{
    $uploadErrors = [];

    switch ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
            $uploadErrors[] = 'アップロードされたファイルが大きすぎます。(' . ini_get('upload_max_filesize') . '以下)';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $maxFileSize = isset($_POST['maxFileSize']) ? (int)$_POST['maxFileSize'] : 0;
            $uploadErrors[] = 'アップロードされたファイルが大きすぎます。(' . ($maxFileSize / 1024) . 'KB以下)';
            break;
        case UPLOAD_ERR_PARTIAL:
            $uploadErrors[] = 'アップロードが途中で中断されました。もう一度お試しください。';
            break;
        case UPLOAD_ERR_NO_FILE:
            $uploadErrors[] = 'ファイルが選択されていません。';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $uploadErrors[] = 'サーバーエラーが発生しました。管理者にお問い合わせください。';
            break;
        default:
            $uploadErrors[] = 'アップロードに失敗しました。';
            break;
    }

    return $uploadErrors;
}

function validateUploadData(
    string $fileName,
    string $comment,
    string $dlKey,
    string $delKey,
    int $fileSize,
    array $config
): array {
    $validationErrors = [];

    if ($fileSize > $config['maxFileSize'] * 1024 * 1024) {
        $validationErrors[] = "ファイルサイズが上限({$config['maxFileSize']}MB)を超えています。";
    }

    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = array_map('strtolower', $config['extension']);
    if (!in_array($fileExtension, $allowedExtensions, true)) {
        $validationErrors[] = '許可されていない拡張子です。(' . implode(', ', $config['extension']) . 'のみ)';
    }

    if (mb_strlen($comment) > $config['maxComment']) {
        $validationErrors[] = "コメントが長すぎます。({$config['maxComment']}文字以下)";
    }

    if (!empty($dlKey) && mb_strlen($dlKey) < $config['security']['minKeyLength']) {
        $validationErrors[] = "ダウンロードキーは{$config['security']['minKeyLength']}文字以上で設定してください。";
    }

    if (!empty($delKey) && mb_strlen($delKey) < $config['security']['minKeyLength']) {
        $validationErrors[] = "削除キーは{$config['security']['minKeyLength']}文字以上で設定してください。";
    }

    return $validationErrors;
}

function deleteDirectoryRecursive(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectoryRecursive($path);
            continue;
        }

        @unlink($path);
    }

    @rmdir($directory);
}

function cleanupStaleChunkUploads(string $chunkRoot): void
{
    if (!is_dir($chunkRoot)) {
        return;
    }

    $items = scandir($chunkRoot);
    if ($items === false) {
        return;
    }

    $expiration = time() - 86400;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $chunkRoot . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && filemtime($path) !== false && filemtime($path) < $expiration) {
            deleteDirectoryRecursive($path);
        }
    }
}

function removeOldestFileIfNeeded(
    \PDO $db,
    array $config,
    \PHPUploader\Core\Logger $logger
): void {
    $fileCountStmt = $db->prepare('SELECT COUNT(id) as count, MIN(id) as min_id FROM uploaded');
    $fileCountStmt->execute();
    $countResult = $fileCountStmt->fetch();

    if ($countResult['count'] < $config['saveMaxFiles']) {
        return;
    }

    $oldFileStmt = $db->prepare('SELECT id, origin_file_name, stored_file_name FROM uploaded WHERE id = :id');
    $oldFileStmt->execute(['id' => $countResult['min_id']]);
    $oldFile = $oldFileStmt->fetch();

    if (!$oldFile) {
        return;
    }

    if (!empty($oldFile['stored_file_name'])) {
        $oldFilePath = $config['dataDirectoryPath'] . '/' . $oldFile['stored_file_name'];
    } else {
        $oldFilePath = $config['dataDirectoryPath'] . '/file_' . $oldFile['id'] .
            '.' . pathinfo($oldFile['origin_file_name'], PATHINFO_EXTENSION);
    }

    if (file_exists($oldFilePath)) {
        unlink($oldFilePath);
    }

    $deleteStmt = $db->prepare('DELETE FROM uploaded WHERE id = :id');
    $deleteStmt->execute(['id' => $oldFile['id']]);

    $logger->info('Old file deleted due to storage limit', ['deleted_file_id' => $oldFile['id']]);
}

function persistUploadedFile(
    string $sourcePath,
    string $fileName,
    string $comment,
    string $dlKey,
    string $delKey,
    int $fileSize,
    bool $sourceIsUploadedFile,
    array $config,
    \PDO $db,
    \PHPUploader\Core\Logger $logger,
    \PHPUploader\Core\ResponseHandler $responseHandler
): array {
    $validationErrors = validateUploadData($fileName, $comment, $dlKey, $delKey, $fileSize, $config);
    if (!empty($validationErrors)) {
        $responseHandler->error('バリデーションエラー', $validationErrors, 400);
    }

    removeOldestFileIfNeeded($db, $config, $logger);

    $fileHash = hash_file('sha256', $sourcePath);
    if ($fileHash === false) {
        $responseHandler->error('ファイルハッシュの生成に失敗しました。', [], 500);
    }

    $dlKeyHash =
        (!empty($dlKey) && trim($dlKey) !== '') ? \PHPUploader\Core\SecurityUtils::hashPassword($dlKey) : null;
    $delKeyHash =
        (!empty($delKey) && trim($delKey) !== '') ? \PHPUploader\Core\SecurityUtils::hashPassword($delKey) : null;

    $insertStmt = $db->prepare('
        INSERT INTO uploaded (
            origin_file_name, comment, size, count, input_date,
            dl_key_hash, del_key_hash, file_hash, ip_address
        ) VALUES (
            :origin_file_name, :comment, :size, :count, :input_date,
            :dl_key_hash, :del_key_hash, :file_hash, :ip_address
        )
    ');

    $insertData = [
        'origin_file_name' => $fileName,
        'comment' => $comment,
        'size' => $fileSize,
        'count' => 0,
        'input_date' => time(),
        'dl_key_hash' => $dlKeyHash,
        'del_key_hash' => $delKeyHash,
        'file_hash' => $fileHash,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    if (!$insertStmt->execute($insertData)) {
        $errorInfo = $insertStmt->errorInfo();
        error_log('Database insert failed: ' . print_r($errorInfo, true));
        $responseHandler->error('データベースへの保存に失敗しました。', [], 500);
    }

    $fileId = (int)$db->lastInsertId();
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $hashedFileName = \PHPUploader\Core\SecurityUtils::generateSecureFileName($fileId, $fileName);
    $storedFileName = \PHPUploader\Core\SecurityUtils::generateStoredFileName($hashedFileName, $fileExtension);
    $saveFilePath = $config['dataDirectoryPath'] . '/' . $storedFileName;

    $saved = $sourceIsUploadedFile
        ? move_uploaded_file($sourcePath, $saveFilePath)
        : rename($sourcePath, $saveFilePath);

    if (!$saved) {
        $db->prepare('DELETE FROM uploaded WHERE id = :id')->execute(['id' => $fileId]);
        $responseHandler->error('ファイルの保存に失敗しました。', [], 500);
    }

    $updateStmt = $db->prepare('UPDATE uploaded SET stored_file_name = :stored_file_name WHERE id = :id');
    if (!$updateStmt->execute(['stored_file_name' => $storedFileName, 'id' => $fileId])) {
        if (file_exists($saveFilePath)) {
            unlink($saveFilePath);
        }
        $db->prepare('DELETE FROM uploaded WHERE id = :id')->execute(['id' => $fileId]);
        $responseHandler->error('ファイル情報の更新に失敗しました。', [], 500);
    }

    $logger->access($fileId, 'upload', 'success');

    return [
        'file_id' => $fileId,
        'file_name' => $fileName,
        'file_size' => $fileSize,
    ];
}

function handleStandardUpload(
    array $config,
    \PDO $db,
    \PHPUploader\Core\Logger $logger,
    \PHPUploader\Core\ResponseHandler $responseHandler
): void {
    if (!isset($_FILES['file'])) {
        $responseHandler->error('ファイルが選択されていません。', [], 400);
    }

    $uploadErrors = getUploadedFileErrors($_FILES['file']);
    if (!empty($uploadErrors)) {
        $responseHandler->error('アップロードエラー', $uploadErrors, 400);
    }

    if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
        $responseHandler->error('不正なファイルアップロードです。', [], 400);
    }

    $fileSize = filesize($_FILES['file']['tmp_name']);
    if ($fileSize === false) {
        $responseHandler->error('ファイルサイズの取得に失敗しました。', [], 400);
    }

    $fileName = htmlspecialchars($_FILES['file']['name'], ENT_QUOTES, 'UTF-8');
    $comment = htmlspecialchars($_POST['comment'] ?? '', ENT_QUOTES, 'UTF-8');
    $dlKey = $_POST['dlkey'] ?? '';
    $delKey = $_POST['delkey'] ?? '';

    $data = persistUploadedFile(
        $_FILES['file']['tmp_name'],
        $fileName,
        $comment,
        $dlKey,
        $delKey,
        $fileSize,
        true,
        $config,
        $db,
        $logger,
        $responseHandler
    );

    $responseHandler->success('ファイルのアップロードが完了しました。', $data);
}

function handleChunkedUpload(
    array $config,
    \PDO $db,
    \PHPUploader\Core\Logger $logger,
    \PHPUploader\Core\ResponseHandler $responseHandler
): void {
    if (!isset($_FILES['file'])) {
        $responseHandler->error('ファイルが選択されていません。', [], 400);
    }

    $uploadErrors = getUploadedFileErrors($_FILES['file']);
    if (!empty($uploadErrors)) {
        $responseHandler->error('アップロードエラー', $uploadErrors, 400);
    }

    if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
        $responseHandler->error('不正なファイルアップロードです。', [], 400);
    }

    $uploadId = $_POST['upload_id'] ?? '';
    if (!is_string($uploadId) || preg_match('/^[A-Za-z0-9_-]{16,128}$/', $uploadId) !== 1) {
        $responseHandler->error('チャンクアップロードIDが不正です。', [], 400);
    }

    $chunkIndex = parsePostedInteger($_POST['chunk_index'] ?? null);
    $totalChunks = parsePostedInteger($_POST['total_chunks'] ?? null);
    $totalSize = parsePostedInteger($_POST['total_size'] ?? null);

    if ($chunkIndex === null || $totalChunks === null || $totalSize === null) {
        $responseHandler->error('チャンク情報が不正です。', [], 400);
    }

    if ($totalChunks <= 0 || $chunkIndex < 0 || $chunkIndex >= $totalChunks || $totalSize <= 0) {
        $responseHandler->error('チャンク情報が範囲外です。', [], 400);
    }

    $chunkSize = filesize($_FILES['file']['tmp_name']);
    if ($chunkSize === false) {
        $responseHandler->error('チャンクサイズの取得に失敗しました。', [], 400);
    }

    $configuredChunkSize = getConfiguredChunkSizeBytes($config);
    if ($chunkSize > $configuredChunkSize) {
        $responseHandler->error(
            'チャンクサイズが上限(' . round($configuredChunkSize / 1024 / 1024, 2) . 'MB)を超えています。',
            [],
            400
        );
    }

    $fileName = htmlspecialchars((string)($_POST['file_name'] ?? $_FILES['file']['name']), ENT_QUOTES, 'UTF-8');
    if ($fileName === '') {
        $responseHandler->error('ファイル名が不正です。', [], 400);
    }

    $comment = htmlspecialchars($_POST['comment'] ?? '', ENT_QUOTES, 'UTF-8');
    $dlKey = $_POST['dlkey'] ?? '';
    $delKey = $_POST['delkey'] ?? '';

    $validationErrors = validateUploadData($fileName, $comment, $dlKey, $delKey, $totalSize, $config);
    if (!empty($validationErrors)) {
        $responseHandler->error('バリデーションエラー', $validationErrors, 400);
    }

    $chunkRoot = rtrim($config['dataDirectoryPath'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'chunks';
    if (!is_dir($chunkRoot) && !mkdir($chunkRoot, 0755, true)) {
        $responseHandler->error('チャンク保存ディレクトリの作成に失敗しました。', [], 500);
    }

    cleanupStaleChunkUploads($chunkRoot);

    $uploadDirectory = $chunkRoot . DIRECTORY_SEPARATOR . $uploadId;
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        $responseHandler->error('チャンク保存ディレクトリの作成に失敗しました。', [], 500);
    }

    $metadataPath = $uploadDirectory . DIRECTORY_SEPARATOR . 'metadata.json';
    if (file_exists($metadataPath)) {
        $metadata = json_decode((string)file_get_contents($metadataPath), true);
        if (
            !is_array($metadata)
            || ($metadata['file_name'] ?? '') !== $fileName
            || ($metadata['total_chunks'] ?? 0) !== $totalChunks
            || ($metadata['total_size'] ?? 0) !== $totalSize
        ) {
            $responseHandler->error('チャンクアップロード情報が一致しません。', [], 400);
        }
    } else {
        $metadata = [
            'file_name' => $fileName,
            'total_chunks' => $totalChunks,
            'total_size' => $totalSize,
            'created_at' => time(),
        ];
        if (file_put_contents($metadataPath, json_encode($metadata, JSON_UNESCAPED_UNICODE)) === false) {
            $responseHandler->error('チャンクアップロード情報の保存に失敗しました。', [], 500);
        }
    }

    $chunkPath = $uploadDirectory . DIRECTORY_SEPARATOR . sprintf('%06d.part', $chunkIndex);
    if (file_exists($chunkPath)) {
        unlink($chunkPath);
    }

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
        $responseHandler->error('チャンクの保存に失敗しました。', [], 500);
    }

    $allChunksReceived = true;
    for ($i = 0; $i < $totalChunks; $i++) {
        if (!file_exists($uploadDirectory . DIRECTORY_SEPARATOR . sprintf('%06d.part', $i))) {
            $allChunksReceived = false;
            break;
        }
    }

    if (!$allChunksReceived) {
        $responseHandler->success('チャンクを受信しました。', [
            'chunk_received' => true,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'upload_complete' => false,
        ]);
    }

    $assembledPath = $uploadDirectory . DIRECTORY_SEPARATOR . 'assembled.upload';
    $destination = fopen($assembledPath, 'wb');
    if ($destination === false) {
        deleteDirectoryRecursive($uploadDirectory);
        $responseHandler->error('チャンクの結合に失敗しました。', [], 500);
    }

    for ($i = 0; $i < $totalChunks; $i++) {
        $partPath = $uploadDirectory . DIRECTORY_SEPARATOR . sprintf('%06d.part', $i);
        $source = fopen($partPath, 'rb');
        if ($source === false || stream_copy_to_stream($source, $destination) === false) {
            if (is_resource($source)) {
                fclose($source);
            }
            fclose($destination);
            deleteDirectoryRecursive($uploadDirectory);
            $responseHandler->error('チャンクの結合に失敗しました。', [], 500);
        }
        fclose($source);
    }
    fclose($destination);

    $assembledSize = filesize($assembledPath);
    if ($assembledSize === false || $assembledSize !== $totalSize) {
        deleteDirectoryRecursive($uploadDirectory);
        $responseHandler->error('結合後のファイルサイズが一致しません。', [], 400);
    }

    $data = persistUploadedFile(
        $assembledPath,
        $fileName,
        $comment,
        $dlKey,
        $delKey,
        $totalSize,
        false,
        $config,
        $db,
        $logger,
        $responseHandler
    );

    deleteDirectoryRecursive($uploadDirectory);

    $data['upload_complete'] = true;
    $responseHandler->success('ファイルのアップロードが完了しました。', $data);
}

try {
    // 設定とユーティリティの読み込み（絶対パスで修正）
    $baseDir = dirname(dirname(__DIR__)); // アプリケーションルートディレクトリ
    require_once $baseDir . '/src/Core/ConfigLoader.php';
    \PHPUploader\Core\ConfigLoader::requireConfig($baseDir);
    require_once $baseDir . '/src/Core/Logger.php';
    require_once $baseDir . '/src/Core/ResponseHandler.php';

    $configInstance = new \PHPUploader\Config();
    $config = $configInstance->index();

    // アプリケーション初期化
    require_once $baseDir . '/app/models/init.php';

    $initInstance = new \PHPUploader\Model\Init($config);
    $db = $initInstance -> initialize();

    // ログとレスポンスハンドラーの初期化
    $logger = new \PHPUploader\Core\Logger($config['logDirectoryPath'], $config['logLevel'], $db);
    $responseHandler = new \PHPUploader\Core\ResponseHandler($logger);

    // リクエストメソッドの確認
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $responseHandler->error('無効なリクエストメソッドです。', [], 405);
    }

    // CSRFトークンの検証
    if (!\PHPUploader\Core\SecurityUtils::validateCSRFToken($_POST['csrf_token'] ?? null)) {
        $logger->warning('CSRF token validation failed', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        $responseHandler->error('無効なリクエストです。ページを再読み込みしてください。', [], 403);
    }

    if (($_POST['chunk_upload'] ?? '') === '1') {
        handleChunkedUpload($config, $db, $logger, $responseHandler);
    }

    handleStandardUpload($config, $db, $logger, $responseHandler);
} catch (Throwable $e) {
    // 出力バッファをクリア
    if (ob_get_level()) {
        ob_clean();
    }

    // 緊急時のエラーハンドリング
    if (isset($logger)) {
        $logger->error('Upload API Error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    if (isset($responseHandler)) {
        $responseHandler->error('システムエラーが発生しました。', [], 500);
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'システムエラーが発生しました。',
        'timestamp' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
