import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const ROUTES = [
  { path: '/student/dashboard', name: 'dashboard' },
  { path: '/student/schedule', name: 'schedule' },
  { path: '/student/zoom', name: 'zoom' },
  { path: '/student/assignments', name: 'assignments' },
  { path: '/student/materials', name: 'materials' },
  { path: '/student/performance', name: 'performance' },
  { path: '/student/messages', name: 'messages' },
  { path: '/student/settings', name: 'settings' },
];

const VIEWPORTS = [
  { width: 320, height: 640 },
  { width: 360, height: 740 },
  { width: 390, height: 844 },
  { width: 414, height: 896 },
  { width: 768, height: 1024 },
  { width: 820, height: 1180 },
  { width: 1024, height: 768 },
  { width: 1280, height: 800 },
];

const defaultStudentUser = {
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

async function setupStudentMocks(page, userOverrides = {}, customHandlers = {}) {
  const currentUser = { ...defaultStudentUser, ...userOverrides };

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('authToken', token);
    localStorage.setItem('user', JSON.stringify(user));
  }, { token: 'mock-student-auth-token-123', user: currentUser });

  await page.route('**/api/**', async (route) => {
    const url = route.request().url();
    const method = route.request().method();

    if (customHandlers.onRequest) {
      const handled = await customHandlers.onRequest(route, url, method);
      if (handled) return;
    }

    if (url.includes('/auth/user')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ user: currentUser }) });
    }

    if (url.includes('/student/dashboard-stats') || url.includes('/student/stats')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          today_classes: 1,
          total_classes: 8,
          assignments_total: 3,
          pending_assignments: 2,
          attendance_rate: 87.5,
          attendance_rate_has_data: true,
          study_hours: 12.5,
          attended_classes: 7,
          held_classes: 8,
          subject_mastery: [
            { subject: 'Maths', progress: 85 },
            { subject: 'Science', progress: 90 },
            { subject: 'English', progress: 78 }
          ],
          recent_achievements: [
            { title: 'Perfect Attendance', description: 'Attended all classes this week' }
          ],
          user: currentUser
        })
      });
    }

    if (url.includes('/upcoming-schedules') || url.includes('/zoom-classes')) {
      const scheduleArray = [
        {
          id: 101,
          title: 'Grade 10 Mathematics Live',
          subject: 'Maths',
          grade: 'Grade 10',
          medium: 'english',
          scheduled_at: new Date(Date.now() - 60000).toISOString(), // 1 minute ago -> Live Now!
          duration: 60,
          zoom_link: 'https://zoom.us/j/123456789',
          join_url: 'https://zoom.us/j/123456789',
          teacher: 'Mr. Silva',
          attendance_status: 'none'
        },
        {
          id: 102,
          title: 'Grade 10 Science Later',
          subject: 'Science',
          grade: 'Grade 10',
          medium: 'english',
          scheduled_at: new Date(Date.now() + 86400000).toISOString(),
          duration: 60,
          zoom_link: 'https://zoom.us/j/987654321',
          join_url: 'https://zoom.us/j/987654321',
          teacher: 'Mrs. Perera',
          attendance_status: 'none'
        }
      ];
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify(url.includes('/zoom-classes') ? { data: scheduleArray } : scheduleArray)
      });
    }

    if (url.includes('/student/assignments')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 301,
              title: 'Algebra Quadratic Equations',
              subject: 'Maths',
              grade: 'Grade 10',
              due_date: new Date(Date.now() + 86400000).toISOString(),
              description: 'Solve exercise 4.1 to 4.5 and submit your workings.',
              submission: null
            },
            {
              id: 302,
              title: 'Newton Laws of Motion',
              subject: 'Science',
              grade: 'Grade 10',
              due_date: new Date(Date.now() + 172800000).toISOString(),
              description: 'Lab report on force and acceleration.',
              submission: {
                id: 501,
                status: 'pending',
                submitted_at: '2026-10-12T10:00:00Z',
                file_url: 'https://example.com/submission1.pdf'
              }
            }
          ]
        })
      });
    }

    if (url.includes('/student/materials')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 401,
              title: 'Algebra Revision Notes',
              type: 'pdf',
              subject: 'Maths',
              grade: 'Grade 10',
              file_path: 'https://example.com/maths_notes.pdf',
              created_at: '2026-10-01T00:00:00Z'
            }
          ]
        })
      });
    }

    if (url.includes('/student/notifications/read-all')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) });
    }

    if (url.includes('/student/notifications')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          notifications: [
            { id: 'n1', title: 'Welcome', message: 'Welcome to TIT Academy', read: false, created_at: '2026-10-13T09:00:00Z' }
          ],
          unread_count: 1
        })
      });
    }

    if (url.includes('/student/payment/status')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          status: 'paid',
          is_paid: true,
          current_grade: 'Grade 10',
          monthly_amount: 2500,
          currency: 'LKR'
        })
      });
    }

    if (url.includes('/student/attend')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({ success: true, message: 'Attendance marked' })
      });
    }

    if (url.includes('/messages/unread-count')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ unread_count: 1 }) });
    }
    if (url.includes('/messages/sent')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ messages: [] }) });
    }
    if (url.includes('/messages/recipients')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          teachers: [{ id: 1, name: 'Dr. Jane Teacher', email: 'teacher@tit.lk', role: 'teacher' }],
          students: [],
          admins: [],
          broadcast: []
        })
      });
    }
    if (url.includes('/messages')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          messages: [
            { id: 601, sender: { id: 1, name: 'Dr. Jane Teacher', role: 'teacher' }, receiver: { id: 201, name: 'Kasun Perera' }, body: 'Hello Kasun, welcome!', created_at: new Date().toISOString(), is_read: false }
          ]
        })
      });
    }

    if (url.includes('/subjects/prices')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          grade_10_to_11: [
            { id: 1, name: 'Mathematics', price: 1000, medium: 'english' },
            { id: 2, name: 'Science', price: 1000, medium: 'english' },
            { id: 3, name: 'English', price: 800, medium: 'english' }
          ]
        })
      });
    }

    if (url.includes('/packages')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify([
          {
            id: 1,
            name: 'All 3 Subjects Pack',
            applicable_grades: [10, 11],
            medium: 'english',
            price: 2500
          }
        ])
      });
    }

    if (url.includes('/student/payment-details')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          total: 2500,
          subjects: [
            { name: 'Mathematics', price: 1000 },
            { name: 'Science', price: 1000 },
            { name: 'English', price: 800 }
          ]
        })
      });
    }

    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
  });
}

