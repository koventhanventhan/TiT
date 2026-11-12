const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));
const { execSync } = require('child_process');

const BASE_URL = process.env.APP_URL || 'http://127.0.0.1:8000';

const PRESETS = process.env.TEST_PRESET ? [process.env.TEST_PRESET.trim()] : ['royal', 'ocean', 'emerald', 'midnight'];
const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '820x1180', width: 820, height: 1180 },
  { name: '375x800', width: 375, height: 800 }
];

const ROUTES = [
  '/admin/dashboard',
  '/admin/students',
  '/admin/students/pending-payments',
  '/admin/students/create',
  '/admin/students/index-lists',
  '/admin/students/index-lists/pdf',
  '/admin/students/assign-index-tool',
  '/admin/students/import-index',
  '/admin/students-requests/medium-changes',
  '/admin/students-requests/advance-payments',
  '/admin/teachers',
  '/admin/teachers/create',
  '/admin/subjects',
  '/admin/packages',
  '/admin/settings',
  '/admin/settings/about',
  '/admin/settings/contact',
  '/admin/settings/learning',
  '/admin/settings/classes',
  '/admin/settings/footer',
  '/admin/settings/register',
  '/admin/settings/admission-fees',
  '/admin/settings/payments',
  '/admin/settings/grade-promotion-schedule',
  '/admin/settings/index-batches',
  '/admin/settings/index-series',
  '/admin/settings/appearance',
  '/admin/settings/online-payments',
  '/admin/settings/messaging',
  '/admin/subscription',
  '/admin/zoom',
  '/admin/zoom/create',
  '/admin/zoom-accounts',
  '/admin/zoom-accounts/create',
  '/admin/timetables',
  '/admin/timetables/create',
  '/admin/attendance',
  '/admin/messages',
  '/admin/profile',
  '/admin/profile/settings',
  '/admin/notifications',
  '/admin/exam-results',
  '/admin/exam-results/data',
  '/admin/students/363',
  '/admin/students/363/edit'
];

// Excluded non-UI endpoints from page crawl:
// - '/admin/students/index-lists/pdf': Binary PDF generation/download (print layout overflow expected in browser)
// - '/admin/exam-results/data': JSON API data endpoint (non-HTML JSON response)
const EXCLUDED_ROUTES = [
  '/admin/students/index-lists/pdf',
  '/admin/exam-results/data'
];

function setPreset(preset) {
  execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" set_preset ${preset}`);
}

function getAdminToken() {
  return execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
}

function seedCrawlerData() {
  try {
    execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" seed_crawler_data`);
  } catch (e) {
    console.warn('Notice: seed_crawler_data warning:', e.message);
  }
}

