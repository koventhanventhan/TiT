import { test, expect } from '@playwright/test';

const VIEWPORTS = [
  { width: 360, height: 740, name: 'mobile' },
  { width: 1280, height: 800, name: 'desktop' },
];

const mockBaseUser = {
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
  test.describe(`Subject Update & Payment Clarity (${name}: ${width}x${height})`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setViewportSize({ width, height });

      await page.addInitScript(({ token, user }) => {
        localStorage.setItem('authToken', token);
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
      }, { token: 'mock-student-token-part-d', user: mockBaseUser });
    });

    test(`State 1: when current month is already paid, shows green card with fee status and advance months toggle (${name})`, async ({ page }) => {
      let advanceRequestedMonths = null;

      await page.route('**/api/**', async (route) => {
        const url = route.request().url();
        const method = route.request().method();

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

        if (method === 'POST' && (url.includes('/student/update-subjects') || url.includes('/student/subjects'))) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              message: 'Subjects updated successfully.',
              mediumChangeRequested: false,
              already_paid: true,
              paid_amount: 2500,
              paid_until: 'October 2026',
              amount: 2500,
              admission_fee: 0,
              monthly_fee: 2500,
            }),
          });
        }

        if (method === 'POST' && url.includes('/student/advance-payment-offline')) {
          const postData = JSON.parse(route.request().postData() || '{}');
          advanceRequestedMonths = postData.months;
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              message: 'Advance payment requested successfully. Admin will confirm shortly.',
              request_id: 88,
              amount: 2500 * postData.months,
              months: postData.months,
            }),
          });
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

      await page.goto('/student/settings');

      // Tap on Edit Subjects & Medium button
      const editBtn = page.getByRole('button', { name: /Edit Subjects & Medium/i });
      await expect(editBtn).toBeVisible({ timeout: 10000 });
      await editBtn.click();

      // Check modal is open
      const saveBtn = page.getByRole('button', { name: /Save Subjects/i });
      await expect(saveBtn).toBeVisible({ timeout: 5000 });

      // Save subjects
      await saveBtn.click();

      // Verify Step 2 green card is displayed with fee stays message
      const greenCard = page.locator('[data-testid="status-card-already-paid"]');
      await expect(greenCard).toBeVisible({ timeout: 10000 });
      await expect(greenCard).toContainText('Your fee stays Rs 2500 (the monthly maximum / package price). Next month\'s fee: Rs 2500.');

      // Verify NO monthly payment buttons are present (online/offline for this month)
      await expect(page.locator('[data-testid="pay-online-btn"]')).not.toBeVisible();
      await expect(page.locator('[data-testid="pay-offline-btn"]')).not.toBeVisible();

      // Verify Advance months toggle IS present in paid state
      const advanceToggle = page.locator('[data-testid="pay-advance-toggle-btn"]');
      await expect(advanceToggle).toBeVisible();

      // Tapping advance toggle reveals stepper and total
      await advanceToggle.click();
      const advanceInput = page.locator('[data-testid="advance-months-input"]');
      await expect(advanceInput).toBeVisible();
      await expect(advanceInput).toHaveValue('3');

      const advanceBlock = page.locator('[data-testid="advance-months-block"]');
      await expect(advanceBlock).toContainText('Rs. 7,500');

      // Step up to 4 months
      await page.getByLabel('Increase months').click();
      await expect(advanceInput).toHaveValue('4');
      await expect(advanceBlock).toContainText('Rs. 10,000');

      // Step down back to 3 months
      await page.getByLabel('Decrease months').click();
      await expect(advanceInput).toHaveValue('3');
      await expect(advanceBlock).toContainText('Rs. 7,500');

      // Submit advance offline
      const submitAdvanceBtn = page.locator('[data-testid="submit-advance-payment-btn"]');
      await expect(submitAdvanceBtn).toBeVisible();
      await submitAdvanceBtn.click();

      await expect.poll(() => advanceRequestedMonths).toBe(3);

      // Verify single Done button is visible
      const doneBtn = page.locator('[data-testid="modal-already-paid-done-btn"]');
      await expect(doneBtn).toBeVisible();

      // Check no horizontal overflow
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1;
      });
      expect(overflow).toBe(true);

      // Real tap on Done button closes the modal
      await doneBtn.click();
      await expect(page.locator('.shared-modal-overlay')).not.toBeVisible({ timeout: 5000 });
    });

    test(`State 1b: when new fee is lower than paid fee, shows lower fee message (${name})`, async ({ page }) => {
      await page.route('**/api/**', async (route) => {
        const url = route.request().url();
        const method = route.request().method();

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

        if (method === 'POST' && (url.includes('/student/update-subjects') || url.includes('/student/subjects'))) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              message: 'Subjects updated successfully.',
              mediumChangeRequested: false,
              already_paid: true,
              paid_amount: 2500,
              paid_until: 'October 2026',
              amount: 1500,
              admission_fee: 0,
              monthly_fee: 1500,
            }),
          });
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

      await page.goto('/student/settings');

      const editBtn = page.getByRole('button', { name: /Edit Subjects & Medium/i });
      await expect(editBtn).toBeVisible({ timeout: 10000 });
      await editBtn.click();

      const saveBtn = page.getByRole('button', { name: /Save Subjects/i });
      await expect(saveBtn).toBeVisible({ timeout: 5000 });
      await saveBtn.click();

      const greenCard = page.locator('[data-testid="status-card-already-paid"]');
      await expect(greenCard).toBeVisible({ timeout: 10000 });
      await expect(greenCard).toContainText('Your new fee Rs 1500 starts from');
      await expect(greenCard).toContainText('no refund for this month.');
    });

    test(`State 2: when month is not paid, shows 3 payment options with short explanations (${name})`, async ({ page }) => {
      await page.route('**/api/**', async (route) => {
        const url = route.request().url();
        const method = route.request().method();

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
              is_paid: false,
              amount: 2500,
              year_month: '2026-10',
              payment: null,
              history: [],
            }),
          });
        }

        if (method === 'POST' && (url.includes('/student/update-subjects') || url.includes('/student/subjects'))) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              message: 'Subjects updated successfully.',
              mediumChangeRequested: false,
              already_paid: false,
              paid_until: null,
              amount: 2500,
              admission_fee: 0,
              monthly_fee: 2500,
            }),
          });
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

      await page.goto('/student/settings');

      // Tap on Edit Subjects & Medium
      const editBtn = page.getByRole('button', { name: /Edit Subjects & Medium/i });
      await expect(editBtn).toBeVisible({ timeout: 10000 });
      await editBtn.click();

      // Save subjects
      const saveBtn = page.getByRole('button', { name: /Save Subjects/i });
      await expect(saveBtn).toBeVisible({ timeout: 5000 });
      await saveBtn.click();

      // Verify the 3 payment options are shown with short explanation under each
      const payOnlineBtn = page.locator('[data-testid="pay-online-btn"]');
      const payOfflineBtn = page.locator('[data-testid="pay-offline-btn"]');
      const payAdvanceToggleBtn = page.locator('[data-testid="pay-advance-toggle-btn"]');

      await expect(payOnlineBtn).toBeVisible({ timeout: 5000 });
      await expect(payOfflineBtn).toBeVisible({ timeout: 5000 });
      await expect(payAdvanceToggleBtn).toBeVisible({ timeout: 5000 });

      // Check explanation line under each option
      const modalBody = page.locator('.shared-modal-body');
      await expect(modalBody).toContainText('Pay online – receipt is sent immediately');
      await expect(modalBody).toContainText('Pay offline – the admin marks it paid, then you receive the receipt and your access opens');
      await expect(modalBody).toContainText('Advance months – pay for multiple months in advance offline');

      // Tap on Advance months toggle to expand
      await payAdvanceToggleBtn.click();
      const advanceMonthsInput = page.locator('[data-testid="advance-months-input"]');
      await expect(advanceMonthsInput).toBeVisible({ timeout: 5000 });

      // Check no horizontal overflow
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1;
      });
      expect(overflow).toBe(true);
    });

    test(`State 3: when medium change is pending, displays yellow status card in Settings and Modal (${name})`, async ({ page }) => {
      const userWithPendingMedium = {
        ...mockBaseUser,
        medium: 'tamil',
        pending_medium: 'english',
      };

      await page.route('**/api/**', async (route) => {
        const url = route.request().url();
        const method = route.request().method();

        if (url.includes('/auth/user')) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ user: userWithPendingMedium }),
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
              user: userWithPendingMedium,
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
              payment: null,
              history: [],
            }),
          });
        }

        if (method === 'POST' && (url.includes('/student/update-subjects') || url.includes('/student/subjects'))) {
          return route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              message: 'Subjects updated successfully.',
              mediumChangeRequested: true,
              already_paid: true,
              paid_until: 'October 2026',
              amount: 2500,
              admission_fee: 0,
              monthly_fee: 2500,
            }),
          });
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

      await page.goto('/student/settings');

      // Settings card must show yellow card
      const settingsYellowCard = page.locator('[data-testid="settings-pending-medium-card"]');
      await expect(settingsYellowCard).toBeVisible({ timeout: 10000 });
      await expect(settingsYellowCard).toContainText('Medium change requested – waiting for admin approval; until then you keep Tamil materials.');

      // Tap on Edit Subjects & Medium
      const editBtn = page.getByRole('button', { name: /Edit Subjects & Medium/i });
      await editBtn.click();

      // Save subjects
      const saveBtn = page.getByRole('button', { name: /Save Subjects/i });
      await expect(saveBtn).toBeVisible({ timeout: 5000 });
      await saveBtn.click();

      // Step 2 in Modal must show yellow card
      const modalYellowCard = page.locator('[data-testid="modal-pending-medium-card"]');
      await expect(modalYellowCard).toBeVisible({ timeout: 5000 });
      await expect(modalYellowCard).toContainText('Medium change requested – waiting for admin approval; until then you keep Tamil materials.');

      // Check no horizontal overflow
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1;
      });
      expect(overflow).toBe(true);
    });
  });
});
