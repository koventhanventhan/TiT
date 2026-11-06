import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import path from 'path';

test.describe('Admin Per-Institute Integrations UI (PayHere, E-mail, WhatsApp)', () => {
  const BASE_URL = 'http://127.0.0.1:8000';
  let token = '';

  test.beforeAll(() => {
    token = execSync(`php "${path.resolve(__dirname, '../../scripts/theme_helper.php')}" get_token`).toString().trim();
  });

  async function loginAdmin(page) {
    await page.route('**/*', (route) => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ count: 0, notifications: [] }),
        });
      }
      if (url.startsWith(BASE_URL)) {
        return route.continue();
      }
      return route.abort();
    });

    await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    });
  }

  test('PayHere form: save shows TitNotify toast, 0 native dialogs, masks secret, confirms Live mode, and cleans up', async ({ page }) => {
    let dialogCount = 0;
    page.on('dialog', async (d) => {
      dialogCount++;
      await d.dismiss();
    });

    await loginAdmin(page);
    await page.setViewportSize({ width: 1366, height: 800 });
    await page.goto(`${BASE_URL}/admin/settings/online-payments`, { waitUntil: 'domcontentloaded' });

    // If an existing config exists from prior run, reveal secret field via Replace
    const replaceBtn = page.getByTestId('replace-merchant-secret-btn');
    if (await replaceBtn.isVisible()) {
      await replaceBtn.click();
    }

    const secretPlain = 'E2E_PAYHERE_SECRET_9012';
    await page.fill('#payhere_merchant_id', 'E2EMERCHANT01');
    await page.fill('#payhere_merchant_secret', secretPlain);

    // Switching to Live mode opens themed TitNotify confirm modal (not native confirm)
    await page.getByTestId('payhere-mode-live').click();
    const confirmModal = page.locator('#tit-confirm-modal');
    await expect(confirmModal).toBeVisible();
    await page.locator('#tit-confirm-cancel').click();
    await expect(confirmModal).not.toBeVisible();

    await page.getByTestId('payhere-save-btn').click();
    await page.waitForLoadState('domcontentloaded');

    // Toast shown, zero native dialogs, masked ••••9012 shown, plaintext absent from HTML
    await expect(page.locator('.tit-toast--success')).toHaveCount(1, { timeout: 10000 });
    expect(dialogCount).toBe(0);

    const maskedWrap = page.getByTestId('payhere-merchant-secret-masked');
    await expect(maskedWrap).toBeVisible();
    await expect(maskedWrap).toContainText('••••9012');

    const html = await page.content();
    expect(html).not.toContain(secretPlain);

    // Clean up via Clear / Disable + TitNotify confirm
    await page.getByTestId('payhere-clear-btn').click();
    await expect(confirmModal).toBeVisible();
    await page.locator('#tit-confirm-ok').click();
    await page.waitForLoadState('domcontentloaded');
    expect(dialogCount).toBe(0);
  });

  test('Messaging & E-mail forms (SMTP + WhatsApp) and 375px mobile layout: save, toast, masked secrets, 0 overflow', async ({ page }) => {
    let dialogCount = 0;
    page.on('dialog', async (d) => {
      dialogCount++;
      await d.dismiss();
    });

    await loginAdmin(page);
    await page.setViewportSize({ width: 1366, height: 800 });
    await page.goto(`${BASE_URL}/admin/settings/messaging`, { waitUntil: 'domcontentloaded' });

    // 1. Save Outgoing E-mail (SMTP)
    const replaceMailBtn = page.getByTestId('replace-mail-password-btn');
    if (await replaceMailBtn.isVisible()) {
      await replaceMailBtn.click();
    }
    const smtpSecret = 'E2E_SMTP_PASS_4455';
    await page.fill('#mail_host', 'smtp.example.org');
    await page.fill('#mail_port', '587');
    await page.selectOption('#mail_encryption', 'tls');
    await page.fill('#mail_username', 'mailer@example.org');
    await page.fill('#mail_password', smtpSecret);
    await page.fill('#mail_from_address', 'noreply@example.org');
    await page.fill('#mail_from_name', 'TiT Academy');
    await page.getByTestId('mail-save-btn').click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.tit-toast--success')).toHaveCount(1, { timeout: 10000 });
    await expect(page.getByTestId('mail-password-masked')).toContainText('••••4455');
    expect(await page.content()).not.toContain(smtpSecret);

    // 2. Save WhatsApp Cloud API
    const replaceWaBtn = page.getByTestId('replace-whatsapp-token-btn');
    if (await replaceWaBtn.isVisible()) {
      await replaceWaBtn.click();
    }
    const waSecret = 'EAAG_E2E_WA_TOKEN_6677';
    await page.fill('#whatsapp_access_token', waSecret);
    await page.fill('#whatsapp_phone_number_id', '123456789012345');
    await page.fill('#whatsapp_api_version', 'v21.0');
    await page.getByTestId('whatsapp-save-btn').click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.tit-toast--success')).toHaveCount(1, { timeout: 10000 });
    await expect(page.getByTestId('whatsapp-token-masked')).toContainText('••••6677');
    expect(await page.content()).not.toContain(waSecret);

    // 3. Check 375px mobile layout on both pages (zero horizontal overflow)
    await page.setViewportSize({ width: 375, height: 800 });
    const messagingOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(messagingOverflow).toBe(false);

    await page.goto(`${BASE_URL}/admin/settings/online-payments`, { waitUntil: 'domcontentloaded' });
    const payhereOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(payhereOverflow).toBe(false);

    // 4. Clean up both saved test rows so local DB remains clean
    await page.setViewportSize({ width: 1366, height: 800 });
    await page.goto(`${BASE_URL}/admin/settings/messaging`, { waitUntil: 'domcontentloaded' });

    await page.getByTestId('mail-clear-btn').click();
    await page.locator('#tit-confirm-ok').click();
    await page.waitForLoadState('domcontentloaded');

    await page.getByTestId('whatsapp-clear-btn').click();
    await page.locator('#tit-confirm-ok').click();
    await page.waitForLoadState('domcontentloaded');

    expect(dialogCount).toBe(0);
  });
});

