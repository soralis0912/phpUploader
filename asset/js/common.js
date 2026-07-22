// エラー表示ヘルパー関数
function showError(message) {
  $('#errorContainer > .panel-body').html(message);
  $('#errorContainer').fadeIn();
}

// 成功表示ヘルパー関数
function showSuccess(message, persist) {
  // 成功用のコンテナがない場合は作成
  if ($('#successContainer').length === 0) {
    var successHtml = '<div id="successContainer" class="panel panel-success" style="display: none;">' +
                     '<div class="panel-heading">成功</div>' +
                     '<div class="panel-body"></div>' +
                     '</div>';
    $('#errorContainer').after(successHtml);
  }
  $('#successContainer > .panel-body').html(message);
  $('#successContainer').fadeIn();

  if (!persist) {
    // 3秒後に自動で非表示
    setTimeout(function() {
      $('#successContainer').fadeOut();
    }, 3000);
  }
}

// CSRFトークンを取得する関数
function getCSRFToken() {
  return $('#csrfToken').val();
}
