<?php
$assetJsVersions = [];
$commonScripts = [
    'modal.js',
    'app-url.js',
    'common.js',
];
$pageScripts = isset($pageScripts) && is_array($pageScripts) ? $pageScripts : [];
$assetJsFiles = array_values(array_unique(array_merge($commonScripts, $pageScripts)));

foreach ($assetJsFiles as $assetJsFile) {
    $assetJsPath = dirname(__DIR__, 3) . '/asset/js/' . $assetJsFile;
    $assetJsVersions[$assetJsFile] = file_exists($assetJsPath)
        ? (string)filemtime($assetJsPath)
        : ($version ?? 'dev');
}
?>
    <script
      src="https://code.jquery.com/jquery-3.7.1.min.js"
      integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
      crossorigin="anonymous"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"
      integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd"
      crossorigin="anonymous"></script>
<?php foreach ($assetJsFiles as $assetJsFile) : ?>
<?php
    $assetJsUrl = $escapedAppBasePath . 'asset/js/' . rawurlencode($assetJsFile)
        . '?v=' . rawurlencode($assetJsVersions[$assetJsFile]);
?>
    <script src="<?php echo $assetJsUrl; ?>"></script>
<?php endforeach; ?>
