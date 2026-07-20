<?php

declare(strict_types=1);

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if (preg_match('#^/([0-9]+)$#', $pathInfo, $matches) === 1) {
    $_GET['id'] = $matches[1];
}

$scriptBase = dirname(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/show/index.php')));
$_SERVER['SCRIPT_NAME'] = ($scriptBase === '/' ? '' : $scriptBase) . '/show.php';

require dirname(__DIR__) . '/show.php';