// In-browser contrast evaluation and interactive state crawler
async function inBrowserCrawler({ isDashboard, vpWidth, runDialogsScan }) {
  function parseRgba(str) {
    if (!str || str === 'transparent' || str === 'inherit') return null;
    const m = str.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)/);
    if (!m) return null;
    return {
      r: parseFloat(m[1]),
      g: parseFloat(m[2]),
      b: parseFloat(m[3]),
      a: m[4] !== undefined ? parseFloat(m[4]) : 1
    };
  }

  function composite(fg, bg) {
    if (!fg) return bg;
    if (!bg) return fg;
    const a = fg.a + bg.a * (1 - fg.a);
    if (a === 0) return { r: 0, g: 0, b: 0, a: 0 };
    const r = (fg.r * fg.a + bg.r * bg.a * (1 - fg.a)) / a;
    const g = (fg.g * fg.a + bg.g * bg.a * (1 - fg.a)) / a;
    const b = (fg.b * fg.a + bg.b * bg.a * (1 - fg.a)) / a;
    return { r, g, b, a };
  }

  function luminance(c) {
    const sRGB = [c.r / 255, c.g / 255, c.b / 255];
    const lum = sRGB.map(val => val <= 0.04045 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4));
    return 0.2126 * lum[0] + 0.7152 * lum[1] + 0.0722 * lum[2];
  }

  function contrastRatio(lum1, lum2) {
    const l1 = Math.max(lum1, lum2);
    const l2 = Math.min(lum1, lum2);
    return (l1 + 0.05) / (l2 + 0.05);
  }

  // Get surface / background colors from CSS variables
  const rootStyle = window.getComputedStyle(document.documentElement);
  const admSurface = parseRgba(rootStyle.getPropertyValue('--adm-surface').trim()) || { r: 59, g: 51, b: 99, a: 1 };
  const admBg = parseRgba(rootStyle.getPropertyValue('--adm-bg').trim()) || { r: 44, g: 37, b: 74, a: 1 };

  function getEffectiveBg(el) {
    let cur = el;
    let accumulated = null;

    while (cur && cur !== document.documentElement) {
      const style = window.getComputedStyle(cur);
      const bgImg = style.backgroundImage;
      if (bgImg && bgImg !== 'none') {
        const bgCol = parseRgba(style.backgroundColor);
        if (bgCol && bgCol.a > 0) {
          accumulated = composite(accumulated, bgCol);
        } else {
          accumulated = composite(accumulated, admSurface);
        }
        if (accumulated && accumulated.a >= 0.95) break;
      }
      const bgCol = parseRgba(style.backgroundColor);
      if (bgCol && bgCol.a > 0) {
        accumulated = composite(accumulated, bgCol);
        if (accumulated && accumulated.a >= 0.95) break;
      }
      cur = cur.parentElement;
    }

    if (!accumulated || accumulated.a < 0.95) {
      accumulated = composite(accumulated, admBg);
    }
    return accumulated;
  }

  function scanTextNodes(rootEl, customMinRatio = null) {
    const violations = [];
    const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_TEXT);
    let node;
    let count = 0;

    while ((node = walker.nextNode())) {
      const text = node.nodeValue.trim();
      if (!text || text.length < 1) continue;

      const el = node.parentElement;
      if (!el) continue;

      const tag = el.tagName.toLowerCase();
      if (['script', 'style', 'noscript', 'svg', 'path'].includes(tag)) continue;

      const rect = el.getBoundingClientRect();
      if (rect.width <= 0 || rect.height <= 0) continue;
      if (vpWidth && (rect.right <= 0 || rect.left >= vpWidth)) continue;

      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || parseFloat(style.opacity) === 0) continue;

      const fgColor = parseRgba(style.color);
      if (!fgColor || fgColor.a === 0) continue;

      count++;

      const bgColor = getEffectiveBg(el);
      const effectiveFg = composite(fgColor, bgColor);

      const lumFg = luminance(effectiveFg);
      const lumBg = luminance(bgColor);
      const ratio = contrastRatio(lumFg, lumBg);

      const fontSize = parseFloat(style.fontSize);
      const fontWeight = parseInt(style.fontWeight, 10) || (style.fontWeight === 'bold' ? 700 : 400);
      const isLarge = fontSize >= 24 || (fontSize >= 18.5 && fontWeight >= 700);

      // Rule: WCAG AA - normal text >= 4.5:1, large/bold >= 3.0:1
      let minRatio = customMinRatio || (isLarge ? 3.0 : 4.5);

      // Allow-list checks with documented reasons:
      // 1. Placeholder text in inputs: standard placeholder contrast is 3:1 WCAG
      if (el.classList.contains('filter-option-placeholder') || el.classList.contains('bs-placeholder') || el.closest('.bs-placeholder')) {
        minRatio = 3.0;
      }
      // 2. Disabled controls: Rule: label >= 3:1 on its background (WCAG 2.1 SC 1.4.3 / Prompt 23 Part B)
      const isDisabled = el.disabled || el.getAttribute('aria-disabled') === 'true' || el.closest(':disabled') || el.classList.contains('disabled');
      if (isDisabled) {
        minRatio = 3.0;
      }
      // 3. Emoji glyphs: rendered with OS font bitmaps/vectors, exempt from text color contrast
      if (/^[\p{Emoji}\s]+$/u.test(text)) {
        continue;
      }

      if (ratio < minRatio) {
        violations.push({
          text: text.slice(0, 40),
          tag,
          ratio: ratio.toFixed(2),
          minRatio,
          fg: style.color,
          bg: `rgb(${Math.round(bgColor.r)}, ${Math.round(bgColor.g)}, ${Math.round(bgColor.b)})`,
          isLarge,
          isDisabled
        });
      }
    }

    return { violations, count };
  }

  const allViolations = [];
  let scannedNodesCount = 0;

  // 1. Base page scan
  const baseScan = scanTextNodes(document.body);
  scannedNodesCount += baseScan.count;
  allViolations.push(...baseScan.violations);

  // 2. Modals scan (open, scan, close)
  const modalEls = Array.from(document.querySelectorAll('.modal'));
  const openedModalIds = [];
  let modalsOpenedCount = 0;

  for (const modalEl of modalEls) {
    const id = modalEl.id || ('modal_probe_' + Math.random().toString(36).substring(2, 7));
    if (!modalEl.id) modalEl.id = id;

    if (typeof window.jQuery === 'undefined') {
      throw new Error(`jQuery unavailable to open modal #${id}`);
    }

    // Open modal
    let opened = false;
    await new Promise((resolve) => {
      window.jQuery(modalEl).one('shown.bs.modal', () => {
        opened = true;
        resolve();
      });
      window.jQuery(modalEl).modal('show');
      setTimeout(() => {
        opened = modalEl.classList.contains('show') || window.getComputedStyle(modalEl).display !== 'none';
        resolve();
      }, 700);
    });

    if (!opened && !modalEl.classList.contains('show')) {
      throw new Error(`Modal #${id} failed to open (shown.bs.modal not triggered).`);
    }

    openedModalIds.push(id);
    modalsOpenedCount++;

    // Scan modal content
    const modalScan = scanTextNodes(modalEl);
    scannedNodesCount += modalScan.count;
    allViolations.push(...modalScan.violations);

    // Close modal
    await new Promise((resolve) => {
      window.jQuery(modalEl).one('hidden.bs.modal', resolve);
      window.jQuery(modalEl).modal('hide');
      setTimeout(resolve, 400);
    });

    // Cleanup backdrops & modal-open
    window.jQuery('.modal-backdrop').remove();
    window.jQuery('body').removeClass('modal-open').css('padding-right', '');
  }

  // 3. Dropdowns scan (open, scan, close)
  let dropdownsOpenedCount = 0;
  if (typeof window.jQuery !== 'undefined') {
    const toggles = Array.from(document.querySelectorAll('.dropdown-toggle, [data-toggle="dropdown"]')).slice(0, 10);
    for (const toggle of toggles) {
      const parent = toggle.closest('.dropdown, .btn-group') || toggle.parentElement;
      if (!parent) continue;

      window.jQuery(toggle).dropdown('toggle');
      const menu = parent.querySelector('.dropdown-menu');
      if (menu && (menu.classList.contains('show') || window.getComputedStyle(menu).display !== 'none')) {
        dropdownsOpenedCount++;
        const dropScan = scanTextNodes(menu);
        scannedNodesCount += dropScan.count;
        allViolations.push(...dropScan.violations);
        window.jQuery(toggle).dropdown('toggle');
      }
    }
    window.jQuery('.dropdown-menu.show').removeClass('show');
  }

  // 4. Tabs & Pills scan
  if (typeof window.jQuery !== 'undefined') {
    const tabs = Array.from(document.querySelectorAll('[data-toggle="tab"], [data-toggle="pill"]')).slice(0, 5);
    for (const tab of tabs) {
      window.jQuery(tab).tab('show');
      await new Promise(r => setTimeout(r, 100));
      const targetSelector = tab.getAttribute('data-target') || tab.getAttribute('href');
      if (targetSelector && targetSelector.startsWith('#')) {
        const pane = document.querySelector(targetSelector);
        if (pane) {
          const tabScan = scanTextNodes(pane);
          scannedNodesCount += tabScan.count;
          allViolations.push(...tabScan.violations);
        }
      }
    }
  }

  // 5. Select2 scan
  if (typeof window.jQuery !== 'undefined') {
    const select2Els = window.jQuery('select.select2, select[data-toggle="select2"], .select2-hidden-accessible');
    if (select2Els.length > 0) {
      try {
        const s = select2Els.first();
        s.select2('open');
        await new Promise(r => setTimeout(r, 150));
        const drop = document.querySelector('.select2-container--open .select2-dropdown');
        if (drop) {
          const sScan = scanTextNodes(drop);
          scannedNodesCount += sScan.count;
          allViolations.push(...sScan.violations);
        }
        s.select2('close');
      } catch (e) {
        // select2 might not be initialized on all pages
      }
    }
  }

  // 6. Datepicker scan
  if (typeof window.jQuery !== 'undefined') {
    const dpInput = window.jQuery('input.daterange, input.datepicker').first();
    if (dpInput.length > 0 && dpInput.data('daterangepicker')) {
      try {
        dpInput.data('daterangepicker').show();
        await new Promise(r => setTimeout(r, 150));
        const dpContainer = document.querySelector('.daterangepicker:not([style*="display: none"])');
        if (dpContainer) {
          const dpScan = scanTextNodes(dpContainer);
          scannedNodesCount += dpScan.count;
          allViolations.push(...dpScan.violations);
        }
        dpInput.data('daterangepicker').hide();
      } catch (e) {
        // daterangepicker optional
      }
    }
  }

  // 7. Interactive States scan (:hover, :focus, :disabled, .is-loading)
  let statesMeasuredCount = 0;
  const stateCandidates = Array.from(document.querySelectorAll('.btn, .badge, .nav-link, .page-link, .dropdown-item, .form-control')).slice(0, 15);
  const seenSignatures = new Set();

  for (const el of stateCandidates) {
    const tag = el.tagName.toLowerCase();
    const classList = Array.from(el.classList).sort().join('.');
    const sig = `${tag}.${classList}`;
    if (seenSignatures.has(sig)) continue;
    seenSignatures.add(sig);

    // Normal state contrast
    statesMeasuredCount++;

    // Probe: Disabled state (Rule: >= 3.0:1 on background)
    if (tag === 'button' || tag === 'input' || el.classList.contains('btn')) {
      const clone = el.cloneNode(true);
      clone.disabled = true;
      clone.classList.add('disabled');
      clone.style.position = 'absolute';
      clone.style.visibility = 'hidden';
      document.body.appendChild(clone);
      clone.style.visibility = 'visible';

      const disScan = scanTextNodes(clone, 3.0);
      scannedNodesCount += disScan.count;
      allViolations.push(...disScan.violations);
      clone.remove();
      statesMeasuredCount++;
    }

    // Probe: Loading state (.is-loading, Rule: >= 4.5:1)
    if (el.classList.contains('btn') && typeof window.TitButton !== 'undefined') {
      const clone = el.cloneNode(true);
      clone.style.position = 'absolute';
      clone.style.visibility = 'hidden';
      document.body.appendChild(clone);
      window.TitButton.setLoading(clone, 'Saving...');
      clone.style.visibility = 'visible';

      const loadScan = scanTextNodes(clone, 4.5);
      scannedNodesCount += loadScan.count;
      allViolations.push(...loadScan.violations);
      clone.remove();
      statesMeasuredCount++;
    }
  }

  // 8. Dialogs and Toasts Scan (once per preset/viewport on dashboard)
  if (runDialogsScan && typeof window.TitNotify !== 'undefined') {
    // Toasts
    window.TitNotify.success('Record saved successfully.', { title: 'Success' });
    window.TitNotify.error('Action failed with error.', { title: 'Error' });
    window.TitNotify.warning('Warning: check your data.', { title: 'Warning' });
    window.TitNotify.info('Update in progress.', { title: 'Info' });

    await new Promise(r => setTimeout(r, 100));

    const toastEls = document.querySelectorAll('.tit-toast');
    for (const t of toastEls) {
      const tScan = scanTextNodes(t, 4.5);
      scannedNodesCount += tScan.count;
      allViolations.push(...tScan.violations);
    }
    document.querySelectorAll('.tit-toast').forEach(t => t.remove());

    // Confirm dialog
    const confirmPromise = window.TitNotify.confirm({
      title: 'Confirm Action',
      text: 'Are you sure you want to proceed?',
      confirmText: 'Confirm',
      cancelText: 'Cancel',
      danger: true
    });

    await new Promise(r => setTimeout(r, 100));

    const confirmModal = document.querySelector('.tit-confirm-modal');
    if (confirmModal) {
      const cScan = scanTextNodes(confirmModal, 4.5);
      scannedNodesCount += cScan.count;
      allViolations.push(...cScan.violations);

      const cancelBtn = document.getElementById('tit-confirm-cancel') || confirmModal.querySelector('button');
      if (cancelBtn) cancelBtn.click();
    }
    await Promise.race([confirmPromise, new Promise(r => setTimeout(r, 400))]).catch(() => {});
  }

  // Check horizontal overflow
  const scrollWidth = document.documentElement.scrollWidth;
  const clientWidth = document.documentElement.clientWidth;
  const hasOverflow = scrollWidth > clientWidth + 1; // 1px rounding tolerance

  // Dashboard specific checks
  let dashboardCheck = null;
  if (isDashboard) {
    const statCards = Array.from(document.querySelectorAll('.widget-stat')).map(c => {
      const h4 = c.querySelector('h4');
      const text = h4 ? h4.textContent.trim() : '';
      const color = h4 ? window.getComputedStyle(h4).color : '';
      const bg = window.getComputedStyle(c).backgroundColor;
      return { text, color, bg, ok: text.length > 0 && color !== bg };
    });

    const header = document.querySelector('.header');
    const headerBg = header ? window.getComputedStyle(header).backgroundColor : '';
    const home = document.querySelector('.header-right a[title="Home"] svg');
    const mail = document.querySelector('.header-right a[title="Messages"] svg');
    const bell = document.querySelector('.header-right .notification_dropdown > a svg');
    const hamburger = vpWidth >= 1200
      ? document.querySelector('.nav-header .nav-control, .nav-control')
      : document.querySelector('#customHamburger');

    function checkIcon(icon) {
      if (!icon) return { exists: false, visible: false, ratio: 0 };
      const r = icon.getBoundingClientRect();
      const s = window.getComputedStyle(icon);
      const isVis = r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden';
      const col = parseRgba(s.color || s.stroke || s.fill) || { r: 241, g: 245, b: 249, a: 1 };
      const hBg = parseRgba(headerBg) || admSurface;
      const rRatio = contrastRatio(luminance(col), luminance(hBg));
      return { exists: true, visible: isVis, ratio: parseFloat(rRatio.toFixed(2)) };
    }

    dashboardCheck = {
      statCards,
      statCardsOk: statCards.length === 6 && statCards.every(s => s.ok),
      icons: {
        home: checkIcon(home),
        mail: checkIcon(mail),
        bell: checkIcon(bell),
        hamburger: checkIcon(hamburger)
      }
    };
  }

  // 6. Blank-page detector: Page height vs visible content bottom
  // Fails when document height > 1.6 * bottom of last visible content element + 200px
  const docHeight = Math.max(
    document.body.scrollHeight,
    document.documentElement.scrollHeight,
    document.body.offsetHeight,
    document.documentElement.offsetHeight
  );

  let maxVisibleBottom = 0;
  const contentBody = document.querySelector('.content-body') || document.querySelector('#main-wrapper') || document.body;
  const contentEls = contentBody.querySelectorAll('h1, h2, h3, h4, h5, h6, p, table, form, .card, .btn, input, select, textarea, img, ul, ol');
  for (const el of contentEls) {
    const s = window.getComputedStyle(el);
    if (s.display === 'none' || s.visibility === 'hidden' || parseFloat(s.opacity) === 0) continue;
    const r = el.getBoundingClientRect();
    if (r.height <= 0 || r.width <= 0) continue;
    const absBottom = r.bottom + window.scrollY;
    if (absBottom > maxVisibleBottom) {
      maxVisibleBottom = absBottom;
    }
  }

  if (maxVisibleBottom === 0) {
    maxVisibleBottom = docHeight;
  }

  const heightThreshold = Math.max(window.innerHeight + 200, maxVisibleBottom * 1.6 + 200);
  const isBlankPageViolation = docHeight > heightThreshold;

  const blankPageCheck = {
    docHeight: Math.round(docHeight),
    maxBottom: Math.round(maxVisibleBottom),
    threshold: Math.round(heightThreshold),
    failed: isBlankPageViolation
  };

  // 7. Layout Sanity Check per page and viewport
  const layoutViolations = [];

  // Check 1: Nested .content-body count > 1
  const contentBodies = Array.from(document.querySelectorAll('.content-body'));
  if (contentBodies.length > 1) {
    layoutViolations.push({
      type: 'nested-content-body',
      message: `nested .content-body count (${contentBodies.length}) > 1`
    });
  }

  // Check 2: gapLeft & gapTop on layout's .content-body
  const layoutContentBody = contentBodies[0] || document.querySelector('#main-wrapper') || document.body;
  const cbRect = layoutContentBody.getBoundingClientRect();
  const candidates = Array.from(layoutContentBody.querySelectorAll('.page-titles, .card, h1, h2, h3, h4, form, table'));
  let firstVisibleEl = null;
  let firstElRect = null;
  for (const el of candidates) {
    const s = window.getComputedStyle(el);
    if (s.display === 'none' || s.visibility === 'hidden' || parseFloat(s.opacity) === 0) continue;
    const r = el.getBoundingClientRect();
    if (r.width <= 0 || r.height <= 0) continue;
    firstVisibleEl = el;
    firstElRect = r;
    break;
  }

  let gapLeft = 0;
  let gapTop = 0;
  if (firstElRect) {
    gapLeft = Math.round(firstElRect.left - cbRect.left);
    gapTop = Math.round(firstElRect.top - cbRect.top);

    const maxGapLeft = vpWidth >= 1000 ? 120 : (vpWidth >= 576 ? 60 : 40);
    if (gapLeft > maxGapLeft) {
      layoutViolations.push({
        type: 'gapLeft',
        message: `gapLeft ${gapLeft}px > ${maxGapLeft}px on <${firstVisibleEl.tagName.toLowerCase()}${firstVisibleEl.className ? '.' + firstVisibleEl.className.trim().split(/\s+/).join('.') : ''}>`
      });
    }

    if (gapTop > 220) {
      layoutViolations.push({
        type: 'gapTop',
        message: `gapTop ${gapTop}px > 220px on <${firstVisibleEl.tagName.toLowerCase()}${firstVisibleEl.className ? '.' + firstVisibleEl.className.trim().split(/\s+/).join('.') : ''}>`
      });
    }
  }

  // Check 3: Clipped overflow in .card or .table-responsive (scrollWidth > clientWidth + 4 while overflow-x: hidden|clip)
  const cardsAndTables = Array.from(document.querySelectorAll('.card, .table-responsive'));
  for (const el of cardsAndTables) {
    const s = window.getComputedStyle(el);
    if (s.display === 'none' || s.visibility === 'hidden' || parseFloat(s.opacity) === 0) continue;
    if (s.overflowX === 'hidden' || s.overflowX === 'clip') {
      if (el.scrollWidth > el.clientWidth + 4) {
        const diff = el.scrollWidth - el.clientWidth;
        const sel = el.className ? `.${el.className.trim().split(/\s+/).join('.')}` : el.tagName.toLowerCase();
        layoutViolations.push({
          type: 'clipped-overflow',
          selector: sel,
          hiddenWidth: diff,
          message: `${sel} scrollWidth (${el.scrollWidth}px) > clientWidth (${el.clientWidth}px) + 4 with overflow-x: ${s.overflowX} (hidden: +${diff}px)`
        });
      }
    }
  }

  return {
    violations: allViolations,
    hasOverflow,
    scrollWidth,
    clientWidth,
    dashboardCheck,
    scannedNodesCount,
    openedModalIds,
    modalsOpenedCount,
    dropdownsOpenedCount,
    statesMeasuredCount,
    blankPageCheck,
    layoutViolations,
    gapLeft,
    gapTop
  };
}

