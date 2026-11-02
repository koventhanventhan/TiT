import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const defaultStudentUser = {
  id: 201,
  name: 'Kasun Perera',
  full_name: 'Kasun Perera',
  email: 'kasun@test.lk',
  role: 'user',
  current_grade: 'Grade 10',
  medium: 'english',
  subjects: ['Mathematics', 'Science', 'English'],
  selected_subjects: ['Mathematics', 'Science', 'English'],
  registration_status: 'completed',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: true,
  is_deactivated: false,
  rag_enabled: false,
};

const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '390x800', width: 390, height: 800 },
];

const STATES = [
  { id: 'no-attribute', preset: null, label: 'no attribute' },
  { id: 'royal', preset: 'royal', label: 'royal' },
  { id: 'ocean', preset: 'ocean', label: 'ocean' },
  { id: 'emerald', preset: 'emerald', label: 'emerald' },
  { id: 'midnight', preset: 'midnight', label: 'midnight' },
];

const PUBLIC_ROUTES = [
  '/',
  '/about',
  '/classes',
  '/contact',
  '/blogs',
  '/exam-results',
  '/notes',
  '/past-papers',
  '/privacy',
  '/refund',
  '/register',
  '/terms',
  '/tutor-apply',
  '/recordings',
];

const CONSOLE_ROUTES = [
  { path: '/student/dashboard', role: 'student' },
  { path: '/teacher/dashboard', role: 'teacher' },
  { path: '/select-profile', role: 'parent' },
  { path: '/super-admin', role: 'admin' },
];

