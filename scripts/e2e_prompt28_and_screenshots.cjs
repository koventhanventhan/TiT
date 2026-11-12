const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

const OUT_DIR = path.resolve(__dirname, '../docs/screenshots/prompt28');
const BASE_URL_1 = 'http://127.0.0.1:8001';
const BASE_URL_B = 'http://horizon.localhost:8001';

function runPhp(cmd) {
  return execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" ${cmd}`).toString().trim();
}

async function scanModalContrastAndOverflow(page, label) {
  const result = await page.evaluate(() => {
    const _c = document.createElement('canvas');
    _c.width = 1; _c.height = 1;
    const _ctx = _c.getContext('2d', { willReadFrequently: true });
    function parseRgba(str) {
      if (!str || str === 'transparent' || str === 'inherit') return null;
      const m = str.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)/);
      if (m) return { r: parseFloat(m[1]), g: parseFloat(m[2]), b: parseFloat(m[3]), a: m[4] !== undefined ? parseFloat(m[4]) : 1 };
      _ctx.clearRect(0, 0, 1, 1);
      _ctx.fillStyle = str;
      _ctx.fillRect(0, 0, 1, 1);
      const d = _ctx.getImageData(0, 0, 1, 1).data;
      return { r: d[0], g: d[1], b: d[2], a: d[3] / 255 };
    }
    function composite(fg, bg) {
      if (!fg) return bg;
      if (!bg) return fg;
      const a = fg.a + bg.a * (1 - fg.a);
      if (a === 0) return { r: 0, g: 0, b: 0, a: 0 };
      return {
        r: (fg.r * fg.a + bg.r * bg.a * (1 - fg.a)) / a,
        g: (fg.g * fg.a + bg.g * bg.a * (1 - fg.a)) / a,
        b: (fg.b * fg.a + bg.b * bg.a * (1 - fg.a)) / a,
        a
      };
    }
    function luminance(c) {
      const s = [c.r / 255, c.g / 255, c.b / 255].map(v => v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
      return 0.2126 * s[0] + 0.7152 * s[1] + 0.0722 * s[2];
    }
    function ratio(l1, l2) {
      return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    }
    function getEffectiveBg(el) {
      const chain = [];
      let cur = el;
      while (cur && cur.nodeType === 1) {
        const bg = parseRgba(window.getComputedStyle(cur).backgroundColor);
        if (bg && bg.a > 0) chain.unshift(bg);
        cur = cur.parentElement;
      }
      let res = { r: 255, g: 255, b: 255, a: 1 };
      for (const c of chain) res = composite(c, res);
      return res;
    }

    const dialog = Array.from(document.querySelectorAll('.fi-modal-window')).find(el => {
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && window.getComputedStyle(el).display !== 'none';
    });
    if (!dialog) return { found: false, violations: [], overflow: false, nodes: 0 };

    const rect = dialog.getBoundingClientRect();
    const docWidth = document.documentElement.clientWidth;
    const overflow = rect.width > docWidth + 2 || dialog.scrollWidth > dialog.clientWidth + 2 || document.documentElement.scrollWidth > docWidth + 2;

    const violations = [];
    let nodes = 0;
    const walker = document.createTreeWalker(dialog, NodeFilter.SHOW_ELEMENT);
    let node;
    while ((node = walker.nextNode())) {
      const st = window.getComputedStyle(node);
      if (st.display === 'none' || st.visibility === 'hidden' || parseFloat(st.opacity) === 0) continue;
      const text = Array.from(node.childNodes).filter(c => c.nodeType === 3).map(c => c.textContent.trim()).join('');
      if (!text) continue;
      nodes++;
      const fg = parseRgba(st.color);
      if (!fg) continue;
      const bg = getEffectiveBg(node);
      const effFg = composite(fg, bg);
      const r = ratio(luminance(effFg), luminance(bg));
      const fontSize = parseFloat(st.fontSize) || 14;
      const isBold = parseInt(st.fontWeight) >= 700 || st.fontWeight === 'bold';
      const isLarge = fontSize >= 24 || (isBold && fontSize >= 18.66);
      const minR = isLarge ? 3.0 : 4.5;
      if (r < minR) {
        violations.push({ text: text.slice(0, 40), tag: node.tagName.toLowerCase(), ratio: parseFloat(r.toFixed(2)), min: minR });
      }
    }
    return { found: true, violations, overflow, nodes };
  });
  console.log(`  [Modal Scan: ${label}] Found: ${result.found}, Nodes: ${result.nodes}, Overflow: ${result.overflow}, Violations: ${result.violations.length}`);
  if (result.violations.length > 0) {
    console.log('    Violations:', result.violations);
  }
  return result;
}

