$(document).ready(function(){

  // === 新しいファイル管理システム初期化 (Ver.2.0) ===
  if (window.fileData && document.getElementById('fileManagerContainer')) {
    // DataTables完全廃止・新ファイルマネージャー使用
    const fileManager = new FileManager(
      document.getElementById('fileManagerContainer'), {
        itemsPerPage: 12,
        defaultSort: 'date_desc'
      }
    );
    
    // PHPから渡されたファイルデータを設定
    fileManager.setFiles(window.fileData);
    
    // グローバルに公開（デバッグ・外部操作用）
    window.fileManagerInstance = fileManager;
  }

  // === レガシー DataTables 処理（Ver.2.0では無効化） ===
  if(document.getElementById('fileList') != null && !window.fileData){
    // Ver.1.x互換性のための緊急フォールバック
    console.warn('FileManager initialization failed, falling back to DataTables');
    
    $.extend( $.fn.dataTable.defaults, {
      language: {
        url: 'https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Japanese.json'
      }
    });

    $('#fileList').DataTable({
      "order": [ [0, "desc"] ],
      "columnDefs": [ {
        "ordered": false,
        "targets": [6]
      } ]
    });
  }

  $('input[id=lefile]').change(function() {
    $('#fileInput').val($(this).val().replace('C:\\fakepath\\', ''));
  });

  // ステータスメッセージの自動非表示
  if ($('#statusMessage').length > 0) {
    setTimeout(function() {
      $('#statusMessage').fadeOut();
    }, 5000);
  }
});

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

// カードの詳細部分の開閉機能
function toggleCardDetails(element) {
  var $header = $(element);
  var $details = $header.next('.file-card__details');
  var $toggle = $header.find('.file-card__toggle');

  if ($details.hasClass('expanded')) {
    // 閉じる
    $details.removeClass('expanded');
    $toggle.removeClass('expanded');
  } else {
    // 開く
    $details.addClass('expanded');
    $toggle.addClass('expanded');
  }
}

// プログレスバーのテキスト更新
function updateProgressBar(percent) {
  $('#progressBar').css({width: percent + '%'});
  $('#progressText').text(percent + '%');
}

function updateProgressText(text) {
  $('#progressText').text(text);
}

// 画面リサイズ時の対応
$(window).resize(function() {
  // 新ファイルマネージャーのリサイズ対応
  if (window.fileManagerInstance) {
    window.fileManagerInstance.refresh();
  }
  
  // レガシー DataTables 対応（フォールバック用）
  if ($(window).width() > 768 && $.fn.DataTable && $.fn.DataTable.isDataTable('#fileList')) {
    $('#fileList').DataTable().columns.adjust();
  }
});