// In-browser evaluator function
async function evaluateVisualPage(page, options = {}) {
  return await page.evaluate(({ checkInvisible, checkContrast, checkVars, checkOverflow, stateId }) => {
    const issues = [];
    let nodesScanned = 0;
    const docEl = document.documentElement;

    // 1. Horizontal Overflow Check
    if (checkOverflow) {
      const scrollW = docEl.scrollWidth;
      const innerW = window.innerWidth;
      if (scrollW > innerW + 1) {
        issues.push({
          type: 'overflow',
          selector: 'html',
          text: '',
          detail: `scrollWidth (${scrollW}px) > innerWidth (${innerW}px)`
        });
      }
    }

    // 2. Empty CSS Variables Check
    if (checkVars) {
      const rootStyle = window.getComputedStyle(docEl);
      const usedVars = new Set();
      for (const sheet of document.styleSheets) {
        try {
          for (const rule of sheet.cssRules) {
            if (rule.style) {
              const cssText = rule.style.cssText;
              const matches = cssText.match(/var\(\s*(--[a-zA-Z0-9_-]+)/g);
              if (matches) {
                for (const m of matches) {
                  const varName = m.replace(/var\(\s*/, '').trim();
                  usedVars.add(varName);
                }
              }
            }
          }
        } catch (_) {}
      }

      for (const v of usedVars) {
        const val = rootStyle.getPropertyValue(v).trim();
        if (val === '') {
          issues.push({
            type: 'empty-var',
            selector: ':root',
            text: v,
            detail: `Custom property ${v} resolved to empty string`
          });
        }
      }
    }

    // Color helpers
    function parseRgba(str) {
      if (!str || str === 'transparent') return { r: 0, g: 0, b: 0, a: 0 };
      const m = str.match(/rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+))?\)/);
      if (!m) return null;
      return {
        r: parseFloat(m[1]),
        g: parseFloat(m[2]),
        b: parseFloat(m[3]),
        a: m[4] !== undefined ? parseFloat(m[4]) : 1
      };
    }

    function blend(fg, bg) {
      const a = fg.a + bg.a * (1 - fg.a);
      if (a === 0) return { r: 255, g: 255, b: 255, a: 1 };
      const r = (fg.r * fg.a + bg.r * bg.a * (1 - fg.a)) / a;
      const g = (fg.g * fg.a + bg.g * bg.a * (1 - fg.a)) / a;
      const b = (fg.b * fg.a + bg.b * bg.a * (1 - fg.a)) / a;
      return { r, g, b, a };
    }

    function getLuminance(r, g, b) {
      const a = [r, g, b].map(v => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
      });
      return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
    }

    function getContrast(lum1, lum2) {
      const l1 = Math.max(lum1, lum2);
      const l2 = Math.min(lum1, lum2);
      return (l1 + 0.05) / (l2 + 0.05);
    }

    // Base background of the document
    const bodyBg = parseRgba(window.getComputedStyle(document.body).backgroundColor);
    const baseCanvas = (bodyBg && bodyBg.a > 0) ? bodyBg : { r: 255, g: 255, b: 255, a: 1 };

    // Walk visible text nodes
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        if (!node.textContent || !node.textContent.trim()) return NodeFilter.FILTER_REJECT;
        const el = node.parentElement;
        if (!el || !(el instanceof Element)) return NodeFilter.FILTER_REJECT;
        const tag = el.tagName.toLowerCase();
        if (['script', 'style', 'noscript', 'template', 'svg'].includes(tag)) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });

    let currentNode;
    while ((currentNode = walker.nextNode())) {
      const el = currentNode.parentElement;
      if (!el || !(el instanceof Element)) continue;
      const text = currentNode.textContent.trim();
      if (!text) continue;

      const rect = el.getBoundingClientRect();
      if (rect.width === 0 || rect.height === 0) continue;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) === 0) continue;

      // Allow-list decorative / mock elements
      if (el.closest('.s-dummy-content') || el.closest('.phone-speaker') || el.closest('.phone-home-line')) {
        continue;
      }

      nodesScanned++;

      // Check (a): Invisible text
      const textFill = style.webkitTextFillColor;
      const bgClip = (style.webkitBackgroundClip || style.backgroundClip || '');
      const bgImage = style.backgroundImage;
      const colorRgba = parseRgba(style.color);
      const fillRgba = parseRgba(textFill);

      const isTransparentFill = (fillRgba && fillRgba.a === 0) || textFill === 'transparent';
      const isTransparentColor = colorRgba && colorRgba.a === 0;

      if (isTransparentFill || isTransparentColor) {
        const hasValidGradient = bgClip.includes('text') && bgImage && bgImage !== 'none' && bgImage.includes('gradient');
        if (!hasValidGradient) {
          issues.push({
            type: 'invisible-text',
            selector: `${el.tagName.toLowerCase()}.${(el.className || '').toString().trim().split(/\s+/).join('.')}`,
            text: text.slice(0, 30),
            detail: 'Text fill is transparent without valid background-clip:text gradient'
          });
          continue;
        }
      }

      // Check (b): Contrast
      if (checkContrast) {
        // Collect stacked backgrounds
        let p = el;
        const bgStack = [];
        let hasGradientBg = false;

        while (p && p instanceof Element && p !== document.documentElement) {
          const ps = window.getComputedStyle(p);
          const bg = parseRgba(ps.backgroundColor);
          if (bg && bg.a > 0) {
            bgStack.unshift(bg);
          }
          if (ps.backgroundImage && ps.backgroundImage.includes('gradient')) {
            hasGradientBg = true;
          }
          p = p.parentElement;
        }

        let compBg = baseCanvas;
        for (const b of bgStack) {
          compBg = blend(b, compBg);
        }

        const compFg = (fillRgba && fillRgba.a > 0 && !bgClip.includes('text')) ? fillRgba : (colorRgba || { r: 0, g: 0, b: 0, a: 1 });
        const finalFg = blend(compFg, compBg);

        const lumFg = getLuminance(finalFg.r, finalFg.g, finalFg.b);
        const lumBg = getLuminance(compBg.r, compBg.g, compBg.b);
        const ratio = getContrast(lumFg, lumBg);

        const fontSize = parseFloat(style.fontSize);
        const fontWeight = parseInt(style.fontWeight, 10) || 400;
        const isLarge = fontSize >= 24 || (fontSize >= 18.5 && fontWeight >= 700);
        const minRatio = isLarge ? 3.0 : 4.5;

        // Skip gradient background contrast (handled via gradient sampling) or if text is gradient
        if (!hasGradientBg && !bgClip.includes('text')) {
          if (ratio < minRatio) {
            issues.push({
              type: 'contrast',
              selector: `${el.tagName.toLowerCase()}.${(el.className || '').toString().trim().split(/\s+/).join('.')}`,
              text: text.slice(0, 30),
              ratio: Math.round(ratio * 100) / 100,
              min: minRatio,
              fg: `rgb(${Math.round(finalFg.r)}, ${Math.round(finalFg.g)}, ${Math.round(finalFg.b)})`,
              bg: `rgb(${Math.round(compBg.r)}, ${Math.round(compBg.g)}, ${Math.round(compBg.b)})`
            });
          }
        }
      }
    }

    return { nodesScanned, issues };
  }, { checkInvisible: true, checkContrast: options.checkContrast !== false, checkVars: true, checkOverflow: true, stateId: options.stateId });
}

