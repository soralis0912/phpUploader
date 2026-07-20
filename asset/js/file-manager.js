/**
 * ファイル管理システム Ver.2.0
 * DataTables完全廃止版 - 検索・ソート・ページネーション機能付き
 */

class FileManager {
  constructor(container, options = {}) {
    this.container = container;
    this.files = [];
    this.filteredFiles = [];
    this.currentPage = 1;
    this.itemsPerPage = options.itemsPerPage || 12;
    this.searchQuery = '';
    this.sortBy = options.defaultSort || 'date_desc';

    // ビューモードの初期化（localStorage から復元）
    this.viewMode = this.loadViewMode() || options.defaultView || 'grid';

    this.init();
  }

  // ユーザーのビューモード設定を読み込み
  loadViewMode() {
    try {
      return localStorage.getItem('fileManager_viewMode');
    } catch (e) {
      return null;
    }
  }

  init() {
    this.render();
    this.bindEvents();
  }

  setFiles(files) {
    this.files = files;
    this.applyFilters();
    this.render();
  }

  applyFilters() {
    let filtered = [...this.files];

    // 検索フィルター
    if (this.searchQuery) {
      const query = this.searchQuery.toLowerCase();
      filtered = filtered.filter(file =>
        file.origin_file_name.toLowerCase().includes(query) ||
        file.comment.toLowerCase().includes(query) ||
        this.getFileExtension(file.origin_file_name).toLowerCase().includes(query)
      );
    }

    // ソート適用
    filtered.sort((a, b) => {
      switch (this.sortBy) {
        case 'name_asc':
          return a.origin_file_name.localeCompare(b.origin_file_name);
        case 'name_desc':
          return b.origin_file_name.localeCompare(a.origin_file_name);
        case 'size_asc':
          return a.size - b.size;
        case 'size_desc':
          return b.size - a.size;
        case 'downloads_asc':
          return a.count - b.count;
        case 'downloads_desc':
          return b.count - a.count;
        case 'date_asc':
          return a.input_date - b.input_date;
        case 'date_desc':
        default:
          return b.input_date - a.input_date;
      }
    });

    this.filteredFiles = filtered;
    this.currentPage = 1; // 検索・ソート時は1ページ目に戻る
  }

  render() {
    // フォーカス状態を保存
    const activeElement = document.activeElement;
    const wasSearchFocused = activeElement && activeElement.id === 'fileSearchInput';
    const searchValue = wasSearchFocused ? activeElement.value : this.searchQuery;
    const cursorPosition = wasSearchFocused ? activeElement.selectionStart : 0;

    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    const pageFiles = this.filteredFiles.slice(startIndex, endIndex);

    this.container.innerHTML = `
      <div class="file-manager">
        ${this.renderHeader()}
        ${this.renderControls()}
        ${this.renderContent(pageFiles)}
        ${this.renderPagination()}
      </div>
    `;

    // フォーカス状態を復元
    if (wasSearchFocused) {
      const searchInput = document.getElementById('fileSearchInput');
      if (searchInput) {
        searchInput.focus();
        searchInput.setSelectionRange(cursorPosition, cursorPosition);
      }
    }
  }

  renderHeader() {
    const totalFiles = this.files.length;
    const filteredCount = this.filteredFiles.length;

    return `
      <div class="file-manager__header">
        <h2 class="file-manager__title">
          📁 ファイル一覧
        </h2>
        <div class="file-manager__stats">
          ${filteredCount !== totalFiles ?
            `${filteredCount}件 (全${totalFiles}件中)` :
            `${totalFiles}件`
          }
        </div>
      </div>
    `;
  }

