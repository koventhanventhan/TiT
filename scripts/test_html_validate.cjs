const { HtmlValidate } = require('html-validate');
const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../laravel_api/node_modules/@playwright/test'));

async function validatePage() {
  const token = execSync(`php "${path.resolve(__dirname, 'theme_helper.php')}" get_token`).toString().trim();
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
  const page = await context.newPage();

  await page.route('**/*', (route) => {
    const url = route.request().url();
    if (url.includes('/admin/messages/unread-count') || url.includes('/admin/notifications')) {
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ count: 0, notifications: [] }) });
    }
    return route.continue();
  });

  await page.goto(`http://127.0.0.1:8000/admin/login?token=${encodeURIComponent(token)}`);
  await page.goto('http://127.0.0.1:8000/admin/settings/admission-fees');
  await page.waitForTimeout(500);

  const fullHtml = await page.content();
  await browser.close();

  // Extract .admission-fees-page
  const match = fullHtml.match(/<div class="admission-fees-page">[\s\S]*?<\/details>\s*<\/div>/);
  const pageHtml = match ? match[0] : fullHtml;

  const validator = new HtmlValidate({
    extends: ['html-validate:recommended'],
    rules: {
      'no-trailing-whitespace': 'off',
      'no-inline-style': 'off',
      'wcag/h32': 'off',
      'wcag/h30': 'off',
      'prefer-native-element': 'off',
      'valid-autocomplete': 'off',
      'attribute-allowed-values': 'off',
      'hidden-focusable': 'off',
      'no-redundant-for': 'off',
      'attribute-boolean-style': 'off'
    }
  });

  const report = await validator.validateString(pageHtml);
  console.log('--- HTML Validation for Admission Fees Page ---');
  console.log(`Valid: ${report.valid}`);
  console.log(`Errors: ${report.errorCount}`);
  console.log(`Warnings: ${report.warningCount}`);
  if (!report.valid) {
    for (const res of report.results) {
      for (const msg of res.messages) {
        console.error(`Line ${msg.line}:${msg.column} [${msg.ruleId}] ${msg.message}`);
      }
    }
  } else {
    console.log('0 errors found for this page.');
  }
}

validatePage().catch(console.error);