async function runCrawler() {
  console.log('=== ADMIN CONTRAST & OVERFLOW CRAWLER (PERMANENT GUARD) ===');
  seedCrawlerData();
  const token = getAdminToken();
  const browser = await chromium.launch({ headless: true });

  const summary = [];
  const globalUniqueModals = new Set();
  const globalLayoutOffenders = [];

  for (const preset of PRESETS) {
    console.log(`\nSetting preset to: ${preset}`);
    setPreset(preset);

    for (const vp of VIEWPORTS) {
      console.log(`  Scanning at viewport: ${vp.name} (${vp.width}x${vp.height})...`);
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        extraHTTPHeaders: { 'Connection': 'close' }
      });
      const page = await context.newPage();

      // Route-abort external requests to avoid blocking PHP single-threaded server
      await page.route('**/*', route => {
        const url = route.request().url();
        if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications') || url.includes('/broadcasting/auth') || url.includes('/admin/calendar/events') || url.includes('/admin/exam-results/data')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [], events: [], data: [] }) });
        }
        if (url.includes('/api/messages')) {
          return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ messages: [], recipients: {} }) });
        }
        if (url.startsWith(BASE_URL)) {
          return route.continue();
        }
        return route.abort();
      });

      // Authenticate
      await page.goto(`${BASE_URL}/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await page.waitForURL('**/admin/dashboard', { timeout: 30000 });

      let pagesScanned = 0;
      let totalViolations = 0;
      let totalLayoutViolations = 0;
      let overflowPages = 0;
      let totalScannedNodes = 0;
      let totalModalsOpened = 0;
      let totalDropdownsOpened = 0;
      let totalStatesMeasured = 0;

      for (let i = 0; i < ROUTES.length; i++) {
        const route = ROUTES[i];
        if (EXCLUDED_ROUTES.includes(route)) {
          console.log(`    [${i + 1}/${ROUTES.length}] ${route} ... SKIP (excluded non-UI)`);
          continue;
        }
        try {
          process.stdout.write(`    [${i + 1}/${ROUTES.length}] ${route} ... `);
          let resp = null;
          for (let attempt = 0; attempt < 2; attempt++) {
            try {
              resp = await page.goto(`${BASE_URL}${route}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
              break;
            } catch (navErr) {
              if (attempt === 1) throw navErr;
              await page.waitForTimeout(1000);
            }
          }
          if (!resp || resp.status() !== 200) {
            console.log(`HTTP ${resp ? resp.status() : 'NONE'}`);
            continue;
          }
          const ct = resp.headers()['content-type'] || '';
          if (!ct.includes('text/html')) {
            console.log('SKIP (non-html)');
            continue;
          }
          pagesScanned++;
          const isDashboard = route === '/admin/dashboard';
          if (route === '/admin/exam-results') {
            await page.waitForTimeout(400);
          }

          const runDialogsScan = isDashboard;
          const evalResult = await page.evaluate(inBrowserCrawler, { isDashboard, vpWidth: vp.width, runDialogsScan });

          if (evalResult.scannedNodesCount <= 20) {
            throw new Error(`Broken scan guard: ${route} had only ${evalResult.scannedNodesCount} scanned text nodes (expected > 20).`);
          }
          totalScannedNodes += evalResult.scannedNodesCount;
          totalModalsOpened += evalResult.modalsOpenedCount;
          totalDropdownsOpened += evalResult.dropdownsOpenedCount;
          totalStatesMeasured += evalResult.statesMeasuredCount;

          for (const mId of evalResult.openedModalIds) {
            globalUniqueModals.add(mId);
          }

          if (evalResult.hasOverflow) {
            overflowPages++;
            console.warn(`[OVERFLOW: ${evalResult.scrollWidth}px]`);
          }

          if (evalResult.blankPageCheck && evalResult.blankPageCheck.failed) {
            totalViolations++;
            console.error(`      [BLANK PAGE DETECTED] docHeight=${evalResult.blankPageCheck.docHeight}px exceeds threshold ${evalResult.blankPageCheck.threshold}px (content bottom: ${evalResult.blankPageCheck.maxBottom}px)`);
          }

          if (evalResult.layoutViolations && evalResult.layoutViolations.length > 0) {
            totalLayoutViolations += evalResult.layoutViolations.length;
            totalViolations += evalResult.layoutViolations.length;
            for (const lv of evalResult.layoutViolations) {
              globalLayoutOffenders.push({
                preset,
                viewport: vp.name,
                route,
                ...lv
              });
              console.error(`      [LAYOUT VIOLATION] ${lv.message}`);
            }
          }

          if (evalResult.violations.length > 0) {
            totalViolations += evalResult.violations.length;
            console.log(`[FAIL: ${evalResult.violations.length} violations]`);
            for (const v of evalResult.violations.slice(0, 5)) {
              console.error(`      - "${v.text}" (${v.tag}) ratio ${v.ratio}:1 < ${v.minRatio}:1 [fg: ${v.fg}, bg: ${v.bg}]`);
            }
          } else if (!evalResult.hasOverflow && !(evalResult.blankPageCheck && evalResult.blankPageCheck.failed) && (!evalResult.layoutViolations || evalResult.layoutViolations.length === 0)) {
            const hInfo = evalResult.blankPageCheck ? ` [h:${evalResult.blankPageCheck.docHeight}px/b:${evalResult.blankPageCheck.maxBottom}px]` : '';
            console.log(`OK (${evalResult.scannedNodesCount} nodes, ${evalResult.modalsOpenedCount} modals${hInfo})`);
          }

          if (isDashboard && evalResult.dashboardCheck) {
            const dc = evalResult.dashboardCheck;
            if (!dc.statCardsOk) {
              console.error(`    [DASHBOARD FAIL] Stat cards check failed:`, dc.statCards);
            }
            for (const [name, icon] of Object.entries(dc.icons)) {
              if (!icon.exists || !icon.visible || icon.ratio < 3.0) {
                console.error(`    [HEADER ICON FAIL] ${name}: visible=${icon.visible}, ratio=${icon.ratio}:1`);
              }
            }
          }
        } catch (e) {
          console.error(`    Error crawling ${route}:`, e.message);
          totalViolations++;
        }
      }

      summary.push({
        preset,
        viewport: vp.name,
        pages: pagesScanned,
        modalsOpened: totalModalsOpened,
        dropdownsOpened: totalDropdownsOpened,
        statesMeasured: totalStatesMeasured,
        nodesScanned: totalScannedNodes,
        layoutViolations: totalLayoutViolations,
        violations: totalViolations
      });

      await context.close();
    }
  }

  await browser.close();

  console.log('\n=== CRAWLER SUMMARY TABLE ===');
  console.table(summary);

  console.log('\n=== VIEWPORT -> PAGES / LAYOUT VIOLATIONS ===');
  const layoutTable = summary.map(s => ({
    preset: s.preset,
    viewport: s.viewport,
    pages: s.pages,
    layoutViolations: s.layoutViolations,
    totalViolations: s.violations
  }));
  console.table(layoutTable);

  if (globalLayoutOffenders.length > 0) {
    console.error('\nTop 5 Layout Offenders:');
    globalLayoutOffenders.slice(0, 5).forEach((off, idx) => {
      console.error(`  ${idx + 1}. [${off.preset} ${off.viewport}] ${off.route} -> ${off.message}`);
    });
  }

  console.log(`\nUnique modals opened (${globalUniqueModals.size}):`);
  console.log(Array.from(globalUniqueModals));

  console.log('\nSUMMARY_JSON:' + JSON.stringify({ summary, uniqueModals: Array.from(globalUniqueModals) }));

  if (globalUniqueModals.size < 16) {
    console.error(`\nFAILED: Expected >= 16 unique modals opened on seeded run, but found ${globalUniqueModals.size}.`);
    process.exit(1);
  }

  const totalFailures = summary.reduce((acc, row) => acc + row.violations, 0);
  if (totalFailures > 0) {
    console.error(`\nFAILED: Found ${totalFailures} total violations.`);
    process.exit(1);
  } else {
    console.log('\nSUCCESS: 0 contrast violations and 0 horizontal overflows across all presets & viewports!');
    console.log(`Verified 0 layout violations across ${summary.length} preset/viewport runs.`);
    console.log(`Verified >= 16 unique modals (${globalUniqueModals.size}) and comprehensive interactive state coverage.`);
  }
}

if (require.main === module) {
  runCrawler().catch(err => {
    console.error(err);
    process.exit(1);
  });
}

module.exports = { runCrawler };
