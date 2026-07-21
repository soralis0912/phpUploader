function download_page_submit(id) {
  $('#errorContainer').fadeOut();
  dl_certificat(id, $('#downloadKeyInput').val() || '');
}

function delete_page_submit(id) {
  $('#errorContainer').fadeOut();
  del_certificat(id, '');
}
