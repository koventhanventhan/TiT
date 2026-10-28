import { test, expect } from '@playwright/test';

const VIEWPORTS = [
  { width: 360, height: 740, name: 'mobile-360x740' },
  { width: 1280, height: 800, name: 'desktop-1280x800' },
];

const mockBaseUser = {
  id: 301,
  name: 'Test Student Grade 9',
  full_name: 'Test Student Grade 9',
  email: 'student9@test.com',
  role: 'user',
  current_grade: '9', // Purely numeric grade to verify "For Grade 9" header prefix
  medium: 'tamil',
  subjects: ['Mathematics', 'Science'],
  selected_subjects: ['Mathematics', 'Science'],
  registration_status: 'approved',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: true,
  is_deactivated: false,
};

const mockSubjectsPrices = {
  grade_6_to_9: [
    { name: 'Mathematics', price: 1500, medium: 'both' },
    { name: 'Science', price: 1500, medium: 'both' },
    { name: 'English', price: 1000, medium: 'both' },
  ],
};

VIEWPORTS.forEach(({ width, height, name }) => {
  test.describe(`Part C: Mobile Select Chevron & Modal Layout (${name})`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setViewportSize({ width, height });

      await page.addInitScript(({ token, user }) => {
        localStorage.setItem('authToken', token);
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
      }, { token: 'mock-token-part-c', user: mockBaseUser });
    });

    test(`Chevron is visible, Grade 9 header formatted, assistant hidden in modal, no overflow (${name})`, async ({ page }) => {
      await page.route('**/api/**', async (route) => {
        const url = route.request().url();

        if (url.includes('/auth/user')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ user: mockBaseUser }),
          });
        }

        if (url.includes('/subjects/prices')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(mockSubjectsPrices),
          });
        }

        if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              user: mockBaseUser,
              today_classes: 0,
              total_classes: 0,
              pending_assignments: 0,
              attendance_rate: 100,
            }),
          });
        }

        if (url.includes('/student/payment-status')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              is_paid: true,
              amount: 2500,
              year_month: '2026-10',
              payment: { id: 1, amount: 2500, status: 'paid' },
              history: [],
            }),
          });
        }

        if (url.includes('/settings')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              site_name: 'TiT Education',
              public_assistant_enabled: true, // Enable assistant to test suppression during modal
            }),
          });
        }

        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
      });

      await page.goto('/student/settings');

      // 1. Open modal via Edit Subjects & Medium button
      const editBtn = page.getByRole('button', { name: /Edit Subjects & Medium/i });
      await expect(editBtn).toBeVisible({ timeout: 10000 });
      await editBtn.click();

      // 2. Modal is visible
      const modalDialog = page.locator('.shared-modal-dialog');
      await expect(modalDialog).toBeVisible({ timeout: 5000 });

      // 3. Header check: Pure numeric grade "9" should display "For Grade 9"
      const gradeHeader = modalDialog.locator('p').filter({ hasText: /For Grade 9/i });
      await expect(gradeHeader).toBeVisible();

      // 4. Select Medium dropdown check
      const mediumSelect = page.locator('#subject-update-medium-select');
      await expect(mediumSelect).toBeVisible();

      // Verify .t-select class is applied
      await expect(mediumSelect).toHaveClass(/t-select/);

      // Verify computed styles: SVG chevron in background-image, background-position, min-height, font-size
      const styles = await mediumSelect.evaluate((el) => {
        const computed = window.getComputedStyle(el);
        return {
          backgroundImage: computed.backgroundImage,
          backgroundPosition: computed.backgroundPosition,
          backgroundRepeat: computed.backgroundRepeat,
          minHeight: parseFloat(computed.minHeight),
          fontSize: parseFloat(computed.fontSize),
          appearance: computed.appearance || computed.webkitAppearance,
        };
      });

      // SVG chevron must be present in background-image
      expect(styles.backgroundImage).toContain('svg');
      expect(styles.backgroundImage).toContain('polyline'); // Our chevron svg
      expect(styles.backgroundPosition).toMatch(/calc\(100% - 14px\)|right/i);
      expect(styles.minHeight).toBeGreaterThanOrEqual(44);
      expect(styles.fontSize).toBeGreaterThanOrEqual(16); // iOS zoom prevention

      // 5. Verify interaction: select can be tapped/changed
      await mediumSelect.selectOption('english');
      expect(await mediumSelect.inputValue()).toBe('english');

      await mediumSelect.selectOption('tamil');
      expect(await mediumSelect.inputValue()).toBe('tamil');

      // 6. Floating assistant widget suppression inside bottom-sheet/modal
      const assistantWidget = page.locator('#unified-assistant-widget');
      if (await assistantWidget.count() > 0) {
        const isHiddenOrNone = await assistantWidget.evaluate((el) => {
          const comp = window.getComputedStyle(el);
          return comp.display === 'none' || comp.visibility === 'hidden' || parseFloat(comp.opacity) === 0;
        });
        expect(isHiddenOrNone).toBe(true);
      }

      // 7. Verify NO horizontal overflow
      const hasHorizontalScroll = await page.evaluate(() => {
        return document.documentElement.scrollWidth > window.innerWidth;
      });
      expect(hasHorizontalScroll).toBe(false);
    });
  });
});
