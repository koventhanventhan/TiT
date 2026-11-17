const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt31c/after');

const VIEWPORTS = [
  { name: '1366', width: 1366, height: 800 },
  { name: '1024', width: 1024, height: 800 },
  { name: '820', width: 820, height: 1180 },
  { name: '768', width: 768, height: 1024 },
  { name: '390', width: 390, height: 844 },
  { name: '360', width: 360, height: 800 }
];

const PAGES = [
  { name: 'dashboard', path: '/admin/dashboard' },
  { name: 'students', path: '/admin/students' },
  { name: 'settings_ai_security', path: '/admin/settings/ai-security' },
  { name: 'assistant', path: '/admin/assistant' }
];

async function setupPage(page) {
  await page.route('**/*', (route) => {
    const url = route.request().url();
    if (url.includes('/broadcasting/auth') || url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 120, notifications: [] }) });
    }
    return route.continue();
  });
}

async function main() {
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  // Ensure state has valid avatar and 120 notifications
  execSync(`php "${path.resolve(__dirname, 'prompt31c_state_helper.php')}" set_avatar_valid`);
  execSync(`php "${path.resolve(__dirname, 'prompt31c_state_helper.php')}" set_notifications_99plus`);

  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  console.log('--- 1. CAPTURING STANDARD AFTER SCREENSHOTS ---');
  for (const vp of VIEWPORTS) {
    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      deviceScaleFactor: 1,
      extraHTTPHeaders: { 'Connection': 'close' }
    });
    const page = await context.newPage();
    await setupPage(page);

    await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    for (const p of PAGES) {
      await page.goto(`http://127.0.0.1:8000${p.path}`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(600);

      const filePath = path.join(OUT_DIR, `${p.name}-${vp.name}.png`);
      await page.screenshot({ path: filePath, fullPage: false });
      console.log(`Saved: ${filePath}`);

      // Pixel-level sanity check: capture .header element screenshot on dashboard
      if (p.name === 'dashboard') {
        const headerEl = await page.$('.header');
        if (headerEl) {
          const headerShot = path.join(OUT_DIR, `header_only-${vp.name}.png`);
          await headerEl.screenshot({ path: headerShot });
          console.log(`Saved header clip: ${headerShot}`);
        }
      }
    }

    await context.close();
  }

  console.log('\n--- 2. CAPTURING 120 NOTIFICATIONS (99+) CLOSE-UPS ---');
  {
    const context = await browser.newContext({
      viewport: { width: 1366, height: 800 },
      deviceScaleFactor: 1,
      extraHTTPHeaders: { 'Connection': 'close' }
    });
    const page = await context.newPage();
    await setupPage(page);
    await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
    await page.goto(`http://127.0.0.1:8000/admin/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    for (const vp of VIEWPORTS) {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.waitForTimeout(400);

      await page.evaluate(() => {
        const badge = document.getElementById('notification-pulse');
        const count = document.getElementById('notification-count');
        if (badge && count) {
          badge.classList.remove('d-none');
          badge.classList.add('has-count');
          count.classList.remove('d-none');
          count.textContent = '99+';
        }
      });

      const notifBtn = await page.$('.adm-header-dropdown:has(#notification-pulse)');
      if (notifBtn) {
        const filePath = path.join(OUT_DIR, `notification_99plus_btn-${vp.name}.png`);
        await notifBtn.screenshot({ path: filePath });
        console.log(`Saved 99+ button clip: ${filePath}`);
      }

      const headerEl = await page.$('.header');
      if (headerEl) {
        const filePath = path.join(OUT_DIR, `dashboard-notifications-99plus-${vp.name}.png`);
        await page.screenshot({ path: filePath });
        console.log(`Saved 99+ full header shot: ${filePath}`);
      }
    }
    await context.close();
  }

  console.log('\n--- 3. CAPTURING BROKEN AVATAR URL SET ---');
  execSync(`php "${path.resolve(__dirname, 'prompt31c_state_helper.php')}" set_avatar_broken`);
  {
    const context = await browser.newContext({
      viewport: { width: 1366, height: 800 },
      deviceScaleFactor: 1,
      extraHTTPHeaders: { 'Connection': 'close' }
    });
    const page = await context.newPage();
    await setupPage(page);
    await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
    await page.goto(`http://127.0.0.1:8000/admin/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);

    for (const vp of VIEWPORTS) {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.waitForTimeout(400);

      const avatarBtn = await page.$('.adm-header-profile-btn');
      if (avatarBtn) {
        const filePath = path.join(OUT_DIR, `avatar_broken_btn-${vp.name}.png`);
        await avatarBtn.screenshot({ path: filePath });
        console.log(`Saved broken avatar button clip: ${filePath}`);
      }

      const filePath = path.join(OUT_DIR, `dashboard-broken-avatar-${vp.name}.png`);
      await page.screenshot({ path: filePath });
      console.log(`Saved broken avatar shot: ${filePath}`);
    }
    await context.close();
  }

  // Restore valid avatar
  execSync(`php "${path.resolve(__dirname, 'prompt31c_state_helper.php')}" set_avatar_valid`);
  console.log('Restored valid avatar.');

  await browser.close();
  console.log('All screenshots captured successfully!');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
