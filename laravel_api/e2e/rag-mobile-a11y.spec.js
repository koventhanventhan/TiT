import { test, expect } from '@playwright/test';

const defaultStudentUser = {
  id: 201,
  name: 'Kasun Perera',
  full_name: 'Kasun Perera',
  email: 'kasun@test.lk',
  role: 'user',
  current_grade: 'grade-10',
  medium: 'english',
  subjects: ['Mathematics', 'Science', 'English'],
  selected_subjects: ['Mathematics', 'Science', 'English'],
  registration_status: 'approved',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: true,
  is_deactivated: false,
  rag_enabled: true,
};

async function setupStudentMocks(page) {
  // Set auth tokens in localStorage
  await page.addInitScript((user) => {
    localStorage.setItem('token', 'mock-valid-jwt-token-student');
    localStorage.setItem('authToken', 'mock-valid-jwt-token-student');
    localStorage.setItem('user', JSON.stringify(user));
  }, defaultStudentUser);

  // Mock API endpoints
  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ user: defaultStudentUser }),
    });
  });

  await page.route('**/api/settings', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        public_assistant_enabled: true,
        institute_id: 1,
        institute_name: 'Premier Academy',
      }),
    });
  });

  await page.route('**/api/student/ai-chat/usage', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ used_today: 3, daily_limit: 30, remaining: 27 }),
    });
  });

  await page.route('**/api/student/ai-chat/history', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 101,
          question: 'What is photosynthesis?',
          answer: 'Photosynthesis is the process by which green plants manufacture food.',
          subject: 'Science',
          status: 'answered',
          feedback_rating: 'up',
          created_at: new Date().toISOString(),
        }
      ]),
    });
  });

  await page.route('**/api/announcements/active', async (route) => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
  });

  await page.route('**/api/settings/branding', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ institute_name: 'Premier Academy' }),
    });
  });
}

test.describe('Student Study Assistant Chat Page Mobile & Accessibility Tests', () => {
  const viewports = [
    { width: 320, height: 568, name: '320px (Compact Mobile)' },
    { width: 360, height: 740, name: '360px (Standard Mobile)' },
    { width: 390, height: 844, name: '390px (Modern Mobile)' },
    { width: 768, height: 1024, name: '768px (Tablet Portrait)' },
  ];

  for (const vp of viewports) {
    test(`Chat page renders cleanly without horizontal overflow at ${vp.name}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize({ width: vp.width, height: vp.height });

      await page.goto('/student/study-assistant', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(600);

      // Verify no horizontal overflow
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth > window.innerWidth;
      });
      expect(overflow, `Page should not have horizontal overflow at ${vp.width}px`).toBe(false);

      // Verify key elements are visible
      const notice = page.locator('.assistant-first-time-notice');
      if (await notice.isVisible()) {
        const text = await notice.innerText();
        expect(text).toContain('mistakes');
      }

      // Daily usage pill
      const usagePill = page.locator('.daily-usage-pill');
      await expect(usagePill).toBeVisible();

      // Input area
      const textarea = page.locator('#rag-question-input, textarea.assistant-textarea');
      await expect(textarea).toBeVisible();

      // Send button
      const sendBtn = page.locator('button.btn-send-question');
      await expect(sendBtn).toBeVisible();

      // Check ARIA & Keyboard basics
      await textarea.focus();
      const isFocused = await textarea.evaluate(el => document.activeElement === el);
      expect(isFocused).toBe(true);
    });
  }
});

test.describe('Public Assistant Widget on Home, Classes, and Contact Pages', () => {
  const publicPages = [
    { path: '/', name: 'Home Page' },
    { path: '/classes', name: 'Classes Page' },
    { path: '/contact', name: 'Contact Page' },
  ];

  for (const pg of publicPages) {
    test(`Widget is present and interactive on ${pg.name} at 390px mobile`, async ({ page }) => {
      // Mock public assistant endpoints & settings
      await page.route('**/api/settings', async (route) => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            public_assistant_enabled: true,
            institute_id: 1,
            institute_name: 'Premier Academy',
          }),
        });
      });

      await page.route('**/api/assistant/public-context', async (route) => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            institute_name: 'Premier Academy',
            public_assistant_enabled: true,
          }),
        });
      });

      await page.route('**/api/announcements/active', async (route) => {
        await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify([]) });
      });

      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto(pg.path, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(600);

      // Check floating widget trigger button
      const widgetTrigger = page.locator('.unified-assistant-trigger, button[aria-label*="Assistant"], button[aria-label*="உதவியாளர்"]');
      await expect(widgetTrigger.first()).toBeVisible({ timeout: 5000 });

      // Click to open widget
      await widgetTrigger.first().click();
      await page.waitForTimeout(300);

      // Assistant window / modal must be visible
      const chatWindow = page.locator('.unified-assistant-modal');
      await expect(chatWindow.first()).toBeVisible();

      // Check no horizontal overflow with widget open
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth > window.innerWidth;
      });
      expect(overflow, `Public page ${pg.path} should not overflow when widget is open`).toBe(false);

      // Check input exists in widget
      const widgetInput = page.locator('.unified-assistant-modal input, .unified-assistant-modal textarea');
      await expect(widgetInput.first()).toBeVisible();
    });
  }
});
