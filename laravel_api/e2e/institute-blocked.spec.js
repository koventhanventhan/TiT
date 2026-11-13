import { test, expect } from '@playwright/test';

test.describe('Institute Blocked Notice & Interceptor', () => {
  test('402 institute_expired displays InstituteBlockedNotice, shows institute name and contact, and sets noindex meta', async ({ page }) => {
    // Intercept /api/settings to return 402 institute_expired
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 402,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Subscription has ended',
          status: 'expired',
          code: 'institute_expired',
          institute_name: 'Apex Academy Test',
          institute_logo_url: null,
          contact: {
            email: 'support@platform.lk',
            phone: '0771234567',
            whatsapp: '+94771234567',
          },
        }),
      });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });

    // Assert the notice card with role="alert" is visible
    const alert = page.locator('div[role="alert"]');
    await expect(alert).toBeVisible({ timeout: 10000 });

    // Assert title and texts
    await expect(page.locator('.institute-blocked-title')).toHaveText('Subscription has ended');
    await expect(page.locator('.institute-blocked-name')).toHaveText('Apex Academy Test');
    await expect(page.locator('.institute-blocked-desc')).toContainText('The subscription for this institute has ended');

    // Assert contact links
    await expect(page.locator('a[href="mailto:support@platform.lk"]')).toBeVisible();
    await expect(page.locator('a[href="tel:0771234567"]')).toBeVisible();
    await expect(page.locator('a[href*="wa.me/94771234567"]')).toBeVisible();

    // Assert noindex robots meta tag exists
    const robotsMeta = page.locator('meta[name="robots"]');
    await expect(robotsMeta).toHaveAttribute('content', 'noindex, nofollow');
  });

  test('403 institute_suspended displays suspended title and wording', async ({ page }) => {
    // Intercept /api/settings to return 403 institute_suspended
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 403,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Institute access is suspended',
          status: 'suspended',
          code: 'institute_suspended',
          institute_name: 'Suspended Academy',
          institute_logo_url: null,
          contact: {},
        }),
      });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const alert = page.locator('div[role="alert"]');
    await expect(alert).toBeVisible({ timeout: 10000 });

    await expect(page.locator('.institute-blocked-title')).toHaveText('This institute is temporarily unavailable');
    await expect(page.locator('.institute-blocked-desc')).toContainText('This institute is temporarily unavailable. Public access is currently paused.');
  });

  test('Plain 403 (e.g. context mismatch or forbidden) does not trigger blocked notice', async ({ page }) => {
    // Intercept /api/settings to return 403 context mismatch without code
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 403,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Institute context mismatch',
        }),
      });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });

    // Assert that the blocked notice does not render
    const alert = page.locator('div[role="alert"]');
    await expect(alert).not.toBeVisible({ timeout: 5000 });
  });
});
