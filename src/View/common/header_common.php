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
      href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
      integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
      crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/dataTables.bootstrap.min.css">

    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/common.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/responsive.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/responsive-extra.css">
    <link rel="stylesheet" href="<?php echo $escapedAppBasePath; ?>asset/css/file-manager.css">

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
