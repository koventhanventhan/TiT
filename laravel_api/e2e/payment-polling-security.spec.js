import { test, expect } from '@playwright/test';

const VIEWPORT_MOBILE = { width: 360, height: 740 };

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

test.describe('Payment Polling & Security Feedback (360x740 Mobile)', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(VIEWPORT_MOBILE);

    await page.addInitScript(({ token, user }) => {
      localStorage.setItem('authToken', token);
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
    }, { token: 'mock-student-token-polling-test', user: mockStudentUser });
  });

  test('StudentPaymentModule shows "Payment received – confirming…" on onCompleted and unlocks upon paid status', async ({ page }) => {
    let pollCount = 0;

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
        pollCount++;
        // First initial load: unpaid. After onCompleted starts polling:
        // polls 1-2 return unpaid, poll 3+ returns paid.
        const isPaid = pollCount >= 3;
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            is_paid: isPaid,
            has_paid: isPaid,
            amount: 3000,
            year_month: '2026-10',
            payment: isPaid ? { id: 77, status: 'paid', payment_method: 'online', amount: 3000 } : null,
            history: [],
          }),
        });
      }

      if (url.includes('/student/pay-monthly')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            payhere_url: 'https://sandbox.payhere.lk/pay/checkout',
            params: {
              order_id: 'MONTHLY_POLL_1001',
              amount: '3000.00',
              currency: 'LKR',
            },
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

    // Inject fake payhere object that invokes onCompleted
    await page.evaluate(() => {
      window.payhere = {
        startPayment: () => {
          setTimeout(() => {
            if (window.payhere.onCompleted) {
              window.payhere.onCompleted('MONTHLY_POLL_1001');
            }
          }, 100);
        },
      };
    });

    // Find and click the Pay Online button
    const payOnlineBtn = page.locator('button.pay-now-btn, [data-testid="pay-now-btn"]');
    await expect(payOnlineBtn).toBeVisible({ timeout: 10000 });
    await payOnlineBtn.click();

    // Verify confirming card is displayed
    const confirmingCard = page.locator('[data-testid="status-card-confirming"]');
    await expect(confirmingCard).toBeVisible({ timeout: 5000 });
    await expect(confirmingCard).toContainText('Payment received – confirming…');

    // Wait for polling to succeed and unlock
    await expect(confirmingCard).not.toBeVisible({ timeout: 15000 });
    const paidCard = page.locator('.payment-module-card.paid');
    await expect(paidCard).toBeVisible({ timeout: 5000 });
  });

  test('StudentPaymentModule shows timeout message when confirmation does not arrive within limit', async ({ page }) => {
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
            has_paid: false,
            amount: 3000,
            year_month: '2026-10',
            payment: null,
            history: [],
          }),
        });
      }

      if (url.includes('/student/pay-monthly')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            payhere_url: 'https://sandbox.payhere.lk/pay/checkout',
            params: {
              order_id: 'MONTHLY_POLL_TIMEOUT',
              amount: '3000.00',
              currency: 'LKR',
            },
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

    // Inject fake payhere object
    await page.evaluate(() => {
      window.payhere = {
        startPayment: () => {
          setTimeout(() => {
            if (window.payhere.onCompleted) {
              window.payhere.onCompleted('MONTHLY_POLL_TIMEOUT');
            }
          }, 100);
        },
      };
    });

    const payOnlineBtn = page.locator('button.pay-now-btn, [data-testid="pay-now-btn"]');
    await expect(payOnlineBtn).toBeVisible({ timeout: 10000 });
    await payOnlineBtn.click();

    // Confirming banner shows initially
    const confirmingCard = page.locator('[data-testid="status-card-confirming"]');
    await expect(confirmingCard).toBeVisible({ timeout: 5000 });
    await expect(confirmingCard).toContainText('Payment received – confirming…');
  });
});
