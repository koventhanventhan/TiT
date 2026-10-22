import { test, expect } from '@playwright/test';

const defaultStudent = {
  id: 201,
  name: 'Kasun Perera',
  full_name: 'Kasun Perera',
  email: 'kasun@test.lk',
  role: 'user',
  current_grade: 'Grade 10',
  medium: 'english',
  subjects: ['Mathematics', 'Science', 'English'],
  selected_subjects: ['Mathematics', 'Science', 'English'],
  registration_status: 'completed',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: true,
  is_deactivated: false,
};

async function setupStudentTest(page, userOverrides = {}) {
  const currentUser = { ...defaultStudent, ...userOverrides };

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('authToken', token);
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
  }, { token: 'mock-valid-jwt-token-student', user: currentUser });

  await page.route('**/api/**', async (route) => {
    const url = route.request().url();

    if (url.includes('/auth/user')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ user: currentUser }) });
    }
    if (url.includes('/student/stats') || url.includes('/student/dashboard-stats')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({ today_classes: 0, total_classes: 0, attendance_rate: 100, pending_assignments: 0 }),
      });
    }
    if (url.includes('/student/upcoming-schedules') || url.includes('/zoom-classes')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    }
    if (url.includes('/student/payment-status')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ has_paid: true, status: 'paid', amount: 0 }) });
    }
    if (url.includes('/announcements/active')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
    }
    if (url.includes('/settings')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({ site_name: 'TiT Education', public_assistant_enabled: false }),
      });
    }
    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
  });
}

test.describe('Feature Flags Fail-Safe (RAG & Public Assistant)', () => {

  test('public widget is hidden when backend settings lack public_assistant_enabled (old backend)', async ({ page }) => {
    await page.route('**/api/**', async (route) => {
      const url = route.request().url();
      if (url.includes('/settings')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            site_name: 'TiT Education',
            // public_assistant_enabled is omitted completely (old backend)
          }),
        });
      }
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const trigger = page.locator('#unified-assistant-trigger');
    await expect(trigger).toHaveCount(0);
  });

  test('public widget is hidden when public_assistant_enabled is false or 0', async ({ page }) => {
    await page.route('**/api/**', async (route) => {
      const url = route.request().url();
      if (url.includes('/settings')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            public_assistant_enabled: false,
            site_name: 'TiT Education',
          }),
        });
      }
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const trigger = page.locator('#unified-assistant-trigger');
    await expect(trigger).toHaveCount(0);
  });

  test('public widget hides quietly when assistant endpoint returns 404', async ({ page }) => {
    await page.route('**/api/**', async (route) => {
      const url = route.request().url();
      if (url.includes('/assistant/chat')) {
        return route.fulfill({
          status: 404,
          contentType: 'application/json',
          body: JSON.stringify({ message: 'Not Found' }),
        });
      }
      if (url.includes('/settings')) {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            public_assistant_enabled: true,
            site_name: 'TiT Education',
          }),
        });
      }
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
    });

    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const trigger = page.locator('#unified-assistant-trigger');
    await expect(trigger).toBeVisible({ timeout: 10000 });

    await trigger.click();
    await expect(page.locator('#unified-assistant-window')).toBeVisible({ timeout: 5000 });

    await page.fill('#unified-assistant-input', 'What are your tuition fees?');
    await page.click('#unified-assistant-submit');

    // On 404, widget must fail safe: hide quietly from DOM
    await expect(page.locator('#unified-assistant-widget')).toHaveCount(0, { timeout: 10000 });
  });

  test('student study assistant menu item is hidden when user lacks rag_enabled field (old backend)', async ({ page }) => {
    // Old backend user: rag_enabled is omitted
    await setupStudentTest(page, { rag_enabled: undefined });

    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);

    // Assert Study Assistant rail button and link are NOT present in sidebar
    await expect(page.locator('button[data-rail="study-assistant"]')).toHaveCount(0);
    await expect(page.locator('a[href="/student/study-assistant"]')).toHaveCount(0);
  });

  test('direct navigation to /student/study-assistant redirects to dashboard when rag_enabled is missing', async ({ page }) => {
    await setupStudentTest(page, { rag_enabled: undefined });

    await page.goto('/student/study-assistant', { waitUntil: 'domcontentloaded' });

    // RagRouteGuard redirects to /student/dashboard
    await expect(page).toHaveURL(/.*\/student\/dashboard/, { timeout: 10000 });
  });

  test('student study assistant menu item appears when rag_enabled === true', async ({ page }) => {
    await setupStudentTest(page, { rag_enabled: true });

    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);

    // Verify rail item exists
    const railButton = page.locator('button[data-rail="study-assistant"]');
    await expect(railButton).toBeVisible({ timeout: 10000 });

    // Click rail logo button to expand drawer and verify NavLink exists
    await page.click('button[data-rail="logo"]');
    const studyAssistantLink = page.locator('a[href="/student/study-assistant"]');
    await expect(studyAssistantLink).toBeVisible({ timeout: 5000 });
  });

});
