const fs = require('fs');
const path = require('path');
const http = require('http');
const { chromium } = require(path.join(__dirname, '..', 'laravel_api', 'node_modules', '@playwright', 'test'));
const sharp = require(path.join(__dirname, '..', 'laravel_api', 'node_modules', 'sharp'));

const OLD_DIST = process.env.OLD_DIST || 'C:\\Users\\HP\\AppData\\Local\\Temp\\e791792\\laravel_api\\dist';
const NEW_DIST = path.join(__dirname, '..', 'laravel_api', 'dist');
const OUTPUT_DIR = path.join(__dirname, '..', 'diff_reports');

const ROUTES = ['/', '/about', '/classes', '/contact', '/register'];
const VIEWPORTS = [
  { name: '1366x800', width: 1366, height: 800 },
  { name: '390x800', width: 390, height: 800 },
];

const MIME_TYPES = {
  '.html': 'text/html',
  '.js': 'application/javascript',
  '.css': 'text/css',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.webp': 'image/webp',
  '.svg': 'image/svg+xml',
  '.avif': 'image/avif',
  '.gif': 'image/gif',
  '.woff2': 'font/woff2',
  '.woff': 'font/woff',
  '.ttf': 'font/ttf',
};

function createStaticServer(distDir, port) {
  const server = http.createServer((req, res) => {
    let reqPath = req.url.split('?')[0];
    if (reqPath === '/') reqPath = '/index.html';
    let filePath = path.join(distDir, reqPath);

    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      const ext = path.extname(filePath).toLowerCase();
      res.writeHead(200, { 'Content-Type': MIME_TYPES[ext] || 'application/octet-stream' });
      fs.createReadStream(filePath).pipe(res);
    } else {
      // SPA Fallback
      const indexPath = path.join(distDir, 'index.html');
      res.writeHead(200, { 'Content-Type': 'text/html' });
      fs.createReadStream(indexPath).pipe(res);
    }
  });

  return new Promise((resolve) => {
    server.listen(port, () => resolve(server));
  });
}

