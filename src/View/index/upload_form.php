<div class="row bg-white radius box-shadow">
  <div class="col-sm-12">
    <p class="h2">ファイルを登録</p>
    <form id="upload" class="upload-form">
      <input
        type="hidden"
        id="csrfToken"
        name="csrf_token"
        value="<?php echo $escapedCsrfToken; ?>">

      <div class="form-section file-input-group">
        <input id="lefile" name="file" type="file" style="display:none">
        <div
          id="uploadDropZone"
          class="upload-drop-zone"
          role="button"
          tabindex="0"
          aria-label="ファイルを選択またはドロップ">
          <div class="upload-drop-zone__message">
            <strong>ここにファイルをドロップ</strong>
            <span>またはファイルを選択</span>
          </div>
          <div class="input-group">
            <input type="text" id="fileInput" class="form-control" placeholder="ファイルを選択..." readonly>
            <span class="input-group-btn">
              <button type="button" class="btn btn-primary" onclick="$('input[id=lefile]').click();">
                📁 ファイル選択
              </button>
            </span>
          </div>
        </div>
        <p class="help-block">
          📊 最大<?php echo $escapedMaxFileSize; ?>MBまでアップロード可能<br>
          🧩 1チャンク: <?php echo $escapedChunkSize; ?>MB<br>
          📎 対応拡張子: <?php echo $escapedExtensionList; ?>
        </p>
      </div>

      <div class="form-section">
        <div class="form-group">
          <label for="commentInput">💬 コメント</label>
          <input type="text" class="form-control" id="commentInput" name="comment" placeholder="ファイルの説明を入力...">
          <p class="help-block"><?php echo $escapedMaxComment; ?>文字まで入力可能</p>
        </div>
      </div>

      <div class="form-section">
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <label for="dlkeyInput">🔐 ダウンロードキー</label>
              <input type="text" class="form-control" id="dleyInput" name="dlkey" placeholder="任意のパスワード...">
              <p class="help-block">空白で認証なし</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <label>🗑️ 削除キー</label>
              <p class="form-control-static">アップロード後に4文字のキーを自動発行します。</p>
              <p class="help-block">削除時に必要です。発行後に控えてください。</p>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="row">
          <div class="col-sm-offset-10 col-sm-2">
            <button type="button" class="btn btn-success btn-block btn-submit" onclick="file_upload()">
              🚀 アップロード
            </button>
          </div>
        </div>
      </div>
    </form>

    <div id="uploadContainer" class="upload-progress" style="display: none;">
      <div class="panel-heading">
        <h4>⏳ アップロード中...</h4>
      </div>
      <div class="panel-body">
        <div class="progress">
          <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;">
            <span id="progressText">0%</span>
          </div>
        </div>
      </div>
    </div>

    <div id="errorContainer" class="error-container" style="display: none;">
      <div class="panel-heading">
        <h4>⚠️ エラー</h4>
      </div>
      <div class="panel-body">
      </div>
    </div>
  </div>
</div>
