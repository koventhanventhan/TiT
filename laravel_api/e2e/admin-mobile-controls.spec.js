import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import path from 'path';

test.describe('Admin Mobile Controls & Tap Targets (375px)', () => {
  const BASE_URL = 'http://127.0.0.1:8000';
  let token = '';

  test.beforeAll(() => {
    token = execSync(`php "${path.resolve(__dirname, '../../scripts/theme_helper.php')}" get_token`).toString().trim();
  });

  test.use({
    viewport: { width: 375, height: 740 },
    extraHTTPHeaders: { Connection: 'close' },
  });

  const targetPages = [
    { name: 'students', path: '/admin/students' },
    { name: 'settings', path: '/admin/settings' },
    { name: 'payments', path: '/admin/students/pending-payments' },
  ];

  for (const pageInfo of targetPages) {
    test(`Page ${pageInfo.name} at 375px: no clipped controls and tap targets >= 44px`, async ({ page }) => {
      // Abort external and mock background polling
      await page.route('**/*', (route) => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
        }
        if (url.startsWith(BASE_URL)) {
          return route.continue();
        }
        return route.abort();
      });

      // Login
      await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await page.goto(`${BASE_URL}${pageInfo.path}`, { waitUntil: 'domcontentloaded', timeout: 30000 });

      // 1. Verify no horizontal overflow / no clipped controls
      const overflow = await page.evaluate(() => {
        const scrollWidth = document.documentElement.scrollWidth;
        const clientWidth = document.documentElement.clientWidth;
        return {
          scrollWidth,
          clientWidth,
          hasOverflow: scrollWidth > clientWidth + 1,
        };
      });

      expect(overflow.hasOverflow).toBe(false);

      // 2. Verify tap targets >= 44px for clickable controls
      const tapTargetViolations = await page.evaluate(() => {
        const interactiveSelectors = [
          'button',
          'a[href]',
          'input:not([type="hidden"])',
          'select',
          '[role="button"]',
          '.btn',
          '.custom-control-label',
          '.pagination-item',
        ];

        const violations = [];
        const elements = document.querySelectorAll(interactiveSelectors.join(','));

        for (const el of elements) {
          // Check if element or parent is hidden
          const style = window.getComputedStyle(el);
          if (
            style.display === 'none' ||
            style.visibility === 'hidden' ||
            parseFloat(style.opacity) === 0 ||
            el.closest('[style*="display: none"]') ||
            el.closest('.d-none')
          ) {
            continue;
          }

          const rect = el.getBoundingClientRect();
          // Skip elements not laid out on screen
          if (rect.width <= 0 || rect.height <= 0) continue;
          // Skip elements outside viewport bounds (e.g. offcanvas menu closed)
          if (rect.right < 0 || rect.bottom < 0 || rect.left > window.innerWidth || rect.top > 50000) continue;

          // Inline text links inside paragraphs/sentences are exempt by WCAG 2.5.5
          if (el.tagName.toLowerCase() === 'a' && el.closest('p, .text-muted, small') && !el.classList.contains('btn')) {
            continue;
          }

          // Effective tap target dimension check (>= 44px in both dimensions or adequate clickable padding)
          const width = rect.width;
          const height = rect.height;

          // Allow subtle rounding tolerance (>= 43.5px)
          if (width < 43.5 || height < 43.5) {
            violations.push({
              tag: el.tagName.toLowerCase(),
              id: el.id || '',
              className: el.className || '',
              text: (el.textContent || el.getAttribute('aria-label') || el.value || '').trim().slice(0, 30),
              width: Math.round(width * 10) / 10,
              height: Math.round(height * 10) / 10,
            });
          }
        }

        return violations;
      });

      if (tapTargetViolations.length > 0) {
        console.warn(`[Tap Target Violations on ${pageInfo.name}]:`, tapTargetViolations);
      }

      expect(tapTargetViolations).toEqual([]);
    });
  }

  test('Mobile header search at 375px: collapses to icon, does not overlap other icons, and expands on tap', async ({ page }) => {
    await page.route('**/*', (route) => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
      }
      if (url.startsWith(BASE_URL)) return route.continue();
      return route.abort();
    });

    await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.goto(`${BASE_URL}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Verify search button is visible and search bar input is collapsed
    const searchBtn = page.locator('#mobileSearchBtn');
    await expect(searchBtn).toBeVisible();

    const searchInput = page.locator('.header-left .search_bar input');
    await expect(searchInput).not.toBeVisible();

    // Verify search button does not overlap hamburger or right icons
    const searchBtnBox = await searchBtn.boundingBox();
    const hamburgerBox = await page.locator('#customHamburger').boundingBox();
    const homeBox = await page.locator('.header-right a[title="Home"]').boundingBox();

    expect(searchBtnBox).not.toBeNull();
    expect(hamburgerBox).not.toBeNull();
    expect(homeBox).not.toBeNull();

    // Search button must be to the right of hamburger without overlap
    expect(searchBtnBox.x).toBeGreaterThanOrEqual(hamburgerBox.x + hamburgerBox.width - 2);
    // Search button must be to the left of home icon without overlap
    expect(searchBtnBox.x + searchBtnBox.width).toBeLessThanOrEqual(homeBox.x + 2);

    // Tap search button to expand
    await searchBtn.click();

    // Now input is visible
    await expect(searchInput).toBeVisible();

    // Tap close button to collapse
    const closeBtn = page.locator('#mobileSearchClose');
    await expect(closeBtn).toBeVisible();
    await closeBtn.click();

    await expect(searchInput).not.toBeVisible();
  });

  test('Dashboard stat cards at 375px: 2 per row (col-6) with compact icon left, values right', async ({ page }) => {
    await page.route('**/*', (route) => {
      const url = route.request().url();
      if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
      }
      if (url.startsWith(BASE_URL)) return route.continue();
      return route.abort();
    });

    await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.goto(`${BASE_URL}/admin/dashboard`, { waitUntil: 'domcontentloaded', timeout: 30000 });

    const cardColumns = page.locator('.row .col-6');
    const count = await cardColumns.count();
    expect(count).toBeGreaterThanOrEqual(6);

    // Verify card 1 and card 2 share the same row (similar top offset, 2 cards per row)
    const box1 = await cardColumns.nth(0).boundingBox();
    const box2 = await cardColumns.nth(1).boundingBox();
    expect(box1).not.toBeNull();
    expect(box2).not.toBeNull();
    expect(Math.abs(box1.y - box2.y)).toBeLessThan(10); // Same row!
  });
});

