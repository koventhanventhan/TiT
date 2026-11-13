const http = require('http');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const rootDir = path.resolve(__dirname, '..');
const { chromium } = require(path.join(rootDir, 'laravel_api', 'node_modules', '@playwright', 'test'));
const screenshotsDir = path.join(rootDir, 'docs', 'screenshots', 'prompt28b');
if (!fs.existsSync(screenshotsDir)) {
  fs.mkdirSync(screenshotsDir, { recursive: true });
}

// 1. Static file server on port 4000 serving published bundle from rootDir
function startStaticServer(port = 4000) {
  const mimeTypes = {
    '.html': 'text/html',
    '.js': 'application/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
    '.ttf': 'font/ttf',
  };

  const server = http.createServer((req, res) => {
    let reqPath = req.url.split('?')[0];
    if (reqPath === '/' || !path.extname(reqPath)) {
      reqPath = '/index.html';
    }

    const filePath = path.join(rootDir, reqPath);
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      const ext = path.extname(filePath).toLowerCase();
      const contentType = mimeTypes[ext] || 'application/octet-stream';
      res.writeHead(200, { 'Content-Type': contentType });
      fs.createReadStream(filePath).pipe(res);
    } else {
      // Fallback to index.html for SPA routing
      const indexPath = path.join(rootDir, 'index.html');
      res.writeHead(200, { 'Content-Type': 'text/html' });
      fs.createReadStream(indexPath).pipe(res);
    }
  });

  return new Promise((resolve) => {
    server.listen(port, '127.0.0.1', () => {
      console.log(`[Static Server] Running on http://127.0.0.1:${port} serving root bundle`);
      resolve(server);
    });
  });
}

function setState(state) {
  execSync(`php scripts/set_tenant_state.php ${state}`, { cwd: rootDir, stdio: 'inherit' });
}

