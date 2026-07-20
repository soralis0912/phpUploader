function download_page_submit(id) {
  $('#errorContainer').fadeOut();
  dl_certificat(id, $('#downloadKeyInput').val() || '');
}