async function captureScreenshot(page, baseUrl, routePath, vp, isNew = false) {
  await page.setViewportSize({ width: vp.width, height: vp.height });

  await page.goto(`${baseUrl}${routePath}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(400);

  // Freeze animations and transitions for stable visual comparison
  await page.addStyleTag({
    content: `
      *, *::before, *::after {
        transition-duration: 0s !important;
        animation-duration: 0s !important;
        animation-delay: -0.0001s !important;
        caret-color: transparent !important;
      }
    `
  });

  if (isNew) {
    await page.evaluate(() => {
      document.documentElement.dataset.themePreset = 'royal';
    });
  }

  await page.waitForTimeout(200);
  return await page.screenshot({ fullPage: true });
}

async function compareImages(oldPng, newPng, routeName, vpName) {
  const metaOld = await sharp(oldPng).metadata();
  const metaNew = await sharp(newPng).metadata();

  const width = Math.max(metaOld.width, metaNew.width);
  const height = Math.max(metaOld.height, metaNew.height);

  // Normalize dimensions with white background
  const oldNorm = await sharp(oldPng)
    .resize(width, height, { fit: 'contain', position: 'top', background: { r: 255, g: 255, b: 255, alpha: 1 } })
    .raw()
    .toBuffer({ resolveWithObject: true });

  const newNorm = await sharp(newPng)
    .resize(width, height, { fit: 'contain', position: 'top', background: { r: 255, g: 255, b: 255, alpha: 1 } })
    .raw()
    .toBuffer({ resolveWithObject: true });

  const bufOld = oldNorm.data;
  const bufNew = newNorm.data;
  const channels = oldNorm.info.channels;
  const totalPixels = width * height;
  let diffPixels = 0;

  const diffMask = Buffer.alloc(width * height * 4);

  for (let i = 0; i < totalPixels; i++) {
    const idx = i * channels;
    const r1 = bufOld[idx], g1 = bufOld[idx + 1], b1 = bufOld[idx + 2];
    const r2 = bufNew[idx], g2 = bufNew[idx + 1], b2 = bufNew[idx + 2];

    const dr = r1 - r2;
    const dg = g1 - g2;
    const db = b1 - b2;
    const distSq = dr * dr + dg * dg + db * db;

    const outIdx = i * 4;
    if (distSq > 100) {
      diffPixels++;
      // Red/Magenta highlight for diffs
      diffMask[outIdx] = 255;
      diffMask[outIdx + 1] = 0;
      diffMask[outIdx + 2] = 128;
      diffMask[outIdx + 3] = 255;
    } else {
      // Grayed out background
      const gray = Math.round(0.299 * r1 + 0.587 * g1 + 0.114 * b1);
      diffMask[outIdx] = gray;
      diffMask[outIdx + 1] = gray;
      diffMask[outIdx + 2] = gray;
      diffMask[outIdx + 3] = 120;
    }
  }

  const diffPct = (diffPixels / totalPixels) * 100;

  // If diff > 1.0%, save side-by-side screenshot and diff mask
  if (diffPct > 1.0) {
    if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    const cleanRoute = routeName === '/' ? 'home' : routeName.replace(/\//g, '');
    const prefix = `${cleanRoute}_${vpName}`;

    await sharp(oldPng).toFile(path.join(OUTPUT_DIR, `${prefix}_old.png`));
    await sharp(newPng).toFile(path.join(OUTPUT_DIR, `${prefix}_current.png`));
    await sharp(diffMask, { raw: { width, height, channels: 4 } }).png().toFile(path.join(OUTPUT_DIR, `${prefix}_diff.png`));
  }

  return {
    widthOld: metaOld.width,
    heightOld: metaOld.height,
    widthNew: metaNew.width,
    heightNew: metaNew.height,
    totalPixels,
    diffPixels,
    diffPct: Math.round(diffPct * 100) / 100,
  };
}

async function run() {
  console.log('=== VISUAL REGRESSION GUARD: e791792 (Before Prompt 19) vs Current Royal ===\n');
  console.log(`Old bundle path: ${OLD_DIST}`);
  console.log(`Current bundle path: ${NEW_DIST}\n`);

  if (!fs.existsSync(OLD_DIST)) {
    console.error(`ERROR: Old bundle not found at ${OLD_DIST}`);
    process.exit(1);
  }

  const oldServer = await createStaticServer(OLD_DIST, 4181);
  const newServer = await createStaticServer(NEW_DIST, 4182);

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();

  await context.route('**/api/settings', async (route) => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        site_name: 'Online Education Platform',
        theme_preset: 'royal',
        classes_types: JSON.stringify([
          { title: 'Direct Tuition', color: '#4f0bd9', image: '/venthan1.webp', format: 'Physical', duration: 'Weekly', students: 'Group' },
          { title: 'Online Tuition', color: '#4f0bd9', image: '/venthan2.webp', format: 'Online', duration: 'Flexible', students: 'Group/1-on-1' }
        ])
      })
    });
  });

  await context.route('**/api/**', async (route) => {
    const url = route.request().url();
    if (url.includes('/api/settings')) return;
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, data: [] })
    });
  });

  const page = await context.newPage();

  const results = [];

  for (const routePath of ROUTES) {
    for (const vp of VIEWPORTS) {
      process.stdout.write(`Comparing ${routePath} @ ${vp.name}... `);

      const oldPng = await captureScreenshot(page, 'http://127.0.0.1:4181', routePath, vp, false);
      const newPng = await captureScreenshot(page, 'http://127.0.0.1:4182', routePath, vp, true);

      const cmp = await compareImages(oldPng, newPng, routePath, vp.name);
      console.log(`Diff: ${cmp.diffPct}% (${cmp.diffPixels} / ${cmp.totalPixels} px)`);

      results.push({
        route: routePath,
        viewport: vp.name,
        oldDim: `${cmp.widthOld}x${cmp.heightOld}`,
        newDim: `${cmp.widthNew}x${cmp.heightNew}`,
        diffPixels: cmp.diffPixels,
        diffPct: cmp.diffPct,
      });
    }
  }

  await browser.close();
  oldServer.close();
  newServer.close();

  console.log('\n========================================================================================');
  console.log('                            VISUAL REGRESSION COMPARE TABLE                             ');
  console.log('========================================================================================');
  console.log('| Route       | Viewport   | Old Dim      | Current Dim  | Diff Pixels | Diff % | Verdict ');
  console.log('|-------------|------------|--------------|--------------|-------------|--------|---------');

  for (const r of results) {
    const routeCol = r.route.padEnd(11);
    const vpCol = r.viewport.padEnd(10);
    const oldCol = r.oldDim.padEnd(12);
    const newCol = r.newDim.padEnd(12);
    const pxCol = String(r.diffPixels).padStart(11);
    const pctCol = `${r.diffPct.toFixed(2)}%`.padStart(6);
    const verdict = r.diffPct <= 1.0 ? 'PASS (<1%)' : 'DIFF (>1%)';
    console.log(`| ${routeCol} | ${vpCol} | ${oldCol} | ${newCol} | ${pxCol} | ${pctCol} | ${verdict}`);
  }
  console.log('========================================================================================\n');
}

run().catch((err) => {
  console.error('Visual compare failed:', err);
  process.exit(1);
});