function file_upload()
{
  if (window.uploadInProgress) {
    return;
  }

  if($('#fileInput').val() == ''){
    showError('ファイルを選択してください。');
    return;
  }

  var fileInput = $('#lefile').get(0);
  var file = fileInput && fileInput.files ? fileInput.files[0] : null;
  if (!file) {
    showError('ファイルを選択してください。');
    return;
  }

  $('#errorContainer').fadeOut();
  $('#uploadContainer').fadeIn();
  window.uploadInProgress = true;
  var submitButton = $('.btn-submit');
  var originalButtonHtml = submitButton.html();
  submitButton.prop('disabled', true).html('⏳ アップロード中...');
  updateProgressBar(0);

  function getChunkSizeBytes() {
    var chunkSizeMb = 50;
    if (window.uploadConfig && Number(window.uploadConfig.chunkSize) > 0) {
      chunkSizeMb = Number(window.uploadConfig.chunkSize);
    }

    return Math.round(chunkSizeMb * 1024 * 1024);
  }

  function buildUploadId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID().replace(/-/g, '');
    }

    return String(Date.now()) + String(Math.random()).replace(/[^0-9]/g, '');
  }

  function appendCommonFields(formdata) {
    formdata.append('csrf_token', getCSRFToken());
    formdata.append('comment', $('[name=comment]').val() || '');
    formdata.append('dlkey', $('[name=dlkey]').val() || '');
  }

  function createUploadRequest(formdata, progressCallback) {
    return $.ajax({
      url  : appUrl('app/api/upload.php'),
      type : 'POST',
      data : formdata,
      cache       : false,
      contentType : false,
      processData : false,
      dataType    : 'json',
      async: true,
      xhr : function(){
        var XHR = $.ajaxSettings.xhr();
        if(XHR.upload){
          XHR.upload.addEventListener('progress', progressCallback, false);
        }
        return XHR;
      },
    });
  }

  function uploadStandardFile() {
    var formdata = new FormData();
    appendCommonFields(formdata);
    formdata.append('file', file, file.name);

    return createUploadRequest(formdata, function(e){
      if (!e.lengthComputable) {
        return;
      }

      var progress = parseInt(e.loaded / e.total * 100);
      updateProgressBar(progress);
      if (progress >= 100) {
        updateProgressText('サーバー処理中...');
      }
    });
  }

  function uploadChunkedFile(chunkSize) {
    var deferred = $.Deferred();
    var uploadId = buildUploadId();
    var totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));

    function sendChunk(chunkIndex) {
      var start = chunkIndex * chunkSize;
      var end = Math.min(start + chunkSize, file.size);
      var chunk = file.slice(start, end);
      var formdata = new FormData();

      appendCommonFields(formdata);
      formdata.append('file', chunk, file.name);
      formdata.append('chunk_upload', '1');
      formdata.append('upload_id', uploadId);
      formdata.append('chunk_index', String(chunkIndex));
      formdata.append('total_chunks', String(totalChunks));
      formdata.append('total_size', String(file.size));
      formdata.append('file_name', file.name);

      createUploadRequest(formdata, function(e){
        if (!e.lengthComputable) {
          return;
        }

        var uploadedBytes = Math.min(start + e.loaded, file.size);
        var progress = parseInt(uploadedBytes / file.size * 100);
        updateProgressBar(progress);
        if (chunkIndex + 1 >= totalChunks && progress >= 100) {
          updateProgressText('サーバー処理中...');
        }
      })
      .done(function(data){
        if (data.status === 'error') {
          deferred.resolve(data);
          return;
        }

        if (chunkIndex + 1 >= totalChunks) {
          updateProgressBar(100);
          deferred.resolve(data);
          return;
        }

        sendChunk(chunkIndex + 1);
      })
      .fail(function(jqXHR, textStatus, errorThrown){
        deferred.reject(jqXHR, textStatus, errorThrown);
      });
    }

    sendChunk(0);
    return deferred.promise();
  }

  var chunkSize = getChunkSizeBytes();
  var uploadRequest = chunkSize > 0 ? uploadChunkedFile(chunkSize) : uploadStandardFile();

  uploadRequest.done(function(data, textStatus, jqXHR){
    if (data.status === 'success') {
      var uploadData = data.data || {};
      addUploadedFileToList(uploadData);
      showUploadComplete(uploadData);
      $('#upload')[0].reset();
      $('#fileInput').val('');
    } else if (data.status === 'error') {
      // Ver.2.0のエラーレスポンス形式に対応
      var errorMessage = '';

      // バリデーションエラーがある場合は詳細を表示
      if (data.validation_errors && data.validation_errors.length > 0) {
        errorMessage = '<strong>バリデーションエラー:</strong><br>' + data.validation_errors.join('<br>');
      } else if (data.message) {
        errorMessage = data.message;
      } else {
        errorMessage = 'アップロードに失敗しました。';
      }

      // エラーコードがある場合は追加情報として表示
      if (data.error_code) {
        errorMessage += '<br><small class="text-muted">(エラーコード: ' + data.error_code + ')</small>';
      }

      showError(errorMessage);
    } else {
      // 旧バージョン互換
      switch (data.status){
        case 'filesize_over':
            showError('ファイル容量が大きすぎます。');
          break;
        case 'extension_error':
            showError('許可されていない拡張子です。拡張子:'+data.ext);
          break;
        case 'comment_error':
            showError('コメントの文字数が規定数を超えています。');
          break;
        case 'sqlwrite_error':
            showError('データベースの書き込みに失敗しました。');
          break;
        case 'ok':
          location.reload();
          break;
        default:
          showError('アップロードに失敗しました: ' + (data.message || '不明なエラー'));
      }
    }
  })
  .fail(function(jqXHR, textStatus, errorThrown){
    var errorMsg = 'サーバーエラーが発生しました。';

    // レスポンスがJSONの場合は詳細情報を取得
    if (jqXHR.responseJSON) {
      if (jqXHR.responseJSON.message) {
        errorMsg = jqXHR.responseJSON.message;
      }
      if (jqXHR.responseJSON.error_code) {
        errorMsg += '<br><small class="text-muted">(エラーコード: ' + jqXHR.responseJSON.error_code + ')</small>';
      }
      if (jqXHR.responseJSON.validation_errors && jqXHR.responseJSON.validation_errors.length > 0) {
        errorMsg += '<br><strong>詳細:</strong><br>' + jqXHR.responseJSON.validation_errors.join('<br>');
      }
    } else if (jqXHR.responseText) {
      // JSONでない場合はテキスト内容を確認
      try {
        var parsed = JSON.parse(jqXHR.responseText);
        if (parsed.message) {
          errorMsg = parsed.message;
        }
      } catch(e) {
        // JSONパースに失敗した場合はHTTPステータスを表示
        errorMsg += '<br><small class="text-muted">(HTTP ' + jqXHR.status + ': ' + errorThrown + ')</small>';
      }
    }

    showError(errorMsg);
  })
  .always(function( jqXHR, textStatus ) {
    window.uploadInProgress = false;
    submitButton.prop('disabled', false).html(originalButtonHtml);
    $('#uploadContainer').hide();
  });
}

// DLボタンを押すと実行
function dl_button(id){
  // DLkey空白で投げる
  dl_certificat(id ,'');
}

