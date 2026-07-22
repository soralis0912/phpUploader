function escapeHtmlForMessage(text) {
  return $('<div>').text(text == null ? '' : String(text)).html();
}

function normalizeUploadData(uploadData) {
  if (!window.fileManagerInstance) {
    throw new Error('FileManager is not initialized.');
  }

  if (!uploadData || typeof uploadData !== 'object') {
    throw new Error('Upload response data is missing.');
  }

  var fileId = Number(uploadData.file_id);
  var fileName = String(uploadData.file_name || '');
  var fileSize = Number(uploadData.file_size);
  var deleteKey = String(uploadData.delete_key || '');
  var inputDate = Number(uploadData.input_date);
  var count = Number(uploadData.count);

  if (!Number.isInteger(fileId) || fileId < 1) {
    throw new Error('Upload response file_id is invalid.');
  }

  if (fileName === '') {
    throw new Error('Upload response file_name is missing.');
  }

  if (!Number.isFinite(fileSize) || fileSize < 0) {
    throw new Error('Upload response file_size is invalid.');
  }

  if (deleteKey === '') {
    throw new Error('Upload response delete_key is missing.');
  }

  if (!Number.isFinite(inputDate) || inputDate < 1) {
    throw new Error('Upload response input_date is invalid.');
  }

  if (!Number.isFinite(count) || count < 0) {
    throw new Error('Upload response count is invalid.');
  }

  return {
    file: {
      id: fileId,
      origin_file_name: fileName,
      comment: String(uploadData.comment || ''),
      size: fileSize,
      count: count,
      input_date: inputDate
    },
    deleteKey: deleteKey,
    detailPageUrl: buildDownloadPageUrl(fileId)
  };
}

function addUploadedFileToList(uploadData) {
  var normalizedUpload = normalizeUploadData(uploadData);
  window.fileData = window.fileData || [];
  window.fileData.push(normalizedUpload.file);
  window.fileManagerInstance.setFiles(window.fileData);

  return normalizedUpload;
}

function showUploadComplete(uploadData) {
  var normalizedUpload = uploadData && uploadData.file ? uploadData : normalizeUploadData(uploadData);
  var html = '<strong>ファイルのアップロードが完了しました。</strong>';

  html += '<div class="upload-result">' +
          '<div class="upload-result__row">' +
          '<span class="upload-result__label">削除キー</span>' +
          '<code class="upload-result__key">' + escapeHtmlForMessage(normalizedUpload.deleteKey) + '</code>' +
          '</div>' +
          '<p class="upload-result__note">このキーは再表示できません。削除時に必要です。</p>' +
          '<div class="upload-result__row">' +
          '<span class="upload-result__label">詳細ページ</span>' +
          '<a href="' + escapeHtmlForMessage(normalizedUpload.detailPageUrl) + '">' +
          escapeHtmlForMessage(normalizedUpload.detailPageUrl) +
          '</a>' +
          '</div>' +
          '</div>';

  showSuccess(html, true);
}
