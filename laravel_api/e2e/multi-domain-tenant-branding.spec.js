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

  test('Two-State Smoke: theme attribute present renders valid gradients and visible text', async ({ page }) => {
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ site_name: 'Online Education Platform', theme_preset: 'royal' })
      });
    });
    await page.route('**/api/**', async (route) => {
      if (route.request().url().includes('/api/settings')) return;
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }) });
    });

    await page.goto('/');
    await page.waitForTimeout(200);

    // State 1: Attribute present
    const stateAttr = await page.evaluate(() => document.documentElement.dataset.themePreset);
    expect(stateAttr).toBe('royal');

    const varsPresent = await page.evaluate(() => {
      const style = window.getComputedStyle(document.documentElement);
      return {
        grad1: style.getPropertyValue('--gradient-1').trim(),
        gradHero: style.getPropertyValue('--gradient-hero').trim(),
        shadowGlow: style.getPropertyValue('--shadow-glow').trim(),
      };
    });

    expect(varsPresent.grad1.length).toBeGreaterThan(0);
    expect(varsPresent.grad1).not.toBe('var(--gradient-1)');
    expect(varsPresent.gradHero.length).toBeGreaterThan(0);
    expect(varsPresent.gradHero).not.toBe('var(--gradient-hero)');
    expect(varsPresent.shadowGlow.length).toBeGreaterThan(0);
    expect(varsPresent.shadowGlow).not.toBe('var(--shadow-glow)');
  });

  test('Two-State Smoke: theme attribute absent falls back cleanly to royal without invisible text', async ({ page }) => {
    await page.route('**/api/settings', async (route) => {
      // Simulate failed or pending settings
      await route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ error: 'Server error' }) });
    });
    await page.route('**/api/**', async (route) => {
      if (route.request().url().includes('/api/settings')) return;
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }) });
    });

    await page.goto('/');
    await page.waitForTimeout(200);

    // Explicitly remove the attribute to test resilient :root fallback
    await page.evaluate(() => {
      document.documentElement.removeAttribute('data-theme-preset');
    });

    const varsAbsent = await page.evaluate(() => {
      const style = window.getComputedStyle(document.documentElement);
      return {
        grad1: style.getPropertyValue('--gradient-1').trim(),
        gradHero: style.getPropertyValue('--gradient-hero').trim(),
        shadowGlow: style.getPropertyValue('--shadow-glow').trim(),
      };
    });

    // None of these variables should be empty or circular
    expect(varsAbsent.grad1.length).toBeGreaterThan(0);
    expect(varsAbsent.grad1).not.toBe('var(--gradient-1)');
    expect(varsAbsent.gradHero.length).toBeGreaterThan(0);
    expect(varsAbsent.gradHero).not.toBe('var(--gradient-hero)');
    expect(varsAbsent.shadowGlow.length).toBeGreaterThan(0);
    expect(varsAbsent.shadowGlow).not.toBe('var(--shadow-glow)');

    // Verify key brand elements are visible
    const logoVisible = await page.locator('.logo, .logo-text, .brand-logo').first().isVisible();
    expect(logoVisible).toBe(true);

    // Check no text has transparent fill without valid gradient
    const hasInvisibleText = await page.evaluate(() => {
      const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
      let node;
      while ((node = walker.nextNode())) {
        const el = node.parentElement;
        if (!el) continue;
        const s = window.getComputedStyle(el);
        const fill = s.webkitTextFillColor;
        const clip = s.webkitBackgroundClip || s.backgroundClip || '';
        const bgImg = s.backgroundImage;
        if (fill === 'transparent' && (!clip.includes('text') || !bgImg || bgImg === 'none')) {
          return true;
        }
      }
      return false;
    });

    expect(hasInvisibleText).toBe(false);
  });
});
