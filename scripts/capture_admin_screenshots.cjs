const fs = require('fs');
const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));
const { execSync } = require('child_process');

const BASE_URL = process.env.APP_URL || 'http://127.0.0.1:8000';
const PRESETS = ['royal', 'ocean', 'emerald', 'midnight'];
const WIDTHS = [
  { width: 375, height: 740 },
  { width: 820, height: 1024 },
  { width: 1366, height: 800 }
];

const PAGES = [
  { key: 'dashboard', path: '/admin/dashboard' },
  { key: 'students', path: '/admin/students' },
  { key: 'settings', path: '/admin/settings' },
  { key: 'appearance', path: '/admin/settings/appearance' }
];

const CONVERSATION_ID = 'b9f4d111-6647-41e9-bb11-91d18d644037';
const ARTIFACT_DIR = path.resolve('C:/Users/HP/.gemini/antigravity/brain', CONVERSATION_ID, 'scratch/screenshots');
const LOCAL_DIR = path.resolve(__dirname, '../scratch/screenshots');

function setPreset(preset) {
  execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" set_preset ${preset}`);
}

function getAdminToken() {
  return execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
}

async function captureScreenshots() {
  console.log('=== CAPTURING 48 ADMIN SCREENSHOTS ===');
  const token = getAdminToken();
  const browser = await chromium.launch({ headless: true });

  for (const preset of PRESETS) {
    console.log(`\nPreset: ${preset}`);
    setPreset(preset);

    const artifactPresetDir = path.join(ARTIFACT_DIR, preset);
    const localPresetDir = path.join(LOCAL_DIR, preset);
    fs.mkdirSync(artifactPresetDir, { recursive: true });
    fs.mkdirSync(localPresetDir, { recursive: true });

    for (const vp of WIDTHS) {
      console.log(`  Viewport: ${vp.width}px`);
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        extraHTTPHeaders: { 'Connection': 'close' }
      });
      const page = await context.newPage();

      // Block external requests and mock polling
      await page.route('**/*', route => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
        }
        if (url.includes('/api/messages')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ messages: [], recipients: {} }) });
        }
        if (url.startsWith(BASE_URL)) return route.continue();
        return route.abort();
      });

      // Login
      await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
      await page.waitForURL('**/admin/dashboard', { timeout: 15000 });

      for (const p of PAGES) {
        process.stdout.write(`    ${p.key} ... `);
        await page.goto(`${BASE_URL}${p.path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
        await page.waitForTimeout(400); // Allow render/charts to settle

        const fileName = `${p.key}-${vp.width}.png`;
        const artifactPath = path.join(artifactPresetDir, fileName);
        const localPath = path.join(localPresetDir, fileName);

        await page.screenshot({ path: artifactPath, fullPage: false });
        fs.copyFileSync(artifactPath, localPath);
        console.log(`saved (${fileName})`);
      }

      await context.close();
    }
  }

  await browser.close();
  console.log('\nAll 48 screenshots successfully captured!');
}

captureScreenshots().catch(err => {
  console.error(err);
  process.exit(1);
});

