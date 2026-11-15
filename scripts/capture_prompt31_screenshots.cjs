const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const MODE = process.argv[2] || 'before';
const OUT_DIR = path.resolve(__dirname, `../docs/screenshots/prompt31/${MODE}`);

const VIEWPORTS = [
  { name: '1366', width: 1366, height: 800 },
  { name: '1024', width: 1024, height: 800 },
  { name: '820', width: 820, height: 1180 },
  { name: '390', width: 390, height: 844 },
  { name: '360', width: 360, height: 800 }
];

const PAGES = [
  { name: 'dashboard', path: '/admin/dashboard' },
  { name: 'settings_ai_security', path: '/admin/settings/ai-security' },
  { name: 'assistant', path: '/admin/assistant' },
  { name: 'students', path: '/admin/students' },
  { name: 'subscription', path: '/admin/subscription' }
];

async function main() {
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  for (const vp of VIEWPORTS) {
    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      deviceScaleFactor: 1
    });
    const page = await context.newPage();

    // Fast-fulfill background polling
    await page.route('**/*', (route) => {
      const url = route.request().url();
      if (url.includes('/broadcasting/auth') || url.includes('/admin/messages/unread-count')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 5 }) });
      }
      return route.continue();
    });

    // Login via token
    await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    for (const p of PAGES) {
      await page.goto(`http://127.0.0.1:8000${p.path}`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(500);

      const filePath = path.join(OUT_DIR, `${p.name}-${vp.name}.png`);
      await page.screenshot({ path: filePath, fullPage: false });
      console.log(`Saved: ${filePath}`);
    }

    await context.close();
  }

  await browser.close();
  console.log(`Successfully captured all ${MODE} screenshots in ${OUT_DIR}`);
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
