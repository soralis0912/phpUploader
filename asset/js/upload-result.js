function escapeHtmlForMessage(text) {
  return $('<div>').text(text == null ? '' : String(text)).html();
}

function addUploadedFileToList(uploadData) {
  if (!window.fileManagerInstance || !uploadData) {
    return;
  }

  var file = {
    id: Number(uploadData.file_id),
    origin_file_name: uploadData.file_name || '',
    comment: uploadData.comment || '',
    size: Number(uploadData.file_size || 0),
    count: Number(uploadData.count || 0),
    input_date: Number(uploadData.input_date || Math.floor(Date.now() / 1000))
  };

  window.fileData = window.fileData || [];
  window.fileData.push(file);
  window.fileManagerInstance.setFiles(window.fileData);
}

function showUploadComplete(uploadData) {
  var deleteKey = uploadData && uploadData.delete_key ? uploadData.delete_key : '';
  var detailPageUrl = uploadData && uploadData.file_id ? buildDownloadPageUrl(uploadData.file_id) : '';
  var html = '<strong>ファイルのアップロードが完了しました。</strong>';

  if (deleteKey) {
    html += '<div class="upload-result">' +
            '<div class="upload-result__row">' +
            '<span class="upload-result__label">削除キー</span>' +
            '<code class="upload-result__key">' + escapeHtmlForMessage(deleteKey) + '</code>' +
            '</div>' +
            '<p class="upload-result__note">このキーは再表示できません。削除時に必要です。</p>' +
            '</div>';
  }

  if (detailPageUrl) {
    html += '<div class="upload-result__row">' +
            '<span class="upload-result__label">詳細ページ</span>' +
            '<a href="' + escapeHtmlForMessage(detailPageUrl) + '">' +
            escapeHtmlForMessage(detailPageUrl) +
            '</a>' +
            '</div>';
  }

  showSuccess(html, true);
}
