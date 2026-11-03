const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

async function main() {
  const screenshotsDir = path.resolve(__dirname, '../laravel_api/e2e/screenshots');
  if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
  }

  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  // 1. Desktop context for admin branding
  const desktopContext = await browser.newContext({ viewport: { width: 1366, height: 800 } });
  const page = await desktopContext.newPage();

  // Mock polling endpoints
  await page.route('**/*', (route) => {
    const url = route.request().url();
    if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
    }
    return route.continue();
  });

  // Login
  await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
  await page.goto(`http://127.0.0.1:8000/admin/profile/settings`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(500);

  // a. Open Edit Brand Logo modal idle
  await page.evaluate(() => {
    if (typeof window.openBrandingModal === 'function') {
      window.openBrandingModal();
    }
  });
  await page.waitForSelector('#adminBrandingModal', { state: 'visible' });
  await page.waitForTimeout(400);
  await page.screenshot({ path: path.join(screenshotsDir, 'edit-brand-logo-idle.png') });
  console.log('Saved: edit-brand-logo-idle.png');

  // b. Loading state
  await page.evaluate(() => {
    const btn = document.getElementById('saveBrandingBtn');
    if (btn && window.TitButton) {
      window.TitButton.setLoading(btn, 'Saving...');
    }
  });
  await page.waitForTimeout(200);
  await page.screenshot({ path: path.join(screenshotsDir, 'edit-brand-logo-saving.png') });
  console.log('Saved: edit-brand-logo-saving.png');

  // c. Save and success toast
  await page.evaluate(() => {
    $('#adminBrandingModal').modal('hide');
    if (window.TitNotify) {
      window.TitNotify.success('Branding updated successfully!');
    }
  });
  await page.waitForSelector('.tit-toast--success', { state: 'visible' });
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(screenshotsDir, 'branding-success-toast.png') });
  console.log('Saved: branding-success-toast.png');

  // e. Header profile dropdown or select2 under Ocean preset
  await page.evaluate(() => {
    document.documentElement.dataset.themePreset = 'ocean';
  });
  await page.goto(`http://127.0.0.1:8000/admin/profile/settings`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    document.documentElement.dataset.themePreset = 'ocean';
    const toggle = document.querySelector('.header-profile[data-toggle="dropdown"], [data-toggle="dropdown"]');
    if (toggle) toggle.click();
  });
  await page.waitForTimeout(400);
  await page.screenshot({ path: path.join(screenshotsDir, 'dropdown-ocean.png') });
  console.log('Saved: dropdown-ocean.png');

  await desktopContext.close();

  // d. Mobile 375px context for ConfirmDialog
  const mobileContext = await browser.newContext({ viewport: { width: 375, height: 800 } });
  const mobilePage = await mobileContext.newPage();
  await mobilePage.goto('http://localhost:4173/student/dashboard', { waitUntil: 'domcontentloaded' });
  await mobilePage.evaluate(() => {
    if (window.__titConfirm) {
      window.__titConfirm({
        title: 'Confirm Subject Drop',
        message: 'Are you sure you want to drop Mathematics from your course list?',
        confirmText: 'Drop Subject',
        cancelText: 'Keep Subject',
        danger: true,
      });
    }
  });
  await mobilePage.waitForSelector('.tit-confirm-modal', { state: 'visible' });
  await mobilePage.waitForTimeout(300);
  await mobilePage.screenshot({ path: path.join(screenshotsDir, 'confirm-dialog-mobile-375.png') });
  console.log('Saved: confirm-dialog-mobile-375.png');

  await mobileContext.close();
  await browser.close();
  console.log('All 5 screenshots captured successfully.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