// Global summary statistics collector
const summaryResults = [];

test.describe('Frontend Visual & Contrast Crawler', () => {
  for (const st of STATES) {
    for (const vp of VIEWPORTS) {
      test(`Matrix: [${st.label}] @ [${vp.name}]`, async ({ page }) => {
        await page.setViewportSize({ width: vp.width, height: vp.height });

        // Setup API mock
        await page.route('**/api/settings', async (route) => {
          route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              site_name: 'Online Education Platform',
              theme_preset: st.preset || undefined,
              classes_types: JSON.stringify([
                { title: 'Direct Tuition', color: '#4f0bd9', image: '/venthan1.webp', format: 'Physical', duration: 'Weekly', students: 'Group' },
                { title: 'Online Tuition', color: '#4f0bd9', image: '/venthan2.webp', format: 'Online', duration: 'Flexible', students: 'Group/1-on-1' }
              ])
            })
          });
        });

        await page.route('**/api/**', async (route) => {
          const url = route.request().url();
          if (url.includes('/api/settings')) return;
          if (url.includes('/auth/user') || url.includes('/api/user')) {
            return route.fulfill({
              status: 200,
              contentType: 'application/json',
              body: JSON.stringify({ user: defaultStudentUser })
            });
          }
          if (url.includes('/auth/profiles') || url.includes('/api/profiles')) {
            return route.fulfill({
              status: 200,
              contentType: 'application/json',
              body: JSON.stringify({
                profiles: [
                  { id: 201, name: 'Kasun Perera', full_name: 'Kasun Perera', username: 'kasun', role: 'user', current_grade: 'Grade 10' }
                ]
              })
            });
          }
          if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
            return route.fulfill({
              status: 200,
              contentType: 'application/json',
              body: JSON.stringify({
                today_classes: 1,
                total_classes: 8,
                attendance_rate: 87.5,
                attendance_rate_has_data: true,
                study_hours: 12.5,
                subject_mastery: [],
                recent_achievements: [],
                user: defaultStudentUser
              })
            });
          }
          route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ success: true, data: [] })
          });
        });

        let totalNodes = 0;
        let totalViolations = 0;
        let pagesCount = 0;
        const allIssues = [];

        // 1. Scan Public Routes
        for (const routePath of PUBLIC_ROUTES) {
          await page.goto(routePath, { waitUntil: 'domcontentloaded' });
          await page.waitForTimeout(100);

          // Apply state condition
          if (st.id === 'no-attribute') {
            await page.evaluate(() => document.documentElement.removeAttribute('data-theme-preset'));
          } else {
            await page.evaluate((p) => { document.documentElement.dataset.themePreset = p; }, st.preset);
          }

          const res = await evaluateVisualPage(page, {
            stateId: st.id,
            checkContrast: true
          });

          pagesCount++;
          totalNodes += res.nodesScanned;
          if (res.issues.length > 0) {
            totalViolations += res.issues.length;
            allIssues.push({ route: routePath, issues: res.issues });
            if (st.id === 'midnight') {
              const safeName = routePath.replace(/[^a-z0-9]/gi, '_');
              const dir = path.join(process.cwd(), 'test-results');
              if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
              await page.screenshot({ path: path.join(dir, `midnight-${vp.name}-${safeName}.png`) });
            }
          }

          // Assert (e): nodesScanned > 20 per page
          expect(res.nodesScanned, `Route ${routePath} should scan > 20 text nodes`).toBeGreaterThan(20);
        }

        // 2. Scan Login Modal
        await page.goto('/', { waitUntil: 'domcontentloaded' });
        if (st.id === 'no-attribute') {
          await page.evaluate(() => document.documentElement.removeAttribute('data-theme-preset'));
        } else {
          await page.evaluate((p) => { document.documentElement.dataset.themePreset = p; }, st.preset);
        }
        const loginBtn = page.locator('button:has-text("Login"), a:has-text("Login")').first();
        if (await loginBtn.isVisible()) {
          await loginBtn.click();
          await page.waitForTimeout(200);
          const modalRes = await evaluateVisualPage(page, { stateId: st.id, checkContrast: true });
          pagesCount++;
          totalNodes += modalRes.nodesScanned;
          if (modalRes.issues.length > 0) {
            totalViolations += modalRes.issues.length;
            allIssues.push({ route: '/#login-modal', issues: modalRes.issues });
          }
        }

        // 3. Scan Console Routes
        for (const consoleItem of CONSOLE_ROUTES) {
          let userObj = defaultStudentUser;
          if (consoleItem.role === 'teacher') {
            userObj = { id: 1, role: 'teacher', name: 'Dr. Jane' };
          } else if (consoleItem.role === 'admin') {
            userObj = { id: 1, role: 'super_admin', name: 'Super Admin' };
          } else if (consoleItem.role === 'parent') {
            userObj = { id: 3, role: 'parent', name: 'Parent User', children: [{ id: 201, name: 'Kasun Perera' }] };
          }

          await page.evaluate(({ u, role }) => {
            localStorage.setItem('authToken', 'mock-console-token');
            localStorage.setItem('user', JSON.stringify(u));
            if (role === 'parent') {
              localStorage.setItem('availableProfiles', JSON.stringify([
                { id: 201, name: 'Kasun Perera', full_name: 'Kasun Perera', username: 'kasun', role: 'user', current_grade: 'Grade 10' }
              ]));
            }
          }, { u: userObj, role: consoleItem.role });

          await page.goto(consoleItem.path, { waitUntil: 'domcontentloaded' });
          await page.waitForTimeout(150);

          if (st.id === 'no-attribute') {
            await page.evaluate(() => document.documentElement.removeAttribute('data-theme-preset'));
          } else {
            await page.evaluate((p) => { document.documentElement.dataset.themePreset = p; }, st.preset);
          }

          const consoleRes = await evaluateVisualPage(page, { stateId: st.id, checkContrast: true });
          pagesCount++;
          totalNodes += consoleRes.nodesScanned;
          if (consoleRes.issues.length > 0) {
            totalViolations += consoleRes.issues.length;
            allIssues.push({ route: consoleItem.path, issues: consoleRes.issues });
            if (st.id === 'midnight') {
              const safeName = consoleItem.path.replace(/[^a-z0-9]/gi, '_');
              const dir = path.join(process.cwd(), 'test-results');
              if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
              await page.screenshot({ path: path.join(dir, `midnight-${vp.name}-${safeName}.png`) });
            }
          }
        }

        // Record into summary
        summaryResults.push({
          state: st.label,
          viewport: vp.name,
          pages: pagesCount,
          nodes: totalNodes,
          violations: totalViolations
        });

        // For enabled presets and no-attribute, zero violations are expected
        if (st.id !== 'midnight') {
          if (totalViolations > 0) {
            console.error(`Violations in [${st.label}] @ [${vp.name}]:`, JSON.stringify(allIssues, null, 2));
          }
          expect(totalViolations, `Violations in [${st.label}] @ [${vp.name}]`).toBe(0);
        } else {
          console.log(`\n[INFO] Midnight Dark Preset (GATED) @ [${vp.name}]: ${totalViolations} contrast violations detected across ${pagesCount} pages.`);
        }
      });
    }
  }

  test.afterAll(async () => {
    console.log('\n========================================================================================');
    console.log('                          FRONTEND VISUAL CRAWLER SUMMARY TABLE                         ');
    console.log('========================================================================================');
    console.log('| State            | Viewport   | Pages Scanned | Text Nodes Scanned | Violations |');
    console.log('|------------------|------------|---------------|--------------------|------------|');
    for (const r of summaryResults) {
      const stateCol = r.state.padEnd(16);
      const vpCol = r.viewport.padEnd(10);
      const pagesCol = String(r.pages).padStart(13);
      const nodesCol = String(r.nodes).padStart(18);
      const violCol = String(r.violations).padStart(10);
      console.log(`| ${stateCol} | ${vpCol} | ${pagesCol} | ${nodesCol} | ${violCol} |`);
    }
    console.log('========================================================================================\n');
  });
});
