import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import path from 'path';

test.describe('Admin Branding Modal & Toast Notifications', () => {
  const BASE_URL = 'http://127.0.0.1:8000';
  let token = '';

  test.beforeAll(() => {
    token = execSync(`php "${path.resolve(__dirname, '../../scripts/theme_helper.php')}" get_token`).toString().trim();
  });

  test('Save branding with tiny PNG displays exactly one tit-toast--success, 0 native dialogs, modal closed, button reset', async ({ page }) => {
    let dialogCount = 0;
    page.on('dialog', async (dialog) => {
      dialogCount++;
      await dialog.dismiss();
    });

    // Mock polling endpoints to avoid noise
    await page.route('**/*', (route) => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
      }
      return route.continue();
    });

    // 1. Log in and navigate to an admin page
    await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.goto(`${BASE_URL}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 30000 });

    // 2. Open branding modal
    await page.evaluate(() => {
      if (typeof window.openBrandingModal === 'function') {
        window.openBrandingModal();
      }
    });

    const modal = page.locator('#adminBrandingModal');
    await expect(modal).toBeVisible({ timeout: 10000 });

    // 3. Set input file with tiny 1x1 transparent PNG
    const tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    const pngBuffer = Buffer.from(tinyPngBase64, 'base64');

    await page.setInputFiles('#modalAdminLogoInput', {
      name: 'test-logo.png',
      mimeType: 'image/png',
      buffer: pngBuffer,
    });

    // 4. Click Save Changes button
    const saveBtn = page.locator('#saveBrandingBtn');
    await expect(saveBtn).toBeVisible();
    await saveBtn.click();

    // 5. Assertions:
    // a) Exactly one .tit-toast--success appears
    const successToast = page.locator('.tit-toast--success');
    await expect(successToast).toHaveCount(1, { timeout: 10000 });
    await expect(successToast).toContainText('Branding updated successfully!');

    // b) No native browser alert/confirm/prompt was triggered
    expect(dialogCount).toBe(0);

    // c) Modal is closed
    await expect(modal).not.toBeVisible();

    // d) Save button resets label and disabled state
    await expect(saveBtn).toHaveText('Save Changes');
    await expect(saveBtn).not.toBeDisabled();
  });
});
