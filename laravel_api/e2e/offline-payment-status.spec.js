import { test, expect } from '@playwright/test';

const VIEWPORTS = [
  { width: 360, height: 740, name: 'mobile' },
  { width: 1280, height: 800, name: 'desktop' },
];

const mockStudentUser = {
  id: 201,
  name: 'Koventhan Venthan',
  full_name: 'Koventhan Venthan',
  email: 'koventhan@student.com',
  role: 'user',
  current_grade: 'Grade 8',
  medium: 'tamil',
  subjects: ['Mathematics', 'Science'],
  selected_subjects: ['Mathematics', 'Science'],
  registration_status: 'approved',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: false,
  is_deactivated: false,
};

VIEWPORTS.forEach(({ width, height, name }) => {
  test.describe(`Offline Payment Status & Flow (${name}: ${width}x${height})`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setViewportSize({ width, height });

      await page.addInitScript(({ token, user }) => {
        localStorage.setItem('authToken', token);
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
      }, { token: 'mock-student-token-offline-test', user: mockStudentUser });
    });

    test(`displays pending status card when offline payment is pending admin confirmation (${name})`, async ({ page }) => {
      await page.route('**/api/**', async (route) => {
        const url = route.request().url();

        if (url.includes('/auth/user')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ user: mockStudentUser }),
          });
        }

        if (url.includes('/student/payment-status')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              is_paid: false,
              amount: 3000,
              year_month: '2026-10',
              payment: {
                id: 55,
                status: 'pending',
                payment_method: 'offline',
                amount: 3000,
                created_at: '2026-10-25T08:30:00.000000Z',
              },
              history: [],
            }),
          });
        }

        if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ today_classes: 0, total_classes: 0, pending_assignments: 0, attendance_rate: 100 }),
          });
        }

        if (url.includes('/zoom-classes') || url.includes('/upcoming-schedules')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
        }

        if (url.includes('/announcements/active')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
        }

        if (url.includes('/settings')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ site_name: 'TiT Education', public_assistant_enabled: false }),
          });
        }

        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
      });

      await page.goto('/student/dashboard');

      const pendingCard = page.locator('[data-testid="status-card-pending"]');
      await expect(pendingCard).toBeVisible({ timeout: 10000 });
      await expect(pendingCard).toContainText('Request received on 2026-10-25');
      await expect(pendingCard).toContainText('waiting for admin confirmation – Rs 3000');

      // Persistence check: reload the page and verify card is still displayed
      await page.reload();
      const reloadedPendingCard = page.locator('[data-testid="status-card-pending"]');
      await expect(reloadedPendingCard).toBeVisible({ timeout: 10000 });
      await expect(reloadedPendingCard).toContainText('Request received on 2026-10-25');
      await expect(reloadedPendingCard).toContainText('waiting for admin confirmation – Rs 3000');
    });

    test(`displays cancelled status card when payment is rejected/cancelled (${name})`, async ({ page }) => {
      await page.route('**/api/**', async (route) => {
        const url = route.request().url();

        if (url.includes('/auth/user')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ user: mockStudentUser }),
          });
        }

        if (url.includes('/student/payment-status')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              is_paid: false,
              amount: 3000,
              year_month: '2026-10',
              payment: {
                id: 56,
                status: 'cancelled',
                payment_method: 'offline',
                amount: 3000,
                created_at: '2026-10-25T08:30:00.000000Z',
              },
              history: [],
            }),
          });
        }

        if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ today_classes: 0, total_classes: 0, pending_assignments: 0, attendance_rate: 100 }),
          });
        }

        if (url.includes('/zoom-classes') || url.includes('/upcoming-schedules')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
        }

        if (url.includes('/announcements/active')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
        }

        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
      });

      await page.goto('/student/dashboard');

      const cancelledCard = page.locator('[data-testid="status-card-cancelled"]');
      await expect(cancelledCard).toBeVisible({ timeout: 10000 });
      await expect(cancelledCard).toContainText('Request cancelled – please contact the institute or submit again');
    });

    test(`prevents double-submit on "I WILL PAY OFFLINE" button with 1500ms guard (${name})`, async ({ page }) => {
      let offlinePaymentCallCount = 0;
      let paymentState = {
        is_paid: false,
        amount: 3000,
        year_month: '2026-10',
        payment: null,
      };

      await page.route('**/api/**', async (route) => {
        const url = route.request().url();
        const method = route.request().method();

        if (url.includes('/auth/user')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ user: mockStudentUser }),
          });
        }

        if (url.includes('/student/payment-status')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(paymentState),
          });
        }

        if (url.includes('/student/pay-monthly-offline') && method === 'POST') {
          offlinePaymentCallCount++;
          // Simulate server network latency
          await new Promise((r) => setTimeout(r, 400));
          paymentState.payment = {
            id: 99,
            status: 'pending',
            payment_method: 'offline',
            amount: 3000,
            created_at: '2026-10-25T09:00:00.000000Z',
          };
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'Offline payment request received.',
              payment: paymentState.payment,
            }),
          });
        }

        if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ today_classes: 0, total_classes: 0, pending_assignments: 0, attendance_rate: 100 }),
          });
        }

        return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
      });

      await page.goto('/student/dashboard');

      const payOfflineBtn = page.locator('button.pay-offline-btn');
      await expect(payOfflineBtn).toBeVisible({ timeout: 10000 });
      await expect(payOfflineBtn).toBeEnabled();

      // Click the offline button
      await payOfflineBtn.click();

      // Immediately verify button is disabled and shows submitting state
      await expect(payOfflineBtn).toBeDisabled();

      // Attempt second rapid click while button is in submitting / 1500ms debounce state
      await payOfflineBtn.click({ force: true }).catch(() => {});

      // Wait for pending card to appear
      const pendingCard = page.locator('[data-testid="status-card-pending"]');
      await expect(pendingCard).toBeVisible({ timeout: 10000 });
      await expect(pendingCard).toContainText('Request received on 2026-10-25');
      await expect(pendingCard).toContainText('waiting for admin confirmation – Rs 3000');

      // Verify exactly ONE request was dispatched to backend
      expect(offlinePaymentCallCount).toBe(1);
    });
  });
});
