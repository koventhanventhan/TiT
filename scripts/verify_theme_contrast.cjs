const fs = require('fs');
const path = require('path');

const jsonPath = path.join(__dirname, '..', 'laravel_api', 'backend', 'resources', 'themes', 'presets.json');
const presets = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

function hexToRgb(hex) {
  const cleaned = hex.replace('#', '');
  const num = parseInt(cleaned, 16);
  return [
    (num >> 16) & 255,
    (num >> 8) & 255,
    num & 255,
  ];
}

function relativeLuminance([r, g, b]) {
  const a = [r, g, b].map(v => {
    v /= 255;
    return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
}

function contrastRatio(hex1, hex2) {
  const lum1 = relativeLuminance(hexToRgb(hex1));
  const lum2 = relativeLuminance(hexToRgb(hex2));
  const brightest = Math.max(lum1, lum2);
  const darkest = Math.min(lum1, lum2);
  return (brightest + 0.05) / (darkest + 0.05);
}

const requiredPairs = [
  ['on_primary', 'primary', 'on-primary on primary', 4.5],
  ['text', 'surface', 'text on surface', 4.5],
  ['text_2', 'surface', 'text-2 on surface', 4.5],
  ['text_3', 'surface', 'text-3 on surface', 4.5],
  ['text', 'bg', 'text on bg', 4.5],
  ['primary_text', 'surface', 'primary-text on surface', 4.5],
  ['primary_text', 'primary_soft', 'primary-text on primary-soft', 4.5],
  ['sidebar_text', 'sidebar', 'sidebar-text on sidebar', 4.5],
  ['success', 'success_bg', 'success on success-bg', 4.5],
  ['warning', 'warning_bg', 'warning on warning-bg', 4.5],
  ['danger', 'danger_bg', 'danger on danger-bg', 4.5],
];

console.log('=== WCAG AA (>= 4.5:1) Contrast Ratio Verification ===\n');

let allPassed = true;
const resultsTable = [];

for (const [presetId, preset] of Object.entries(presets)) {
  console.log(`Checking Preset: ${preset.label} (${presetId})`);
  for (const [fgKey, bgKey, description, minRatio] of requiredPairs) {
    const fgColor = preset[fgKey];
    const bgColor = preset[bgKey];
    const ratio = contrastRatio(fgColor, bgColor);
    const passed = ratio >= minRatio;

    resultsTable.push({
      preset: presetId,
      check: description,
      foreground: fgColor,
      background: bgColor,
      ratio: ratio.toFixed(2) + ':1',
      min: minRatio + ':1',
      status: passed ? 'PASS' : 'FAIL',
    });

    if (!passed) {
      allPassed = false;
      console.error(`  [FAIL] ${description}: ${ratio.toFixed(2)}:1 (minimum ${minRatio}:1)`);
    } else {
      console.log(`  [PASS] ${description}: ${ratio.toFixed(2)}:1`);
    }
  }
  console.log('');
}

console.table(resultsTable);

if (!allPassed) {
  console.error('\nFAIL: One or more contrast checks failed WCAG 4.5:1 ratio.');
  process.exit(1);
}

console.log('\nSUCCESS: All presets passed all WCAG AA 4.5:1 contrast requirements.');
process.exit(0);

