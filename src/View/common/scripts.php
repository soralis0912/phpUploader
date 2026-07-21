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
    $assetJsPath = dirname(__DIR__, 3) . '/asset/js/' . $assetJsFile;
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script
      src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
      integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
      crossorigin="anonymous"></script>
    <script src="<?php echo $escapedAppBasePath; ?>asset/js/modal.js"></script>
    <script src="<?php echo $appUrlJs; ?>"></script>
    <script src="<?php echo $fileManagerJs; ?>"></script>
    <script src="<?php echo $uploadResultJs; ?>"></script>
    <script src="<?php echo $downloadPageJs; ?>"></script>
    <script src="<?php echo $commonJs; ?>"></script>
