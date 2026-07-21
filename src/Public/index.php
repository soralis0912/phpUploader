<?php

/**
 * PHP Uploader Ver.2.0 - メインエントリーポイント
 *
 * 簡易フレームワーク with モダンPHP対応
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);
$page = (string)($_GET['page'] ?? 'index');

require_once $baseDir . '/src/Bootstrap/render_page.php';

render_phpuploader_page($baseDir, $page, $baseDir . '/src/View/header.php');
