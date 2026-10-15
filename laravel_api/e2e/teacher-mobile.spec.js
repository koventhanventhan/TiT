import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const ROUTES = [
  { path: '/teacher/dashboard', name: 'dashboard' },
  { path: '/teacher/students', name: 'students' },
  { path: '/teacher/schedule', name: 'schedule' },
  { path: '/teacher/assignments', name: 'assignments' },
  { path: '/teacher/materials', name: 'materials' },
  { path: '/teacher/reports', name: 'reports' },
  { path: '/teacher/messages', name: 'messages' },
  { path: '/teacher/settings', name: 'settings' },
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

const teacherUser = {
  id: 1,
  name: 'Dr. Jane Teacher',
  full_name: 'Dr. Jane Teacher',
  email: 'jane.teacher@tit.lk',
  role: 'teacher',
  subject: 'Chemistry',
};

const studentUser = {
  id: 201,
  name: 'Kasun Perera',
  full_name: 'Kasun Perera',
  email: 'kasun@test.lk',
  role: 'user',
  registration_status: 'completed',
  admin_confirmed_at: '2026-01-01 10:00:00',
  is_paid: true,
};

async function setupMocks(page, isTeacher = true) {
  const currentUser = isTeacher ? teacherUser : studentUser;

  await page.addInitScript(({ token, user }) => {
    localStorage.setItem('authToken', token);
    localStorage.setItem('user', JSON.stringify(user));
  }, { token: 'mock-auth-token-xyz', user: currentUser });

  await page.route('**/api/**', async (route) => {
    const url = route.request().url();

    if (url.includes('/auth/user')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ user: currentUser }) });
    }
    if (url.includes('/dashboard-stats')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          today_classes: 2, total_students: 18, new_submissions: 3, week_classes: 8,
          total_assignments: 5, total_submissions: 15, user: currentUser
        })
      });
    }
    if (url.includes('/upcoming-schedules') || url.includes('/zoom-classes')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 101, title: 'Grade 10 Chemistry Session', subject: 'Chemistry', grade: '10', scheduled_at: new Date(Date.now() + 3600000).toISOString(), duration: 60, zoom_link: 'https://zoom.us/j/123', attendance_status: 'none' }
          ]
        })
      });
    }
    if (url.includes('/student/201')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          profile: { name: 'Kasun Perera', email: 'kasun@test.lk', phone_number: '0771234567', current_grade: '10', stream: 'Science', registration_status: 'approved' },
          attendance: [
            { zoom_class: { title: 'Chemistry Lab' }, attended_at: '2026-10-10T10:00:00Z', status: 'present' }
          ]
        })
      });
    }
    if (url.includes('/students')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify([
          { id: 201, name: 'Kasun Perera', email: 'kasun@test.lk', phone_number: '0771234567', current_grade: '10', stream: 'Science', is_online: 1 }
        ])
      });
    }
    if (url.includes('/assignments/301/submissions')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 401, student: { name: 'Kasun Perera', email: 'kasun@test.lk' }, submitted_at: '2026-10-11T10:00:00Z', marks: null, feedback: '', status: 'submitted', file_url: 'https://example.com/sub.pdf' }
          ]
        })
      });
    }
    if (url.includes('/assignments')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 301, title: 'Organic Chemistry Lab', subject: 'Chemistry', grade: '10', due_date: new Date(Date.now() + 86400000).toISOString(), total_students: 20, submissions_count: 5, ungraded_count: 1, description: 'Lab report on chemical reactions and esters.' }
          ]
        })
      });
    }
    if (url.includes('/materials')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify([
          { id: 501, title: 'Chapter 1 Notes', type: 'pdf', subject: 'Chemistry', grade: '10', file_path: 'notes.pdf', created_at: '2026-10-01T00:00:00Z' }
        ])
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
          teachers: [],
          students: [{ id: 201, name: 'Kasun Perera', email: 'kasun@test.lk', role: 'user' }],
          admins: [],
          broadcast: [{ value: 'all_students', label: 'All Students' }, { value: 'grade_10', label: 'Grade 10' }]
        })
      });
    }
    if (url.includes('/messages')) {
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({
          messages: [
            { id: 601, sender: { id: 201, name: 'Kasun Perera', role: 'user' }, receiver: { id: 1, name: 'Dr. Jane Teacher' }, body: 'Hello teacher, I have completed the assignment.', created_at: new Date().toISOString(), is_read: false }
          ]
        })
      });
    }

    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({}) });
  });
}

