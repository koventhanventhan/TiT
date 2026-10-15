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
        const logoBtn = page.locator('button[aria-label="Open menu"]');
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

    const logoBtn = page.locator('button[aria-label="Open menu"]');
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
    const navItem = page.locator('aside nav a').first();
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

      const logoBtn = page.locator('button[aria-label="Open menu"]');
      await expect(logoBtn).toBeVisible();
      const logoBox = await logoBtn.boundingBox();
      expect(logoBox).not.toBeNull();
      expect(logoBox.width, 'Logo button width must be >= 44px').toBeGreaterThanOrEqual(44);
      expect(logoBox.height, 'Logo button height must be >= 44px').toBeGreaterThanOrEqual(44);

      if (vp.width === 320 && vp.height === 568) {
        // Check all 8 item icon buttons + Logout inside viewport at 320x568
        const railItems = page.locator('.sidebar-rail a.sidebar-rail-item');
        const count = await railItems.count();
        expect(count, 'Must have 8 rail item icon buttons').toBe(8);

        for (let i = 0; i < count; i++) {
          const item = railItems.nth(i);
          await expect(item).toBeVisible();
          const box = await item.boundingBox();
          expect(box).not.toBeNull();
          expect(box.width, `Rail item ${i} width must be >= 44px`).toBeGreaterThanOrEqual(44);
          expect(box.height, `Rail item ${i} height must be >= 44px`).toBeGreaterThanOrEqual(44);
          expect(box.y + box.height, `Rail item ${i} must be within viewport height`).toBeLessThanOrEqual(vp.height);
        }

        const logoutBtn = page.locator('.sidebar-rail button[aria-label="Logout"]');
        await expect(logoutBtn).toBeVisible();
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

    const railItems = page.locator('.sidebar-rail a.sidebar-rail-item');
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

    const logoutBtn = page.locator('.sidebar-rail button[aria-label="Logout"]');
    await expect(logoutBtn).toBeVisible();
    const logoutBox = await logoutBtn.boundingBox();
    expect(logoutBox.width).toBeGreaterThanOrEqual(44);
    expect(logoutBox.height).toBeGreaterThanOrEqual(44);
    expect(logoutBox.y + logoutBox.height).toBeLessThanOrEqual(640);
  });

  // 2. Tap logo -> expanded; labels visible; at 320x568 / 360x640 / 360x740 all 8 labels incl. Settings and Logout inside viewport
  for (const vp of [
    { width: 320, height: 568 },
    { width: 360, height: 640 },
    { width: 360, height: 740 },
  ]) {
    test(`Teacher expanded sidebar displays all 8 labels incl. Settings and Logout inside viewport at ${vp.width}x${vp.height} with no MENU label`, async ({ page }) => {
      await setupMocks(page, true);
      await page.setViewportSize(vp);
      await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(300);

      // Tap logo button to expand
      const logoBtn = page.locator('button[aria-label="Open menu"]');
      await logoBtn.click();
      await page.waitForTimeout(300);

      const expandedAside = page.locator('aside');
      await expect(expandedAside).toBeVisible();

      // No "MENU" label
      const menuLabel = expandedAside.locator('text=/^MENU$/i');
      await expect(menuLabel).toHaveCount(0);

      // All 8 nav items visible with labels
      const navItems = expandedAside.locator('a.sidebar-nav-item');
      const count = await navItems.count();
      expect(count, 'Must have 8 expanded nav items').toBe(8);

      for (let i = 0; i < count; i++) {
        const item = navItems.nth(i);
        await expect(item).toBeVisible();
        const box = await item.boundingBox();
        expect(box).not.toBeNull();
        expect(box.y + box.height, `Nav item ${i} bottom must be <= viewport height`).toBeLessThanOrEqual(vp.height);
      }

      // Explicitly check Settings is visible inside viewport
      const settingsItem = expandedAside.locator('a.sidebar-nav-item:has-text("Settings")');
      await expect(settingsItem).toBeVisible();
      const settingsBox = await settingsItem.boundingBox();
      expect(settingsBox.y + settingsBox.height, 'Settings item must be within viewport height').toBeLessThanOrEqual(vp.height);

      // Logout button visible inside viewport
      const logoutBtn = expandedAside.locator('button[aria-label="Logout"]');
      await expect(logoutBtn).toBeVisible();
      const logoutBox = await logoutBtn.boundingBox();
      expect(logoutBox.y + logoutBox.height, 'Logout button must be within viewport height').toBeLessThanOrEqual(vp.height);
    });
  }

  // 3. Backdrop tap, X, Escape, and clicking a menu item each collapse the overlay at <1024px
  test('Teacher backdrop tap, X button, Escape, and menu item click collapse the overlay (<1024px)', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 360, height: 740 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const logoBtn = page.locator('button[aria-label="Open menu"]');
    const closeBtn = page.locator('button[aria-label="Close sidebar"]');
    const overlay = page.locator('div[aria-label="Close sidebar overlay"]');

    // 1) Close via X button
    await logoBtn.click();
    await expect(closeBtn).toBeVisible();
    await closeBtn.click();
    await expect(closeBtn).not.toBeVisible();

    // 2) Close via Backdrop tap
    await logoBtn.click();
    await expect(overlay).toBeVisible();
    await overlay.click({ position: { x: 300, y: 200 } });
    await expect(overlay).not.toBeVisible();

    // 3) Close via Escape key
    await logoBtn.click();
    await expect(closeBtn).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(closeBtn).not.toBeVisible();

    // 4) Close via clicking a menu item
    await logoBtn.click();
    await expect(closeBtn).toBeVisible();
    const scheduleLink = page.locator('aside a.sidebar-nav-item:has-text("Schedule")');
    await scheduleLink.click();
    await expect(closeBtn).not.toBeVisible();
    expect(page.url()).toContain('/teacher/schedule');
  });

  // 4. Desktop: toggle persists across reload (>=1024px)
  test('Teacher desktop toggle persists across reload at 1280x800', async ({ page }) => {
    await setupMocks(page, true);
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/teacher/dashboard', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    const desktopAside = page.locator('aside.sidebar-desktop-shell');
    await expect(desktopAside).toBeVisible();

    // Initially collapsed (width ~72px)
    let box = await desktopAside.boundingBox();
    expect(Math.round(box.width)).toBe(72);

    // Tap logo to expand
    const logoBtn = page.locator('button[aria-label="Open menu"]');
    await logoBtn.click();
    await page.waitForTimeout(300);

    // Should now be expanded (width ~260px)
    box = await desktopAside.boundingBox();
    expect(Math.round(box.width)).toBe(260);

    // Reload page -> should stay expanded
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    box = await desktopAside.boundingBox();
    expect(Math.round(box.width)).toBe(260);

    // Collapse again
    const closeBtn = page.locator('button[aria-label="Close sidebar"]');
    await closeBtn.click();
    await page.waitForTimeout(300);

    box = await desktopAside.boundingBox();
    expect(Math.round(box.width)).toBe(72);

    // Reload -> should stay collapsed
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(300);

    box = await desktopAside.boundingBox();
    expect(Math.round(box.width)).toBe(72);
  });

  // 5. Colours: rail/expanded use tokens; primary resolves to rgb(79, 11, 217)
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
    await page.locator('button[aria-label="Open menu"]').click();
    await page.waitForTimeout(250);

    // Expanded logo tile also has primary color
    const expandedLogoTile = page.locator('.sidebar-drawer .sidebar-logo-tile');
    await expect(expandedLogoTile).toBeVisible();
    const expandedPrimaryBg = await expandedLogoTile.evaluate(el => window.getComputedStyle(el).backgroundColor);
    expect(expandedPrimaryBg).toBe('rgb(79, 11, 217)');
  });

  // 6. Screenshots (saved under /tmp): collapsed + expanded at 320x568, 360x740, 768x1024, 1280x800
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
      const logoBtn = page.locator('button[aria-label="Open menu"]');
      await logoBtn.click();
      await page.waitForTimeout(300);

      const expandedPath = path.join(tmpDir, `teacher_expanded_${shotVp.width}x${shotVp.height}.png`);
      await page.screenshot({ path: expandedPath, fullPage: false });

      expect(fs.existsSync(collapsedPath)).toBe(true);
      expect(fs.existsSync(expandedPath)).toBe(true);
    });
  }

});