  renderControls() {
    return `
      <div class="file-controls">
        <div class="file-search">
          <div class="file-search__input">
            <input 
              type="text" 
              placeholder="🔍 ファイル名・コメントで検索..." 
              value="${this.searchQuery}"
              id="fileSearchInput"
            >
          </div>
          <div class="file-search__sort">
            <label for="fileSortSelect">並び順:</label>
            <select id="fileSortSelect">
              <option value="date_desc" ${this.sortBy === 'date_desc' ? 'selected' : ''}>新しい順</option>
              <option value="date_asc" ${this.sortBy === 'date_asc' ? 'selected' : ''}>古い順</option>
              <option value="name_asc" ${this.sortBy === 'name_asc' ? 'selected' : ''}>名前 A-Z</option>
              <option value="name_desc" ${this.sortBy === 'name_desc' ? 'selected' : ''}>名前 Z-A</option>
              <option value="size_desc" ${this.sortBy === 'size_desc' ? 'selected' : ''}>サイズ大順</option>
              <option value="size_asc" ${this.sortBy === 'size_asc' ? 'selected' : ''}>サイズ小順</option>
              <option value="downloads_desc" ${this.sortBy === 'downloads_desc' ? 'selected' : ''}>DL数多順</option>
              <option value="downloads_asc" ${this.sortBy === 'downloads_asc' ? 'selected' : ''}>DL数少順</option>
            </select>
          </div>
          ${this.searchQuery ? `
            <button class="file-search__clear" id="fileSearchClear">
              クリア
            </button>
          ` : ''}
        </div>

        <div class="file-view-toggle">
          <button 
            class="file-view-toggle__btn ${this.viewMode === 'grid' ? 'file-view-toggle__btn--active' : ''}" 
            data-view="grid"
            title="グリッドビュー"
          >
            ⊞ グリッド
          </button>
          <button 
            class="file-view-toggle__btn ${this.viewMode === 'list' ? 'file-view-toggle__btn--active' : ''}" 
            data-view="list"
            title="リストビュー"
          >
            ☰ リスト
          </button>
        </div>
      </div>
    `;
  }

  renderSearch() {
    // 旧バージョン互換用（使用されない）
    return this.renderControls();
  }

  renderContent(files) {
    if (files.length === 0) {
      if (this.filteredFiles.length === 0 && this.files.length === 0) {
        return `
          <div class="file-empty">
            <div class="file-empty__icon">📄</div>
            <h3 class="file-empty__title">アップロードされたファイルはありません</h3>
            <p class="file-empty__message">上のフォームからファイルをアップロードしてください。</p>
          </div>
        `;
      } else {
        return `
          <div class="file-no-results">
            <div class="file-empty__icon">🔍</div>
            <h3 class="file-empty__title">検索結果が見つかりません</h3>
            <p class="file-empty__message">検索条件を変更してお試しください。</p>
          </div>
        `;
      }
    }

    if (this.viewMode === 'list') {
      return `
        <div class="file-list">
          ${files.map(file => this.renderFileListItem(file)).join('')}
        </div>
      `;
    } else {
      return `
        <div class="file-cards">
          ${files.map(file => this.renderFileCard(file)).join('')}
        </div>
      `;
    }
  }

  renderFileListItem(file) {
    const fileSize = (file.size / (1024 * 1024)).toFixed(1);
    const uploadDate = new Date(file.input_date * 1000);
    const formattedDate = uploadDate.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'numeric',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    const fileExt = this.getFileExtension(file.origin_file_name);
    const fileIcon = this.getFileIcon(fileExt);
    const detailPageUrl = this.getDownloadPageUrl(file.id);

    return `
      <div class="file-list-item" data-file-id="${file.id}">
        <div class="file-list-item__icon">
          ${fileIcon}
        </div>
        <div class="file-list-item__main">
          <div class="file-list-item__info">
            <a 
              href="${detailPageUrl}" 
              class="file-list-item__filename"
              title="${this.escapeHtml(file.origin_file_name)}"
            >
              ${this.escapeHtml(file.origin_file_name)}
            </a>
            ${file.comment ? `
              <p class="file-list-item__comment" title="${this.escapeHtml(file.comment)}">
                ${this.escapeHtml(file.comment)}
              </p>
            ` : ''}
          </div>
          <div class="file-list-item__meta">
            <span class="file-list-item__meta-item">
              <span class="file-list-item__meta-label">ID:</span>
              <span class="file-list-item__meta-value">#${file.id}</span>
            </span>
            <span class="file-list-item__meta-item">
              <span class="file-list-item__meta-label">サイズ:</span>
              <span class="file-list-item__meta-value">${fileSize}MB</span>
            </span>
            <span class="file-list-item__meta-item">
              <span class="file-list-item__meta-label">日付:</span>
              <span class="file-list-item__meta-value">${formattedDate}</span>
            </span>
            <span class="file-list-item__meta-item">
              <span class="file-list-item__meta-label">DL:</span>
              <span class="file-list-item__meta-value">${file.count}回</span>
            </span>
          </div>
        </div>
        <div class="file-list-item__actions">
          <a 
            href="${detailPageUrl}" 
            class="file-list-item__btn"
            title="詳細"
          >
            🔎
          </a>
        </div>
      </div>
    `;
  }
  
