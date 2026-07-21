<div class="container">
  <div id="downloadPage" class="row bg-white radius box-shadow">
    <div class="col-sm-12">
      <?php if ($downloadView === null) : ?>
        <div class="download-page">
          <p class="h2">ファイルが見つかりません</p>
          <p class="text-muted">指定されたファイルは存在しないか、削除されています。</p>
          <p>
            <a class="btn btn-default" href="<?php echo $escapedAppBasePath; ?>">一覧へ戻る</a>
          </p>
        </div>
      <?php else : ?>
        <div class="download-page">
          <p class="h2">ファイル詳細</p>

          <div class="download-file">
            <div class="download-file__icon">📄</div>
            <div class="download-file__body">
              <h1 class="download-file__name"><?php echo $downloadView['fileName']; ?></h1>
              <?php if ($downloadView['comment'] !== '') : ?>
                <p class="download-file__comment"><?php echo $downloadView['comment']; ?></p>
              <?php endif; ?>
              <div class="download-file__meta">
                <span>サイズ: <?php echo $downloadView['fileSize']; ?>MB</span>
                <span>日付: <?php echo $downloadView['uploadedAt']; ?></span>
                <span>DL: <?php echo $downloadView['downloadCount']; ?>回</span>
              </div>
            </div>
          </div>

          <div id="errorContainer" class="error-container" style="display: none;">
            <div class="panel-heading">
              <h4>⚠️ エラー</h4>
            </div>
            <div class="panel-body"></div>
          </div>

          <form class="download-form" onsubmit="download_page_submit(<?php echo $downloadView['fileId']; ?>); return false;">
            <input
              type="hidden"
              id="csrfToken"
              name="csrf_token"
              value="<?php echo $escapedCsrfToken; ?>">

            <?php if ($downloadView['hasDownloadKey']) : ?>
              <div class="form-group">
                <label for="downloadKeyInput">ダウンロードキー</label>
                <input
                  type="text"
                  class="form-control"
                  id="downloadKeyInput"
                  name="download_key"
                  placeholder="ダウンロードキーを入力">
              </div>
            <?php endif; ?>

            <div class="download-page__actions">
              <button type="submit" class="btn btn-primary">⬇️ ダウンロード</button>
              <a class="btn btn-default" href="<?php echo $escapedAppBasePath; ?>">一覧へ戻る</a>
            </div>
          </form>

          <form class="delete-form" onsubmit="delete_page_submit(<?php echo $downloadView['fileId']; ?>); return false;">
            <?php if ($downloadView['hasDeleteKey']) : ?>
              <div class="form-group">
                <label for="deleteKeyInput">削除キー</label>
                <input
                  type="text"
                  class="form-control"
                  id="deleteKeyInput"
                  name="delete_key"
                  placeholder="削除キーを入力">
              </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-danger">🗑️ 削除</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
