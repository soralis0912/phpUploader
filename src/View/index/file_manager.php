<div class="row bg-white radius box-shadow">
  <div class="col-sm-12">
    <div id="fileManagerContainer"></div>

    <div class="file-table-container" style="display: none;">
      <table id="fileList" class="table table-striped" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>ファイル名</th>
            <th>コメント</th>
            <th>サイズ</th>
            <th>日付</th>
            <th>DL数</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="file-cards-container" style="display: none;"></div>
  </div>
  <p class="text-right">@<a
    href="https://github.com/shimosyan/phpUploader"
    target="_blank">shimosyan/phpUploader</a> v<?php echo $escapedVersion; ?> (GitHub)</p>
</div>