  renderFileCard(file) {
    const fileSize = (file.size / (1024 * 1024)).toFixed(1);
    const uploadDate = new Date(file.input_date * 1000);
    const formattedDate = uploadDate.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'numeric',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    const fileExt = this.getFileExtension(file.origin_file_name);
    const fileIcon = this.getFileIcon(fileExt);
    const detailPageUrl = this.getDownloadPageUrl(file.id);
    
    return `
      <div class="file-card-v2" data-file-id="${file.id}">
        <div class="file-card-v2__header">
          <a 
            href="${detailPageUrl}" 
            class="file-card-v2__filename"
            title="${this.escapeHtml(file.origin_file_name)}"
          >
            ${fileIcon} ${this.escapeHtml(file.origin_file_name)}
          </a>
          ${file.comment ? `
            <p class="file-card-v2__comment" title="${this.escapeHtml(file.comment)}">
              ${this.escapeHtml(file.comment)}
            </p>
          ` : ''}
        </div>
        
        <div class="file-card-v2__body">
          <div class="file-card-v2__meta">
            <div class="file-card-v2__meta-item">
              <span class="file-card-v2__meta-icon">🆔</span>
              <span class="file-card-v2__meta-label">ID</span>
              <span class="file-card-v2__meta-value">#${file.id}</span>
            </div>
            <div class="file-card-v2__meta-item">
              <span class="file-card-v2__meta-icon">💾</span>
              <span class="file-card-v2__meta-label">サイズ</span>
              <span class="file-card-v2__meta-value">${fileSize}MB</span>
            </div>
            <div class="file-card-v2__meta-item">
              <span class="file-card-v2__meta-icon">📅</span>
              <span class="file-card-v2__meta-label">日付</span>
              <span class="file-card-v2__meta-value">${formattedDate}</span>
            </div>
            <div class="file-card-v2__meta-item">
              <span class="file-card-v2__meta-icon">⬇️</span>
              <span class="file-card-v2__meta-label">DL数</span>
              <span class="file-card-v2__meta-value">${file.count}</span>
            </div>
          </div>
          
          <div class="file-card-v2__actions">
            <a 
              href="${detailPageUrl}" 
              class="file-card-v2__btn"
            >
              🔎 詳細
            </a>
          </div>
        </div>
      </div>
    `;
  }
  
  renderPagination() {
    const totalPages = Math.ceil(this.filteredFiles.length / this.itemsPerPage);
    
    if (totalPages <= 1) {
      return '';
    }
    
    const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
    const endItem = Math.min(this.currentPage * this.itemsPerPage, this.filteredFiles.length);
    
    let paginationHTML = `
      <div class="file-pagination">
        <div class="file-pagination__info">
          ${startItem}-${endItem}件 (全${this.filteredFiles.length}件)
        </div>
        
        <div class="file-pagination__controls">
          <div class="file-pagination__per-page">
            <label for="itemsPerPageSelect">表示件数:</label>
            <select id="itemsPerPageSelect">
              <option value="6" ${this.itemsPerPage === 6 ? 'selected' : ''}>6件</option>
              <option value="12" ${this.itemsPerPage === 12 ? 'selected' : ''}>12件</option>
              <option value="24" ${this.itemsPerPage === 24 ? 'selected' : ''}>24件</option>
              <option value="48" ${this.itemsPerPage === 48 ? 'selected' : ''}>48件</option>
            </select>
          </div>
          
          <div class="file-pagination__nav">
    `;
    
    // 前へボタン
    paginationHTML += `
      <button 
        class="file-pagination__btn" 
        data-page="${this.currentPage - 1}"
        ${this.currentPage === 1 ? 'disabled' : ''}
      >
        ←
      </button>
    `;
    
    // ページ番号ボタン
    const maxVisiblePages = 5;
    let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
      paginationHTML += `<button class="file-pagination__btn" data-page="1">1</button>`;
      if (startPage > 2) {
        paginationHTML += `<span class="file-pagination__ellipsis">...</span>`;
      }
    }
    
