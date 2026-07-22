$(document).ready(function(){
  const fileManager = new FileManager(
    document.getElementById('fileManagerContainer'), {
      itemsPerPage: 12,
      defaultSort: 'date_desc'
    }
  );

  fileManager.setFiles(window.fileData || []);
  window.fileManagerInstance = fileManager;

  var selectedUploadFile = null;

  function updateSelectedFile(file) {
    selectedUploadFile = file || null;
    window.selectedUploadFile = selectedUploadFile;
    $('#fileInput').val(file ? file.name : '');
  }

  function setInputFiles(files) {
    var fileInput = $('#lefile').get(0);
    if (!fileInput || !files || files.length === 0) {
      updateSelectedFile(null);
      return;
    }

    var file = files[0];
    if (window.DataTransfer) {
      try {
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
      } catch (error) {
        // Some browsers expose DataTransfer but keep input.files read-only.
      }
    }

    updateSelectedFile(file);
  }

  $('input[id=lefile]').change(function() {
    var file = this.files && this.files.length > 0 ? this.files[0] : null;
    updateSelectedFile(file);
  });

  var dropZone = $('#uploadDropZone');
  dropZone.on('click', function(event) {
    if ($(event.target).closest('button').length > 0) {
      return;
    }

    $('#lefile').trigger('click');
  });

  dropZone.on('keydown', function(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      $('#lefile').trigger('click');
    }
  });

  dropZone.on('dragenter dragover', function(event) {
    event.preventDefault();
    event.stopPropagation();
    dropZone.addClass('upload-drop-zone--active');
  });

  dropZone.on('dragleave dragend drop', function(event) {
    event.preventDefault();
    event.stopPropagation();
    dropZone.removeClass('upload-drop-zone--active');
  });

  dropZone.on('drop', function(event) {
    var files = event.originalEvent &&
      event.originalEvent.dataTransfer &&
      event.originalEvent.dataTransfer.files;
    setInputFiles(files);
  });

  $(document).on('dragover drop', function(event) {
    var dataTransfer = event.originalEvent && event.originalEvent.dataTransfer;
    if (dataTransfer && dataTransfer.types && Array.prototype.indexOf.call(dataTransfer.types, 'Files') !== -1) {
      event.preventDefault();
    }
  });

  // ステータスメッセージの自動非表示
  if ($('#statusMessage').length > 0) {
    setTimeout(function() {
      $('#statusMessage').fadeOut();
    }, 5000);
  }
});

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
  if (window.fileManagerInstance) {
    window.fileManagerInstance.refresh();
  }
});

function file_upload()
{
  if (window.uploadInProgress) {
    return;
  }

  var fileInput = $('#lefile').get(0);
  var file = fileInput && fileInput.files && fileInput.files.length > 0
    ? fileInput.files[0]
    : window.selectedUploadFile || null;
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
      url  : appUrl('api/upload.php'),
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
      try {
        var normalizedUpload = addUploadedFileToList(uploadData);
        showUploadComplete(normalizedUpload);
      } catch (error) {
        console.error(error);
        showError('アップロード結果の形式が不正です。ページを再読み込みして確認してください。');
        return;
      }
      $('#upload')[0].reset();
      $('#fileInput').val('');
      window.selectedUploadFile = null;
    } else if (data.status === 'error') {
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
      showError('アップロードに失敗しました: ' + (data.message || '不明なエラー'));
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
