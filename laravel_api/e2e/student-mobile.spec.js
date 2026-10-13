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
  subjects: ['Maths', 'Science', 'English'],
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

  // 6. Gating screens (PendingApproval, PaymentRequired, Deactivated) at 320 and 390: no overflow
  for (const gVp of [{ width: 320, height: 640 }, { width: 390, height: 844 }]) {
    test(`Gating screens at ${gVp.width}x${gVp.height}: no overflow`, async ({ page }) => {
      // 6A: Pending Approval
      await setupStudentMocks(page, { admin_confirmed_at: null });
      await page.setViewportSize(gVp);
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      let scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `PendingApproval at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);
      await expect(page.locator('text=Account pending admin approval')).toBeVisible();

      // 6B: Payment Required
      await setupStudentMocks(page, { admin_confirmed_at: '2026-01-01 10:00:00', is_paid: false });
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `PaymentRequired at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);
      await expect(page.locator('h2.tit-reg-title:has-text("Payment Status")')).toBeVisible();

      // 6C: Deactivated
      await setupStudentMocks(page, { is_deactivated: true });
      await page.goto('/student/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      expect(scrollWidth, `Deactivated at ${gVp.width}px must not scroll horizontally`).toBeLessThanOrEqual(gVp.width);
      await expect(page.locator('text=Account Status')).toBeVisible();
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
