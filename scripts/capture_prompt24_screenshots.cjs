const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt24');

async function main() {
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
      await page.goto('http://127.0.0.1:8000/admin/settings/admission-fees');
      await page.waitForTimeout(500);

      // Verify tap targets & dimensions
      const stats = await page.evaluate(() => {
        const switches = Array.from(document.querySelectorAll('.fee-switch')).map(el => {
          const r = el.getBoundingClientRect();
          return { w: r.width, h: r.height };
        });
        const inputs = Array.from(document.querySelectorAll('.fee-control')).map(el => {
          const r = el.getBoundingClientRect();
          return { w: r.width, h: r.height };
        });
        const btn = document.querySelector('.fee-submit-btn');
        const btnRect = btn ? btn.getBoundingClientRect() : null;
        const hasHScroll = document.documentElement.scrollWidth > document.documentElement.clientWidth;

        return {
          switchesCount: switches.length,
          allSwitchesGe44: switches.every(s => s.w >= 44 && s.h >= 44),
          allInputsGe44: inputs.every(i => i.h >= 44),
          btnGe44: btnRect ? (btnRect.height >= 44 && btnRect.width >= 44) : false,
          btnDimensions: btnRect ? { w: Math.round(btnRect.width), h: Math.round(btnRect.height) } : null,
          hasHScroll
        };
      });

      console.log(`[${preset} - ${vp.name}]: switchesGe44=${stats.allSwitchesGe44}, inputsGe44=${stats.allInputsGe44}, btnGe44=${stats.btnGe44} (${JSON.stringify(stats.btnDimensions)}), hasHScroll=${stats.hasHScroll}`);

      const filename = `admission-fees-${vp.name}-${preset}.png`;
      const outPath = path.join(OUT_DIR, filename);
      await page.screenshot({ path: outPath, fullPage: true });
      console.log(`Saved screenshot: ${outPath}`);

      if (preset === 'royal' && vp.name === '1366') {
        // Capture sidebar focus
        const sidebar = await page.$('.deznav');
        if (sidebar) {
          const sidebarPath = path.join(OUT_DIR, 'sidebar-payment-settings-group.png');
          await sidebar.screenshot({ path: sidebarPath });
          console.log(`Saved sidebar screenshot: ${sidebarPath}`);
        }
      }

      await context.close();
    }
  }

  // Restore preset to royal
  execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" set_preset royal`);
  await browser.close();
  console.log('Screenshots complete.');
}

main().catch(console.error);
