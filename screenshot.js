const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto('http://localhost:4000/pastpapers?grade=Grade%209', { waitUntil: 'load' });
  await new Promise(r => setTimeout(r, 3000));
  
  // Scroll to CTA banner area (roughly middle-bottom of page)
  await page.evaluate(() => {
    const cta = document.querySelector('.past-papers-cta-banner');
    if (cta) cta.scrollIntoView({ block: 'center' });
    else window.scrollTo(0, 600);
  });
  await new Promise(r => setTimeout(r, 2000));
  
  await page.screenshot({ path: 'screenshot_cta.png' });
  await browser.close();
  console.log('CTA screenshot taken!');
})();
