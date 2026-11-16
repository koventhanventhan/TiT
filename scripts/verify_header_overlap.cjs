const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));
const { execSync } = require('child_process');

const BASE_URL = process.env.APP_URL || 'http://127.0.0.1:8000';

const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '1024x800', width: 1024, height: 800 },
  { name: '820x1180', width: 820, height: 1180 },
  { name: '390x844', width: 390, height: 844 },
  { name: '360x800', width: 360, height: 800 }
];

const ROUTES = [
  '/admin/dashboard',
  '/admin/students',
  '/admin/settings/ai-security'
];

function getAdminToken() {
  return execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
}

async function run() {
  console.log('--- STARTING HEADER OVERLAP VERIFICATION ---');
  const token = getAdminToken();
  const browser = await chromium.launch({ headless: true });

  const allViolations = [];

  for (const vp of VIEWPORTS) {
    console.log(`\nTesting Viewport: ${vp.name} (${vp.width}x${vp.height})`);
    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      extraHTTPHeaders: { 'Connection': 'close' }
    });
    const page = await context.newPage();

    // Route-abort non-essential requests
    await page.route('**/*', route => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth') || url.includes('/admin/calendar/events') || url.includes('/admin/exam-results/data')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 2, notifications: [{id: 1, text: 'Test'}], events: [], data: [] }) });
      }
      if (url.includes('/api/messages')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ messages: [], recipients: {} }) });
      }
      if (url.startsWith(BASE_URL)) {
        return route.continue();
      }
      return route.abort();
    });

    // Login
    await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

    for (const route of ROUTES) {
      await page.goto(`${BASE_URL}${route}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await page.waitForTimeout(600);

      const violations = await page.evaluate(({ route, vpName }) => {
        const issues = [];

        // 1. Visible header buttons
        const buttons = Array.from(document.querySelectorAll('.adm-header-btn, .adm-header-profile-btn, .mobile-search-btn, .custom-hamburger')).filter(el => {
          const s = window.getComputedStyle(el);
          const r = el.getBoundingClientRect();
          return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
        });

        // Centre point check: document.elementFromPoint(x, y) must be the button or a descendant
        for (const btn of buttons) {
          const r = btn.getBoundingClientRect();
          const cx = Math.round(r.left + r.width / 2);
          const cy = Math.round(r.top + r.height / 2);
          const hit = document.elementFromPoint(cx, cy);
          if (!hit || (!btn.contains(hit) && hit !== btn)) {
            const hitDesc = hit ? `${hit.tagName.toLowerCase()}${hit.className ? '.' + hit.className.trim().split(/\s+/).join('.') : ''}${hit.id ? '#' + hit.id : ''}` : 'null';
            const btnDesc = `${btn.tagName.toLowerCase()}${btn.className ? '.' + btn.className.trim().split(/\s+/).join('.') : ''}${btn.id ? '#' + btn.id : ''}`;
            issues.push({
              type: 'button-center-occluded',
              route,
              viewport: vpName,
              message: `Button [${btnDesc}] centre (${cx}, ${cy}) is occluded by [${hitDesc}]`
            });
          }
        }

        // Pairwise non-intersection of buttons
        for (let i = 0; i < buttons.length; i++) {
          const b1 = buttons[i];
          const r1 = b1.getBoundingClientRect();
          for (let j = i + 1; j < buttons.length; j++) {
            const b2 = buttons[j];
            const r2 = b2.getBoundingClientRect();
            const xOverlap = Math.max(0, Math.min(r1.right, r2.right) - Math.max(r1.left, r2.left));
            const yOverlap = Math.max(0, Math.min(r1.bottom, r2.bottom) - Math.max(r1.top, r2.top));
            if (xOverlap > 1 && yOverlap > 1) {
              const d1 = `${b1.className}#${b1.id || i}`;
              const d2 = `${b2.className}#${b2.id || j}`;
              issues.push({
                type: 'button-pairwise-overlap',
                route,
                viewport: vpName,
                message: `Buttons [${d1}] and [${d2}] overlap: xOverlap=${xOverlap.toFixed(1)}px, yOverlap=${yOverlap.toFixed(1)}px`
              });
            }
          }
        }

        // Badges box non-intersection with other buttons
        const badges = Array.from(document.querySelectorAll('.adm-badge, #message-pulse, #notification-pulse')).filter(el => {
          const s = window.getComputedStyle(el);
          const r = el.getBoundingClientRect();
          return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
        });
        for (const badge of badges) {
          const br = badge.getBoundingClientRect();
          const parentBtn = badge.closest('.adm-header-btn, .adm-header-profile-btn, .mobile-search-btn');
          for (const btn of buttons) {
            if (btn === parentBtn) continue;
            const btnR = btn.getBoundingClientRect();
            const xOverlap = Math.max(0, Math.min(br.right, btnR.right) - Math.max(br.left, btnR.left));
            const yOverlap = Math.max(0, Math.min(br.bottom, btnR.bottom) - Math.max(br.top, btnR.top));
            if (xOverlap > 1 && yOverlap > 1) {
              issues.push({
                type: 'badge-button-overlap',
                route,
                viewport: vpName,
                message: `Badge #${badge.id || badge.className} overlaps non-parent button ${btn.className}#${btn.id}: xOverlap=${xOverlap.toFixed(1)}px, yOverlap=${yOverlap.toFixed(1)}px`
              });
            }
          }
        }

        // Search button vs brand non-intersection
        const searchBtn = document.querySelector('.mobile-search-btn');
        const brand = document.querySelector('.adm-header-brand');
        if (searchBtn && brand) {
          const sStyle = window.getComputedStyle(searchBtn);
          const bStyle = window.getComputedStyle(brand);
          if (sStyle.display !== 'none' && bStyle.display !== 'none') {
            const sr = searchBtn.getBoundingClientRect();
            const br = brand.getBoundingClientRect();
            const xOverlap = Math.max(0, Math.min(sr.right, br.right) - Math.max(sr.left, br.left));
            const yOverlap = Math.max(0, Math.min(sr.bottom, br.bottom) - Math.max(sr.top, br.top));
            if (xOverlap > 1 && yOverlap > 1) {
              issues.push({
                type: 'search-brand-overlap',
                route,
                viewport: vpName,
                message: `Mobile search button overlaps brand text: xOverlap=${xOverlap.toFixed(1)}px, yOverlap=${yOverlap.toFixed(1)}px`
              });
            }
          }
        }

        return issues;
      }, { route, vpName: vp.name });

      if (violations.length > 0) {
        console.error(`  FAIL [${vp.name}] ${route}: ${violations.length} violation(s)`);
        for (const v of violations) {
          console.error(`    - ${v.type}: ${v.message}`);
        }
        allViolations.push(...violations);
      } else {
        console.log(`  PASS [${vp.name}] ${route}`);
      }
    }

    await context.close();
  }

  await browser.close();

  console.log('\n--- VERIFICATION SUMMARY ---');
  console.log(`Total Violations: ${allViolations.length}`);
  if (allViolations.length > 0) {
    console.error('FAILED: Header overlap violations detected!');
    process.exit(1);
  } else {
    console.log('PASSED: All header buttons have clear center points, zero pairwise overlap, and badges stay inside buttons.');
    process.exit(0);
  }
}

run().catch(err => {
  console.error('Unexpected error:', err);
  process.exit(1);
});