function confirm_dl_button(id){
  closeModal();
  dl_certificat(id ,$('#confirmDlkeyInput').val());
}

function dl_certificat(id, key){
  var postdata = {
    id: id,
    key: key,
    csrf_token: getCSRFToken()
  };

  $.ajax({
    url  : appUrl('app/api/verifydownload.php'),
    type : 'POST',
    data : postdata,
    dataType    : 'json'
  })
  .done(function(data, textStatus, jqXHR){
    if (data.status === 'success') {
      // Ver.2.0の成功レスポンス
      location.href = appUrl('download.php?id=' + encodeURIComponent(data.data.id) + '&key=' + encodeURIComponent(data.data.token));
    } else if (data.status === 'error') {
      // Ver.2.0のエラーレスポンス
      if (data.error_code === 'AUTH_REQUIRED' || data.error_code === 'INVALID_KEY') {
        if (document.getElementById('downloadPage')) {
          showError(data.message || 'ダウンロードキーを入力してください。');
          return;
        }
        // 認証が必要
        var html = '<div class="form-group">' +
                  '<label for="confirmDlkeyInput">DLキーの入力</label>' +
                  '<input type="text" class="form-control" id="confirmDlkeyInput" name="confirmdlkey" placeholder="DLキーを入力...">' +
                  '</div>';
        openModal('okcansel', '認証が必要です', html, 'confirm_dl_button(' + id + ');');
      } else {
        var errorMessage = data.message || 'ダウンロードに失敗しました。';
        if (data.error_code) {
          errorMessage += '<br><small class="text-muted">(エラーコード: ' + data.error_code + ')</small>';
        }
        showError(errorMessage);
      }
    } else {
      // 旧バージョン互換
      var html = '<div class="form-group">' +
                '<label for="confirmDlkeyInput">DLキーの入力</label>' +
                '<input type="text" class="form-control" id="confirmDlkeyInput" name="confirmdlkey" placeholder="DLキーを入力...">' +
                '</div>';
      switch (data.status){
        case 'failed':
          openModal('okcansel', '認証が必要です', html, 'confirm_dl_button(' + id + ');');
          break;
        case 'ok':
          location.href = appUrl('download.php?id=' + encodeURIComponent(data.id) + '&key=' + encodeURIComponent(data.key));
          break;
        default:
          showError('ダウンロードに失敗しました。');
      }
    }
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
  })
  .always(function( jqXHR, textStatus ) {
  });
}

// DELボタンを押すと実行
function del_button(id){
  // DLkey空白で投げる
  del_certificat(id ,'');
}

function confirm_del_button(id){
  closeModal();
  del_certificat(id ,$('#confirmDelkeyInput').val());
}

function del_certificat(id, key){
  var postdata = {
    id: id,
    key: key,
    csrf_token: getCSRFToken()
  };

  $.ajax({
    url  : appUrl('app/api/verifydelete.php'),
    type : 'POST',
    data : postdata,
    dataType    : 'json'
  })
  .done(function(data, textStatus, jqXHR){
    if (data.status === 'success') {
      // Ver.2.0の成功レスポンス
      location.href = appUrl('delete.php?id=' + encodeURIComponent(data.data.id) + '&key=' + encodeURIComponent(data.data.token));
    } else if (data.status === 'error') {
      // Ver.2.0のエラーレスポンス
      if (data.error_code === 'AUTH_REQUIRED' || data.error_code === 'INVALID_KEY') {
        if (document.getElementById('downloadPage')) {
          showError(data.message || '削除キーを入力してください。');
          return;
        }
        // 認証が必要
        var html = '<div class="form-group">' +
                  '<label for="confirmDelkeyInput">DELキーの入力</label>' +
                  '<input type="text" class="form-control" id="confirmDelkeyInput" name="confirmdelkey" placeholder="DELキーを入力...">' +
                  '</div>';
        openModal('okcansel', '認証が必要です', html, 'confirm_del_button(' + id + ');');
      } else {
        var errorMessage = data.message || '削除に失敗しました。';
        if (data.error_code) {
          errorMessage += '<br><small class="text-muted">(エラーコード: ' + data.error_code + ')</small>';
        }
        showError(errorMessage);
      }
    } else {
      // 旧バージョン互換
      var html = '<div class="form-group">' +
                '<label for="confirmDelkeyInput">DELキーの入力</label>' +
                '<input type="text" class="form-control" id="confirmDelkeyInput" name="confirmdelkey" placeholder="DELキーを入力...">' +
                '</div>';
      switch (data.status){
        case 'failed':
          openModal('okcansel', '認証が必要です', html, 'confirm_del_button(' + id + ');');
          break;
        case 'ok':
          location.href = appUrl('delete.php?id=' + encodeURIComponent(data.id) + '&key=' + encodeURIComponent(data.key));
          break;
        default:
          showError('削除に失敗しました。');
      }
    }
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
  })
  .always(function( jqXHR, textStatus ) {
  });
}
