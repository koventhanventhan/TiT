const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt25');

async function main() {
  const isBefore = process.argv.includes('--before');
  const prefix = isBefore ? 'index-series-before-' : 'index-series-';

  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  const viewports = [
    { name: '1366', width: 1366, height: 768 },
    { name: '820', width: 820, height: 1180 },
    { name: '375', width: 375, height: 800 }
  ];

  const presets = ['royal', 'ocean'];

  for (const preset of presets) {
    execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" set_preset ${preset}`);
    console.log(`Switched preset to ${preset}`);

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

      await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`);
      await page.goto('http://127.0.0.1:8000/admin/settings/index-series');
      await page.waitForTimeout(600);

      const filePath = path.join(OUT_DIR, `${prefix}${vp.name}-${preset}.png`);
      await page.screenshot({ path: filePath, fullPage: true });
      console.log(`Saved screenshot: ${filePath}`);

      await context.close();
    }
  }

  // Also capture standalone messages/create page screenshot
  if (isBefore) {
    console.log('Capturing messages/create standalone screenshot...');
    const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
    const page = await context.newPage();
    const previewFile = path.resolve(__dirname, 'messages_create_preview.html');
    await page.goto(`file://${previewFile}`);
    await page.waitForTimeout(600);
    const msgPath = path.join(OUT_DIR, 'messages-create-standalone-1366.png');
    await page.screenshot({ path: msgPath, fullPage: true });
    console.log(`Saved messages/create screenshot: ${msgPath}`);
    await context.close();
  }

  await browser.close();
  console.log('Capture completed successfully.');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
