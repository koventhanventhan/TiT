import { test, expect } from '@playwright/test';

test.describe('Multi-Domain Tenant Branding & Isolation', () => {
  test('Institute A (a.test) loads branding and title dynamically', async ({ page, context }) => {
    // Intercept settings for Institute A
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          site_name: 'Alpha Academy',
          theme_preset: 'emerald',
          footer_phone: '+94 77 111 2222',
        }),
      });
    });

    // Mock other public endpoints
    await page.route('**/api/classes**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });
    await page.route('**/api/teachers**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });
    await page.route('**/api/announcements**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });

    await page.goto('/');

    // Verify dynamic page title
    await expect(page).toHaveTitle('Alpha Academy');

    // Verify theme preset applied
    const preset = await page.evaluate(() => document.documentElement.dataset.themePreset);
    expect(preset).toBe('emerald');

    // Verify cached_site_settings in localStorage matches host
    const cached = await page.evaluate(() => {
      const raw = localStorage.getItem('cached_site_settings');
      return raw ? JSON.parse(raw) : null;
    });
    expect(cached).not.toBeNull();
    expect(cached.settings.site_name).toBe('Alpha Academy');
  });

  test('Institute B (b.test) loads separate branding and does not leak cache', async ({ page }) => {
    // Intercept settings for Institute B
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          site_name: 'Beta Tuition Centre',
          theme_preset: 'royal',
          footer_phone: '+94 77 333 4444',
        }),
      });
    });

    await page.route('**/api/classes**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });
    await page.route('**/api/teachers**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });
    await page.route('**/api/announcements**', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    });

    // Simulate stale cached settings from a different host 'other-domain.lk'
    await page.addInitScript(() => {
      localStorage.setItem('cached_site_settings', JSON.stringify({
        host: 'other-domain.lk',
        settings: { site_name: 'Stale Leaked Name' }
      }));
    });

    await page.goto('/');

    // Stale foreign domain cache must NOT be used
    await expect(page).toHaveTitle('Beta Tuition Centre');

    const preset = await page.evaluate(() => document.documentElement.dataset.themePreset);
    expect(preset).toBe('royal');
  });
});

