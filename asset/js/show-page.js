function download_page_submit(id) {
  $('#errorContainer').fadeOut();
  dl_certificat(id, $('#downloadKeyInput').val() || '');
}

function delete_page_submit(id) {
  $('#errorContainer').fadeOut();
  del_certificat(id, '');
}

function confirm_dl_button(id){
  closeModal();
  dl_certificat(id ,$('#confirmDlkeyInput').val());
}

function confirm_del_button(id){
  closeModal();
  del_certificat(id ,$('#confirmDelkeyInput').val());
}

function dl_certificat(id, key){
  var postdata = {
    id: id,
    key: key,
    csrf_token: getCSRFToken()
  };

  $.ajax({
    url  : appUrl('api/verifydownload.php'),
    type : 'POST',
    data : postdata,
    dataType    : 'json'
  })
  .done(function(data, textStatus, jqXHR){
    if (data.status === 'success') {
      location.href = appUrl('download.php?id=' + encodeURIComponent(data.data.id) + '&key=' + encodeURIComponent(data.data.token));
      return;
    }

    if (data.status === 'error') {
      var errorMessage = data.message || 'ダウンロードに失敗しました。';
      if (data.error_code) {
        errorMessage += '<br><small class="text-muted">(エラーコード: ' + data.error_code + ')</small>';
      }
      showError(errorMessage);
      return;
    }

    showError('ダウンロードに失敗しました。');
  })
  .fail(function(jqXHR, textStatus, errorThrown){
    var errorMsg = 'ダウンロード処理でサーバーエラーが発生しました。';

    // レスポンスがJSONの場合は詳細情報を取得
    if (jqXHR.responseJSON) {
      if (jqXHR.responseJSON.message) {
        errorMsg = jqXHR.responseJSON.message;
      }
      if (jqXHR.responseJSON.error_code) {
        errorMsg += '<br><small class="text-muted">(エラーコード: ' + jqXHR.responseJSON.error_code + ')</small>';
      }
    } else if (jqXHR.responseText) {
      try {
        var parsed = JSON.parse(jqXHR.responseText);
        if (parsed.message) {
          errorMsg = parsed.message;
        }
      } catch(e) {
        errorMsg += '<br><small class="text-muted">(HTTP ' + jqXHR.status + ': ' + errorThrown + ')</small>';
      }
    }

    showError(errorMsg);
  });
}

function del_certificat(id, key){
  var postdata = {
    id: id,
    key: key,
    csrf_token: getCSRFToken()
  };

  $.ajax({
    url  : appUrl('api/verifydelete.php'),
    type : 'POST',
    data : postdata,
    dataType    : 'json'
  })
  .done(function(data, textStatus, jqXHR){
    if (data.status === 'success') {
      location.href = appUrl('delete.php?id=' + encodeURIComponent(data.data.id) + '&key=' + encodeURIComponent(data.data.token));
      return;
    }

    if (data.status === 'error') {
      if (data.error_code === 'AUTH_REQUIRED' || data.error_code === 'INVALID_KEY') {
        var html = '<div class="form-group">' +
                  '<label for="confirmDelkeyInput">DELキーの入力</label>' +
                  '<input type="text" class="form-control" id="confirmDelkeyInput" name="confirmdelkey" placeholder="DELキーを入力...">' +
                  '</div>';
        openModal('okcansel', '認証が必要です', html, 'confirm_del_button(' + id + ');');
        return;
      }

      var errorMessage = data.message || '削除に失敗しました。';
      if (data.error_code) {
        errorMessage += '<br><small class="text-muted">(エラーコード: ' + data.error_code + ')</small>';
      }
      showError(errorMessage);
      return;
    }

    showError('削除に失敗しました。');
  })
  .fail(function(jqXHR, textStatus, errorThrown){
    var errorMsg = '削除処理でサーバーエラーが発生しました。';

    // レスポンスがJSONの場合は詳細情報を取得
    if (jqXHR.responseJSON) {
      if (jqXHR.responseJSON.message) {
        errorMsg = jqXHR.responseJSON.message;
      }
      if (jqXHR.responseJSON.error_code) {
        errorMsg += '<br><small class="text-muted">(エラーコード: ' + jqXHR.responseJSON.error_code + ')</small>';
      }
    } else if (jqXHR.responseText) {
      try {
        var parsed = JSON.parse(jqXHR.responseText);
        if (parsed.message) {
          errorMsg = parsed.message;
        }
      } catch(e) {
        errorMsg += '<br><small class="text-muted">(HTTP ' + jqXHR.status + ': ' + errorThrown + ')</small>';
      }
    }

    showError(errorMsg);
  });
}
