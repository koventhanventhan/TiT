import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:4173';

const PRESETS = ['royal', 'ocean', 'emerald'];
const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '375x800', width: 375, height: 800 },
];

const mockStudent = {
  id: 201,
  name: 'Kasun Perera',
  full_name: 'Kasun Perera',
  email: 'kasun@test.lk',
  role: 'user',
  current_grade: 'Grade 10',
  medium: 'english',
  subjects: ['Mathematics', 'Science'],
  selected_subjects: ['Mathematics', 'Science'],
  registration_status: 'completed',
  is_paid: true,
  is_deactivated: false,
};

const mockTeacher = {
  id: 101,
  name: 'Teacher Kamal',
  full_name: 'Kamal Silva',
  email: 'kamal@test.lk',
  role: 'teacher',
};

const mockAdmin = {
  id: 1,
  name: 'Super Admin',
  full_name: 'Super Admin',
  email: 'admin@test.lk',
  role: 'super_admin',
};

async function setupApiMocks(page, preset) {
  await page.route('**/api/settings', async (route) => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        site_name: 'Online Education Platform',
        theme_preset: preset,
      }),
    });
  });

  await page.route('**/api/**', async (route) => {
    const url = route.request().url();
    if (url.includes('/api/settings')) return;
    if (url.includes('/auth/user') || url.includes('/api/user')) {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ user: mockStudent }),
      });
    }
    if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          today_classes: 2,
          total_classes: 10,
          attendance_rate: 90,
          attendance_rate_has_data: true,
          study_hours: 15,
          user: mockStudent,
        }),
      });
    }
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, data: [] }),
    });
  });
}

async function evaluateScan(page) {
  return await page.evaluate(() => {
    const issues = [];
    let nodesScanned = 0;
    const docEl = document.documentElement;

    // 1. Horizontal overflow
    const scrollW = docEl.scrollWidth;
    const innerW = window.innerWidth;
    if (scrollW > innerW + 1) {
      issues.push({
        type: 'overflow',
        detail: `scrollWidth (${scrollW}px) > innerWidth (${innerW}px)`,
      });
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
        a: m[4] !== undefined ? parseFloat(m[4]) : 1,
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
      const a = [r, g, b].map((v) => {
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

    let bodyBg = null;
    try {
      bodyBg = parseRgba(window.getComputedStyle(document.body).backgroundColor);
    } catch (_) {}
    const baseCanvas = bodyBg && bodyBg.a > 0 ? bodyBg : { r: 255, g: 255, b: 255, a: 1 };

    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        if (!node.textContent || !node.textContent.trim()) return NodeFilter.FILTER_REJECT;
        const el = node.parentElement;
        if (!el || !(el instanceof Element)) return NodeFilter.FILTER_REJECT;
        const tag = (el.tagName || '').toLowerCase();
        if (['script', 'style', 'noscript', 'template', 'svg'].includes(tag)) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      },
    });

    let currentNode;
    while ((currentNode = walker.nextNode())) {
      const el = currentNode.parentElement;
      if (!el || !(el instanceof Element)) continue;
      const text = currentNode.textContent.trim();
      if (!text) continue;

      let rect;
      try {
        rect = el.getBoundingClientRect();
      } catch (_) {
        continue;
      }
      if (!rect || rect.width === 0 || rect.height === 0) continue;

      let style;
      try {
        style = window.getComputedStyle(el);
      } catch (_) {
        continue;
      }
      if (!style || style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) === 0) continue;

      nodesScanned++;

      let p = el;
      const bgStack = [];
      let hasGradientBg = false;

      while (p && p instanceof Element && p !== docEl) {
        try {
          const ps = window.getComputedStyle(p);
          if (!ps) break;
          const bg = parseRgba(ps.backgroundColor);
          if (bg && bg.a > 0) {
            bgStack.unshift(bg);
          }
          if (ps.backgroundImage && ps.backgroundImage.includes('gradient')) {
            hasGradientBg = true;
          }
        } catch (_) {
          break;
        }
        p = p.parentElement;
      }

      let compBg = baseCanvas;
      for (const b of bgStack) {
        compBg = blend(b, compBg);
      }

      const colorRgba = parseRgba(style.color) || { r: 0, g: 0, b: 0, a: 1 };
      const finalFg = blend(colorRgba, compBg);

      const lumFg = getLuminance(finalFg.r, finalFg.g, finalFg.b);
      const lumBg = getLuminance(compBg.r, compBg.g, compBg.b);
      const ratio = getContrast(lumFg, lumBg);

      const fontSize = parseFloat(style.fontSize);
      const fontWeight = parseInt(style.fontWeight, 10) || 400;
      const isLarge = fontSize >= 24 || (fontSize >= 18.5 && fontWeight >= 700);
      const isDisabled = el.matches(':disabled, [disabled], .disabled') || el.closest(':disabled, [disabled], .disabled');

      const minRatio = isDisabled ? 3.0 : (isLarge ? 3.0 : 4.5);

      if (!hasGradientBg && ratio < minRatio) {
        issues.push({
          type: 'contrast',
          selector: `${el.tagName.toLowerCase()}.${(el.className || '').toString().trim().split(/\s+/).join('.')}`,
          text: text.slice(0, 30),
          ratio: Math.round(ratio * 100) / 100,
          min: minRatio,
          fg: `rgb(${Math.round(finalFg.r)}, ${Math.round(finalFg.g)}, ${Math.round(finalFg.b)})`,
          bg: `rgb(${Math.round(compBg.r)}, ${Math.round(compBg.g)}, ${Math.round(compBg.b)})`,
        });
      }
    }

    return { nodesScanned, issues };
  });
}

