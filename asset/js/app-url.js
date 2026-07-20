function appUrl(path) {
  var basePath = window.appBasePath || './';
  if (basePath.slice(-1) !== '/') {
    basePath += '/';
  }

  return basePath + String(path).replace(/^\/+/, '');
}

function buildDownloadPageUrl(id) {
  return appUrl('show/' + encodeURIComponent(id));
}
