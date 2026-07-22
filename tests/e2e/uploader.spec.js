const path = require('path');
const fs = require('fs');
const { expect, test } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

test.describe('phpUploader UI', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.waitForFunction(() => window.jQuery && window.fileManagerInstance);
  });

  test('renders the upload form and empty file list', async ({ page }) => {
    await expect(page).toHaveTitle(/PHP Uploader/);
    await expect(page.getByText('ファイルを登録')).toBeVisible();
    await expect(page.locator('#fileManagerContainer')).toContainText('ファイル一覧');
    await expect(page.locator('#fileManagerContainer')).toContainText('アップロードされたファイルはありません');
    await expect(page.locator('meta[property="og:title"]')).toHaveAttribute('content', 'PHP Uploader');
    await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', /\/image\/cover\.png$/);
    await expect(page.locator('meta[name="twitter:card"]')).toHaveAttribute('content', 'summary_large_image');
  });

  test('requires the router to pass a show id', async ({ page }) => {
    const response = await page.goto('/show.php');

    expect(response.status()).toBe(404);
    await expect(page.locator('#downloadPage')).toContainText('ファイルが見つかりません');
  });

  test('shows a client-side error when no file is selected', async ({ page }) => {
    await page.getByRole('button', { name: /アップロード/ }).click();

    await expect(page.locator('#errorContainer')).toBeVisible();
    await expect(page.locator('#errorContainer')).toContainText('ファイルを選択してください。');
  });

  test('fits the mobile viewport without horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    await page.waitForFunction(() => window.jQuery && window.fileManagerInstance);

    const metrics = await page.evaluate(() => {
      const viewportWidth = window.innerWidth;
      const selectors = [
        '.container',
        '.row.bg-white',
        '.input-group',
        '#fileManagerContainer',
        '.file-manager'
      ];

      return {
        viewportWidth,
        scrollWidth: document.documentElement.scrollWidth,
        elements: selectors.map((selector) => {
          const element = document.querySelector(selector);
          if (!element) {
            return { selector, right: 0, width: 0 };
          }

          const rect = element.getBoundingClientRect();
          return {
            selector,
            right: Math.ceil(rect.right),
            width: Math.ceil(rect.width)
          };
        })
      };
    });

    expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.viewportWidth);
    for (const element of metrics.elements) {
      expect(element.right, element.selector).toBeLessThanOrEqual(metrics.viewportWidth);
    }
  });

  test('uploads a file and supports searching and list view', async ({ page }) => {
    const fixturePath = path.join(__dirname, 'fixtures', 'sample-upload.pdf');

    await page.locator('#lefile').setInputFiles(fixturePath);
    await expect(page.locator('#fileInput')).toHaveValue('sample-upload.pdf');

    const uploadResponsePromise = page.waitForResponse((response) => (
      response.url().includes('/api/upload.php') &&
      response.request().method() === 'POST'
    ));

    await page.getByRole('button', { name: /アップロード/ }).click();

    const uploadResponse = await uploadResponsePromise;
    expect(uploadResponse.ok()).toBeTruthy();

    const payload = await uploadResponse.json();
    expect(payload.status).toBe('success');
    expect(payload.data.delete_key).toMatch(/^[A-Z0-9]{4}$/);

    await expect(page.locator('.file-card-v2__filename')).toContainText('sample-upload.pdf');
    await expect(page.locator('#successContainer')).toContainText('削除キー');
    await expect(page.locator('.file-card-v2__btn--delete')).toHaveCount(0);

    const fileData = await page.evaluate(() => window.fileData);
    expect(fileData).toHaveLength(1);
    expect(fileData[0]).toEqual(expect.objectContaining({
      id: expect.any(Number),
      origin_file_name: 'sample-upload.pdf',
      comment: '',
      size: expect.any(Number),
      count: expect.any(Number),
      input_date: expect.any(Number)
    }));
    expect(fileData[0]).not.toHaveProperty('ip_address');
    expect(fileData[0]).not.toHaveProperty('dl_key_hash');
    expect(fileData[0]).not.toHaveProperty('del_key_hash');
    expect(fileData[0]).not.toHaveProperty('stored_file_name');
    expect(fileData[0]).not.toHaveProperty('file_hash');

    await page.locator('#fileSearchInput').fill('sample');
    await expect(page.locator('.file-card-v2__filename')).toContainText('sample-upload.pdf');

    await page.locator('.file-view-toggle__btn[data-view="list"]').click();
    await expect(page.locator('.file-list-item__filename')).toContainText('sample-upload.pdf');

    await page.locator('#fileSearchInput').fill('missing-file');
    await expect(page.locator('#fileManagerContainer')).toContainText('検索結果が見つかりません');

    await page.goto(`/show/${payload.data.file_id}`);
    await expect(page.locator('#downloadPage')).toContainText('ファイル詳細');
    await expect(page.locator('#downloadPage')).toContainText('sample-upload.pdf');
    await expect(page.locator('#downloadPage')).not.toContainText('ID:');
    await expect(page.locator('#downloadPage')).not.toContainText('このファイルのページ');
    await expect(page.locator('#downloadPage')).not.toContainText('ダウンロードキー');
    await expect(page.locator('#downloadPage')).not.toContainText('削除キー');
    await expect(page.locator('#downloadPage a[href*="download.php"]')).toHaveCount(0);
    await page.getByRole('button', { name: /削除/ }).click();
    await expect(page.locator('#OKCanselModal')).toBeVisible();
    await expect(page.locator('#OKCanselModal')).toContainText('DELキーの入力');
    await expect(page.locator('#confirmDelkeyInput')).toBeVisible();
    await expect(page.locator('meta[property="og:title"]')).toHaveAttribute(
      'content',
      'sample-upload.pdf | PHP Uploader'
    );
    await expect(page.locator('meta[property="og:description"]')).toHaveAttribute(
      'content',
      /PHP Uploaderで共有されたファイルです。 サイズ:/
    );
    await expect(page.locator('meta[property="og:url"]')).toHaveAttribute('content', /\/show\/\d+$/);
  });

  test('uploads a file in chunks', async ({ page }) => {
    const fixturePath = test.info().outputPath('chunked-upload.pdf');
    fs.writeFileSync(fixturePath, Buffer.alloc(2 * 1024 * 1024 + 123, '%PDF-1.7\n'));

    const uploadResponses = [];
    page.on('response', (response) => {
      if (
        response.url().includes('/api/upload.php') &&
        response.request().method() === 'POST'
      ) {
        uploadResponses.push(response);
      }
    });

    await page.locator('#lefile').setInputFiles(fixturePath);
    await expect(page.locator('#fileInput')).toHaveValue('chunked-upload.pdf');

    await page.getByRole('button', { name: /アップロード/ }).click();

    await expect(page.getByRole('link', { name: /chunked-upload\.pdf/ })).toBeVisible();
    await expect.poll(() => uploadResponses.length).toBeGreaterThan(1);

    const fileData = await page.evaluate(() => window.fileData);
    expect(fileData).toContainEqual(expect.objectContaining({
      origin_file_name: 'chunked-upload.pdf',
      size: 2 * 1024 * 1024 + 123
    }));
  });

  test('uploads a dropped file', async ({ page }) => {
    const fixturePath = path.join(__dirname, 'fixtures', 'sample-upload.pdf');
    const dataTransfer = await page.evaluateHandle(({ fileName, bytes }) => {
      const transfer = new DataTransfer();
      const file = new File([new Uint8Array(bytes)], fileName, { type: 'application/pdf' });
      transfer.items.add(file);
      return transfer;
    }, {
      fileName: 'dropped-upload.pdf',
      bytes: Array.from(fs.readFileSync(fixturePath))
    });

    await page.locator('#uploadDropZone').dispatchEvent('dragenter', { dataTransfer });
    await expect(page.locator('#uploadDropZone')).toHaveClass(/upload-drop-zone--active/);
    await page.locator('#uploadDropZone').dispatchEvent('drop', { dataTransfer });
    await expect(page.locator('#fileInput')).toHaveValue('dropped-upload.pdf');

    const uploadResponsePromise = page.waitForResponse((response) => (
      response.url().includes('/api/upload.php') &&
      response.request().method() === 'POST'
    ));

    await page.getByRole('button', { name: /アップロード/ }).click();

    const uploadResponse = await uploadResponsePromise;
    expect(uploadResponse.ok()).toBeTruthy();

    const payload = await uploadResponse.json();
    expect(payload.status).toBe('success');
    await expect(page.getByRole('link', { name: /dropped-upload\.pdf/ })).toBeVisible();
  });
});
