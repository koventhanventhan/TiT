const path = require('path');
const fs = require('fs');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));
const { execSync } = require('child_process');

const BASE_URL = process.env.APP_URL || 'http://127.0.0.1:8000';

const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '1024x800', width: 1024, height: 800 },
  { name: '820x1180', width: 820, height: 1180 },
  { name: '768x1024', width: 768, height: 1024 },
  { name: '390x844', width: 390, height: 844 },
  { name: '360x800', width: 360, height: 800 }
];

const ALL_ROUTES = [
  '/admin/dashboard',
  '/admin/students',
  '/admin/students/pending-payments',
  '/admin/students/create',
  '/admin/students/index-lists',
  '/admin/students/assign-index-tool',
  '/admin/students/import-index',
  '/admin/students-requests/medium-changes',
  '/admin/students-requests/advance-payments',
  '/admin/teachers',
  '/admin/teachers/create',
  '/admin/subjects',
  '/admin/packages',
  '/admin/settings',
  '/admin/settings/about',
  '/admin/settings/contact',
  '/admin/settings/learning',
  '/admin/settings/classes',
  '/admin/settings/footer',
  '/admin/settings/register',
  '/admin/settings/admission-fees',
  '/admin/settings/payments',
  '/admin/settings/grade-promotion-schedule',
  '/admin/settings/index-batches',
  '/admin/settings/index-series',
  '/admin/settings/appearance',
  '/admin/settings/online-payments',
  '/admin/settings/messaging',
  '/admin/settings/ai-security',
  '/admin/subscription',
  '/admin/assistant',
  '/admin/zoom',
  '/admin/zoom/create',
  '/admin/zoom-accounts',
  '/admin/zoom-accounts/create',
  '/admin/timetables',
  '/admin/timetables/create',
  '/admin/attendance',
  '/admin/messages',
  '/admin/profile',
  '/admin/profile/settings',
  '/admin/notifications',
  '/admin/exam-results'
];

const ROUTES = process.env.VERIFY_ROUTES
  ? process.env.VERIFY_ROUTES.split(',').map(r => r.trim()).filter(Boolean)
  : ALL_ROUTES;

function getAdminToken() {
  return execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
}

