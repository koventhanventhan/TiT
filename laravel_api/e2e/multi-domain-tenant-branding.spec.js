import { test, expect } from '@playwright/test';

test.use({
  launchOptions: {
    args: ['--host-resolver-rules=MAP a.test 127.0.0.1, MAP b.test 127.0.0.1'],
  },
});

test.describe('Multi-Domain Tenant Branding & Isolation', () => {
  test('Institute A (a.test) loads branding, canonical, and og:url dynamically', async ({ page }) => {
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

    await page.goto('http://a.test:4173/');

    // Verify dynamic page title
    await expect(page).toHaveTitle('Alpha Academy');

    // Verify theme preset applied
    const preset = await page.evaluate(() => document.documentElement.dataset.themePreset);
    expect(preset).toBe('emerald');

    // Verify canonical href starts with origin
    const canonical = await page.evaluate(() => document.querySelector('link[rel="canonical"]')?.getAttribute('href') || '');
    expect(canonical.startsWith('http://a.test:4173')).toBe(true);

    // Verify og:url starts with origin
    const ogUrl = await page.evaluate(() => document.querySelector('meta[property="og:url"]')?.getAttribute('content') || '');
    expect(ogUrl.startsWith('http://a.test:4173')).toBe(true);

    // Verify cached_site_settings in localStorage matches host
    const cached = await page.evaluate(() => {
      const raw = localStorage.getItem('cached_site_settings');
      return raw ? JSON.parse(raw) : null;
    });
    expect(cached).not.toBeNull();
    expect(cached.settings.site_name).toBe('Alpha Academy');
  });

  test('Institute B (b.test) loads separate branding, canonical, og:url and no titjaffna leak', async ({ page }) => {
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

    await page.goto('http://b.test:4173/');

    // Stale foreign domain cache must NOT be used
    await expect(page).toHaveTitle('Beta Tuition Centre');

    const preset = await page.evaluate(() => document.documentElement.dataset.themePreset);
    expect(preset).toBe('royal');

    // Verify canonical href starts with origin
    const canonical = await page.evaluate(() => document.querySelector('link[rel="canonical"]')?.getAttribute('href') || '');
    expect(canonical.startsWith('http://b.test:4173')).toBe(true);

    // Verify og:url starts with origin
    const ogUrl = await page.evaluate(() => document.querySelector('meta[property="og:url"]')?.getAttribute('content') || '');
    expect(ogUrl.startsWith('http://b.test:4173')).toBe(true);

    // Verify no 'titjaffna' in document.documentElement.outerHTML on host b
    const html = await page.evaluate(() => document.documentElement.outerHTML);
    expect(html.toLowerCase()).not.toContain('titjaffna');
  });
});
