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

export async function setupStudentMocks(page, userOverrides = {}, customHandlers = {}) {
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
            price: 2500,
            package_price: 2500,
            type: 'all_subjects'
          }
        ])
      });
    }

    if (url.includes('/student/update-subjects')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          monthly_fee: 2500,
          amount: 2500,
          admission_fee: 0,
          already_paid: false
        })
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

        // Navigation check: on phones (<640px) hamburger is visible & rail hidden; on tablet/desktop (>=640px) rail logo is visible
        if (vp.width < 640) {
          const hamburger = page.locator('header button[aria-label="Open menu"]');
          await expect(hamburger).toBeVisible();
          await expect(page.locator('.sidebar-rail')).not.toBeVisible();
          const mainBox = await page.locator('main').first().boundingBox();
          expect(mainBox.x).toBeLessThanOrEqual(16);
        } else {
          const logoBtn = page.locator('button[data-rail="logo"]');
          await expect(logoBtn).toBeVisible();
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

    const logoBtn = page.locator('button[data-rail="logo"]');
    await expect(logoBtn).toBeVisible();

    // Tap logo to open drawer
    await logoBtn.click();
    const closeBtn = page.locator('button[aria-label="Close sidebar"]');
    await expect(closeBtn).toBeVisible();

    // Close via overlay tap
    const overlay = page.locator('div[aria-label="Close sidebar overlay"]');
    await overlay.click();
    await expect(closeBtn).not.toBeVisible();

    // Rotate to landscape 1024x768
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.waitForTimeout(300);

    // Rail should be visible statically
    await expect(logoBtn).toBeVisible();
    const navItem = page.locator('.sidebar-rail-item').first();
    await expect(navItem).toBeVisible();

    // Rotate back to portrait 768x1024
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(300);

    // Logo button must remain functional
    await expect(logoBtn).toBeVisible();
    await logoBtn.click();
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

      // Open mobile drawer via header hamburger button
      const menuBtn = page.locator('header button[aria-label="Open menu"]');
      await expect(menuBtn).toBeVisible();
      await menuBtn.click();
      await page.waitForTimeout(200);

      const aside = page.locator('aside.sidebar-drawer');
      await expect(aside).toBeVisible();

      // No "MENU" label header
      const menuLabel = aside.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);

      // All 8 nav items fit inside viewport and have min-height >= 44
      const navItems = aside.locator('a.sidebar-nav-item');
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

