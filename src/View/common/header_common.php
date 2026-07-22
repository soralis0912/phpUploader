<?php
$siteTitle = (string)($siteTitle ?? $title ?? 'PHP Uploader');
$escapeMeta = $escapeMeta ?? static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $escapeMeta($siteTitle); ?></title>

    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css"
      integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu"
      crossorigin="anonymous">

    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/common.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/responsive.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/responsive-extra.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/file-manager.css">
