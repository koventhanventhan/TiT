const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt29');

async function main() {
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  const viewports = [
    { name: '1366', width: 1366, height: 768 },
    { name: '375', width: 375, height: 800 }
  ];

  const presets = ['royal', 'ocean'];

  for (const preset of presets) {
    try {
      execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" set_preset ${preset}`);
      console.log(`Switched preset to ${preset}`);
    } catch (e) {
      console.warn(`Failed to set preset ${preset}:`, e.message);
    }

    for (const vp of viewports) {
      const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const page = await context.newPage();

      await page.route('**/*', (route) => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
        }
        return route.continue();
      });

      // Login as admin
      await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`);

      // 1. AI & Security page
      await page.goto('http://127.0.0.1:8000/admin/settings/ai-security');
      await page.waitForTimeout(800);
      const aiPath = path.join(OUT_DIR, `ai-security-${vp.name}-${preset}.png`);
      await page.screenshot({ path: aiPath, fullPage: true });
      console.log(`Saved screenshot: ${aiPath}`);

      // 2. Zoom accounts page with Test connection button
      await page.goto('http://127.0.0.1:8000/admin/zoom-accounts');
      await page.waitForTimeout(800);
      const zoomPath = path.join(OUT_DIR, `zoom-accounts-${vp.name}-${preset}.png`);
      await page.screenshot({ path: zoomPath, fullPage: true });
      console.log(`Saved screenshot: ${zoomPath}`);

      await context.close();
    }
  }

  await browser.close();
  console.log('Capture completed successfully.');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});