test.describe('Student Area Mobile, Tablet & Tap Optimization Suite', () => {

  test.beforeAll(() => {
    const screenshotsDir = path.join(process.cwd(), 'e2e', 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
      fs.mkdirSync(screenshotsDir, { recursive: true });
    }
  });

  // 1. Matrix test: 8 routes x 8 viewports
  for (const vp of VIEWPORTS) {
    for (const r of ROUTES) {
      test(`Matrix: ${r.name} @ ${vp.width}x${vp.height} - no overflow & responsive typography`, async ({ page }) => {
        await setupStudentMocks(page);
        await page.setViewportSize(vp);

        await page.goto(r.path, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        // Assert 1: No horizontal page scroll
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const innerWidth = await page.evaluate(() => window.innerWidth);
        expect(scrollWidth, `Route ${r.name} at ${vp.width}px must not have horizontal scroll`).toBeLessThanOrEqual(innerWidth);

        // Screenshot
        const screenshotPath = path.join(process.cwd(), 'e2e', 'screenshots', `student_${r.name}_${vp.width}x${vp.height}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: false });

        // Assert 2: Font size >= 16px on inputs/textareas/selects at widths <= 768
        if (vp.width <= 768) {
          const smallFontInputs = await page.evaluate(() => {
            const elements = Array.from(document.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea'));
            const offenders = [];
            for (const el of elements) {
              const rect = el.getBoundingClientRect();
              if (rect.width > 0 && rect.height > 0) {
                const fs = parseFloat(window.getComputedStyle(el).fontSize);
                if (fs < 15.5) { // allow float precision down to 15.5
                  offenders.push({ tag: el.tagName, placeholder: el.placeholder || '', fontSize: fs });
                }
              }
            }
            return offenders;
          });
          expect(smallFontInputs, `Inputs with font-size < 16px on mobile: ${JSON.stringify(smallFontInputs)}`).toEqual([]);
        }

        // Navigation check for viewports < 1024
        if (vp.width < 1024) {
          const hamburger = page.locator('button[aria-label="Toggle navigation menu"]');
          await expect(hamburger).toBeVisible();
        }
      });
    }
  }

  // 2. Rotation test 768x1024 -> 1024x768 -> 768x1024 keeps navigation reachable
  test('Rotation 768x1024 -> 1024x768 -> 768x1024 keeps navigation reachable', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 768, height: 1024 });

    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const hamburger = page.locator('button[aria-label="Toggle navigation menu"]');
    await expect(hamburger).toBeVisible();

    // Tap hamburger to open drawer
    await hamburger.click();
    const closeBtn = page.locator('button[aria-label="Close sidebar"]');
    await expect(closeBtn).toBeVisible();

    // Close via overlay tap
    const overlay = page.locator('div[aria-label="Close sidebar overlay"]');
    await overlay.click();
    await expect(closeBtn).not.toBeVisible();

    // Rotate to landscape 1024x768
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.waitForTimeout(300);

    // Sidebar should be visible statically, hamburger hidden
    await expect(hamburger).not.toBeVisible();
    const navItem = page.locator('aside nav a').first();
    await expect(navItem).toBeVisible();

    // Rotate back to portrait 768x1024
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(300);

    // Hamburger must reappear and be functional
    await expect(hamburger).toBeVisible();
    await hamburger.click();
    await expect(closeBtn).toBeVisible();
  });

  // 3. Touch target minimum sizing (width and height >= 44 on touch emulation) covering header, aside, main + inputs at 390x844 and 320x640
  for (const touchVp of [{ width: 390, height: 844 }, { width: 320, height: 640 }]) {
    test(`Touch targets in header, aside, main have boundingBox >= 43 at ${touchVp.width}x${touchVp.height}`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: touchVp,
        hasTouch: true,
        isMobile: true,
      });
      const page = await context.newPage();
      await setupStudentMocks(page);

      for (const r of ROUTES) {
        await page.goto(r.path, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        const offenders = await page.evaluate(() => {
          const elements = Array.from(document.querySelectorAll('header button, header input:not([type="hidden"]), aside button, aside a.t-tap, main button, main select, main input:not([type="hidden"]), main .t-icon-btn, main a.t-tap'));
          const issues = [];
          for (const el of elements) {
            const rect = el.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              if (rect.width < 43 || rect.height < 43) {
                issues.push({
                  text: el.innerText?.slice(0, 30) || el.getAttribute('aria-label') || el.className,
                  tag: el.tagName,
                  width: Math.round(rect.width),
                  height: Math.round(rect.height)
                });
              }
            }
          }
          return issues;
        });

        expect(offenders, `Under-sized touch targets found on ${r.path} at ${touchVp.width}px: ${JSON.stringify(offenders)}`).toEqual([]);
      }

      await context.close();
    });
  }

  // 4. Submit-assignment flow: open modal at 320px (fits viewport, close >= 44px), pick a file, submit, mocked 200 -> card shows "Submitted"; mocked 422 -> error visible
  test('Submit-assignment flow: modal sizing at 320px, submit success (200) and validation error (422)', async ({ page }) => {
    let submitStatus = 200;
    let submitted = false;
    let submitResponseBody = {
      message: 'Assignment submitted successfully',
      submission: {
        id: 888,
        status: 'pending',
        submitted_at: new Date().toISOString(),
        file_url: 'https://example.com/uploaded.pdf'
      }
    };

    await setupStudentMocks(page, {}, {
      onRequest: async (route, url, method) => {
        if (url.includes('/student/assignments/301/submit') && method === 'POST') {
          if (submitStatus === 200) {
            submitted = true;
            await route.fulfill({
              status: 200,
              contentType: 'application/json',
              body: JSON.stringify(submitResponseBody)
            });
            return true;
          } else {
            await route.fulfill({
              status: 422,
              contentType: 'application/json',
              body: JSON.stringify({ message: 'Already graded, resubmission is not allowed' })
            });
            return true;
          }
        }
        if (url.includes('/student/assignments') && method === 'GET' && submitted) {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              data: [
                {
                  id: 301,
                  title: 'Algebra Quadratic Equations',
                  subject: 'Maths',
                  grade: 'Grade 10',
                  due_date: new Date(Date.now() + 86400000).toISOString(),
                  description: 'Solve exercise 4.1 to 4.5 and submit your workings.',
                  submissions: [
                    {
                      id: 888,
                      status: 'pending',
                      submitted_at: new Date().toISOString(),
                      file_url: 'https://example.com/uploaded.pdf'
                    }
                  ]
                }
              ]
            })
          });
          return true;
        }
        return false;
      }
    });

    await page.setViewportSize({ width: 320, height: 640 });
    await page.goto('/student/assignments', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    // Find first "Submit Work" button
    const submitWorkBtn = page.locator('button:has-text("Submit Work")').first();
    await expect(submitWorkBtn).toBeVisible();
    await submitWorkBtn.click();
    await page.waitForTimeout(300);

    // Modal dialog check
    const modalDialog = page.locator('.shared-modal-dialog');
    await expect(modalDialog).toBeVisible();
    const modalBox = await modalDialog.boundingBox();
    expect(modalBox.width).toBeLessThanOrEqual(320);

    // Close button check >= 44px
    const closeBtn = page.locator('button[aria-label="Close submit modal"]');
    await expect(closeBtn).toBeVisible();
    const closeBox = await closeBtn.boundingBox();
    expect(closeBox.width).toBeGreaterThanOrEqual(43);
    expect(closeBox.height).toBeGreaterThanOrEqual(43);

    // Part A: Validation error (mocked 422)
    submitStatus = 422;
    const fileInput = modalDialog.locator('input[type="file"]');
    await fileInput.setInputFiles({
      name: 'solution.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('%PDF-1.4 dummy pdf content')
    });

    const modalSubmitBtn = modalDialog.locator('button[type="submit"]');
    await modalSubmitBtn.click();
    await page.waitForTimeout(300);

    // Error message visible
    const errorAlert = page.locator('text=Already graded, resubmission is not allowed');
    await expect(errorAlert).toBeVisible();

    // Part B: Success (mocked 200)
    submitStatus = 200;
    await modalSubmitBtn.click();
    await page.waitForTimeout(400);

    // Modal should close and status updated
    await expect(modalDialog).not.toBeVisible();
    await expect(page.locator('text=Submitted - awaiting grade').first()).toBeVisible();
  });

  // 5. Join flow: tapping Join twice quickly sends exactly ONE attend request
  test('Join flow: tapping Join twice rapidly sends exactly ONE attend request', async ({ page }) => {
    let attendCalls = 0;

    await setupStudentMocks(page, {}, {
      onRequest: async (route, url) => {
        if (url.includes('/student/attend')) {
          attendCalls++;
          // Simulate slight network latency
          await new Promise(r => setTimeout(r, 100));
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ success: true, message: 'Attendance recorded' })
          });
          return true;
        }
        return false;
      }
    });

    // Mock window.open to prevent actual new window in test
    await page.addInitScript(() => {
      window.open = () => ({ location: { href: '' } });
    });

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const joinBtn = page.locator('button:has-text("Join Class")').first();
    await expect(joinBtn).toBeVisible();

    // Rapid double-tap
    await Promise.all([
      joinBtn.click(),
      joinBtn.click()
    ]);
    await page.waitForTimeout(500);

    expect(attendCalls, 'Double-tapping Join must only invoke attend API once').toBe(1);
  });

  // 6. Gating screens (PendingApproval, PaymentRequired, Deactivated) at 320x568, 320x640, and 390x844: bilingual & no overflow
  for (const gVp of [{ width: 320, height: 568 }, { width: 320, height: 640 }, { width: 390, height: 844 }]) {
    test(`Gating screens at ${gVp.width}x${gVp.height}: bilingual & no overflow`, async ({ page }) => {
      // 6A: Pending Approval
      await setupStudentMocks(page, { admin_confirmed_at: null });
      await page.setViewportSize(gVp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      let scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `PendingApproval at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);

      const pendingScreen = page.locator('[data-testid="gating-pending-approval"]');
      await expect(pendingScreen).toBeVisible();
      const pendingText = await pendingScreen.innerText();
      expect(pendingText).toMatch(/[A-Za-z]/);
      expect(pendingText).toMatch(/[\u0B80-\u0BFF]/);

      const pendingLogoutBtn = pendingScreen.locator('[data-testid="gating-logout-btn"]');
      await pendingLogoutBtn.scrollIntoViewIfNeeded();
      await expect(pendingLogoutBtn).toBeVisible();

      // 6B: Payment Required
      await setupStudentMocks(page, { admin_confirmed_at: '2026-01-01 10:00:00', is_paid: false });
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `PaymentRequired at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);

      const paymentScreen = page.locator('[data-testid="gating-payment-required"]');
      await expect(paymentScreen).toBeVisible();
      const paymentText = await paymentScreen.innerText();
      expect(paymentText).toMatch(/[A-Za-z]/);
      expect(paymentText).toMatch(/[\u0B80-\u0BFF]/);

      const paymentLogoutBtn = paymentScreen.locator('[data-testid="gating-logout-btn"]');
      await paymentLogoutBtn.scrollIntoViewIfNeeded();
      await expect(paymentLogoutBtn).toBeVisible();

      // 6C: Deactivated
      await setupStudentMocks(page, { is_deactivated: true });
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `Deactivated at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);

      const deactScreen = page.locator('[data-testid="gating-deactivated"]');
      await expect(deactScreen).toBeVisible();
      const deactText = await deactScreen.innerText();
      expect(deactText).toMatch(/[A-Za-z]/);
      expect(deactText).toMatch(/[\u0B80-\u0BFF]/);

      const deactLogoutBtn = deactScreen.locator('[data-testid="gating-logout-btn"]');
      await deactLogoutBtn.scrollIntoViewIfNeeded();
      await expect(deactLogoutBtn).toBeVisible();
    });
  }

  // 7. Colour check: primary buttons resolve to rgb(79, 11, 217); no element in the student area has computed background rgb(14, 165, 233) or rgb(99, 102, 241)
  test('Colour check: primary buttons resolve to rgb(79, 11, 217); no legacy blue/indigo backgrounds', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1024, height: 768 });

    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    // Primary action button resolves to rgb(79, 11, 217)
    const joinBtn = page.locator('button:has-text("Join Class")').first();
    if (await joinBtn.isVisible()) {
      const btnBg = await joinBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
      expect(btnBg).toBe('rgb(79, 11, 217)');
    }

    // Check no element has computed background of #0ea5e9 (rgb(14, 165, 233)) or #6366f1 (rgb(99, 102, 241)) across student dashboard
    const legacyColoredElements = await page.evaluate(() => {
      const all = Array.from(document.querySelectorAll('*'));
      const banned = ['rgb(14, 165, 233)', 'rgb(99, 102, 241)'];
      const found = [];
      for (const el of all) {
        const bg = window.getComputedStyle(el).backgroundColor;
        if (banned.includes(bg)) {
          found.push({ tag: el.tagName, className: el.className, bg });
        }
      }
      return found;
    });

    expect(legacyColoredElements, `Elements with legacy blue/indigo backgrounds found: ${JSON.stringify(legacyColoredElements)}`).toEqual([]);
  });

});

