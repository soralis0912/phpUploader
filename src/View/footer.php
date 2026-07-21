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
    $assetJsFiles = [
        'app-url.js',
        'file-manager.js',
        'upload-result.js',
        'download-page.js',
        'common.js',
    ];
    foreach ($assetJsFiles as $assetJsFile) {
        $assetJsPath = dirname(__DIR__, 2) . '/asset/js/' . $assetJsFile;
        $assetJsVersions[$assetJsFile] = file_exists($assetJsPath)
            ? (string)filemtime($assetJsPath)
            : ($version ?? 'dev');
    }
    $appUrlJs = $escapedAppBasePath . 'asset/js/app-url.js?v=' . rawurlencode($assetJsVersions['app-url.js']);
    $fileManagerJs = $escapedAppBasePath . 'asset/js/file-manager.js?v=' .
        rawurlencode($assetJsVersions['file-manager.js']);
    $uploadResultJs = $escapedAppBasePath . 'asset/js/upload-result.js?v=' .
        rawurlencode($assetJsVersions['upload-result.js']);
    $downloadPageJs = $escapedAppBasePath . 'asset/js/download-page.js?v=' .
        rawurlencode($assetJsVersions['download-page.js']);
    $commonJsVersion = $assetJsVersions['common.js'];
    $commonJs = $escapedAppBasePath . 'asset/js/common.js?v=' . rawurlencode($commonJsVersion);
    ?>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/modal.js"></script>
    <script src="<?php echo $appUrlJs; ?>"></script>
    <script src="<?php echo $fileManagerJs; ?>"></script>
    <script src="<?php echo $uploadResultJs; ?>"></script>
    <script src="<?php echo $downloadPageJs; ?>"></script>
    <script src="<?php echo $commonJs; ?>"></script>
  </body>
</html>
