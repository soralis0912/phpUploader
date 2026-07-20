<?php
$file = $downloadFile ?? null;
?>

<div class="container">
  <div id="downloadPage" class="row bg-white radius box-shadow">
    <div class="col-sm-12">
      <?php if ($file === null) : ?>
        <div class="download-page">
          <p class="h2">ファイルが見つかりません</p>
          <p class="text-muted">指定されたファイルは存在しないか、削除されています。</p>
          <p>
            <a class="btn btn-default" href="<?php echo $escapedAppBasePath; ?>">一覧へ戻る</a>
          </p>
        </div>
      <?php else : ?>
        <?php
        $fileId = (int)$file['id'];
        $fileName = htmlspecialchars((string)$file['origin_file_name'], ENT_QUOTES, 'UTF-8');
        $comment = htmlspecialchars((string)($file['comment'] ?? ''), ENT_QUOTES, 'UTF-8');
        $fileSize = number_format(((int)$file['size']) / 1024 / 1024, 1);
        $downloadCount = (int)$file['count'];
        $uploadedAt = date('Y/m/d H:i', (int)$file['input_date']);
        $detailUrl = $appBasePath . 'show.php?id=' . rawurlencode((string)$fileId);
        ?>
        <div class="download-page">
          <p class="h2">ファイル詳細</p>

          <div class="download-file">
            <div class="download-file__icon">📄</div>
            <div class="download-file__body">
              <h1 class="download-file__name"><?php echo $fileName; ?></h1>
              <?php if ($comment !== '') : ?>
                <p class="download-file__comment"><?php echo $comment; ?></p>
              <?php endif; ?>
              <div class="download-file__meta">
                <span>ID: #<?php echo $fileId; ?></span>
                <span>サイズ: <?php echo $fileSize; ?>MB</span>
                <span>日付: <?php echo htmlspecialchars($uploadedAt, ENT_QUOTES, 'UTF-8'); ?></span>
                <span>DL: <?php echo $downloadCount; ?>回</span>
              </div>
            </div>
          </div>

          <div class="detail-link">
            <label for="detailUrlInput">このファイルのページ</label>
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                id="detailUrlInput"
                value="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>"
                readonly>
              <span class="input-group-btn">
                <a class="btn btn-default" href="<?php echo htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>">
                  開く
                </a>
              </span>
            </div>
          </div>

          <div id="errorContainer" class="error-container" style="display: none;">
            <div class="panel-heading">
              <h4>⚠️ エラー</h4>
            </div>
            <div class="panel-body"></div>
          </div>

          <form class="download-form" onsubmit="download_page_submit(<?php echo $fileId; ?>); return false;">
            <input
              type="hidden"
              id="csrfToken"
              name="csrf_token"
              value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
              <label for="downloadKeyInput">ダウンロードキー</label>
              <input
                type="text"
                class="form-control"
                id="downloadKeyInput"
                name="download_key"
                placeholder="設定されている場合のみ入力">
              <p class="help-block">キーなしのファイルは空白のままダウンロードできます。</p>
            </div>

            <div class="download-page__actions">
              <button type="submit" class="btn btn-primary">⬇️ ダウンロード</button>
              <a class="btn btn-default" href="<?php echo $escapedAppBasePath; ?>">一覧へ戻る</a>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
