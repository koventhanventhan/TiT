const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));
const { execSync } = require('child_process');

const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();

(async () => {
  const b = await chromium.launch({ headless: true });
  const page = await b.newPage({ viewport: { width: 820, height: 1180 } });
  await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`, { waitUntil: 'domcontentloaded' });
  await page.waitForURL('**/admin/dashboard');
  await page.waitForTimeout(1000);

  const chartInfo = await page.evaluate(() => {
    const chart = document.querySelector('#registrationActivityChart');
    if (!chart) {
      const emptyNotice = document.body.innerText.includes('No registrations in the last 7 days');
      return { exists: false, emptyNotice };
    }
    const svg = chart.querySelector('svg');
    const bars = chart.querySelectorAll('.ct-bar');
    const labels = chart.querySelectorAll('.ct-label');
    return {
      exists: true,
      hasSvg: !!svg,
      barsCount: bars.length,
      labelsCount: labels.length,
      rect: chart.getBoundingClientRect()
    };
  });
  console.log('Chart info:', JSON.stringify(chartInfo, null, 2));

  const card = await page.$('.card:has(#registrationActivityChart)');
  if (card) {
    await card.screenshot({ path: path.resolve(__dirname, '../docs/screenshots/prompt31c/after/registration_chart_820.png') });
    console.log('Saved docs/screenshots/prompt31c/after/registration_chart_820.png');
  } else {
    const emptyCard = await page.$('.card:has(.flaticon-381-calendar-1)');
    if (emptyCard) {
      await emptyCard.screenshot({ path: path.resolve(__dirname, '../docs/screenshots/prompt31c/after/registration_chart_empty_820.png') });
      console.log('Saved docs/screenshots/prompt31c/after/registration_chart_empty_820.png');
    }
  }

  await b.close();
})();
