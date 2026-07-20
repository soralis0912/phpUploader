<?php

?>
    <div class="modal" id="OKModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modal title</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Modal body text goes here.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary f-action">OK</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal" id="OKCanselModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modal title</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Modal body text goes here.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
            <button type="button" class="btn btn-primary f-action">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <!-- Latest compiled and minified JavaScript -->
    <script
      src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
      integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
      crossorigin="anonymous"></script>

    <?php
    $assetJsVersions = [];
    foreach (['app-url.js', 'file-manager.js', 'upload-result.js', 'download-page.js', 'common.js'] as $assetJsFile) {
        $assetJsPath = dirname(__DIR__, 2) . '/asset/js/' . $assetJsFile;
        $assetJsVersions[$assetJsFile] = file_exists($assetJsPath) ? (string)filemtime($assetJsPath) : ($version ?? 'dev');
    }
    $commonJsVersion = $assetJsVersions['common.js'];
    ?>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/modal.js"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/app-url.js?v=<?php echo rawurlencode($assetJsVersions['app-url.js']); ?>"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/file-manager.js?v=<?php echo rawurlencode($assetJsVersions['file-manager.js']); ?>"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/upload-result.js?v=<?php echo rawurlencode($assetJsVersions['upload-result.js']); ?>"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/download-page.js?v=<?php echo rawurlencode($assetJsVersions['download-page.js']); ?>"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/common.js?v=<?php echo rawurlencode($commonJsVersion); ?>"></script>
  </body>
</html>
