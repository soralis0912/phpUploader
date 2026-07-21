<!DOCTYPE html>
<html lang="ja">
  <head>
    <?php require __DIR__ . '/header_common.php'; ?>
    <?php
    if (isset($pageHeaderPath) && is_string($pageHeaderPath) && file_exists($pageHeaderPath)) {
        require $pageHeaderPath;
    }
    ?>
  </head>
  <body class="bg-fade">
    <script>
      window.appBasePath = <?php echo json_encode($appBasePath ?? '/'); ?>;
    </script>