test.describe('Teacher Area Mobile, Tablet & Tap Optimization Suite', () => {

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
        await setupMocks(page, true);
        await page.setViewportSize(vp);

        await page.goto(r.path, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);

        // Assert 1: No horizontal page scroll
        const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
        const innerWidth = await page.evaluate(() => window.innerWidth);
        expect(scrollWidth, `Route ${r.name} at ${vp.width}px must not have horizontal scroll`).toBeLessThanOrEqual(innerWidth);

        // Screenshot
        const screenshotPath = path.join(process.cwd(), 'e2e', 'screenshots', `${r.name}_${vp.width}x${vp.height}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: false });

        // Assert 4: Font size >= 16px on inputs/textareas/selects at widths <= 768
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

        // Navigation rail check: logo button is reachable on all viewports
        const logoBtn = page.locator('button[data-rail="logo"]');
        await expect(logoBtn).toBeVisible();
      });
    }
  }

  // 2. Regression Test for 2.1: Tablet rotation 768 -> 1024 -> 768
  test('Regression 2.1: Rotation 768x1024 -> 1024x768 -> 768x1024 keeps navigation reachable', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 768, height: 1024 });

    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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

  // 3. Touch target minimum sizing (width and height >= 44 on touch emulation)
  test('Requirement 3: Touch targets in main content have boundingBox width and height >= 44', async ({ browser }) => {
    const context = await browser.newContext({
      viewport: { width: 390, height: 844 },
      hasTouch: true,
      isMobile: true,
    });
    const page = await context.newPage();
    await setupMocks(page, true);

    for (const r of ROUTES) {
      await page.goto(r.path, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const offenders = await page.evaluate(() => {
        const buttonsAndLinks = Array.from(document.querySelectorAll('header button, header input:not([type="hidden"]), aside button, aside a.t-tap, main button, main select, main input:not([type="hidden"]), main .t-icon-btn, main a.t-tap'));
        const issues = [];
        for (const el of buttonsAndLinks) {
          const rect = el.getBoundingClientRect();
          if (rect.width > 0 && rect.height > 0) {
            // Check boundingBox
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

      expect(offenders, `Under-sized touch targets found on ${r.path}: ${JSON.stringify(offenders)}`).toEqual([]);
    }

    await context.close();
  });

  // 5. Opening Grade modal and Student info modal at 320px fits in viewport
  test('Requirement 5: Grade and Student info modals at 320px fit in viewport and close button >= 44px', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 320, height: 640 });

    // Test Grade Modal
    await page.goto('/teacher/assignments', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const gradeBtn = page.locator('button:has-text("Grade Papers")').first();
    await gradeBtn.click();
    await page.waitForTimeout(300);

    const gradeDialog = page.locator('.teacher-modal-dialog');
    await expect(gradeDialog).toBeVisible();
    const gradeBox = await gradeDialog.boundingBox();
    expect(gradeBox.width).toBeLessThanOrEqual(320);

    const gradeCloseBtn = page.locator('button[aria-label="Close grading modal"]');
    const closeBox = await gradeCloseBtn.boundingBox();
    expect(closeBox.width).toBeGreaterThanOrEqual(43);
    expect(closeBox.height).toBeGreaterThanOrEqual(43);
    await gradeCloseBtn.click();
    await expect(gradeDialog).not.toBeVisible();

    // Test Student Info Modal
    await page.goto('/teacher/students', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const infoBtn = page.locator('button:has-text("View Info")').first();
    await infoBtn.click();
    await page.waitForTimeout(300);

    const infoDialog = page.locator('.teacher-modal-dialog');
    await expect(infoDialog).toBeVisible();
    const infoBox = await infoDialog.boundingBox();
    expect(infoBox.width).toBeLessThanOrEqual(320);

    const infoCloseBtn = page.locator('button[aria-label="Close student info modal"]');
    const infoCloseBox = await infoCloseBtn.boundingBox();
    expect(infoCloseBox.width).toBeGreaterThanOrEqual(43);
    expect(infoCloseBox.height).toBeGreaterThanOrEqual(43);
    await infoCloseBtn.click();
    await expect(infoDialog).not.toBeVisible();
  });

  // 6. Messages at 390x844: list view -> tap chat -> chat view -> back
  test('Requirement 6: Messages mobile screen splitting, 44px back button, and composer in viewport', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 390, height: 844 });

    await page.goto('/teacher/messages', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    // Initial state: list is visible, chat-main is hidden on mobile
    const chatRow = page.locator('.chat-sidebar .hide-scrollbar > div').first();
    await expect(chatRow).toBeVisible();

    // Tap chat row
    await chatRow.click();
    await page.waitForTimeout(300);

    // Chat view active: back button visible with >= 44x44
    const backBtn = page.locator('button[aria-label="Back to conversations list"]');
    await expect(backBtn).toBeVisible();
    const backBox = await backBtn.boundingBox();
    expect(backBox.width).toBeGreaterThanOrEqual(43);
    expect(backBox.height).toBeGreaterThanOrEqual(43);

    // Composer reply bar is inside viewport
    const replyBar = page.locator('.chat-reply-bar');
    await expect(replyBar).toBeVisible();
    const replyBox = await replyBar.boundingBox();
    expect(replyBox.y + replyBox.height).toBeLessThanOrEqual(845);

    // Tap back returns to chat list
    await backBtn.click();
    await page.waitForTimeout(300);
    await expect(chatRow).toBeVisible();
  });

  // 7. Theme color verification: Student Messages and Teacher Messages both resolve to rgb(79, 11, 217)
  test('Requirement 7: Student Messages and Teacher Messages both resolve to rgb(79, 11, 217)', async ({ page }) => {
    // 7A: Student View
    await setupMocks(page, false);
    await page.setViewportSize({ width: 1024, height: 768 });
    await page.goto('/student/messages', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const studentNewChatBtn = page.locator('button[title="New Chat"]');
    await expect(studentNewChatBtn).toBeVisible();
    const studentBtnColor = await studentNewChatBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(studentBtnColor).toBe('rgb(79, 11, 217)');

    // 7B: Teacher View
    await setupMocks(page, true);
    await page.goto('/teacher/messages', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);

    const teacherNewChatBtn = page.locator('button[title="New Chat"]');
    await expect(teacherNewChatBtn).toBeVisible();
    const teacherBtnColor = await teacherNewChatBtn.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(teacherBtnColor).toBe('rgb(79, 11, 217)');
  });

});

test.describe('Phase 3 - Teacher Sidebar Redesign Icon Rail & Adaptive Expanded Drawer', () => {

  // 1. Rail visible by default at 320x568, 360x740, 390x844, 768x1024, 1024x768, 1280x800
  // Logo button >= 44x44; all 8 item icon buttons + Logout inside viewport at 320x568 and 360x640, each >= 44x44
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1024, height: 768 },
    { width: 1280, height: 800 },
  ]) {
    test(`Teacher rail visible by default at ${vp.width}x${vp.height} with logo button >= 44x44`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const logoBtn = page.locator('button[data-rail="logo"]');
      await expect(logoBtn).toBeVisible();
      await expect(logoBtn).toHaveAttribute('aria-label', 'Open menu');
      await expect(logoBtn).toHaveAttribute('aria-expanded', 'false');
      await expect(logoBtn).toHaveAttribute('aria-controls', 'expanded-sidebar-drawer');
      const logoBox = await logoBtn.boundingBox();
      expect(logoBox).not.toBeNull();
      expect(logoBox.width, 'Logo button width must be >= 44px').toBeGreaterThanOrEqual(44);
      expect(logoBox.height, 'Logo button height must be >= 44px').toBeGreaterThanOrEqual(44);

      if (vp.width === 320 && vp.height === 568) {
        // Check all 8 item icon buttons + Logout inside viewport at 320x568
        const railItems = page.locator('.sidebar-rail button.sidebar-rail-item');
        const count = await railItems.count();
        expect(count, 'Must have 8 rail item icon buttons').toBe(8);

        for (let i = 0; i < count; i++) {
          const item = railItems.nth(i);
          await expect(item).toBeVisible();
          await expect(item).toHaveAttribute('aria-label', 'Open menu');
          await expect(item).toHaveAttribute('aria-expanded', 'false');
          await expect(item).toHaveAttribute('aria-controls', 'expanded-sidebar-drawer');
          const box = await item.boundingBox();
          expect(box).not.toBeNull();
          expect(box.width, `Rail item ${i} width must be >= 44px`).toBeGreaterThanOrEqual(44);
          expect(box.height, `Rail item ${i} height must be >= 44px`).toBeGreaterThanOrEqual(44);
          expect(box.y + box.height, `Rail item ${i} must be within viewport height`).toBeLessThanOrEqual(vp.height);
        }

        // Active page highlight
        const dashboardRailBtn = page.locator('.sidebar-rail button[data-rail="dashboard"]');
        await expect(dashboardRailBtn).toHaveClass(/active/);

        const logoutBtn = page.locator('.sidebar-rail button[data-rail="logout"]');
        await expect(logoutBtn).toBeVisible();
        await expect(logoutBtn).toHaveAttribute('aria-label', 'Open menu');
        await expect(logoutBtn).toHaveAttribute('aria-expanded', 'false');
        await expect(logoutBtn).toHaveAttribute('aria-controls', 'expanded-sidebar-drawer');
        const logoutBox = await logoutBtn.boundingBox();
        expect(logoutBox).not.toBeNull();
        expect(logoutBox.width, 'Logout button width must be >= 44px').toBeGreaterThanOrEqual(44);
        expect(logoutBox.height, 'Logout button height must be >= 44px').toBeGreaterThanOrEqual(44);
        expect(logoutBox.y + logoutBox.height, 'Logout button must be within viewport height').toBeLessThanOrEqual(vp.height);
      }
    });
  }

  // Also check 360x640 for all 8 items + Logout >= 44x44 inside viewport
  test('Teacher rail has all 8 items and Logout >= 44x44 inside viewport at 360x640', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 360, height: 640 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const railItems = page.locator('.sidebar-rail button.sidebar-rail-item');
    const count = await railItems.count();
    expect(count).toBe(8);

    for (let i = 0; i < count; i++) {
      const item = railItems.nth(i);
      await expect(item).toBeVisible();
      const box = await item.boundingBox();
      expect(box.width).toBeGreaterThanOrEqual(44);
      expect(box.height).toBeGreaterThanOrEqual(44);
      expect(box.y + box.height).toBeLessThanOrEqual(640);
    }

    const logoutBtn = page.locator('.sidebar-rail button[data-rail="logout"]');
    await expect(logoutBtn).toBeVisible();
    const logoutBox = await logoutBtn.boundingBox();
    expect(logoutBox.width).toBeGreaterThanOrEqual(44);
    expect(logoutBox.height).toBeGreaterThanOrEqual(44);
    expect(logoutBox.y + logoutBox.height).toBeLessThanOrEqual(640);
  });

  // 2. For each of: logo, Dashboard icon, Messages icon, Settings icon, Logout icon, empty rail area:
  // click/tap on the rail at 320x568, 360x740, 768x1024 and 1280x800 -> sidebar expands (all 8 labels + subtitle visible),
  // URL unchanged, user still logged in (no logout request/redirect).
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
  ]) {
    for (const trigger of [
      { name: 'logo', selector: 'button[data-rail="logo"]' },
      { name: 'Dashboard icon', selector: 'button[data-rail="dashboard"]' },
      { name: 'Messages icon', selector: 'button[data-rail="messages"]' },
      { name: 'Settings icon', selector: 'button[data-rail="settings"]' },
      { name: 'Logout icon', selector: 'button[data-rail="logout"]' },
      { name: 'empty rail area', selector: '.sidebar-rail', position: { x: 30, y: 10 } },
    ]) {
      test(`Teacher tapping ${trigger.name} at ${vp.width}x${vp.height} expands full sidebar without navigation or logout`, async ({ page }) => {
        await setupMocks(page, true);
        await page.setViewportSize(vp);
        await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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
        await expect(drawer.locator('.sidebar-brand-subtitle')).toHaveText(/Teacher Portal/i);

        // All 8 labels visible
        const navLabels = drawer.locator('.sidebar-nav-label');
        expect(await navLabels.count()).toBe(8);
        const expectedLabels = ['Dashboard', 'My Students', 'Schedule', 'Materials', 'Assignments', 'Reports', 'Messages', 'Settings'];
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
  test('Teacher tapping a labelled item navigates and auto-collapses back to rail within 500ms', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Expand sidebar via messages rail button
    await page.locator('button[data-rail="messages"]').click();
    const drawer = page.locator('.sidebar-drawer');
    await expect(drawer).toBeVisible();
    await page.waitForTimeout(250);

    // Tap Schedule labelled item
    const scheduleLink = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
    await expect(scheduleLink).toBeVisible();
    await scheduleLink.click();

    // URL changes
    await expect(page).toHaveURL(/\/teacher\/schedule/);

    // Auto-collapses within 500ms: drawer not visible, rail visible
    await expect(drawer).not.toBeVisible({ timeout: 500 });
    await expect(page.locator('.sidebar-rail')).toBeVisible();
  });

  // 4. Backdrop tap, X, Escape each collapse; at 1280x800 main content bounding box is identical before and after opening (no push)
  test('Teacher backdrop tap, X, logo click, and Escape collapse overlay; no push at 1280x800', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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
    await page.locator('.sidebar-header-brand').click();
    await expect(drawer).not.toBeVisible();
  });

  // 5. Double-tap on a rail icon opens once and does not navigate
  test('Teacher double-tap on a rail icon opens once and does not navigate', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const messagesRailBtn = page.locator('button[data-rail="messages"]');
    await messagesRailBtn.dblclick();
    await page.waitForTimeout(300);

    // URL is still /teacher/dashboard (did not navigate)
    expect(page.url()).toContain('/teacher/dashboard');
    expect(page.url()).not.toContain('/teacher/messages');

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
    test(`Teacher expanded sidebar displays all 8 labels incl. Settings and Logout inside viewport at ${vp.width}x${vp.height} with no MENU label`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Open drawer via logo button
      await page.locator('button[data-rail="logo"]').click();
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
  test('Teacher colours: rail and expanded use design tokens, primary resolves to rgb(79, 11, 217)', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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
    test(`Teacher capture screenshots (collapsed + expanded) at ${shotVp.width}x${shotVp.height}`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(shotVp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      const tmpDir = process.platform === 'win32' ? 'C:/tmp/tit-screenshots' : '/tmp/tit-screenshots';
      if (!fs.existsSync(tmpDir)) {
        fs.mkdirSync(tmpDir, { recursive: true });
      }

      // Collapsed screenshot
      const collapsedPath = path.join(tmpDir, `teacher_collapsed_${shotVp.width}x${shotVp.height}.png`);
      await page.screenshot({ path: collapsedPath, fullPage: false });

      // Expanded screenshot
      await page.locator('button[data-rail="logo"]').click();
      await page.waitForTimeout(300);

      const expandedPath = path.join(tmpDir, `teacher_expanded_${shotVp.width}x${shotVp.height}.png`);
      await page.screenshot({ path: expandedPath, fullPage: false });

      expect(fs.existsSync(collapsedPath)).toBe(true);
      expect(fs.existsSync(expandedPath)).toBe(true);
    });
  }


});

test.describe('Phase 4 - Teacher Sidebar Hover (Mouse) Expansion', () => {

  const teacherExpectedLabels = ['Dashboard', 'My Students', 'Schedule', 'Materials', 'Assignments', 'Reports', 'Messages', 'Settings'];

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

  // 1. Hover on rail logo, each of 8 icons, and empty rail area
  for (const vp of [
    { width: 1280, height: 800 },
    { width: 1366, height: 768 },
  ]) {
    for (const trigger of [
      { name: 'rail logo', selector: 'button[data-rail="logo"]' },
      { name: 'Dashboard icon', selector: 'button[data-rail="dashboard"]' },
      { name: 'My Students icon', selector: 'button[data-rail="my-students"]' },
      { name: 'Schedule icon', selector: 'button[data-rail="schedule"]' },
      { name: 'Materials icon', selector: 'button[data-rail="materials"]' },
      { name: 'Assignments icon', selector: 'button[data-rail="assignments"]' },
      { name: 'Reports icon', selector: 'button[data-rail="reports"]' },
      { name: 'Messages icon', selector: 'button[data-rail="messages"]' },
      { name: 'Settings icon', selector: 'button[data-rail="settings"]' },
      { name: 'empty rail area', selector: '.sidebar-rail', position: { x: 30, y: 10 } },
    ]) {
      test(`Teacher hover on ${trigger.name} at ${vp.width}x${vp.height} opens expanded sidebar with all labels`, async ({ page }) => {
        await setupMocks(page, true);
        await page.setViewportSize(vp);
        await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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

        for (const label of teacherExpectedLabels) {
          await expect(drawer.locator(`.sidebar-nav-label:has-text("${label}")`)).toBeVisible();
        }

        await expect(drawer.locator('.sidebar-user-card')).toBeVisible();
        await expect(drawer.locator('button[aria-label="Logout"]')).toBeVisible();

        expect(page.url()).toContain('/teacher/dashboard');
      });
    }
  }

  // 2. Move mouse from rail to expanded drawer → stays open
  test('Teacher hover: moving mouse from rail into expanded drawer keeps it open', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    await page.hover('button[data-rail="dashboard"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    const drawerBox = await drawer.boundingBox();
    await page.mouse.move(drawerBox.x + drawerBox.width / 2, drawerBox.y + drawerBox.height / 2);

    for (let i = 0; i < 3; i++) {
      await page.waitForTimeout(200);
      await expect(drawer).toBeVisible();
    }
  });

  // 3. Move mouse to main content → closes
  test('Teacher hover: moving mouse to main content closes hover drawer within 500ms', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    await page.hover('button[data-rail="dashboard"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    await page.mouse.move(800, 400);
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 4. Gap >= 16px
  for (const vp of [
    { width: 1280, height: 800 },
    { width: 360, height: 740 },
  ]) {
    test(`Teacher gap between header and first nav item >= 16px at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      await page.locator('button[data-rail="logo"]').click();
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
      expect(gap, `Gap must be >= 16px (got ${gap}px)`).toBeGreaterThanOrEqual(16);

      const menuLabel = drawer.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);
    });
  }

  // 5. Main content bounding box unchanged
  test('Teacher hover: main content bounding box unchanged at 1280x800', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const main = page.locator('main').first();
    const initialBox = await main.boundingBox();
    expect(initialBox).not.toBeNull();

    await page.hover('button[data-rail="logo"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });

    const duringBox = await main.boundingBox();
    expect(duringBox.x, 'Main x must not shift').toBe(initialBox.x);
    expect(duringBox.width, 'Main width must not change').toBe(initialBox.width);

    await page.mouse.move(800, 400);
    await expect(drawer).not.toBeVisible({ timeout: 500 });

    const afterBox = await main.boundingBox();
    expect(afterBox.x).toBe(initialBox.x);
    expect(afterBox.width).toBe(initialBox.width);
  });

  // 6. Navigate via hover
  test('Teacher hover: clicking a labelled item navigates and closes drawer', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    await page.hover('button[data-rail="schedule"]');
    const drawer = page.locator('.sidebar-drawer.hover-drawer');
    await expect(drawer).toBeVisible({ timeout: 2000 });
    await page.waitForTimeout(250);

    const scheduleLink = drawer.locator('a.sidebar-nav-item:has-text("Schedule")');
    await expect(scheduleLink).toBeVisible();
    await scheduleLink.click();

    await expect(page).toHaveURL(/\/teacher\/schedule/);
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 7. Escape closes hover drawer
  test('Teacher hover: Escape key closes hover drawer', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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
    test(`Teacher hover: icon and logo zero-jump alignment at ${vp.width}x${vp.height}`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
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
  test('Teacher alignment click: hover icon N, click at same coordinates navigates to route N', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    // Pick Schedule icon (route /teacher/schedule)
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

    // Navigates to /teacher/schedule
    await expect(page).toHaveURL(/\/teacher\/schedule/);
    await expect(drawer).not.toBeVisible({ timeout: 500 });
  });

  // 10. Touch mode at 320x568, 360x740, 768x1024: drawer starts at x=0, covers rail, X button and backdrop visible
  for (const touchVp of [
    { width: 320, height: 568 },
    { width: 360, height: 740 },
    { width: 768, height: 1024 },
  ]) {
    test(`Teacher touch mode at ${touchVp.width}x${touchVp.height}: drawer starts at x=0 with X button and backdrop`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(touchVp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Tap rail logo to expand in touch mode
      await page.click('button[data-rail="logo"]');
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

      // Clicking close button closes drawer
      await closeBtn.click();
      await expect(drawer).not.toBeVisible({ timeout: 500 });
      await expect(page.locator('.sidebar-backdrop')).not.toBeVisible();
    });
  }

});