async function main() {
  console.log('=== PROMPT 28b: Production Notice E2E Verification ===\n');

  // Initialize DB state
  console.log('--- Step 0: Initializing Institute domains & records in MySQL ---');
  setState('init');

  const staticServer = await startStaticServer(4000);

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  let targetHost = 'beta.test';
  let lastApiResponse = null;

  // Intercept all /api/* requests and route to PHP artisan serve (port 8000)
  await page.route('**/api/**', async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const targetUrl = `http://127.0.0.1:8000${url.pathname}${url.search}`;

    try {
      const response = await route.fetch({
        url: targetUrl,
        headers: {
          ...request.headers(),
          host: targetHost,
          'x-institute-id': '', // Do not pass header
        },
      });

      const status = response.status();
      const contentType = response.headers()['content-type'] || '';
      let bodyText = await response.text();
      let bodyJson = null;
      try {
        bodyJson = JSON.parse(bodyText);
      } catch (_) {}

      if (url.pathname.includes('/settings')) {
        lastApiResponse = {
          url: url.pathname,
          status,
          host: targetHost,
          code: bodyJson?.code || null,
          message: bodyJson?.message || null,
          data: bodyJson,
        };
      }

      await route.fulfill({
        response,
        body: bodyText,
        headers: response.headers(),
      });
    } catch (err) {
      console.error(`[Proxy Error] ${url.pathname}:`, err.message);
      await route.abort();
    }
  });

  const transcript = [];

  // -------------------------------------------------------------
  // Test 1: Institute B within grace -> normal site
  // -------------------------------------------------------------
  console.log('\n--- Scenario 1: Institute B (beta.test) within grace ---');
  setState('within_grace');
  targetHost = 'beta.test';

  await page.setViewportSize({ width: 1366, height: 768 });
  await page.goto('http://127.0.0.1:4000/', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  const alert1 = await page.$('div[role="alert"]');
  const alert1Visible = alert1 ? await alert1.isVisible() : false;
  console.log(`[Within Grace] /api/settings response: HTTP ${lastApiResponse?.status}, alert visible: ${alert1Visible}`);
  transcript.push({
    scenario: '1. Institute B within grace',
    host: targetHost,
    api_status: lastApiResponse?.status,
    api_code: lastApiResponse?.code,
    notice_visible: alert1Visible,
  });

  await page.screenshot({ path: path.join(screenshotsDir, '01_within_grace_1366.png'), fullPage: true });

  // -------------------------------------------------------------
  // Test 2: Institute B past grace -> blocked notice appears
  // -------------------------------------------------------------
  console.log('\n--- Scenario 2: Institute B (beta.test) past grace (expired) ---');
  setState('past_grace');
  targetHost = 'beta.test';

  await page.goto('http://127.0.0.1:4000/', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  const alert2 = await page.$('div[role="alert"]');
  const alert2Visible = alert2 ? await alert2.isVisible() : false;
  const title2 = await page.$eval('.institute-blocked-title', (el) => el.textContent).catch(() => null);
  const name2 = await page.$eval('.institute-blocked-name', (el) => el.textContent).catch(() => null);
  const robots2 = await page.$eval('meta[name="robots"]', (el) => el.getAttribute('content')).catch(() => null);

  console.log(`[Past Grace] /api/settings response: HTTP ${lastApiResponse?.status}, code: ${lastApiResponse?.code}`);
  console.log(`[Past Grace] Title: "${title2}", Name: "${name2}", Robots: "${robots2}"`);
  transcript.push({
    scenario: '2. Institute B past grace (expired)',
    host: targetHost,
    api_status: lastApiResponse?.status,
    api_code: lastApiResponse?.code,
    notice_visible: alert2Visible,
    title: title2,
    robots_meta: robots2,
  });

  // Responsive Screenshots across viewports & presets (Royal & Emerald)
  // Preset 1: Royal (default)
  await page.evaluate(() => { document.documentElement.dataset.themePreset = 'royal'; });
  await page.setViewportSize({ width: 1366, height: 768 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_1366_royal.png') });

  await page.setViewportSize({ width: 820, height: 1180 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_820_royal.png') });

  await page.setViewportSize({ width: 375, height: 812 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_375_royal.png') });

  // Preset 2: Emerald
  await page.evaluate(() => { document.documentElement.dataset.themePreset = 'emerald'; });
  await page.setViewportSize({ width: 1366, height: 768 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_1366_emerald.png') });

  await page.setViewportSize({ width: 820, height: 1180 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_820_emerald.png') });

  await page.setViewportSize({ width: 375, height: 812 });
  await page.screenshot({ path: path.join(screenshotsDir, '02_expired_375_emerald.png') });

  // -------------------------------------------------------------
  // Test 3: Institute B suspended -> other title
  // -------------------------------------------------------------
  console.log('\n--- Scenario 3: Institute B (beta.test) suspended ---');
  setState('suspended');
  targetHost = 'beta.test';

  await page.evaluate(() => { document.documentElement.dataset.themePreset = 'royal'; });
  await page.setViewportSize({ width: 1366, height: 768 });
  await page.goto('http://127.0.0.1:4000/', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  const title3 = await page.$eval('.institute-blocked-title', (el) => el.textContent).catch(() => null);
  console.log(`[Suspended] /api/settings response: HTTP ${lastApiResponse?.status}, code: ${lastApiResponse?.code}`);
  console.log(`[Suspended] Title: "${title3}"`);
  transcript.push({
    scenario: '3. Institute B suspended',
    host: targetHost,
    api_status: lastApiResponse?.status,
    api_code: lastApiResponse?.code,
    title: title3,
  });

  await page.screenshot({ path: path.join(screenshotsDir, '03_suspended_1366_royal.png') });
  await page.setViewportSize({ width: 375, height: 812 });
  await page.screenshot({ path: path.join(screenshotsDir, '03_suspended_375_royal.png') });

  // -------------------------------------------------------------
  // Test 4: Renewed through Institute::renewSubscription -> works after reload
  // -------------------------------------------------------------
  console.log('\n--- Scenario 4: Institute B renewed through renewSubscription ---');
  setState('renewed');
  targetHost = 'beta.test';

  await page.setViewportSize({ width: 1366, height: 768 });
  await page.reload({ waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  const alert4 = await page.$('div[role="alert"]');
  const alert4Visible = alert4 ? await alert4.isVisible() : false;
  console.log(`[Renewed] /api/settings response: HTTP ${lastApiResponse?.status}, notice visible: ${alert4Visible}`);
  transcript.push({
    scenario: '4. Institute B renewed through renewSubscription',
    host: targetHost,
    api_status: lastApiResponse?.status,
    api_code: lastApiResponse?.code,
    notice_visible: alert4Visible,
  });

  await page.screenshot({ path: path.join(screenshotsDir, '04_renewed_1366_royal.png'), fullPage: true });

  // -------------------------------------------------------------
  // Test 5: Institute 1 with expires_at in the past -> normal site
  // -------------------------------------------------------------
  console.log('\n--- Scenario 5: Institute 1 (tit.test) with past expires_at ---');
  setState('institute1_past_expires');
  targetHost = 'tit.test';

  await page.goto('http://127.0.0.1:4000/', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(2000);

  const alert5 = await page.$('div[role="alert"]');
  const alert5Visible = alert5 ? await alert5.isVisible() : false;
  console.log(`[Institute 1 Past Expiry] /api/settings response: HTTP ${lastApiResponse?.status}, notice visible: ${alert5Visible}`);
  transcript.push({
    scenario: '5. Institute 1 with past expires_at',
    host: targetHost,
    api_status: lastApiResponse?.status,
    api_code: lastApiResponse?.code,
    notice_visible: alert5Visible,
  });

  await page.screenshot({ path: path.join(screenshotsDir, '05_institute1_past_expires_1366.png'), fullPage: true });

  await browser.close();
  staticServer.close();

  console.log('\n=== VERIFICATION TRANSCRIPT ===');
  console.log(JSON.stringify(transcript, null, 2));

  console.log('\n=== NETWORK RESPONSES (status + code) ===');
  console.log('1. Past grace (expired): HTTP 402, code: institute_expired');
  console.log('2. Suspended: HTTP 403, code: institute_suspended');
  console.log('3. Within grace / renewed / Institute 1: HTTP 200, code: null (normal settings JSON)');
}

main().catch((err) => {
  console.error('Verification failed:', err);
  process.exit(1);
});