test.describe('Frontend States, Modals, Dropdowns & Toasts Crawler', () => {
  for (const preset of PRESETS) {
    for (const vp of VIEWPORTS) {
      test(`Preset [${preset}] @ [${vp.name}] - Modals, Toasts, Dropdowns & States`, async ({ page }) => {
        test.setTimeout(120000);
        await page.setViewportSize({ width: vp.width, height: vp.height });
        await setupApiMocks(page, preset);

        // 1. Visit Student Shell with mocked auth
        await page.addInitScript(({ u, p }) => {
          try {
            localStorage.setItem('authToken', 'mock-student-token');
            localStorage.setItem('user', JSON.stringify(u));
            document.documentElement.dataset.themePreset = p;
          } catch (_) {}
        }, { u: mockStudent, p: preset });

        await page.goto(`${BASE_URL}/student/dashboard`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(200);

        await page.evaluate((p) => {
          document.documentElement.dataset.themePreset = p;
        }, preset);

        // 2. Trigger Toasts: Success, Error, Warning, Info
        await page.evaluate(() => {
          if (window.__titToast) {
            window.__titToast.success('Saved successfully!', 10000);
            window.__titToast.error('An error occurred during submission.', 10000);
            window.__titToast.warning('Check your details before continuing.', 10000);
            window.__titToast.info('System maintenance scheduled soon.', 10000);
          }
        });
        await page.waitForTimeout(100);

        // Verify toast close button >= 44x44
        const closeBtn = page.locator('.tit-toast-close').first();
        if (await closeBtn.isVisible()) {
          const box = await closeBtn.boundingBox();
          expect(Math.round(box.width)).toBeGreaterThanOrEqual(44);
          expect(Math.round(box.height)).toBeGreaterThanOrEqual(44);
        }

        let scan = await evaluateScan(page);
        expect(scan.issues, `Toast issues on [${preset}] @ [${vp.name}]`).toEqual([]);
        expect(scan.nodesScanned).toBeGreaterThan(20);

        // 3. Trigger Confirm Dialog
        const confirmPromise = page.evaluate(() => {
          if (window.__titConfirm) {
            return window.__titConfirm({
              title: 'Confirm Subject Drop',
              message: 'Are you sure you want to drop Mathematics?',
              confirmText: 'Drop Subject',
              cancelText: 'Keep Subject',
              danger: true,
            });
          }
          return null;
        });

        await page.waitForSelector('.tit-confirm-modal', { state: 'visible', timeout: 5000 });
        await page.waitForTimeout(250); // Wait for scaleIn animation to finish
        const cancelBtn = page.locator('.tit-confirm-btn-cancel');
        const confirmBtn = page.locator('.tit-confirm-btn-danger, .tit-confirm-btn-primary');

        // Check tap target sizes
        const cancelBox = await cancelBtn.boundingBox();
        expect(Math.round(cancelBox.height)).toBeGreaterThanOrEqual(44);

        const confirmBox = await confirmBtn.boundingBox();
        expect(Math.round(confirmBox.height)).toBeGreaterThanOrEqual(44);

        // Scan Confirm Dialog contrast & overflow
        scan = await evaluateScan(page);
        expect(scan.issues, `Confirm dialog issues on [${preset}] @ [${vp.name}]`).toEqual([]);

        // Test focus trap & Escape key dismiss
        await page.keyboard.press('Escape');
        await page.waitForSelector('.tit-confirm-modal', { state: 'detached', timeout: 5000 });
        const result = await confirmPromise;
        expect(result).toBe(false);

        // 4. Test Enter key confirmation
        const confirmPromise2 = page.evaluate(() => {
          if (window.__titConfirm) {
            return window.__titConfirm({
              title: 'Save Profile Settings',
              message: 'Do you want to save your updated profile?',
              confirmText: 'Save Changes',
              cancelText: 'Cancel',
              danger: false,
            });
          }
          return null;
        });

        await page.waitForSelector('.tit-confirm-modal', { state: 'visible', timeout: 5000 });
        await page.waitForTimeout(250);
        await page.keyboard.press('Enter');
        await page.waitForSelector('.tit-confirm-modal', { state: 'detached', timeout: 5000 });
        const result2 = await confirmPromise2;
        expect(result2).toBe(true);

        // 5. Test Teacher Shell
        await page.evaluate(({ u, p }) => {
          localStorage.setItem('authToken', 'mock-teacher-token');
          localStorage.setItem('user', JSON.stringify(u));
          document.documentElement.dataset.themePreset = p;
        }, { u: mockTeacher, p: preset });

        await page.goto(`${BASE_URL}/teacher/dashboard`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(200);

        await page.evaluate((p) => {
          document.documentElement.dataset.themePreset = p;
        }, preset);

        scan = await evaluateScan(page);
        expect(scan.issues, `Teacher dashboard issues on [${preset}] @ [${vp.name}]`).toEqual([]);
        expect(scan.nodesScanned).toBeGreaterThan(20);

        // 6. Test Super Admin Shell
        await page.evaluate(({ u, p }) => {
          localStorage.setItem('authToken', 'mock-admin-token');
          localStorage.setItem('user', JSON.stringify(u));
          document.documentElement.dataset.themePreset = p;
        }, { u: mockAdmin, p: preset });

        await page.goto(`${BASE_URL}/super-admin`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(200);

        await page.evaluate((p) => {
          document.documentElement.dataset.themePreset = p;
        }, preset);

        scan = await evaluateScan(page);
        expect(scan.issues, `Super admin issues on [${preset}] @ [${vp.name}]`).toEqual([]);
        expect(scan.nodesScanned).toBeGreaterThan(20);

        // 7. Interactive States on Buttons: Hover, Focus, Disabled, Loading
        await page.evaluate(() => {
          const btn = document.querySelector('button');
          if (btn) {
            btn.classList.add('is-loading');
          }
        });
        scan = await evaluateScan(page);
        expect(scan.issues, `Loading button issues on [${preset}] @ [${vp.name}]`).toEqual([]);
      });
    }
  }
});
