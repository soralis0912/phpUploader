<!DOCTYPE html>
<html lang="ja">
  <head>
    <?php
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $appBasePath = ($scriptDirectory === '/' || $scriptDirectory === '.' || $scriptDirectory === '')
        ? '/'
        : rtrim($scriptDirectory, '/') . '/';
    $escapedAppBasePath = htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8');
    ?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo $title ?></title>

    <!-- Bootstrap -->
    <!-- Latest compiled and minified CSS -->
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

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body class="bg-fade">
    <script>
      window.appBasePath = <?php echo json_encode($appBasePath); ?>;
    </script>