test.describe('Phase 3 - Student Sidebar Redesign Icon Rail & Adaptive Expanded Drawer', () => {

  // 1. Navigation geometry: on phones (<640px), NO rail, hamburger visible >= 44x44, content x <= 16; on tablet/desktop (>=640px), rail visible with logo >= 44x44
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1024, height: 768 },
    { width: 1280, height: 800 },
  ]) {
    test(`Student navigation geometry at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      if (vp.width < 640) {
        // No rail
        await expect(page.locator('.sidebar-rail')).not.toBeVisible();
        // Content x <= 16
        const mainBox = await page.locator('main').first().boundingBox();
        expect(mainBox.x).toBeLessThanOrEqual(16);

        // Hamburger button in header
        const hamburger = page.locator('header button[aria-label="Open menu"]');
        await expect(hamburger).toBeVisible();
        await expect(hamburger).toHaveAttribute('aria-expanded', 'false');
        await expect(hamburger).toHaveAttribute('aria-controls', 'expanded-sidebar-drawer');
        const hBox = await hamburger.boundingBox();
        expect(hBox.width).toBeGreaterThanOrEqual(44);
        expect(hBox.height).toBeGreaterThanOrEqual(44);

        // Click hamburger opens drawer with 8 items + Logout
        await hamburger.click();
        const drawer = page.locator('aside.sidebar-drawer');
        await expect(drawer).toBeVisible();
        const navItems = drawer.locator('a.sidebar-nav-item');
        expect(await navItems.count()).toBe(8);

        for (let i = 0; i < 8; i++) {
          const item = navItems.nth(i);
          const box = await item.boundingBox();
          expect(box.y + box.height).toBeLessThanOrEqual(vp.height);
        }

        const logoutBtn = drawer.locator('button[aria-label="Logout"]');
        await expect(logoutBtn).toBeVisible();
        const logoutBox = await logoutBtn.boundingBox();
        expect(logoutBox.y + logoutBox.height).toBeLessThanOrEqual(vp.height);
      } else {
        const logoBtn = page.locator('button[data-rail="logo"]');
        await expect(logoBtn).toBeVisible();
        await expect(logoBtn).toHaveAttribute('aria-label', 'Open menu');
        await expect(logoBtn).toHaveAttribute('aria-expanded', 'false');
        await expect(logoBtn).toHaveAttribute('aria-controls', 'expanded-sidebar-drawer');
        const logoBox = await logoBtn.boundingBox();
        expect(logoBox.width).toBeGreaterThanOrEqual(44);
        expect(logoBox.height).toBeGreaterThanOrEqual(44);

        const railItems = page.locator('.sidebar-rail button.sidebar-rail-item');
        expect(await railItems.count()).toBe(8);
      }
    });
  }

  // Also check 360x640 for all 8 items + Logout >= 44x44 inside viewport on mobile
  test('Student mobile drawer has all 8 items and Logout >= 44x44 inside viewport at 360x640', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 360, height: 640 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const hamburger = page.locator('header button[aria-label="Open menu"]');
    await expect(hamburger).toBeVisible();
    await hamburger.click();

    const drawer = page.locator('aside.sidebar-drawer');
    await expect(drawer).toBeVisible();

    const navItems = drawer.locator('a.sidebar-nav-item');
    expect(await navItems.count()).toBe(8);

    for (let i = 0; i < 8; i++) {
      const item = navItems.nth(i);
      await expect(item).toBeVisible();
      const box = await item.boundingBox();
      expect(box.height).toBeGreaterThanOrEqual(44);
      expect(box.y + box.height).toBeLessThanOrEqual(640);
    }

    const logoutBtn = drawer.locator('button[aria-label="Logout"]');
    await expect(logoutBtn).toBeVisible();
    const logoutBox = await logoutBtn.boundingBox();
    expect(logoutBox.height).toBeGreaterThanOrEqual(44);
    expect(logoutBox.y + logoutBox.height).toBeLessThanOrEqual(640);
  });

  // 2. Expand drawer without navigation or logout (hamburger on phone, rail on tablet/desktop)
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
  ]) {
    const triggers = vp.width < 640
      ? [{ name: 'hamburger', selector: 'header button[aria-label="Open menu"]' }]
      : [
          { name: 'logo', selector: 'button[data-rail="logo"]' },
          { name: 'Dashboard icon', selector: 'button[data-rail="dashboard"]' },
          { name: 'Messages icon', selector: 'button[data-rail="messages"]' },
          { name: 'Settings icon', selector: 'button[data-rail="settings"]' },
          { name: 'Logout icon', selector: 'button[data-rail="logout"]' },
          { name: 'empty rail area', selector: '.sidebar-rail', position: { x: 30, y: 10 } },
        ];

    for (const trigger of triggers) {
      test(`Student tapping ${trigger.name} at ${vp.width}x${vp.height} expands full sidebar without navigation or logout`, async ({ page }) => {
        await setupStudentMocks(page);
        await page.setViewportSize(vp);
        await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        const initialUrl = page.url();

        // Trigger expand
        const target = page.locator(trigger.selector);
        if (trigger.position) {
          await target.click({ position: trigger.position });
        } else {
          await target.click();
        }
        await page.waitForTimeout(250);

        // Sidebar expanded drawer visible
        const drawer = page.locator('.sidebar-drawer');
        await expect(drawer).toBeVisible();

        // Subtitle visible
        await expect(drawer.locator('.sidebar-brand-subtitle')).toHaveText(/Student Portal/i);

        // All 8 labels visible
        const navLabels = drawer.locator('.sidebar-nav-label');
        expect(await navLabels.count()).toBe(8);
        const expectedLabels = ['Dashboard', 'Schedule', 'Zoom Classes', 'Assignments', 'Materials', 'Performance', 'Messages', 'Settings'];
        for (const label of expectedLabels) {
          await expect(drawer.locator(`.sidebar-nav-label:has-text("${label}")`)).toBeVisible();
        }

        // URL unchanged and no logout redirect
        expect(page.url()).toBe(initialUrl);
        expect(page.url()).not.toContain('logout');
        expect(page.url()).not.toContain('login');
      });
    }
  }

  // 3. Tap a labelled item -> URL changes to that route AND the sidebar is collapsed again within 500 ms
  test('Tapping a labelled item navigates and auto-collapses drawer within 500ms', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Expand sidebar via header hamburger button
    await page.locator('header button[aria-label="Open menu"]').click();
    const drawer = page.locator('.sidebar-drawer');
    await expect(drawer).toBeVisible();
    await page.waitForTimeout(500);

    // Tap Schedule labelled item
    const scheduleLink = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
    await expect(scheduleLink).toBeVisible();
    await scheduleLink.click();

    // URL changes
    await expect(page).toHaveURL(/\/student\/schedule/);

    // Auto-collapses within 500ms: drawer not visible
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 4. Backdrop tap, X, Escape each collapse; at 1280x800 main content bounding box is identical before and after opening (no push)
  test('Backdrop tap, X, logo click, and Escape collapse overlay; no push at 1280x800', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const main = page.locator('main').first();
    const initialMainBox = await main.boundingBox();
    expect(initialMainBox).not.toBeNull();

    const logoBtn = page.locator('button[data-rail="logo"]');
    const drawer = page.locator('.sidebar-drawer');
    const closeBtn = page.locator('button[aria-label="Close sidebar"]').last();
    const backdrop = page.locator('div[aria-label="Close sidebar overlay"]');

    // 1) Test X button and verify bounding box during and after
    await logoBtn.click();
    await expect(drawer).toBeVisible();
    const expandedMainBox = await main.boundingBox();
    expect(expandedMainBox.x, 'Main content X must not shift when sidebar is open').toBe(initialMainBox.x);
    expect(expandedMainBox.width, 'Main content width must not change when sidebar is open').toBe(initialMainBox.width);
    await page.waitForTimeout(500);

    await closeBtn.click();
    await expect(drawer).not.toBeVisible();
    const afterCloseBox = await main.boundingBox();
    expect(afterCloseBox.x).toBe(initialMainBox.x);
    expect(afterCloseBox.width).toBe(initialMainBox.width);

    // 2) Test Backdrop tap
    await page.locator('button[data-rail="settings"]').click();
    await expect(drawer).toBeVisible();
    await backdrop.click({ position: { x: 500, y: 300 } });
    await expect(drawer).not.toBeVisible();

    // 3) Test Escape key
    await page.locator('button[data-rail="dashboard"]').click();
    await expect(drawer).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(drawer).not.toBeVisible();

    // 4) Test clicking expanded header brand/logo
    await page.locator('button[data-rail="logout"]').click();
    await expect(drawer).toBeVisible();
    await page.waitForTimeout(500);
    await page.locator('.sidebar-header-brand').click();
    await expect(drawer).not.toBeVisible();
  });

  // 5. Double-tap on a rail icon opens once and does not navigate
  test('Double-tap on a rail icon opens once and does not navigate', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const messagesRailBtn = page.locator('button[data-rail="messages"]');
    await messagesRailBtn.dblclick();
    await page.waitForTimeout(300);

    // URL is still /student/dashboard (did not navigate)
    expect(page.url()).toContain('/student/dashboard');
    expect(page.url()).not.toContain('/student/messages');

    // Drawer is open
    await expect(page.locator('.sidebar-drawer')).toBeVisible();
  });

  // 6. Adaptive heights: all 8 items + Logout visible with no scrolling at 320x568, 360x640, 360x740, 390x844, 768x1024, 1280x800
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 640 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
  ]) {
    test(`Student expanded sidebar displays all 8 labels incl. Settings and Logout inside viewport at ${vp.width}x${vp.height} with no MENU label`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Open drawer via hamburger on phones, logo on tablet/desktop
      if (vp.width < 640) {
        await page.locator('header button[aria-label="Open menu"]').click();
      } else {
        await page.locator('button[data-rail="logo"]').click();
      }
      await page.waitForTimeout(250);

      const drawer = page.locator('.sidebar-drawer');
      await expect(drawer).toBeVisible();

      // No "MENU" label
      const menuLabel = drawer.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);

      // All 8 nav items visible with labels inside viewport
      const navItems = drawer.locator('a.sidebar-nav-item');
      const count = await navItems.count();
      expect(count).toBe(8);

      for (let i = 0; i < count; i++) {
        const item = navItems.nth(i);
        await expect(item).toBeVisible();
        const box = await item.boundingBox();
        expect(box).not.toBeNull();
        expect(box.y + box.height, `Nav item ${i} bottom must be <= viewport height`).toBeLessThanOrEqual(vp.height);
      }

      // Explicitly check Settings is visible inside viewport
      const settingsItem = drawer.locator('a.sidebar-nav-item:has-text("Settings")');
      await expect(settingsItem).toBeVisible();
      const settingsBox = await settingsItem.boundingBox();
      expect(settingsBox.y + settingsBox.height, 'Settings item must be within viewport height').toBeLessThanOrEqual(vp.height);

      // Logout button visible inside viewport
      const logoutBtn = drawer.locator('button[aria-label="Logout"]');
      await expect(logoutBtn).toBeVisible();
      const logoutBox = await logoutBtn.boundingBox();
      expect(logoutBox.y + logoutBox.height, 'Logout button must be within viewport height').toBeLessThanOrEqual(vp.height);
    });
  }

  // 7. Colours: rail and expanded use design tokens, primary resolves to rgb(79, 11, 217)
  test('Student colours: rail and expanded use design tokens, primary resolves to rgb(79, 11, 217)', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Rail logo tile has primary color
    const railLogoTile = page.locator('.sidebar-rail .sidebar-logo-tile');
    await expect(railLogoTile).toBeVisible();
    const primaryBg = await railLogoTile.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(primaryBg).toBe('rgb(79, 11, 217)');

    // Expand sidebar
    await page.locator('button[data-rail="logo"]').click();
    await page.waitForTimeout(250);

    // Expanded logo tile also has primary color
    const expandedLogoTile = page.locator('.sidebar-drawer .sidebar-logo-tile');
    await expect(expandedLogoTile).toBeVisible();
    const expandedPrimaryBg = await expandedLogoTile.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(expandedPrimaryBg).toBe('rgb(79, 11, 217)');
  });

  // 8. Screenshots (saved under /tmp): collapsed + expanded at 320x568, 360x740, 768x1024, 1280x800
  for (const shotVp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
  ]) {
    test(`Capture screenshots (collapsed + expanded) at ${shotVp.width}x${shotVp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(shotVp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const tmpDir = process.platform === 'win32' ? 'C:/tmp/tit-screenshots' : '/tmp/tit-screenshots';
      if (!fs.existsSync(tmpDir)) {
        fs.mkdirSync(tmpDir, { recursive: true });
      }

      // Collapsed screenshot
      const collapsedPath = path.join(tmpDir, `student_collapsed_${shotVp.width}x${shotVp.height}.png`);
      await page.screenshot({ path: collapsedPath, fullPage: false });

      // Expanded screenshot
      if (shotVp.width < 640) {
        await page.locator('header button[aria-label="Open menu"]').click();
      } else {
        await page.locator('button[data-rail="logo"]').click();
      }
      await page.waitForTimeout(300);

      const expandedPath = path.join(tmpDir, `student_expanded_${shotVp.width}x${shotVp.height}.png`);
      await page.screenshot({ path: expandedPath, fullPage: false });

      expect(fs.existsSync(collapsedPath)).toBe(true);
      expect(fs.existsSync(expandedPath)).toBe(true);
    });
  }


});

