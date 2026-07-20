<?php

/**
 * PHP Uploader Ver.2.0 - メインエントリーポイント
 *
 * 簡易フレームワーク with モダンPHP対応
 */

declare(strict_types=1);

$baseDir = __DIR__;
$page = (string)($_GET['page'] ?? 'index');

require_once $baseDir . '/app/bootstrap/render_page.php';

render_phpuploader_page($baseDir, $page, $baseDir . '/app/views/header.php');
