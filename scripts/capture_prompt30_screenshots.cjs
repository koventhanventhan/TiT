const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt30');

async function main() {
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  // 1. Setup base seed (faqs, materials, queries)
  execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" setup_base`);

  const token = execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });

  const viewports = [
    { name: '1366', width: 1366, height: 768 },
    { name: '820', width: 820, height: 1180 },
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

      // Intercept noisy polling
      await page.route('**/*', (route) => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
        }
        return route.continue();
      });

      // Login
      await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`);

      // 1. State Blocked: Gemini Missing
      execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" state_blocked_gemini`);
      await page.goto('http://127.0.0.1:8000/admin/assistant');
      await page.waitForTimeout(600);
      await page.screenshot({ path: path.join(OUT_DIR, `status-blocked-gemini-${vp.name}-${preset}.png`), fullPage: true });

      // 2. State Blocked: Contacts Missing
      execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" state_blocked_contacts`);
      await page.goto('http://127.0.0.1:8000/admin/assistant');
      await page.waitForTimeout(600);
      await page.screenshot({ path: path.join(OUT_DIR, `status-blocked-contacts-${vp.name}-${preset}.png`), fullPage: true });

      // 3. State Blocked: Switch Off
      execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" state_blocked_switch_off`);
      await page.goto('http://127.0.0.1:8000/admin/assistant');
      await page.waitForTimeout(600);
      await page.screenshot({ path: path.join(OUT_DIR, `status-blocked-switch-off-${vp.name}-${preset}.png`), fullPage: true });

      // 4. State Live
      execSync(`php "${path.resolve(__dirname, 'prompt30_state_helper.php')}" state_live`);
      await page.goto('http://127.0.0.1:8000/admin/assistant');
      await page.waitForTimeout(600);
      await page.screenshot({ path: path.join(OUT_DIR, `status-live-${vp.name}-${preset}.png`), fullPage: true });

      // 5. Contact validation error
      await page.fill('input[name="email"]', 'not-an-email');
      await page.click('button:has-text("Save Official Contact Details")');
      await page.waitForTimeout(600);
      await page.screenshot({ path: path.join(OUT_DIR, `contacts-validation-error-${vp.name}-${preset}.png`), fullPage: true });

      // 6. FAQ section zoom/focus
      const faqTitle = page.locator('h3:has-text("FAQ Manager")').first();
      if (await faqTitle.isVisible()) {
        await faqTitle.scrollIntoViewIfNeeded();
        await page.waitForTimeout(300);
        await page.screenshot({ path: path.join(OUT_DIR, `faq-list-${vp.name}-${preset}.png`), fullPage: false });
      }

      // 7. Knowledge status section zoom/focus
      const knowledgeTitle = page.locator('h3:has-text("Knowledge Base")').first();
      if (await knowledgeTitle.isVisible()) {
        await knowledgeTitle.scrollIntoViewIfNeeded();
        await page.waitForTimeout(300);
        await page.screenshot({ path: path.join(OUT_DIR, `knowledge-status-${vp.name}-${preset}.png`), fullPage: false });
      }

      await context.close();
      console.log(`Captured all states for ${preset} @ ${vp.name}`);
    }
  }

  await browser.close();
  console.log('Capture completed successfully.');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
