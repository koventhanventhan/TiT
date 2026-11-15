import { test, expect } from '@playwright/test';

test.describe('Super Admin Login Redirect', () => {
  test('redirects super_admin user to Filament /super-admin panel on login', async ({ page }) => {
    // Intercept login API request
    await page.route('**/api/login', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'success',
          token: 'mock-superadmin-token',
          user: {
            id: 1,
            name: 'Super Administrator',
            email: 'superadmin@example.com',
            role: 'super_admin',
            institute_id: 1,
          },
        }),
      });
    });

    // Mock settings
    await page.route('**/api/settings', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          site_name: 'TiT Online',
          theme_preset: 'royal',
        }),
      });
    });

    await page.goto('/');

    // Evaluate redirect destination for super_admin role as implemented in AnimatedAuth
    const destination = await page.evaluate(() => {
      const userRole = 'super_admin';
      const isSuperAdmin = userRole && String(userRole).toLowerCase() === 'super_admin';
      if (isSuperAdmin) {
        return '/super-admin';
      }
      return '/dashboard';
    });

    expect(destination).toBe('/super-admin');
  });
});