    for (let i = startPage; i <= endPage; i++) {
      paginationHTML += `
        <button 
          class="file-pagination__btn ${i === this.currentPage ? 'file-pagination__btn--active' : ''}" 
          data-page="${i}"
        >
          ${i}
        </button>
      `;
    }
    
    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHTML += `<span class="file-pagination__ellipsis">...</span>`;
      }
      paginationHTML += `<button class="file-pagination__btn" data-page="${totalPages}">${totalPages}</button>`;
    }
    
    // 次へボタン
    paginationHTML += `
      <button 
        class="file-pagination__btn" 
        data-page="${this.currentPage + 1}"
        ${this.currentPage === totalPages ? 'disabled' : ''}
      >
        →
      </button>
    `;
    
    paginationHTML += `
          </div>
        </div>
      </div>
    `;
    
    return paginationHTML;
  }
  
  bindEvents() {
    // 検索イベント（デバウンス付き）
    let searchTimeout;
    this.container.addEventListener('input', (e) => {
      if (e.target.id === 'fileSearchInput') {
        clearTimeout(searchTimeout);
        this.searchQuery = e.target.value;
        
        // デバウンス処理（300ms）でパフォーマンス向上
        searchTimeout = setTimeout(() => {
          this.applyFilters();
          this.render();
        }, 300);
      }
    });
    
    // ソート・表示件数変更イベント
    this.container.addEventListener('change', (e) => {
      if (e.target.id === 'fileSortSelect') {
        this.sortBy = e.target.value;
        this.applyFilters();
        this.render();
      } else if (e.target.id === 'itemsPerPageSelect') {
        this.itemsPerPage = parseInt(e.target.value);
        this.currentPage = 1;
        this.render();
      }
    });
    
    // クリック イベント
    this.container.addEventListener('click', (e) => {
      // 検索クリアボタン
      if (e.target.id === 'fileSearchClear') {
        this.searchQuery = '';
        this.applyFilters();
        this.render();
      } 
      // ビュー切り替えボタン
      else if (e.target.classList.contains('file-view-toggle__btn')) {
        const newView = e.target.dataset.view;
        if (newView && newView !== this.viewMode) {
          this.viewMode = newView;
          this.render();
          
          // ビュー切り替えを localStorage に保存
          try {
            localStorage.setItem('fileManager_viewMode', this.viewMode);
          } catch (e) {
            // localStorage が使用できない場合は無視
          }
        }
      }
      // ページネーションボタン
      else if (e.target.classList.contains('file-pagination__btn') && !e.target.disabled) {
        const page = parseInt(e.target.dataset.page);
        if (page && page !== this.currentPage) {
          this.currentPage = page;
          this.render();
          // ページ変更時にトップへスクロール
          this.container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  }
  
  // ユーティリティメソッド
  getFileExtension(filename) {
    return filename.split('.').pop() || '';
  }

  getDownloadPageUrl(id) {
    if (typeof window.buildDownloadPageUrl === 'function') {
      return window.buildDownloadPageUrl(id);
    }

    return `./show/${encodeURIComponent(id)}`;
  }
  
  getFileIcon(extension) {
    const iconMap = {
      // 画像
      'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️', 'bmp': '🖼️', 'svg': '🖼️', 'webp': '🖼️',
      // 動画
      'mp4': '🎬', 'avi': '🎬', 'mov': '🎬', 'wmv': '🎬', 'flv': '🎬', 'webm': '🎬', 'mkv': '🎬',
      // 音声
      'mp3': '🎵', 'wav': '🎵', 'aac': '🎵', 'flac': '🎵', 'ogg': '🎵', 'm4a': '🎵',
      // ドキュメント
      'pdf': '📕', 'doc': '📄', 'docx': '📄', 'txt': '📝', 'rtf': '📄',
      'xls': '📊', 'xlsx': '📊', 'csv': '📊',
      'ppt': '📊', 'pptx': '📊',
      // アーカイブ
      'zip': '🗜️', 'rar': '🗜️', '7z': '🗜️', 'tar': '🗜️', 'gz': '🗜️',
      // コード
      'html': '🌐', 'css': '🎨', 'js': '⚡', 'php': '🐘', 'py': '🐍', 'java': '☕', 'cpp': '🔧', 'c': '🔧',
      // その他
      'exe': '⚙️', 'msi': '⚙️', 'dmg': '💽', 'iso': '💽'
    };
    
    return iconMap[extension.toLowerCase()] || '📄';
  }
  
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // 外部から呼び出し可能なメソッド
  refresh() {
    this.render();
  }
  
  search(query) {
    this.searchQuery = query;
    this.applyFilters();
    this.render();
  }
  
  sort(sortBy) {
    this.sortBy = sortBy;
    this.applyFilters();
    this.render();
  }
  
  goToPage(page) {
    this.currentPage = page;
    this.render();
  }
}

// グローバルに公開
window.FileManager = FileManager;