async function run() {
  console.log('--- STARTING STRICT HEADER OVERLAP & CONTAINMENT VERIFICATION ---');
  console.log(`Routes to verify: ${ROUTES.length}`);
  console.log(`Viewports to verify: ${VIEWPORTS.map(v => v.name).join(', ')}`);
  const token = getAdminToken();
  const browser = await chromium.launch({ headless: true });

  const allViolations = [];
  const brandReports = [];

  for (const vp of VIEWPORTS) {
    console.log(`\n======================================================`);
    console.log(`Testing Viewport: ${vp.name} (${vp.width}x${vp.height})`);
    console.log(`======================================================`);

    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      extraHTTPHeaders: { 'Connection': 'close' }
    });
    const page = await context.newPage();

    // Route-abort non-essential requests
    await page.route('**/*', route => {
      if (route.request().resourceType() === 'document') {
        return route.continue();
      }
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
      await page.waitForTimeout(300);

      const evalResult = await page.evaluate(({ route, vpName, vpWidth }) => {
        const issues = [];
        const reports = [];

        const header = document.querySelector('.header');
        if (!header) {
          issues.push({
            type: 'missing-header',
            route,
            viewport: vpName,
            message: 'No .header element found on page'
          });
          return { issues, reports };
        }

        const bar = header.getBoundingClientRect();

        // 1. Visible header buttons/avatar/brand/hamburger
        const trackedElements = Array.from(document.querySelectorAll('.adm-header-btn, .adm-header-profile-btn, .mobile-search-btn, .custom-hamburger, .adm-header-brand')).filter(el => {
          const s = window.getComputedStyle(el);
          const r = el.getBoundingClientRect();
          return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
        });

        // Check 2: Element fully inside bar (top >= bar.top - 1, bottom <= bar.bottom + 1, left >= -1, right <= vpWidth + 1)
        for (const el of trackedElements) {
          const r = el.getBoundingClientRect();
          const tag = el.tagName.toLowerCase();
          const cls = el.className ? '.' + el.className.trim().split(/\s+/).join('.') : '';
          const id = el.id ? '#' + el.id : '';
          const desc = `${tag}${cls}${id}`;

          const isInsideTop = r.top >= bar.top - 1;
          const isInsideBottom = r.bottom <= bar.bottom + 1;
          const isInsideLeft = r.left >= -1;
          const isInsideRight = r.right <= vpWidth + 1;

          if (!isInsideTop || !isInsideBottom || !isInsideLeft || !isInsideRight) {
            issues.push({
              type: 'element-outside-header-bar',
              route,
              viewport: vpName,
              message: `Element [${desc}] rect [top=${r.top.toFixed(1)}, bottom=${r.bottom.toFixed(1)}, left=${r.left.toFixed(1)}, right=${r.right.toFixed(1)}] is outside header bar [top=${bar.top.toFixed(1)}, bottom=${bar.bottom.toFixed(1)}, height=${bar.height.toFixed(1)}, vpWidth=${vpWidth}]`
            });
          }
        }

        // Check 3: All zones have the same top (+- 1px) - i.e. one row
        const leftZone = document.querySelector('.adm-header-zone-left');
        const rightZone = document.querySelector('.adm-header-zone-right');
        const centreZone = document.querySelector('.adm-header-zone-centre');

        if (leftZone && rightZone) {
          const leftRect = leftZone.getBoundingClientRect();
          const rightRect = rightZone.getBoundingClientRect();
          const leftStyle = window.getComputedStyle(leftZone);
          const rightStyle = window.getComputedStyle(rightZone);

          if (leftStyle.display !== 'none' && rightStyle.display !== 'none') {
            const diff = Math.abs(leftRect.top - rightRect.top);
            if (diff > 1.5) {
              issues.push({
                type: 'zones-not-single-row',
                route,
                viewport: vpName,
                message: `Header zones not on single row: left zone top=${leftRect.top.toFixed(1)}px vs right zone top=${rightRect.top.toFixed(1)}px (diff=${diff.toFixed(1)}px > 1px)`
              });
            }
          }

          if (centreZone) {
            const centreStyle = window.getComputedStyle(centreZone);
            if (centreStyle.display !== 'none' && centreStyle.position !== 'absolute') {
              const centreRect = centreZone.getBoundingClientRect();
              const diffCentre = Math.abs(leftRect.top - centreRect.top);
              if (diffCentre > 1.5) {
                issues.push({
                  type: 'centre-zone-not-single-row',
                  route,
                  viewport: vpName,
                  message: `Centre zone not on single row: left zone top=${leftRect.top.toFixed(1)}px vs centre zone top=${centreRect.top.toFixed(1)}px (diff=${diffCentre.toFixed(1)}px > 1px)`
                });
              }
            }
          }
        }

        // Check 4: The first element of <main>/content has top >= bar.bottom
        const contentBody = document.querySelector('.content-body, #main-wrapper .content-body, main');
        if (contentBody) {
          const candidates = Array.from(contentBody.querySelectorAll('.page-titles, .card, .alert, h1, h2, h3, h4, h5, h6, form, table, .container-fluid > div, .row'));
          let firstVisibleEl = null;
          let firstRect = null;
          for (const cand of candidates) {
            const s = window.getComputedStyle(cand);
            const r = cand.getBoundingClientRect();
            if (s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0) {
              if (!firstVisibleEl || r.top < firstRect.top) {
                firstVisibleEl = cand;
                firstRect = r;
              }
            }
          }

          if (firstVisibleEl && firstRect) {
            if (firstRect.top < bar.bottom - 1) {
              const candDesc = `${firstVisibleEl.tagName.toLowerCase()}${firstVisibleEl.className ? '.' + firstVisibleEl.className.trim().split(/\s+/).join('.') : ''}`;
              issues.push({
                type: 'content-overlaps-header',
                route,
                viewport: vpName,
                message: `First content element [${candDesc}] top (${firstRect.top.toFixed(1)}px) is above header bar bottom (${bar.bottom.toFixed(1)}px) by ${(bar.bottom - firstRect.top).toFixed(1)}px`
              });
            }
          }
        }

        // Check 5: Brand name scrollWidth vs clientWidth
        const brandNameEls = Array.from(document.querySelectorAll('.adm-brand-text')).filter(el => {
          const s = window.getComputedStyle(el);
          return s.display !== 'none' && s.visibility !== 'hidden';
        });
        for (const bEl of brandNameEls) {
          const isTruncated = bEl.scrollWidth > bEl.clientWidth + 1;
          let freeSpace = 0;
          if (leftZone && rightZone) {
            const lr = leftZone.getBoundingClientRect();
            const rr = rightZone.getBoundingClientRect();
            freeSpace = Math.max(0, rr.left - lr.right);
          }
          reports.push({
            element: bEl.className,
            text: bEl.textContent.trim(),
            scrollWidth: bEl.scrollWidth,
            clientWidth: bEl.clientWidth,
            isTruncated,
            freeSpacePx: Math.round(freeSpace)
          });

          if (isTruncated && freeSpace > 24) {
            issues.push({
              type: 'brand-truncated-with-excess-free-space',
              route,
              viewport: vpName,
              message: `Brand text "${bEl.textContent.trim()}" is truncated (scrollWidth=${bEl.scrollWidth}px > clientWidth=${bEl.clientWidth}px) while ${Math.round(freeSpace)}px free space is available (> 24px)`
            });
          }
        }

        // Check 6: Pairwise overlap, centre point occlusion, badge isolation
        const buttons = Array.from(document.querySelectorAll('.adm-header-btn, .adm-header-profile-btn, .mobile-search-btn, .custom-hamburger')).filter(el => {
          const s = window.getComputedStyle(el);
          const r = el.getBoundingClientRect();
          return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
        });

        // Centre point check
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

        // Pairwise non-intersection
        for (let i = 0; i < buttons.length; i++) {
          const b1 = buttons[i];
          const r1 = b1.getBoundingClientRect();
          for (let j = i + 1; j < buttons.length; j++) {
            const b2 = buttons[j];
            const r2 = b2.getBoundingClientRect();
            const xOverlap = Math.max(0, Math.min(r1.right, r2.right) - Math.max(r1.left, r2.left));
            const yOverlap = Math.max(0, Math.min(r1.bottom, r2.bottom) - Math.max(r1.top, r2.top));
            if (xOverlap > 1 && yOverlap > 1) {
              issues.push({
                type: 'button-pairwise-overlap',
                route,
                viewport: vpName,
                message: `Buttons [${b1.className}#${b1.id || i}] and [${b2.className}#${b2.id || j}] overlap: xOverlap=${xOverlap.toFixed(1)}px, yOverlap=${yOverlap.toFixed(1)}px`
              });
            }
          }
        }

        // Badge isolation
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

        return { issues, reports };
      }, { route, vpName: vp.name, vpWidth: vp.width });

      if (evalResult.issues.length > 0) {
        console.error(`  FAIL [${vp.name}] ${route}: ${evalResult.issues.length} violation(s)`);
        for (const v of evalResult.issues) {
          console.error(`    - ${v.type}: ${v.message}`);
        }
        allViolations.push(...evalResult.issues);
      } else {
        console.log(`  PASS [${vp.name}] ${route}`);
      }

      if (evalResult.reports && evalResult.reports.length > 0) {
        brandReports.push({ route, vp: vp.name, data: evalResult.reports });
      }
    }

    await context.close();
  }

  await browser.close();

  console.log('\n--- VERIFICATION SUMMARY ---');
  console.log(`Total Violations: ${allViolations.length}`);
  if (allViolations.length > 0) {
    console.error('FAILED: Header overlap or containment violations detected!');
    process.exit(1);
  } else {
    console.log('PASSED: All header buttons inside header bar, single row zones, content strictly below bar, brand truncation compliant, zero pairwise overlap.');
    process.exit(0);
  }
}

run().catch(err => {
  console.error('Unexpected error:', err);
  process.exit(1);
});
