<?php if (isset($statusMessage)) : ?>
    <?php if ($statusMessage === 'success') : ?>
    <div id="statusMessage" class="alert alert-success" role="alert">
      <strong>削除完了！</strong> ファイルが正常に削除されました。
    </div>
    <?php elseif ($statusMessage === 'error') : ?>
    <div id="statusMessage" class="alert alert-danger" role="alert">
      <strong>エラー</strong> ファイルの削除に失敗しました。
    </div>
    <?php endif; ?>
<?php endif; ?>