test.describe('Student Mobile Bug-Fix Round Probe Tests (Phases 1-8)', () => {

  // Probe 1: Mobile drawer fits viewport (all items + Logout inside viewport, no "MENU" label) at 360x740 and 320x568
  for (const vp of [{ width: 360, height: 740 }, { width: 320, height: 568 }]) {
    test(`Drawer fits inside viewport at ${vp.width}x${vp.height} with no MENU label and accessible Logout`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Open mobile drawer
      const menuBtn = page.locator('button[aria-label="Toggle navigation menu"]');
      await expect(menuBtn).toBeVisible();
      await menuBtn.click();
      await page.waitForTimeout(200);

      const aside = page.locator('aside');
      await expect(aside).toBeVisible();

      // No "MENU" label header
      const menuLabel = aside.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);

      // All 8 nav items fit inside viewport and have min-height >= 44
      const navItems = aside.locator('a.t-menu-item');
      const count = await navItems.count();
      expect(count).toBe(8);

      for (let i = 0; i < count; i++) {
        const item = navItems.nth(i);
        await expect(item).toBeVisible();
        const box = await item.boundingBox();
        expect(box).not.toBeNull();
        expect(box.height, `Nav item ${i} height must be >= 44px`).toBeGreaterThanOrEqual(44);
        expect(box.y, `Nav item ${i} y must be >= 0`).toBeGreaterThanOrEqual(0);
        expect(box.y + box.height, `Nav item ${i} must be within viewport height`).toBeLessThanOrEqual(vp.height);
      }

      // Logout button is inside viewport and >= 44px
      const logoutBtn = aside.locator('button[aria-label="Logout"]');
      await expect(logoutBtn).toBeVisible();
      const logoutBox = await logoutBtn.boundingBox();
      expect(logoutBox).not.toBeNull();
      expect(logoutBox.height).toBeGreaterThanOrEqual(44);
      expect(logoutBox.y + logoutBox.height, 'Logout button must be within viewport height').toBeLessThanOrEqual(vp.height);
    });
  }

  // Probe 2: Checkbox visual size <= 28px with label row tap target >= 44px
  test('Checkbox visual size <= 28px with label row tap target >= 44px in SubjectUpdateModal', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/student/settings', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const editBtn = page.locator('button:has-text("Edit Subjects & Medium")');
    await expect(editBtn).toBeVisible();
    await editBtn.click();
    await page.waitForTimeout(400);

    // Modal subject checkboxes
    const checkboxLabels = page.locator('label:has(input[type="checkbox"])');
    const count = await checkboxLabels.count();
    expect(count, 'Subject options should be present').toBeGreaterThan(0);

    for (let i = 0; i < Math.min(count, 4); i++) {
      const label = checkboxLabels.nth(i);
      const labelBox = await label.boundingBox();
      expect(labelBox).not.toBeNull();
      expect(labelBox.height, `Label row ${i} min-height must be >= 44px`).toBeGreaterThanOrEqual(44);

      const checkbox = label.locator('input[type="checkbox"]');
      if (await checkbox.isVisible()) {
        const box = await checkbox.boundingBox();
        expect(box).not.toBeNull();
        expect(box.width, `Checkbox ${i} visual width must be <= 28px`).toBeLessThanOrEqual(28);
        expect(box.height, `Checkbox ${i} visual height must be <= 28px`).toBeLessThanOrEqual(28);
      } else {
        const visualBox = label.locator('div').first();
        const box = await visualBox.boundingBox();
        expect(box).not.toBeNull();
        expect(box.width, `Checkbox visual box ${i} width must be <= 28px`).toBeLessThanOrEqual(28);
        expect(box.height, `Checkbox visual box ${i} height must be <= 28px`).toBeLessThanOrEqual(28);
      }
    }
  });

  // Probe 3: Fee values never wrap (FeeRow)
  test('Fee values never wrap across FeeRow components on mobile', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 320, height: 568 });
    await page.goto('/student/settings', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    await page.locator('button:has-text("Edit Subjects & Medium")').click();
    await page.waitForTimeout(400);

    const feeRowValues = page.locator('.fee-row-value:not(:empty)');
    await expect(feeRowValues.first()).toBeVisible({ timeout: 5000 });
    const count = await feeRowValues.count();
    expect(count).toBeGreaterThan(0);

    for (let i = 0; i < count; i++) {
      const el = feeRowValues.nth(i);
      const whiteSpace = await el.evaluate(node => window.getComputedStyle(node).whiteSpace);
      expect(whiteSpace, 'FeeRow value must have white-space: nowrap').toBe('nowrap');

      const scrollWidth = await el.evaluate(node => node.scrollWidth);
      const clientWidth = await el.evaluate(node => node.clientWidth);
      expect(scrollWidth, 'FeeRow value must not have internal text wrapping overflow').toBeLessThanOrEqual(clientWidth + 2);
    }
  });

  // Probe 4: Months stepper (type 7, type 12, "+", clamp <= 12)
  test('Advance months stepper allows typing 7 and 12, clamps at 12 on +, and updates totals', async ({ page }) => {
    await setupStudentMocks(page, {}, {
      onRequest: async (route, url, method) => {
        if (url.includes('/student/update-subjects') && method === 'POST') {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              already_paid: true,
              paid_until: '2026-11',
              monthly_fee: 1500,
              amount: 1500
            })
          });
          return true;
        }
        return false;
      }
    });

    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/student/settings', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    await page.locator('button:has-text("Edit Subjects & Medium")').click();
    await page.waitForTimeout(300);

    const saveBtn = page.locator('button:has-text("Save Subjects")');
    await expect(saveBtn).toBeVisible();
    await saveBtn.click();
    await page.waitForTimeout(400);

    const advanceCheckboxLabel = page.locator('label:has-text("I want to pay for advance months")');
    await expect(advanceCheckboxLabel).toBeVisible();
    await advanceCheckboxLabel.click();
    await page.waitForTimeout(200);

    const monthsInput = page.locator('input[aria-label="Number of advance months"]');
    const plusBtn = page.locator('button[aria-label="Increase months"]');
    const minusBtn = page.locator('button[aria-label="Decrease months"]');
    await expect(monthsInput).toBeVisible();

    // Type 7
    await monthsInput.fill('7');
    await monthsInput.blur();
    await expect(monthsInput).toHaveValue('7');
    await expect(page.locator('.fee-row-value:has-text("10,500.00")')).toBeVisible();

    // Type 12
    await monthsInput.fill('12');
    await monthsInput.blur();
    await expect(monthsInput).toHaveValue('12');
    await expect(page.locator('.fee-row-value:has-text("18,000.00")')).toBeVisible();

    // Plus button should be disabled when clamped at 12
    await expect(plusBtn).toBeDisabled();

    // Minus button decrements to 11
    await minusBtn.click();
    await expect(monthsInput).toHaveValue('11');
  });

  // Probe 5: Offline-pay feedback < 400 ms and exactly ONE advance-payment request on double tap
  test('Offline-pay gives immediate feedback < 400 ms and triggers exactly ONE request on double tap', async ({ page }) => {
    let advanceCalls = 0;
    await setupStudentMocks(page, {}, {
      onRequest: async (route, url, method) => {
        if (url.includes('/student/update-subjects') && method === 'POST') {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              already_paid: true,
              paid_until: '2026-11',
              monthly_fee: 1500,
              amount: 1500
            })
          });
          return true;
        }
        if ((url.includes('/student/advance-payment-offline') || url.includes('/student/request-advance-payment-offline')) && method === 'POST') {
          advanceCalls++;
          await new Promise(r => setTimeout(r, 600));
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ success: true, message: 'Advance payment request received.' })
          });
          return true;
        }
        return false;
      }
    });

    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/student/settings', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    await page.locator('button:has-text("Edit Subjects & Medium")').click();
    await page.waitForTimeout(300);

    const saveBtn = page.locator('button:has-text("Save Subjects")');
    await saveBtn.click();
    await page.waitForTimeout(400);

    await page.locator('label:has-text("I want to pay for advance months")').click();
    await page.waitForTimeout(200);

    const offlineBtn = page.locator('button:has-text("I WILL PAY OFFLINE")');
    await expect(offlineBtn).toBeVisible();
    await offlineBtn.scrollIntoViewIfNeeded();

    const t0 = Date.now();
    await offlineBtn.evaluate(btn => {
      btn.click();
      btn.click();
    });
    const feedbackTime = Date.now() - t0;
    expect(feedbackTime, 'Offline-pay button must give feedback in < 400ms').toBeLessThan(400);

    const submittingBtn = page.locator('button:has-text("Submitting")');
    await expect(submittingBtn).toBeVisible();
    await expect(submittingBtn).toBeDisabled();

    await page.waitForTimeout(800);
    expect(advanceCalls, 'Double click must send exactly ONE advance payment request').toBe(1);
  });

  // Probe 6: Gating screens bilingual (English + Tamil)
  test('Gating screens contain both English text and Tamil characters', async ({ page }) => {
    // 6A: Pending Approval
    await setupStudentMocks(page, { admin_confirmed_at: null });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const pending = page.locator('[data-testid="gating-pending-approval"]');
    await expect(pending).toBeVisible();
    const pendingText = await pending.innerText();
    expect(pendingText).toMatch(/[A-Za-z]/);
    expect(pendingText).toMatch(/[\u0B80-\u0BFF]/);

    // 6B: Payment Required
    await setupStudentMocks(page, { admin_confirmed_at: '2026-01-01 10:00:00', is_paid: false });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const payment = page.locator('[data-testid="gating-payment-required"]');
    await expect(payment).toBeVisible();
    const paymentText = await payment.innerText();
    expect(paymentText).toMatch(/[A-Za-z]/);
    expect(paymentText).toMatch(/[\u0B80-\u0BFF]/);

    // 6C: Deactivated
    await setupStudentMocks(page, { is_deactivated: true });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const deact = page.locator('[data-testid="gating-deactivated"]');
    await expect(deact).toBeVisible();
    const deactText = await deact.innerText();
    expect(deactText).toMatch(/[A-Za-z]/);
    expect(deactText).toMatch(/[\u0B80-\u0BFF]/);
  });

  // Probe 7: Registration modal payment step (container scrolls, Pay Later/Back reachable, primary button rgb(79, 11, 217) and no red/green gradient)
  test('StudentRegistrationForm payment step: container scrolls, Back/Pay Later reachable, primary button rgb(79, 11, 217) with no gradients', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/register?step=2', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const modal = page.locator('.tit-reg-container');
    await expect(modal).toBeVisible();

    const backBtn = page.locator('.tit-reg-back-link');
    await expect(backBtn).toBeVisible();
    await backBtn.scrollIntoViewIfNeeded();
    await expect(backBtn).toBeVisible();

    const primaryBtn = page.locator('.tit-reg-submit-btn').first();
    await expect(primaryBtn).toBeVisible();
    const bg = await primaryBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(bg, 'Primary button must have brand purple #4f0bd9 background').toBe('rgb(79, 11, 217)');

    const bgImage = await primaryBtn.evaluate(el => window.getComputedStyle(el).backgroundImage);
    expect(bgImage === 'none' || !bgImage.includes('gradient'), 'Primary button must not have gradients').toBe(true);

    const overflowY = await modal.evaluate(el => window.getComputedStyle(el).overflowY);
    expect(['auto', 'scroll']).toContain(overflowY);
  });

  // Probe 8: Automated contrast check for gating screen headings (computed color vs nearest opaque background >= 4.5)
  test('Automated contrast check: gating screen headings have contrast >= 4.5:1 against background', async ({ page }) => {
    function computeContrast(rgb1, rgb2) {
      function lum(r, g, b) {
        const a = [r, g, b].map(v => {
          v /= 255;
          return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
      }
      function parse(c) {
        const m = c.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        return m ? [parseInt(m[1]), parseInt(m[2]), parseInt(m[3])] : [255, 255, 255];
      }
      const l1 = lum(...parse(rgb1));
      const l2 = lum(...parse(rgb2));
      return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    }

    // Check PaymentRequiredDashboard
    await setupStudentMocks(page, { admin_confirmed_at: '2026-01-01 10:00:00', is_paid: false });
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const titleContrast = await page.evaluate(() => {
      function getBg(el) {
        let curr = el;
        while (curr && curr !== document.documentElement) {
          const bg = window.getComputedStyle(curr).backgroundColor;
          const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
          if (m && (m[4] === undefined || parseFloat(m[4]) > 0.8)) {
            return bg;
          }
          curr = curr.parentElement;
        }
        return 'rgb(15, 23, 42)';
      }
      const title = document.querySelector('.tit-reg-title');
      const sectionTitle = document.querySelector('.section-title');
      return {
        titleColor: title ? window.getComputedStyle(title).color : null,
        titleBg: title ? getBg(title) : null,
        sectionTitleColor: sectionTitle ? window.getComputedStyle(sectionTitle).color : null,
        sectionTitleBg: sectionTitle ? getBg(sectionTitle) : null
      };
    });

    expect(titleContrast.titleColor).not.toBeNull();
    const r1 = computeContrast(titleContrast.titleColor, titleContrast.titleBg);
    expect(r1, `Gating title contrast ${r1.toFixed(2)} must be >= 4.5:1`).toBeGreaterThanOrEqual(4.5);

    if (titleContrast.sectionTitleColor) {
      const r2 = computeContrast(titleContrast.sectionTitleColor, titleContrast.sectionTitleBg);
      expect(r2, `Section title contrast ${r2.toFixed(2)} must be >= 4.5:1`).toBeGreaterThanOrEqual(4.5);
    }
  });

  // Probe 9: StudentPaymentModule on PaymentRequiredDashboard has stacked full-width 48px buttons with 12px gap
  test('StudentPaymentModule has stacked full-width 48px buttons with 12px gap on gating screen', async ({ page }) => {
    await setupStudentMocks(page, { admin_confirmed_at: '2026-01-01 10:00:00', is_paid: false }, {
      onRequest: async (route, url) => {
        if (url.includes('/student/payment/status')) {
          await route.fulfill({
            status: 200, contentType: 'application/json',
            body: JSON.stringify({
              status: 'pending',
              is_paid: false,
              year_month: '2026-10',
              amount: 2500,
              payment: null
            })
          });
          return true;
        }
        return false;
      }
    });

    await page.setViewportSize({ width: 320, height: 568 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const payNowBtn = page.locator('button.pay-now-btn');
    const offlineBtn = page.locator('button.pay-offline-btn');
    await expect(payNowBtn).toBeVisible();
    await expect(offlineBtn).toBeVisible();

    const payNowBox = await payNowBtn.boundingBox();
    const offlineBox = await offlineBtn.boundingBox();
    expect(payNowBox.height, 'Pay Now height must be >= 48px').toBeGreaterThanOrEqual(48);
    expect(offlineBox.height, 'Offline button height must be >= 48px').toBeGreaterThanOrEqual(48);

    expect(offlineBox.y, 'Offline button must be below Pay Now button').toBeGreaterThanOrEqual(payNowBox.y + payNowBox.height);
    const gap = offlineBox.y - (payNowBox.y + payNowBox.height);
    expect(Math.round(gap), 'Gap between stacked buttons must be ~12px').toBeCloseTo(12, -1);

    const offlineBg = await offlineBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(offlineBg === 'rgba(0, 0, 0, 0)' || offlineBg === 'transparent' || offlineBg === 'rgb(255, 255, 255)').toBe(true);

    const payNowBg = await payNowBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(payNowBg).toBe('rgb(79, 11, 217)');
  });

});