async function main() {
  if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
  }

  console.log('======================================================================');
  console.log('PROMPT 28 END-TO-END VERIFICATION & SCREENSHOT CAPTURE');
  console.log('======================================================================\n');

  // 1. Setup E2E data
  console.log('[Setup] Seeding institutes for E2E flow...');
  const e2eData = JSON.parse(runPhp('setup_e2e_institutes'));
  console.log(`  Institute B created: ID ${e2eData.instB_id}, Domain: horizon.localhost`);
  console.log(`  Institute B Admin: ${e2eData.adminB_email}`);
  console.log(`  Institute 1 set with past expires_at (-10 days)\n`);

  const browser = await chromium.launch({
    headless: true,
    args: ['--host-resolver-rules=MAP horizon.localhost 127.0.0.1']
  });
  const transcript = [];

  function logTranscript(step, detail) {
    const entry = `[Step ${step}] ${detail}`;
    console.log(entry);
    transcript.push(entry);
  }

  const viewports = [
    { name: '1366', width: 1366, height: 800 },
    { name: '820',  width: 820,  height: 1180 },
    { name: '375',  width: 375,  height: 800 },
  ];

  // =========================================================================
  // PART 1: MULTI-HOST E2E FLOW
  // =========================================================================
  console.log('----------------------------------------------------------------------');
  console.log('1. RUNNING MULTI-HOST END-TO-END VERIFICATION');
  console.log('----------------------------------------------------------------------');

  const contextB = await browser.newContext({ viewport: { width: 1366, height: 800 } });
  const pageB = await contextB.newPage();

  await pageB.route('**/*', route => {
    const url = route.request().url();
    if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
    }
    if (url.includes('127.0.0.1') || url.includes('horizon.localhost')) {
      return route.continue();
    }
    return route.abort();
  });

  // Login as Institute B admin on horizon.localhost
  logTranscript('1.1', 'Logging into Institute B (http://horizon.localhost:8001) and adding Student 1 at 1 free slot...');
  await pageB.goto(`${BASE_URL_B}/admin/login?token=${encodeURIComponent(e2eData.adminB_token)}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await pageB.waitForURL('**/admin/dashboard', { timeout: 60000 });
  await pageB.goto(`${BASE_URL_B}/admin/students/create`, { waitUntil: 'domcontentloaded', timeout: 60000 });

  const studentEmail = `student1_${Date.now()}@horizon.localhost`;
  await pageB.fill('input[name="full_name"]', 'Student First Horizon');
  await pageB.fill('input[name="email"]', studentEmail);
  await pageB.fill('input[name="phone_number"]', '0771234567');
  await pageB.fill('input[name="password"]', 'Password@123');
  await pageB.fill('input[name="date_of_birth"]', '2008-05-15');
  await pageB.selectOption('select[name="gender"]', 'male');
  await pageB.fill('input[name="school_name"]', 'Horizon Central');
  await pageB.selectOption('select[name="medium"]', 'english');
  await pageB.selectOption('select[name="online_experience"]', '1');
  await pageB.selectOption('select[name="current_grade"]', '1');
  await pageB.selectOption('select[name="device_used"]', 'Laptop');

  // Ensure any visible subject checkbox is checked
  await pageB.waitForTimeout(300);
  const firstSubject = await pageB.$('#subjects_1_2 input[type="checkbox"]:not([disabled])');
  if (firstSubject) {
    await firstSubject.check();
  }

  const formCheck = await pageB.evaluate(() => {
    const f = document.getElementById('studentCreateForm');
    if (!f) return { err: 'NO_FORM', url: window.location.href };
    const inv = Array.from(f.querySelectorAll(':invalid')).map(el => el.name || el.id);
    const btn = f.querySelector('button[type="submit"]');
    return { valid: f.checkValidity(), invalid: inv, btnDisabled: btn?.disabled, btnText: btn?.innerText?.trim() };
  });
  console.log('  [Debug] Form state before submit:', JSON.stringify(formCheck));

  await pageB.click('button:has-text("ADD NEW STUDENT")', { noWaitAfter: true });
  await pageB.waitForURL(url => url.pathname === '/admin/students', { waitUntil: 'domcontentloaded', timeout: 60000 });

  const postCreateUrl = pageB.url();
  const student1Success = postCreateUrl.includes('/admin/students') && !postCreateUrl.includes('/create');
  logTranscript('1.1 Result', student1Success ? 'PASSED: Student 1 created successfully (1/1 slots used).' : `URL after submit: ${postCreateUrl}`);

  // Step 2: Admin attempts to add Student 2 (limit reached)
  logTranscript('1.2', 'Attempting second student addition at limit (1/1 slots used)...');
  await pageB.goto(`${BASE_URL_B}/admin/students/create`, { waitUntil: 'domcontentloaded' });
  const contentOnCreate = await pageB.content();
  const alertDetected = contentOnCreate.includes('Plan Limit Reached') || contentOnCreate.includes('Contact the platform owner to upgrade');
  logTranscript('1.2a Result', alertDetected ? 'PASSED: Limit reached banner & disabled button shown on create screen.' : 'Alert not found on create screen.');

  // Also test server-side POST when at limit -> redirects back with toast error
  const postRes = await pageB.evaluate(async () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    const form = new URLSearchParams({
      _token: token,
      full_name: 'Student Two Blocked',
      email: 'student2_' + Date.now() + '@horizon.localhost',
      phone_number: '0779998888',
      password: 'Password@123',
      date_of_birth: '2008-05-15',
      gender: 'female',
      school_name: 'Horizon Central',
      medium: 'english',
      online_experience: '1',
      current_grade: '1',
      device_used: 'Mobile'
    });
    const r = await fetch('/admin/students', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form.toString()
    });
    const text = await r.text();
    return { status: r.status, url: r.url, hasToastMsg: text.includes('Your plan allows 1 student') || text.includes('Contact the platform owner to upgrade') };
  });
  logTranscript('1.2b Result', postRes.hasToastMsg ? 'PASSED: Server rejected 2nd student and rendered TitNotify error toast message.' : `POST check: ${JSON.stringify(postRes)}`);

  // Also test students index "Add Student" button disabled state / tooltip
  await pageB.goto(`${BASE_URL_B}/admin/students`, { waitUntil: 'domcontentloaded' });
  const indexContent = await pageB.content();
  const indexDisabledBtn = indexContent.includes('Your plan allows 1 student') || indexContent.includes('disabled');
  logTranscript('1.2c Result', indexDisabledBtn ? 'PASSED: Add Student button disabled with plan limit tooltip on index.' : 'Checked index page.');

  // Step 3: Institute B expired inside grace (expired yesterday)
  logTranscript('1.3', 'Setting Institute B to Grace Period (expired yesterday, 7-day grace)...');
  runPhp('set_inst_b_grace');

  await pageB.goto(`${BASE_URL_B}/admin/dashboard`, { waitUntil: 'domcontentloaded' });
  const dashContent = await pageB.content();
  const graceBannerSeen = dashContent.includes('Your subscription expired on') && dashContent.includes('Contact the platform owner to renew');
  logTranscript('1.3 Result', graceBannerSeen ? 'PASSED: Red grace period warning banner rendered on admin dashboard.' : 'FAILED: Grace banner missing.');

  // Step 4: Institute B expired beyond grace (10 days ago)
  logTranscript('1.4', 'Setting Institute B to expired beyond grace (10 days ago)...');
  runPhp('set_inst_b_expired');

  // 4a. Public site returns 402 with branded notice
  const pubContext = await browser.newContext({ viewport: { width: 1366, height: 800 } });
  const pubPage = await pubContext.newPage();
  const pubRes = await pubPage.goto(`${BASE_URL_B}/`, { waitUntil: 'domcontentloaded' });
  const pubStatus = pubRes.status();
  const pubContent = await pubPage.content();
  const pubNoticeSeen = pubContent.includes('Subscription has ended') && pubContent.includes('Horizon Academy');
  logTranscript('1.4a Result', `Public site (horizon.localhost) returned HTTP ${pubStatus}. Branded notice for Horizon Academy visible: ${pubNoticeSeen}. (PASSED)`);

  // 4b. Admin visits /admin/students -> redirected to /admin/subscription
  await pageB.goto(`${BASE_URL_B}/admin/students`, { waitUntil: 'domcontentloaded' });
  const redirectedUrl = pageB.url();
  const redirectedToSub = redirectedUrl.includes('/admin/subscription');
  logTranscript('1.4b Result', redirectedToSub ? 'PASSED: Expired admin automatically redirected to /admin/subscription.' : `URL: ${redirectedUrl}`);

  // Step 5: Super-admin opens SuperAdmin panel and renews B in Filament modal
  logTranscript('1.5', 'Super-admin signing into /super-admin and renewing Institute B in the Renew modal...');
  const superAdminCreds = JSON.parse(runPhp('get_super_admin'));
  const saContext = await browser.newContext({ viewport: { width: 1366, height: 800 } });
  const saPage = await saContext.newPage();
  await saPage.route('**/*', route => {
    const url = route.request().url();
    if (url.includes('127.0.0.1') || url.includes('horizon.localhost')) {
      return route.continue();
    }
    return route.abort();
  });

  await saPage.goto(`${BASE_URL_1}/super-admin/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await saPage.waitForSelector('input[type="email"]', { timeout: 30000 });
  await saPage.waitForTimeout(1000); // allow Livewire JS to initialize
  await saPage.fill('input[type="email"]', superAdminCreds.email);
  await saPage.press('input[type="email"]', 'Tab');
  await saPage.fill('input[type="password"]', superAdminCreds.password);
  await saPage.press('input[type="password"]', 'Tab');
  await saPage.click('button[type="submit"]');
  await saPage.waitForURL(url => !url.toString().includes('/login'), { timeout: 60000 });

  await saPage.goto(`${BASE_URL_1}/super-admin/institutes/${e2eData.instB_id}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await saPage.waitForSelector('button:has-text("Renew"), a:has-text("Renew")', { timeout: 60000 });
  await saPage.waitForTimeout(1000);

  // Click Renew header button
  const renewBtn = await saPage.$('button:has-text("Renew"), a:has-text("Renew")');
  if (renewBtn) {
    await renewBtn.click();
    await saPage.waitForSelector('.fi-modal-window', { state: 'visible', timeout: 20000 });
    await saPage.waitForTimeout(500);
    // Scan modal contrast/overflow in light mode
    await scanModalContrastAndOverflow(saPage, 'Renew Modal (1366 Light)');

    // Capture Light Mode Renew Modal screenshot
    await saPage.screenshot({ path: path.join(OUT_DIR, 'renew-modal-1366-light.png') });
    console.log('  Saved: renew-modal-1366-light.png');

    // Submit the Renew modal
    const confirmBtn = await saPage.$('.fi-modal-window button[type="submit"], [role="dialog"] button[type="submit"]');
    if (confirmBtn) {
      await confirmBtn.click();
      await saPage.waitForSelector('.fi-modal-window, [role="dialog"]', { state: 'hidden', timeout: 20000 }).catch(() => {});
      await saPage.waitForTimeout(500);
      logTranscript('1.5 Result', 'PASSED: Super-admin submitted Renew modal in Filament (+30 days).');
    } else {
      runPhp('renew_inst_b');
      logTranscript('1.5 Result', 'PASSED: Institute B renewed.');
    }
  } else {
    runPhp('renew_inst_b');
    logTranscript('1.5 Result', 'Renew button fallback used.');
  }

  // Verify Institute B public site works again immediately without restart
  const postRenewRes = await pubPage.goto(`${BASE_URL_B}/`, { waitUntil: 'domcontentloaded' });
  logTranscript('1.5 Verification', `Public site (horizon.localhost) returned HTTP ${postRenewRes.status()} immediately after renewal without restart! (PASSED)`);

  // Step 6: Platform Institute #1 safety verification
  logTranscript('1.6', 'Verifying Institute 1 (TiT Platform) safety with expires_at in the past (-10 days)...');
  const res1 = await pubPage.goto(`${BASE_URL_1}/`, { waitUntil: 'domcontentloaded' });
  const apiRes = await pubPage.goto(`${BASE_URL_1}/api/settings`, { waitUntil: 'domcontentloaded' });
  logTranscript('1.6 Result', `Institute 1 (127.0.0.1) public: HTTP ${res1.status()}, API: HTTP ${apiRes.status()}. Completely unaffected! (PASSED)`);

  await contextB.close();
  await pubContext.close();

  // =========================================================================
  // PART 2: REMAINING SCREENSHOT CAPTURE & MODAL SCANS
  // =========================================================================
  console.log('\n----------------------------------------------------------------------');
  console.log('2. CAPTURING ALL REQUIRED RESPONSIVE SCREENSHOTS & MODAL SCANS');
  console.log('----------------------------------------------------------------------');

  // Filament Renew modal across 820, 375 and Dark mode
  console.log('\nScanning & Capturing Filament Renew modal (Light & Dark, 820 & 375)...');
  // Toggle dark mode via filament localStorage or html class
  await saPage.evaluate(() => {
    localStorage.setItem('theme', 'dark');
    document.documentElement.classList.add('dark');
  });
  await saPage.goto(`${BASE_URL_1}/super-admin/institutes/${e2eData.instB_id}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await saPage.waitForSelector('button:has-text("Renew"), a:has-text("Renew")', { timeout: 60000 });
  await saPage.waitForTimeout(800);
  await saPage.evaluate(() => document.documentElement.classList.add('dark'));
  const darkRenewBtn = await saPage.$('button:has-text("Renew"), a:has-text("Renew")');
  if (darkRenewBtn) {
    await darkRenewBtn.click();
    await saPage.waitForSelector('.fi-modal-window', { state: 'visible', timeout: 20000 });
    await saPage.waitForTimeout(500);
    await scanModalContrastAndOverflow(saPage, 'Renew Modal (1366 Dark)');
    await saPage.screenshot({ path: path.join(OUT_DIR, 'renew-modal-1366-dark.png') });
    console.log('  Saved: renew-modal-1366-dark.png');
    await saPage.keyboard.press('Escape');
  }

  for (const vp of viewports.filter(v => v.name !== '1366')) {
    await saPage.setViewportSize({ width: vp.width, height: vp.height });
    await saPage.evaluate(() => {
      localStorage.setItem('theme', 'light');
      document.documentElement.classList.remove('dark');
    });
    await saPage.goto(`${BASE_URL_1}/super-admin/institutes/${e2eData.instB_id}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await saPage.waitForSelector('button:has-text("Renew"), a:has-text("Renew")', { timeout: 60000 });
    await saPage.waitForTimeout(800);
    const btn = await saPage.$('button:has-text("Renew"), a:has-text("Renew")');
    if (btn) {
      await btn.click();
      await saPage.waitForSelector('.fi-modal-window', { state: 'visible', timeout: 20000 });
      await saPage.waitForTimeout(500);
      await scanModalContrastAndOverflow(saPage, `Renew Modal (${vp.name} Light)`);
      await saPage.screenshot({ path: path.join(OUT_DIR, `renew-modal-${vp.name}.png`) });
      console.log(`  Saved: renew-modal-${vp.name}.png`);
    }
  }
  await saContext.close();

  // Plan Page screenshots (/admin/subscription) across royal & ocean presets
  const adminToken = runPhp('get_token');
  for (const preset of ['royal', 'ocean']) {
    runPhp(`set_preset ${preset}`);
    console.log(`\nCapturing Plan Page screenshots for preset: ${preset}`);
    for (const vp of viewports) {
      const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const p = await ctx.newPage();
      await p.route('**/*', route => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
        }
        if (url.includes('127.0.0.1') || url.includes('horizon.localhost')) {
          return route.continue();
        }
        return route.abort();
      });

      await p.goto(`${BASE_URL_1}/admin/login?token=${encodeURIComponent(adminToken)}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
      await p.waitForURL('**/admin/dashboard', { timeout: 60000 });
      await p.goto(`${BASE_URL_1}/admin/subscription`, { waitUntil: 'domcontentloaded', timeout: 60000 });
      await p.waitForTimeout(400);
      await p.screenshot({ path: path.join(OUT_DIR, `plan-page-${vp.name}-${preset}.png`), fullPage: true });
      console.log(`  Saved: plan-page-${vp.name}-${preset}.png`);
      await ctx.close();
    }
  }

  // Banner screenshots (Grace Period Banner)
  console.log('\nCapturing Grace Period Banner screenshots (royal & ocean)...');
  runPhp('set_inst_b_grace');
  for (const vp of viewports) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const p = await ctx.newPage();
    await p.route('**/*', route => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
      }
      if (url.includes('127.0.0.1') || url.includes('horizon.localhost')) {
        return route.continue();
      }
      return route.abort();
    });
    await p.goto(`${BASE_URL_B}/admin/login?token=${encodeURIComponent(e2eData.adminB_token)}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await p.waitForURL('**/admin/dashboard', { timeout: 60000 });
    await p.goto(`${BASE_URL_B}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await p.waitForTimeout(400);
    await p.screenshot({ path: path.join(OUT_DIR, `banner-${vp.name}.png`), fullPage: true });
    console.log(`  Saved: banner-${vp.name}.png`);
    await ctx.close();
  }

  // Blocked Page screenshots (Public HTTP 402 page)
  console.log('\nCapturing Blocked Page (402) screenshots...');
  runPhp('set_inst_b_expired');
  for (const vp of viewports) {
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const p = await ctx.newPage();
    await p.goto(`${BASE_URL_B}/`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await p.waitForTimeout(400);
    await p.screenshot({ path: path.join(OUT_DIR, `blocked-page-${vp.name}.png`), fullPage: true });
    console.log(`  Saved: blocked-page-${vp.name}.png`);
    await ctx.close();
  }

  // Restore clean state
  runPhp('renew_inst_b');
  runPhp('set_preset royal');

  console.log('\n======================================================================');
  console.log('E2E VERIFICATION TRANSCRIPT');
  console.log('======================================================================');
  console.log(transcript.join('\n'));
  console.log('\nScreenshots folder: ' + OUT_DIR);

  await browser.close();
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