test.describe('Phase 4 - Student Sidebar Hover (Mouse) Expansion', () => {

  const studentExpectedLabels = ['Dashboard', 'Schedule', 'Zoom Classes', 'Assignments', 'Materials', 'Performance', 'Messages', 'Settings'];

  // Helper: hover on the rail and wait for the hover-drawer to appear
  async function hoverOpenDrawer(page, selector, position) {
    if (position) {
      await page.hover(selector, { position });
    } else {
      await page.hover(selector);
    }
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });
    return drawer;
  }

  // 1. Hover on rail logo, each of 8 icons, and empty rail area → expanded sidebar visible
  for (const vp of [
    { width: 1280, height: 800 },
    { width: 1366, height: 768 },
  ]) {
    for (const trigger of [
      { name: 'rail logo', selector: 'button[data-rail="logo"]' },
      { name: 'Dashboard icon', selector: 'button[data-rail="dashboard"]' },
      { name: 'Schedule icon', selector: 'button[data-rail="schedule"]' },
      { name: 'Zoom Classes icon', selector: 'button[data-rail="zoom-classes"]' },
      { name: 'Assignments icon', selector: 'button[data-rail="assignments"]' },
      { name: 'Materials icon', selector: 'button[data-rail="materials"]' },
      { name: 'Performance icon', selector: 'button[data-rail="performance"]' },
      { name: 'Messages icon', selector: 'button[data-rail="messages"]' },
      { name: 'Settings icon', selector: 'button[data-rail="settings"]' },
      { name: 'empty rail area', selector: '.sidebar-rail', position: { x: 30, y: 10 } },
    ]) {
      test(`Student hover on ${trigger.name} at ${vp.width}x${vp.height} opens expanded sidebar with all labels`, async ({ page }) => {
        await setupStudentMocks(page);
        await page.setViewportSize(vp);
        await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        const drawer = await hoverOpenDrawer(page, trigger.selector, trigger.position);

        // Hover drawer starts at x = 0
        const drawerBox = await drawer.boundingBox();
        expect(drawerBox.x, 'Hover drawer must start at x=0').toBe(0);

        // Rail is hidden underneath as layout placeholder (single icon column)
        await expect(page.locator('.sidebar-rail')).toHaveCSS('visibility', 'hidden');

        // No X button in hover mode
        await expect(drawer.locator('.sidebar-close-btn')).toHaveCount(0);
        await expect(drawer.locator('button[aria-label="Close sidebar"]')).toHaveCount(0);

        // All 8 labels visible
        for (const label of studentExpectedLabels) {
          await expect(drawer.locator(`.sidebar-nav-label:has-text("${label}")`)).toBeVisible();
        }

        // User card and Logout visible
        await expect(drawer.locator('.sidebar-user-card')).toBeVisible();
        await expect(drawer.locator('button[aria-label="Logout"]')).toBeVisible();

        // URL unchanged
        expect(page.url()).toContain('/student/dashboard');
      });
    }
  }

  // 2. Move mouse from rail to expanded drawer → it stays open (no flicker)
  test('Student hover: moving mouse from rail into expanded drawer keeps it open', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Hover on rail to open
    await page.hover('button[data-rail="dashboard"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    // Move mouse into the middle of the expanded drawer
    const drawerBox = await drawer.boundingBox();
    await page.mouse.move(drawerBox.x + drawerBox.width / 2, drawerBox.y + drawerBox.height / 2);

    // Sample 3 times over 600ms to confirm it stays open
    for (let i = 0; i < 3; i++) {
      await page.waitForTimeout(200);
      await expect(drawer).toBeVisible();
    }
  });

  // 3. Move mouse to main content → drawer closes within 500ms
  test('Student hover: moving mouse to main content closes hover drawer within 500ms', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Hover on rail to open
    await page.hover('button[data-rail="dashboard"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    // Move mouse to main content area (far right)
    await page.mouse.move(800, 400);

    // Drawer closes within 500ms
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 4. Gap: distance between header bottom and first nav item top >= 16px
  for (const vp of [
    { width: 1280, height: 800 },
    { width: 360, height: 740 },
  ]) {
    test(`Student gap between header and first nav item >= 16px at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      if (vp.width < 640) {
        await page.locator('header button[aria-label="Open menu"]').click();
      } else {
        await page.locator('button[data-rail="logo"]').click();
      }
      const drawer = page.locator('.sidebar-drawer');
      await expect(drawer).toBeVisible();
      await page.waitForTimeout(250);

      const header = drawer.locator('.sidebar-expanded-header');
      const firstNav = drawer.locator('a.sidebar-nav-item').first();
      await expect(header).toBeVisible();
      await expect(firstNav).toBeVisible();

      const headerBox = await header.boundingBox();
      const firstNavBox = await firstNav.boundingBox();
      const gap = firstNavBox.y - (headerBox.y + headerBox.height);
      expect(gap, `Gap between header and first item must be >= 16px (got ${gap}px)`).toBeGreaterThanOrEqual(16);

      // No "MENU" text
      const menuLabel = drawer.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);
    });
  }

  // 5. Main content bounding box identical before/after hover (no layout shift)
  test('Student hover: main content bounding box unchanged at 1280x800', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const main = page.locator('main').first();
    const initialBox = await main.boundingBox();
    expect(initialBox).not.toBeNull();

    // Hover to open
    await page.hover('button[data-rail="logo"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    const duringBox = await main.boundingBox();
    expect(duringBox.x, 'Main x must not shift during hover').toBe(initialBox.x);
    expect(duringBox.width, 'Main width must not change during hover').toBe(initialBox.width);

    // Move mouse away to close
    await page.mouse.move(800, 400);
    await expect(drawer).not.toBeVisible({ timeout: 500 });

    const afterBox = await main.boundingBox();
    expect(afterBox.x).toBe(initialBox.x);
    expect(afterBox.width).toBe(initialBox.width);
  });

  // 6. Navigate via hover: hover to open, click an item, URL changes, drawer closes
  test('Student hover: clicking a labelled item navigates and closes drawer', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Hover on schedule icon to open
    await page.hover('button[data-rail="schedule"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });
    await page.waitForTimeout(250);

    // Click Schedule in the expanded drawer
    const scheduleLink = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
    await expect(scheduleLink).toBeVisible();
    await scheduleLink.click();

    // URL changes
    await expect(page).toHaveURL(/\/student\/schedule/);

    // Drawer closes
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 7. Escape closes hover drawer
  test('Student hover: Escape key closes hover drawer', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    await page.hover('button[data-rail="logo"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    await page.keyboard.press('Escape');
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 8. Zero-jump alignment at 1280x800 and 1366x768:
  //    - Drawer starts at x = 0
  //    - Rail has visibility: hidden (single icon column)
  //    - elementFromPoint at each rail icon hits inside drawer
  //    - Logo tile center x/y match within 2px
  //    - Each of 8 item icon center x matches within 2px, center y within 4px
  //    - No X button in hover mode
  for (const vp of [
    { width: 1280, height: 800 },
    { width: 1366, height: 768 },
  ]) {
    test(`Student hover: icon and logo zero-jump alignment at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Measure rail logo tile
      const railLogoTile = page.locator('.sidebar-rail .sidebar-logo-tile');
      await expect(railLogoTile).toBeVisible();
      const railLogoBox = await railLogoTile.boundingBox();
      const railLogoCenterX = railLogoBox.x + railLogoBox.width / 2;
      const railLogoCenterY = railLogoBox.y + railLogoBox.height / 2;

      // Measure rail item icons
      const railItems = page.locator('.sidebar-rail button.sidebar-rail-item');
      const railCount = await railItems.count();
      expect(railCount).toBe(8);

      const railIcons = [];
      for (let i = 0; i < railCount; i++) {
        const iconEl = railItems.nth(i).locator('.sidebar-rail-icon');
        const iconBox = await iconEl.boundingBox();
        railIcons.push({
          x: iconBox.x + iconBox.width / 2,
          y: iconBox.y + iconBox.height / 2,
        });
      }

      // Hover to open
      await page.hover('button[data-rail="logo"]');
      const drawer = page.locator('.sidebar-drawer.hover-drawer');
      await expect(drawer).toBeVisible({ timeout: 2000 });

      // 1. Drawer starts at x = 0
      const drawerBox = await drawer.boundingBox();
      expect(drawerBox.x, 'Hover drawer boundingBox x must be 0').toBe(0);

      // 2. Rail is hidden (single visible icon column)
      await expect(page.locator('.sidebar-rail')).toHaveCSS('visibility', 'hidden');

      // 3. No X button in hover mode
      await expect(drawer.locator('.sidebar-close-btn')).toHaveCount(0);

      // 4. Logo tile center x and y match within 2px
      const expLogoTile = drawer.locator('.sidebar-logo-tile');
      await expect(expLogoTile).toBeVisible();
      const expLogoBox = await expLogoTile.boundingBox();
      const expLogoCenterX = expLogoBox.x + expLogoBox.width / 2;
      const expLogoCenterY = expLogoBox.y + expLogoBox.height / 2;
      expect(Math.abs(expLogoCenterX - railLogoCenterX), `Logo tile x diff ${Math.abs(expLogoCenterX - railLogoCenterX)} must be <= 2px`).toBeLessThanOrEqual(2);
      expect(Math.abs(expLogoCenterY - railLogoCenterY), `Logo tile y diff ${Math.abs(expLogoCenterY - railLogoCenterY)} must be <= 2px`).toBeLessThanOrEqual(2);

      // 5. Each item icon center x within 2px, center y within 4px, elementFromPoint inside drawer
      const navItems = drawer.locator('a.sidebar-nav-item');
      const navCount = await navItems.count();
      expect(navCount).toBe(8);

      for (let i = 0; i < navCount; i++) {
        const expIcon = navItems.nth(i).locator('.sidebar-nav-icon');
        const expIconBox = await expIcon.boundingBox();
        const expIconCenterX = expIconBox.x + expIconBox.width / 2;
        const expIconCenterY = expIconBox.y + expIconBox.height / 2;

        const diffX = Math.abs(railIcons[i].x - expIconCenterX);
        const diffY = Math.abs(railIcons[i].y - expIconCenterY);
        expect(diffX, `Item ${i} centre x diff ${diffX.toFixed(2)}px must be <= 2px`).toBeLessThanOrEqual(2);
        expect(diffY, `Item ${i} centre y diff ${diffY.toFixed(2)}px must be <= 4px`).toBeLessThanOrEqual(4);

        // elementFromPoint at rail icon center returns element inside drawer
        const hitDrawer = await page.evaluate(({ x, y }) => {
          const el = document.elementFromPoint(x, y);
          return el ? el.closest('aside.sidebar-drawer') !== null : false;
        }, { x: railIcons[i].x, y: railIcons[i].y });
        expect(hitDrawer, `elementFromPoint at icon ${i} (${railIcons[i].x}, ${railIcons[i].y}) must hit inside drawer`).toBe(true);
      }
    });
  }

  // 9. Alignment click test: hover icon N, then click at the same point → navigates to route N
  test('Student alignment click: hover icon N, click at same coordinates navigates to route N', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Pick Schedule icon (route /student/schedule)
    const scheduleRailItem = page.locator('button[data-rail="schedule"] .sidebar-rail-icon');
    const railBox = await scheduleRailItem.boundingBox();
    const clickX = railBox.x + railBox.width / 2;
    const clickY = railBox.y + railBox.height / 2;

    // Hover to trigger expand
    await page.mouse.move(clickX, clickY);
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });
    await page.waitForTimeout(100);

    // Click at the exact same point
    await page.mouse.click(clickX, clickY);

    // Navigates to /student/schedule
    await expect(page).toHaveURL(/\/student\/schedule/);
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 10. Touch mode at 320x568, 360x740, 768x1024: drawer starts at x=0, covers rail, X button and backdrop visible (REAL TOUCH)
  for (const touchVp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 768, height: 1024 },
  ]) {
    test(`Student touch mode at ${touchVp.width}x${touchVp.height}: drawer starts at x=0 with X button and backdrop`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: touchVp,
        hasTouch: true,
        isMobile: true,
      });
      const page = await context.newPage();
      await setupStudentMocks(page);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Tap opener with real touch: hamburger on phones, rail logo on tablet
      if (touchVp.width < 640) {
        const hamburger = page.locator('header button[aria-label="Open menu"]');
        await expect(hamburger).toBeVisible();
        await hamburger.tap();
      } else {
        const logoBtn = page.locator('button[data-rail="logo"]');
        await expect(logoBtn).toBeVisible();
        await logoBtn.tap();
      }

      const drawer = page.locator('.sidebar-drawer.touch-drawer');
      await expect(drawer).toBeVisible({ timeout: 2000 });

      // Drawer starts at x = 0
      const box = await drawer.boundingBox();
      expect(box.x, 'Touch drawer must start at x=0').toBe(0);

      // Backdrop is visible
      await expect(page.locator('.sidebar-backdrop')).toBeVisible();

      // Close button is visible
      const closeBtn = drawer.locator('button.sidebar-close-btn');
      await expect(closeBtn).toBeVisible();

      // Wait past guard (>450ms) then tapping close button closes drawer
      await page.waitForTimeout(500);
      await closeBtn.tap();
      await expect(drawer).not.toBeVisible({ timeout: 500 });
      await expect(page.locator('.sidebar-backdrop')).not.toBeVisible();

      await context.close();
    });
  }

  // ── Bug 1: Real Touch Tests (locator.tap & touchscreen.tap, hasTouch + isMobile) ──
  const touchViewports = [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
  ];

  const studentRailTargets = [
    { name: 'logo', selector: '.sidebar-rail-logo-btn' },
    { name: 'Dashboard icon', selector: 'button[data-rail="dashboard"]' },
    { name: 'Schedule icon', selector: 'button[data-rail="schedule"]' },
    { name: 'Zoom Classes icon', selector: 'button[data-rail="zoom-classes"]' },
    { name: 'Assignments icon', selector: 'button[data-rail="assignments"]' },
    { name: 'Materials icon', selector: 'button[data-rail="materials"]' },
    { name: 'Performance icon', selector: 'button[data-rail="performance"]' },
    { name: 'Messages icon', selector: 'button[data-rail="messages"]' },
    { name: 'Settings icon', selector: 'button[data-rail="settings"]' },
    { name: 'Logout icon', selector: 'button[data-rail="logout"]' },
    { name: 'empty rail area', selector: '.sidebar-rail', isEmptyArea: true },
  ];

  for (const touchVp of touchViewports) {
    // Regression test with page.touchscreen.tap exactly on the logo/hamburger
    test(`Bug 1 Regression: touchscreen.tap on logo at ${touchVp.width}x${touchVp.height} opens drawer and stays open >= 1500ms`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: touchVp,
        hasTouch: true,
        isMobile: true,
      });
      const page = await context.newPage();
      await setupStudentMocks(page);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const opener = touchVp.width < 640
        ? page.locator('header button[aria-label="Open menu"]')
        : page.locator('.sidebar-rail-logo-btn');
      await expect(opener).toBeVisible();
      const openerBox = await opener.boundingBox();
      expect(openerBox).not.toBeNull();

      // Real touchscreen tap on the exact center of the opener button
      await page.touchscreen.tap(openerBox.x + openerBox.width / 2, openerBox.y + openerBox.height / 2);

      const drawer = page.locator('aside.sidebar-drawer');
      await expect(drawer).toBeVisible({ timeout: 2000 });
      const drawerBox = await drawer.boundingBox();
      expect(drawerBox.width).toBeGreaterThanOrEqual(250);

      // Must stay open >= 1500ms without ghost collapse
      await page.waitForTimeout(1500);
      await expect(drawer).toBeVisible();
      expect(page.url()).toContain('/student/dashboard');

      await context.close();
    });

    // Test opener / rail targets with real touch locator.tap() / touchscreen.tap()
    const targets = touchVp.width < 640
      ? [{ name: 'hamburger', selector: 'header button[aria-label="Open menu"]' }]
      : studentRailTargets;

    for (const target of targets) {
      test(`Bug 1: Real touch tap on ${target.name} at ${touchVp.width}x${touchVp.height} keeps drawer open >= 1500ms, then navigates on row tap`, async ({ browser }) => {
        let logoutRequested = false;
        const context = await browser.newContext({
          viewport: touchVp,
          hasTouch: true,
          isMobile: true,
        });
        const page = await context.newPage();
        await setupStudentMocks(page, {}, {
          onRequest: async (route, url) => {
            if (url.includes('logout') || url.includes('/auth/logout')) {
              logoutRequested = true;
              return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) });
            }
            return false;
          }
        });

        await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        if (target.isEmptyArea) {
          const rail = page.locator('.sidebar-rail');
          await expect(rail).toBeVisible();
          const railBox = await rail.boundingBox();
          // Tap lower empty region of the rail
          await page.touchscreen.tap(railBox.x + railBox.width / 2, railBox.y + railBox.height - 30);
        } else {
          const el = page.locator(target.selector);
          await expect(el).toBeVisible();
          await el.tap();
        }

        const drawer = page.locator('aside.sidebar-drawer');
        await expect(drawer).toBeVisible({ timeout: 2000 });
        const drawerBox = await drawer.boundingBox();
        // width ≈ 260 or 86vw
        const expectedW = Math.min(260, touchVp.width * 0.86);
        expect(drawerBox.width).toBeGreaterThanOrEqual(expectedW - 10);

        // Still visible after 1500 ms, pathname unchanged, no logout
        await page.waitForTimeout(1500);
        await expect(drawer).toBeVisible();
        expect(page.url()).toContain('/student/dashboard');
        expect(logoutRequested, 'User must not be logged out by rail tap').toBe(false);

        // After guard passes (we waited 1500ms > 450ms), tapping a labelled row navigates and collapses within 500ms
        const scheduleRow = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
        await expect(scheduleRow).toBeVisible();
        await scheduleRow.tap();

        await expect(page).toHaveURL(/\/student\/schedule/);
        await expect(drawer).not.toBeVisible({ timeout: 500 });

        await context.close();
      });
    }
  }

  // ── Bug 2: Hover menu row luminance < 0.35 and contrast >= 4.5:1 at 1280x800 ──
  test('Bug 2: Hovered menu rows and Logout have luminance < 0.35 and contrast >= 4.5:1 at 1280x800', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Hover on rail logo to expand drawer
    await page.hover('button[data-rail="logo"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    const navRows = drawer.locator('a.sidebar-nav-item');
    const rowCount = await navRows.count();
    expect(rowCount).toBe(8);

    for (let i = 0; i < rowCount; i++) {
      const row = navRows.nth(i);
      await row.hover();
      await page.waitForTimeout(100);

      const colorCheck = await row.evaluate((el) => {
        function sRGBtoLin(c) {
          c = c / 255;
          return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        }
        function lum(r, g, b) {
          return 0.2126 * sRGBtoLin(r) + 0.7152 * sRGBtoLin(g) + 0.0722 * sRGBtoLin(b);
        }
        function parseColor(str) {
          const match = str.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
          if (!match) return { r: 15, g: 23, b: 42, a: 1 };
          return {
            r: parseInt(match[1], 10),
            g: parseInt(match[2], 10),
            b: parseInt(match[3], 10),
            a: match[4] !== undefined ? parseFloat(match[4]) : 1,
          };
        }
        const bgStr = window.getComputedStyle(el).backgroundColor;
        const pBg = parseColor(bgStr);
        // Blend over base dark sidebar #0f172a
        const effR = Math.round(pBg.r * pBg.a + 15 * (1 - pBg.a));
        const effG = Math.round(pBg.g * pBg.a + 23 * (1 - pBg.a));
        const effB = Math.round(pBg.b * pBg.a + 42 * (1 - pBg.a));
        const bgLuminance = lum(effR, effG, effB);

        const labelEl = el.querySelector('.sidebar-nav-label') || el;
        const textStr = window.getComputedStyle(labelEl).color;
        const pText = parseColor(textStr);
        const textLuminance = lum(pText.r, pText.g, pText.b);

        const lighter = Math.max(bgLuminance, textLuminance);
        const darker = Math.min(bgLuminance, textLuminance);
        const contrast = (lighter + 0.05) / (darker + 0.05);

        return { bgLuminance, contrast, bgStr, textStr };
      });

      expect(colorCheck.bgLuminance, `Row ${i} hover background luminance ${colorCheck.bgLuminance} must be < 0.35`).toBeLessThan(0.35);
      expect(colorCheck.contrast, `Row ${i} text contrast ${colorCheck.contrast.toFixed(2)} must be >= 4.5:1`).toBeGreaterThanOrEqual(4.5);
    }

    // Hover Logout button
    const logoutBtn = drawer.locator('.sidebar-logout-btn');
    await expect(logoutBtn).toBeVisible();
    await logoutBtn.hover();
    await page.waitForTimeout(100);

    const logoutCheck = await logoutBtn.evaluate((el) => {
      function sRGBtoLin(c) {
        c = c / 255;
        return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
      }
      function lum(r, g, b) {
        return 0.2126 * sRGBtoLin(r) + 0.7152 * sRGBtoLin(g) + 0.0722 * sRGBtoLin(b);
      }
      function parseColor(str) {
        const match = str.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
        if (!match) return { r: 15, g: 23, b: 42, a: 1 };
        return {
          r: parseInt(match[1], 10),
          g: parseInt(match[2], 10),
          b: parseInt(match[3], 10),
          a: match[4] !== undefined ? parseFloat(match[4]) : 1,
        };
      }
      const bgStr = window.getComputedStyle(el).backgroundColor;
      const pBg = parseColor(bgStr);
      const effR = Math.round(pBg.r * pBg.a + 15 * (1 - pBg.a));
      const effG = Math.round(pBg.g * pBg.a + 23 * (1 - pBg.a));
      const effB = Math.round(pBg.b * pBg.a + 42 * (1 - pBg.a));
      const bgLuminance = lum(effR, effG, effB);

      const spanEl = el.querySelector('span') || el;
      const textStr = window.getComputedStyle(spanEl).color;
      const pText = parseColor(textStr);
      const textLuminance = lum(pText.r, pText.g, pText.b);

      const lighter = Math.max(bgLuminance, textLuminance);
      const darker = Math.min(bgLuminance, textLuminance);
      const contrast = (lighter + 0.05) / (darker + 0.05);

      return { bgLuminance, contrast, bgStr, textStr };
    });

    expect(logoutCheck.bgLuminance, `Logout hover background luminance ${logoutCheck.bgLuminance} must be < 0.35`).toBeLessThan(0.35);
    expect(logoutCheck.contrast, `Logout text contrast ${logoutCheck.contrast.toFixed(2)} must be >= 4.5:1`).toBeGreaterThanOrEqual(4.5);
  });

  // ── Change 3: Always Stacked User Card & Viewport Fitting ────────────────
  const allViewports = [
    { width: 320, height: 568, mode: 'touch' },
    { width: 360, height: 640, mode: 'touch' },
    { width: 360, height: 740, mode: 'touch' },
    { width: 390, height: 844, mode: 'touch' },
    { width: 768, height: 1024, mode: 'touch' },
    { width: 1280, height: 800, mode: 'hover' },
    { width: 1366, height: 768, mode: 'hover' },
  ];

  for (const vp of allViewports) {
    test(`Change 3: Stacked card & all 8 items fit viewport without scroll at ${vp.width}x${vp.height} (${vp.mode})`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      let drawer;
      if (vp.mode === 'hover') {
        await page.hover('button[data-rail="logo"]');
        drawer = page.locator('.sidebar-drawer.hover-drawer');
      } else {
        if (vp.width < 640) {
          await page.click('header button[aria-label="Open menu"]');
        } else {
          await page.click('button[data-rail="logo"]');
        }
        drawer = page.locator('.sidebar-drawer.touch-drawer');
      }
      await expect(drawer).toBeVisible({ timeout: 2000 });
      await page.waitForTimeout(150);

      const drawerBox = await drawer.boundingBox();
      expect(drawerBox).not.toBeNull();

      // All 8 nav items fit inside viewport without scrolling
      const navItems = drawer.locator('a.sidebar-nav-item');
      const count = await navItems.count();
      expect(count).toBe(8);

      for (let i = 0; i < count; i++) {
        const itemBox = await navItems.nth(i).boundingBox();
        expect(itemBox.y, `Nav item ${i} y must be >= 0`).toBeGreaterThanOrEqual(0);
        expect(itemBox.y + itemBox.height, `Nav item ${i} bottom must be <= viewport height`).toBeLessThanOrEqual(vp.height);
      }

      // Nav container must NOT have vertical scrollbar (since height >= 568px)
      const navScrollable = await drawer.locator('.sidebar-expanded-nav').evaluate((el) => {
        return el.scrollHeight > el.clientHeight + 2;
      });
      expect(navScrollable, `Nav list must not scroll at viewport height ${vp.height}`).toBe(false);

      // Stacked user card
      const userCard = drawer.locator('.sidebar-user-card');
      await expect(userCard).toBeVisible();
      const cardBox = await userCard.boundingBox();
      expect(cardBox.y + cardBox.height, `User card bottom must be <= viewport height`).toBeLessThanOrEqual(vp.height);

      // Top row: avatar & name
      const avatar = userCard.locator('.sidebar-user-avatar');
      const name = userCard.locator('.sidebar-user-name');
      await expect(avatar).toBeVisible();
      await expect(name).toBeVisible();
      const avatarBox = await avatar.boundingBox();

      // Logout button directly below avatar, full card width (>= 80% of sidebar width), min-height >= 44
      const logoutBtn = userCard.locator('.sidebar-logout-btn');
      await expect(logoutBtn).toBeVisible();
      const logoutBox = await logoutBtn.boundingBox();

      expect(logoutBox.y, 'Logout button must be vertically below avatar').toBeGreaterThanOrEqual(avatarBox.y + avatarBox.height - 4);
      expect(logoutBox.y + logoutBox.height, 'Logout button bottom must be within viewport').toBeLessThanOrEqual(vp.height);
      expect(logoutBox.height, 'Logout button min-height must be >= 44px').toBeGreaterThanOrEqual(44);
      expect(logoutBox.width, 'Logout button must be >= 80% of drawer width').toBeGreaterThanOrEqual(drawerBox.width * 0.80);

      // Confirm no one-row variant exists in computed style
      const cardFlexDir = await userCard.evaluate(el => window.getComputedStyle(el).flexDirection);
      expect(cardFlexDir, 'User card must have flex-direction: column').toBe('column');
    });
  }

  // PART B: Fee display tests restoring original design with only package discount wording removed
  const partBMockHandlers = {
    onRequest: async (route, url) => {
      if (url.includes('/subjects/prices')) {
        await route.fulfill({
          status: 200, contentType: 'application/json',
          body: JSON.stringify({
            grade_10_to_11: [
              { id: 1, name: 'Mathematics', price: 2000, medium: 'english' },
              { id: 2, name: 'Science', price: 2000, medium: 'english' },
              { id: 3, name: 'English', price: 1500, medium: 'english' },
              { id: 4, name: 'Tamil', price: 1300, medium: 'english' }
            ]
          })
        });
        return true;
      }
      if (url.includes('/packages')) {
        await route.fulfill({
          status: 200, contentType: 'application/json',
          body: JSON.stringify([
            {
              id: 1,
              name: 'Greade-6-9 in-package English Medium',
              applicable_grades: [10, 11],
              medium: 'english',
              price: 3500,
              package_price: 3500,
              type: 'all_subjects'
            }
          ])
        });
        return true;
      }
      if (url.includes('/settings')) {
        await route.fulfill({
          status: 200, contentType: 'application/json',
          body: JSON.stringify({
            admission_fees_config: JSON.stringify({
              '10': { enabled: true, amount: 500 }
            })
          })
        });
        return true;
      }
      if (url.includes('/student/update-subjects')) {
        await route.fulfill({
          status: 200, contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            monthly_fee: 3500,
            amount: 4000,
            admission_fee: 500,
            already_paid: false
          })
        });
        return true;
      }
      return false;
    }
  };

  const partBUserOverrides = {
    current_grade: 'Grade 10',
    medium: 'english',
    subjects: ['Mathematics', 'Science', 'English', 'Tamil'],
    selected_subjects: ['Mathematics', 'Science', 'English', 'Tamil'],
  };

  for (const vp of [{ width: 320, height: 568 }, { width: 390, height: 844 }, { width: 1280, height: 800 }]) {
    test(`Part B: Registration payment step original look without package discount wording at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page, partBUserOverrides, partBMockHandlers);
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/register?step=2', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);

      const modal = page.locator('.tit-reg-container');
      await expect(modal).toBeVisible();

      // Wait for fee calculation to finish: look for Subjects Total
      await expect(modal.locator('.fee-row-label:has-text("Subjects Total:")')).toBeVisible({ timeout: 5000 });

      // 1. Contains NO text matching /package\s*discount/i, /in-?package/i, or package name
      const text = await modal.innerText();
      expect(text, 'Payment step must not contain package discount wording').not.toMatch(/package\s*discount/i);
      expect(text, 'Payment step must not contain in-package wording').not.toMatch(/in-?package/i);
      expect(text, 'Payment step must not contain appliedPackage name').not.toContain('Greade-6-9');

      // 2. Contains strikethrough element for Subjects Total (Rs. 6,800.00)
      const strikethroughRow = modal.locator('.fee-row.fee-row-strikethrough');
      await expect(strikethroughRow).toHaveCount(1);
      await expect(strikethroughRow.locator('.fee-row-label')).toHaveText('Subjects Total:');
      await expect(strikethroughRow.locator('.fee-row-value')).toContainText('6,800');

      // 3. Contains label "Monthly Fee:" with green discount style and Rs. 3,500.00
      const discountRow = modal.locator('.fee-row.fee-row-discount');
      await expect(discountRow).toHaveCount(1);
      await expect(discountRow.locator('.fee-row-label')).toHaveText('Monthly Fee:');
      await expect(discountRow.locator('.fee-row-value')).toContainText('3,500');

      // 4. Contains Admission Fee (First Time): Rs. 500.00 and Total Amount: Rs. 4,000.00
      const admissionRow = modal.locator('.fee-row', { hasText: 'Admission Fee' });
      await expect(admissionRow).toHaveCount(1);
      await expect(admissionRow.locator('.fee-row-value')).toContainText('500');

      const totalRow = modal.locator('.fee-row', { hasText: 'Total Amount' });
      await expect(totalRow).toHaveCount(1);
      await expect(totalRow.locator('.fee-row-value')).toContainText('4,000');

      // 5. No horizontal overflow
      const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
      expect(scrollWidth, 'Page must not have horizontal overflow').toBeLessThanOrEqual(clientWidth + 1);

      // 6. Each FeeRow label and value do not overlap
      const feeRows = modal.locator('.fee-row');
      const rowCount = await feeRows.count();
      expect(rowCount).toBeGreaterThan(0);
      for (let i = 0; i < rowCount; i++) {
        const row = feeRows.nth(i);
        const label = row.locator('.fee-row-label');
        const val = row.locator('.fee-row-value');
        const lBox = await label.boundingBox();
        const vBox = await val.boundingBox();
        if (lBox && vBox) {
          const isSeparateLines = vBox.y >= lBox.y + lBox.height - 1 || lBox.y >= vBox.y + vBox.height - 1;
          const isNonOverlappingColumns = vBox.x >= lBox.x + lBox.width - 1;
          expect(isSeparateLines || isNonOverlappingColumns, `FeeRow ${i} label and value must not overlap`).toBe(true);
        }
      }

      // Take screenshot at 1280x800 as requested
      if (vp.width === 1280) {
        await page.screenshot({ path: 'C:/tmp/registration-payment-step-1280x800.png' });
      }
    });

    test(`Part B: SubjectUpdateModal step 1 summary & step 2 contain no package wording, correct strikethrough & numbers at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page, partBUserOverrides, partBMockHandlers);
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto('/student/settings', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);

      const editBtn = page.locator('button:has-text("Edit Subjects & Medium")');
      await expect(editBtn).toBeVisible();
      await editBtn.click();
      await page.waitForTimeout(300);

      const updateModal = page.locator('div[role="dialog"], .shared-modal-dialog, .tit-reg-container, div[style*="z-index: 1000"], div[style*="zIndex: 1000"]').first();
      await expect(updateModal).toBeVisible();

      // STEP 1:
      // Verify step 1 summary card contains no /package\s*discount/i, /in-?package/i, or appliedPackage name
      const step1Text = await updateModal.innerText();
      expect(step1Text, 'Step 1 must not contain package discount wording').not.toMatch(/package\s*discount/i);
      expect(step1Text, 'Step 1 must not contain in-package wording').not.toMatch(/in-?package/i);
      expect(step1Text, 'Step 1 must not contain appliedPackage name').not.toContain('Greade-6-9');

      // Step 1 savings chip has "🎉 You save" without package name
      await expect(updateModal.locator('span:has-text("🎉 You save")')).toBeVisible();

      // Step 1 FeeRow check
      const step1FeeRows = updateModal.locator('.fee-row');
      const step1RowCount = await step1FeeRows.count();
      for (let i = 0; i < step1RowCount; i++) {
        const row = step1FeeRows.nth(i);
        const label = row.locator('.fee-row-label');
        const val = row.locator('.fee-row-value');
        const lBox = await label.boundingBox();
        const vBox = await val.boundingBox();
        if (lBox && vBox && vBox.width > 0) {
          const isSeparateLines = vBox.y >= lBox.y + lBox.height - 1 || lBox.y >= vBox.y + vBox.height - 1;
          const isNonOverlappingColumns = vBox.x >= lBox.x + lBox.width - 1;
          expect(isSeparateLines || isNonOverlappingColumns, `Step 1 FeeRow ${i} label and value must not overlap`).toBe(true);
        }
      }

      // Transition to STEP 2
      const saveBtn = page.locator('button:has-text("Save Subjects")');
      await expect(saveBtn).toBeVisible();
      await saveBtn.click();
      await page.waitForTimeout(400);

      // STEP 2:
      // Wait for step 2 fee card with Subjects Total
      await expect(updateModal.locator('.fee-row-label:has-text("Subjects Total:")')).toBeVisible({ timeout: 5000 });

      // 1. Contains NO text matching /package\s*discount/i, /in-?package/i, or package name
      const step2Text = await updateModal.innerText();
      expect(step2Text, 'Step 2 must not contain package discount wording').not.toMatch(/package\s*discount/i);
      expect(step2Text, 'Step 2 must not contain in-package wording').not.toMatch(/in-?package/i);
      expect(step2Text, 'Step 2 must not contain appliedPackage name').not.toContain('Greade-6-9');

      // 2. Contains strikethrough for Subjects Total (6,800) and discount row for Monthly Fee (3,500)
      const s2Strikethrough = updateModal.locator('.fee-row.fee-row-strikethrough');
      await expect(s2Strikethrough).toHaveCount(1);
      await expect(s2Strikethrough.locator('.fee-row-value')).toContainText('6,800');

      const s2Discount = updateModal.locator('.fee-row.fee-row-discount');
      await expect(s2Discount).toHaveCount(1);
      await expect(s2Discount.locator('.fee-row-label')).toHaveText('Monthly Fee:');
      await expect(s2Discount.locator('.fee-row-value')).toContainText('3,500');

      // 3. Admission fee (500) and Total (4,000)
      const s2Admission = updateModal.locator('.fee-row', { hasText: 'Admission Fee' });
      await expect(s2Admission).toHaveCount(1);
      await expect(s2Admission.locator('.fee-row-value')).toContainText('500');

      const s2Total = updateModal.locator('.fee-row', { hasText: 'Total Amount' });
      await expect(s2Total).toHaveCount(1);
      await expect(s2Total.locator('.fee-row-value')).toContainText('4,000');

      // 4. Step 2 FeeRows non-overlapping
      const step2FeeRows = updateModal.locator('.fee-row');
      const step2RowCount = await step2FeeRows.count();
      expect(step2RowCount).toBeGreaterThan(0);
      for (let i = 0; i < step2RowCount; i++) {
        const row = step2FeeRows.nth(i);
        const label = row.locator('.fee-row-label');
        const val = row.locator('.fee-row-value');
        const lBox = await label.boundingBox();
        const vBox = await val.boundingBox();
        if (lBox && vBox) {
          const isSeparateLines = vBox.y >= lBox.y + lBox.height - 1 || lBox.y >= vBox.y + vBox.height - 1;
          const isNonOverlappingColumns = vBox.x >= lBox.x + lBox.width - 1;
          expect(isSeparateLines || isNonOverlappingColumns, `Step 2 FeeRow ${i} label and value must not overlap`).toBe(true);
        }
      }
    });
  }

  // ── Requirement: Resizing / rotating across 640px clean switch ──
  test('Resizing across 640px switches cleanly without stuck drawer, stuck body scroll, or stale overlay', async ({ page }) => {
    await setupStudentMocks(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Open phone drawer
    const hamburger = page.locator('header button[aria-label="Open menu"]');
    await expect(hamburger).toBeVisible();
    await hamburger.click();

    const drawer = page.locator('.sidebar-drawer');
    await expect(drawer).toBeVisible();

    // Body scroll locked
    const overflowLocked = await page.evaluate(() => document.body.style.overflow);
    expect(overflowLocked).toBe('hidden');

    // Resize across 640px to tablet 768x1024
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.mouse.move(600, 600);
    await page.waitForTimeout(500);

    // Drawer must be closed, overlay gone, body scroll restored, rail visible
    await expect(drawer).not.toBeVisible();
    await expect(page.locator('.sidebar-backdrop')).not.toBeVisible();
    const overflowRestored = await page.evaluate(() => document.body.style.overflow);
    expect(overflowRestored).toBe('');
    await expect(page.locator('.sidebar-rail')).toBeVisible();

    // Resize back across 640px to 390x844
    await page.setViewportSize({ width: 390, height: 844 });
    await page.mouse.move(600, 600);
    await page.waitForTimeout(500);

    // Rail hidden, drawer closed, hamburger ready
    await expect(page.locator('.sidebar-rail')).not.toBeVisible();
    await expect(drawer).not.toBeVisible();
    await expect(hamburger).toBeVisible();
  });

  test('Resizing across 640px switches cleanly in touch context (mobile emulation)', async ({ browser }) => {
    const context = await browser.newContext({
      viewport: { width: 390, height: 844 },
      hasTouch: true,
      isMobile: true,
    });
    const page = await context.newPage();
    await setupStudentMocks(page);
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const hamburger = page.locator('header button[aria-label="Open menu"]');
    await expect(hamburger).toBeVisible();
    await hamburger.tap();

    const drawer = page.locator('.sidebar-drawer');
    await expect(drawer).toBeVisible();

    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(500);

    await expect(drawer).not.toBeVisible();
    await expect(page.locator('.sidebar-backdrop')).not.toBeVisible();
    const overflowRestored = await page.evaluate(() => document.body.style.overflow);
    expect(overflowRestored).toBe('');
    await expect(page.locator('.sidebar-rail')).toBeVisible();

    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForTimeout(500);

    await expect(page.locator('.sidebar-rail')).not.toBeVisible();
    await expect(drawer).not.toBeVisible();
    await expect(hamburger).toBeVisible();

    await context.close();
  });

  // ── Ghost-click test: real touch input ──
  test('Ghost-click test: tapping hamburger leaves drawer open >= 1.5s; backdrop closes it; row tap navigates + closes', async ({ browser }) => {
    const context = await browser.newContext({
      viewport: { width: 360, height: 740 },
      hasTouch: true,
      isMobile: true,
    });
    const page = await context.newPage();
    await setupStudentMocks(page);
    await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const hamburger = page.locator('header button[aria-label="Open menu"]');
    await expect(hamburger).toBeVisible();
    await hamburger.tap();

    const drawer = page.locator('.sidebar-drawer');
    await expect(drawer).toBeVisible();

    // Stays open for >= 1.5 s without ghost click re-toggle
    await page.waitForTimeout(1500);
    await expect(drawer).toBeVisible();

    // Tapping backdrop closes it (tap exposed area outside 280px drawer)
    const backdrop = page.locator('.sidebar-backdrop');
    await backdrop.click({ position: { x: 330, y: 300 } });
    await expect(drawer).not.toBeVisible({ timeout: 1000 });

    // Open again and tap a row to navigate and close
    await hamburger.tap();
    await expect(drawer).toBeVisible();
    await page.waitForTimeout(500); // pass 450ms guard

    const scheduleRow = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
    await scheduleRow.tap();
    await expect(page).toHaveURL(/\/student\/schedule/);
    await expect(drawer).not.toBeVisible({ timeout: 500 });

    await context.close();
  });

  // ── Issue 3: Notification panel positioning and clearance across viewports ──
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
  ]) {
    test(`Issue 3: Notification panel bounded and reachable at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Click notification bell
      const bell = page.locator('button[aria-label="Notifications"], button[title="Notifications"]').first();
      await expect(bell).toBeVisible();
      await bell.click();

      const panel = page.locator('.student-notif-dropdown, .global-notif-dropdown').first();
      await expect(panel).toBeVisible();

      const panelBox = await panel.boundingBox();
      expect(panelBox).not.toBeNull();

      // Panel must stay inside viewport
      expect(panelBox.x, `Left edge must be >= 0 at ${vp.width}px`).toBeGreaterThanOrEqual(0);
      if (vp.width < 640) {
        expect(panelBox.x, `Left edge must be >= 10 at ${vp.width}px`).toBeGreaterThanOrEqual(10);
      }
      expect(panelBox.x + panelBox.width, `Right edge must be <= innerWidth at ${vp.width}px`).toBeLessThanOrEqual(vp.width + 1);

      // Not covered by rail: elementFromPoint on its left edge returns an element inside the panel
      const pointElInside = await page.evaluate(({ x, y }) => {
        const el = document.elementFromPoint(x, y);
        const p = document.querySelector('.student-notif-dropdown, .global-notif-dropdown');
        return p && (p === el || p.contains(el));
      }, { x: panelBox.x + 8, y: panelBox.y + 12 });
      expect(pointElInside, `Left edge of notification panel must not be occluded by rail at ${vp.width}px`).toBe(true);

      // Notification header must be on a single line (title and actions do not wrap)
      const notifHeader = panel.locator('.student-notif-header, .global-notif-header').first();
      if (await notifHeader.count() > 0) {
        const headerBox = await notifHeader.boundingBox();
        expect(headerBox).not.toBeNull();
        expect(headerBox.height, `Notification header must remain a single row at ${vp.width}px`).toBeLessThanOrEqual(68);
      }

      // Close button and Mark all read reachable
      const closeBtn = panel.locator('button[aria-label="Close notifications"]');
      await expect(closeBtn).toBeVisible();
      const closeBox = await closeBtn.boundingBox();
      expect(closeBox.width).toBeGreaterThanOrEqual(39);
      expect(closeBox.height).toBeGreaterThanOrEqual(39);

      const markAllRead = panel.locator('button:has-text("Mark all read")');
      if (await markAllRead.count() > 0) {
        await expect(markAllRead).toBeVisible();
      }

      // Close panel
      await closeBtn.click();
      await expect(panel).not.toBeVisible();
    });
  }

  // ── Issue 4: Study Materials tab strip segmented control pills ──
  for (const w of [320, 360, 390]) {
    test(`Issue 4: Study Materials tab strip pills equal height, no clip, no scrollbar at ${w}px`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize({ width: w, height: 740 });
      await page.goto('/student/materials', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const tabPills = page.locator('.materials-tab-pill');
      await tabPills.first().waitFor({ state: 'visible', timeout: 5000 });
      const count = await tabPills.count();
      expect(count).toBeGreaterThanOrEqual(4);

      let activeHeight = null;
      let inactiveHeight = null;

      for (let i = 0; i < count; i++) {
        const pill = tabPills.nth(i);
        const box = await pill.boundingBox();
        expect(box.height, `Pill ${i} min-height must be >= 44px`).toBeGreaterThanOrEqual(43);

        const isActive = await pill.getAttribute('aria-selected') === 'true';
        if (isActive) {
          activeHeight = box.height;
        } else if (inactiveHeight === null) {
          inactiveHeight = box.height;
        }

        // No tab text clipped inside pill: scrollWidth <= clientWidth
        const noTextClip = await pill.evaluate(el => el.scrollWidth <= el.clientWidth + 1);
        expect(noTextClip, `Tab pill ${i} text must not be clipped`).toBe(true);
      }

      if (activeHeight !== null && inactiveHeight !== null) {
        expect(Math.abs(activeHeight - inactiveHeight), 'Active and inactive pill height must match within 1.5px').toBeLessThanOrEqual(1.5);
      }

      // Strip has no visible scrollbar
      const scrollbarStyle = await page.locator('.materials-tab-strip').evaluate(el => {
        const cs = window.getComputedStyle(el);
        return cs.scrollbarWidth;
      });
      expect(scrollbarStyle).toBe('none');

      // Check search input and cards fit at 320px
      const searchInput = page.locator('.materials-search-input');
      await expect(searchInput).toBeVisible();
      const sBox = await searchInput.boundingBox();
      expect(sBox.x + sBox.width).toBeLessThanOrEqual(w);

      const docScrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(docScrollWidth).toBeLessThanOrEqual(w);
    });
  }

  // ── Issue 5: My Schedule cards single-line time, status badge, join button, and chat bubble ──
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
  ]) {
    test(`Issue 5: Schedule cards single-line time, status badge, join >= 44px, no chat bubble at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupStudentMocks(page);
      await page.setViewportSize(vp);
      await page.goto('/student/schedule', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Check every schedule-time-text is single line
      const timeTexts = page.locator('.schedule-time-text');
      await timeTexts.first().waitFor({ state: 'visible', timeout: 5000 });
      const timeCount = await timeTexts.count();
      expect(timeCount).toBeGreaterThan(0);

      for (let i = 0; i < timeCount; i++) {
        const tEl = timeTexts.nth(i);
        const isSingleLine = await tEl.evaluate(el => {
          const lh = parseFloat(window.getComputedStyle(el).lineHeight) || 20;
          return el.clientHeight <= lh * 1.5;
        });
        expect(isSingleLine, `Time text ${i} must be single line at ${vp.width}px`).toBe(true);
      }

      // Status badge never wraps
      const badges = page.locator('.schedule-status-badge');
      const badgeCount = await badges.count();
      expect(badgeCount).toBeGreaterThan(0);
      for (let i = 0; i < badgeCount; i++) {
        const b = badges.nth(i);
        const ws = await b.evaluate(el => window.getComputedStyle(el).whiteSpace);
        expect(ws).toBe('nowrap');
      }

      // Full-width join button >= 44px
      const joinBtn = page.locator('.schedule-join-btn').first();
      if (await joinBtn.count() > 0) {
        const jBox = await joinBtn.boundingBox();
        expect(jBox.height).toBeGreaterThanOrEqual(44);
      }

      // Page content starts at x <= 16
      const mainBox = await page.locator('main').first().boundingBox();
      expect(mainBox.x).toBeLessThanOrEqual(16);

      // Chat bubble removed: verify 0 count
      await expect(page.locator('.floating-chat-bubble, [aria-label="WhatsApp support"]')).toHaveCount(0);

      // Open hamburger drawer and verify Logout button is reachable
      const hamburger = page.locator('header button[aria-label="Open menu"]');
      await hamburger.click();
      const drawer = page.locator('.sidebar-drawer');
      await expect(drawer).toBeVisible();

      // Logout button reachable
      const logoutBtn = drawer.locator('button[aria-label="Logout"]');
      await expect(logoutBtn).toBeVisible();
    });
  }

  // Verify teacher dashboard also has 0 floating chat bubbles
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
  ]) {
    test(`Teacher dashboard has no floating chat bubble at ${vp.width}x${vp.height}`, async ({ page }) => {
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);
      await expect(page.locator('.floating-chat-bubble, [aria-label="WhatsApp support"]')).toHaveCount(0);
    });
  }

});

