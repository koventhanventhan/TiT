const fs = require('fs');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const jsonPath = path.join(rootDir, 'laravel_api', 'backend', 'resources', 'themes', 'presets.json');

if (!fs.existsSync(jsonPath)) {
  console.error('Error: presets.json not found at ' + jsonPath);
  process.exit(1);
}

const presets = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

function renderTokens(p) {
  const isDark = !!p.is_dark;
  const glassBg = isDark ? 'rgba(21, 27, 46, 0.85)' : 'rgba(255, 255, 255, 0.85)';
  const glow = isDark ? `0 0 25px rgba(79, 70, 229, 0.35)` : `0 0 25px rgba(79, 11, 217, 0.25)`;

  return `  /* Core Design Tokens */
  --t-primary: ${p.primary};
  --t-primary-hover: ${p.primary_hover};
  --t-primary-soft: ${p.primary_soft};
  --t-primary-border: ${p.primary_border};
  --t-primary-text: ${p.primary_text};
  --t-on-primary: ${p.on_primary};
  --t-sidebar: ${p.sidebar};
  --t-sidebar-text: ${p.sidebar_text};
  --t-sidebar-active-bg: ${isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(255, 255, 255, 0.10)'};
  --t-bg: ${p.bg};
  --t-surface: ${p.surface};
  --t-border: ${p.border};
  --t-text: ${p.text};
  --t-text-2: ${p.text_2};
  --t-text-3: ${p.text_3};
  --t-muted: ${isDark ? '#94a3b8' : '#94a3b8'};
  --t-radius-card: ${p.radius_card};
  --t-radius-control: ${p.radius_control};
  --t-success: ${p.success};
  --t-success-bg: ${p.success_bg};
  --t-warning: ${p.warning};
  --t-warning-bg: ${p.warning_bg};
  --t-danger: ${p.danger};
  --t-danger-bg: ${p.danger_bg};

  /* Legacy & Semantic Aliases (Unified Source of Truth) */
  --primary-color: var(--t-primary);
  --primary-dark: var(--t-sidebar);
  --primary-light: var(--t-primary-soft);
  --secondary-color: var(--t-primary-hover);
  --accent-color: var(--t-primary);
  --accent-glow: ${glow};
  --gradient-1: linear-gradient(135deg, var(--t-sidebar) 0%, var(--t-primary) 100%);
  --gradient-2: linear-gradient(135deg, var(--t-primary) 0%, var(--t-primary-hover) 100%);
  --gradient-3: linear-gradient(135deg, var(--t-primary) 0%, var(--t-primary-hover) 100%);
  --gradient-hero: linear-gradient(135deg, var(--t-sidebar) 0%, var(--t-primary) 100%);
  --gradient-glass: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
  --shadow-glow: ${glow};
  --glass-bg: ${glassBg};
  --glass-border: var(--t-border);
  --bg-light: var(--t-primary-soft);
  --bg-white: var(--t-surface);
  --bg-navy: var(--t-sidebar);
  --text-dark: var(--t-text);
  --text-medium: var(--t-text-2);
  --text-light: var(--t-text-3);
  --text-muted: var(--t-text-3);
  --border-color: var(--t-border);
  ${isDark ? 'color-scheme: dark;' : 'color-scheme: light;'}
`;
}

function generatePresetsCss() {
  const defaultPreset = presets.royal;
  let out = `/* Auto-generated from resources/themes/presets.json - DO NOT EDIT MANUALLY */\n`;

  // Root defaults to royal
  out += `:root {\n${renderTokens(defaultPreset)}}\n\n`;

  // Specific preset overrides
  for (const [key, p] of Object.entries(presets)) {
    out += `html[data-theme-preset="${key}"], [data-theme-preset="${key}"] {\n${renderTokens(p)}}\n\n`;
  }

  return out;
}

function generateAdminThemeCss() {
  let out = generatePresetsCss();

  out += `/* ==========================================================================
   Admin Template Token Integration & Overrides
   Maps DzEn / Avasta template styles to ONE unified token system (--t-*)
   ========================================================================== */

/* Template CSS Variables */
:root {
  --primary: var(--t-primary);
  --primary-hover: var(--t-primary-hover);
  --primary-dark: var(--t-sidebar);
  --primary-light: var(--t-primary-soft);
}

/* Base Body & Content */
body {
  background-color: var(--t-bg) !important;
  color: var(--t-text) !important;
}

/* Top Header Navbar */
.header {
  background-color: var(--t-surface) !important;
  border-bottom: 1px solid var(--t-border) !important;
}
.header .header-content {
  background-color: var(--t-surface) !important;
}

/* Sidebar & Brand Navigation */
.nav-header {
  background-color: var(--t-sidebar) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.nav-header .brand-title {
  color: var(--t-sidebar-text) !important;
}
.nav-header .brand-logo svg path {
  fill: var(--t-primary) !important;
}
.deznav {
  background-color: var(--t-sidebar) !important;
  border-right: 1px solid var(--t-border) !important;
}
.deznav .metismenu > li > a {
  color: var(--t-sidebar-text) !important;
}
.deznav .metismenu > li > a i {
  color: var(--t-sidebar-text) !important;
}
.deznav .metismenu > li:hover > a,
.deznav .metismenu > li.mm-active > a {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: #ffffff !important;
}
.deznav .metismenu > li:hover > a i,
.deznav .metismenu > li.mm-active > a i {
  color: #ffffff !important;
}
.deznav .metismenu ul a {
  color: var(--t-sidebar-text) !important;
}
.deznav .metismenu ul a:hover,
.deznav .metismenu ul a.mm-active {
  color: #ffffff !important;
}

/* Buttons */
.btn-primary {
  background-color: var(--t-primary) !important;
  border-color: var(--t-primary) !important;
  color: var(--t-on-primary) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
  background-color: var(--t-primary-hover) !important;
  border-color: var(--t-primary-hover) !important;
  color: var(--t-on-primary) !important;
}
.btn-outline-primary {
  color: var(--t-primary) !important;
  border-color: var(--t-primary) !important;
}
.btn-outline-primary:hover {
  background-color: var(--t-primary) !important;
  color: var(--t-on-primary) !important;
}

/* Cards & Card Headers */
.card {
  background-color: var(--t-surface) !important;
  border: 1px solid var(--t-border) !important;
  border-radius: var(--t-radius-card) !important;
}
.card-header,
.card-header-1 {
  background: var(--t-sidebar) !important;
  color: var(--t-sidebar-text) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.card-header .card-title,
.card-header-1 .card-title,
.card-header h4,
.card-header-1 h4 {
  color: #ffffff !important;
}
.card-header-secondary {
  color: var(--t-sidebar-text) !important;
}

/* Tables */
.table {
  color: var(--t-text) !important;
}
.table thead th {
  border-bottom: 2px solid var(--t-border) !important;
  color: var(--t-text-2) !important;
  background-color: var(--t-bg) !important;
}
.table tbody td {
  border-top: 1px solid var(--t-border) !important;
  color: var(--t-text) !important;
}

/* Badges */
.badge-primary {
  background-color: var(--t-primary) !important;
  color: var(--t-on-primary) !important;
}
.badge-primary.light,
.badge-info.light {
  background-color: var(--t-primary-soft) !important;
  color: var(--t-primary-text) !important;
}
.badge-success {
  background-color: var(--t-success) !important;
  color: #ffffff !important;
}
.badge-success.light {
  background-color: var(--t-success-bg) !important;
  color: var(--t-success) !important;
}
.badge-warning {
  background-color: var(--t-warning) !important;
  color: #ffffff !important;
}
.badge-warning.light {
  background-color: var(--t-warning-bg) !important;
  color: var(--t-warning) !important;
}
.badge-danger {
  background-color: var(--t-danger) !important;
  color: #ffffff !important;
}
.badge-danger.light {
  background-color: var(--t-danger-bg) !important;
  color: var(--t-danger) !important;
}

/* Stat Box bgl-* Overrides */
.bgl-primary {
  background-color: var(--t-primary-soft) !important;
  color: var(--t-primary-text) !important;
}
.text-primary {
  color: var(--t-primary) !important;
}
.bgl-warning {
  background-color: var(--t-warning-bg) !important;
  color: var(--t-warning) !important;
}
.text-warning {
  color: var(--t-warning) !important;
}
.bgl-danger {
  background-color: var(--t-danger-bg) !important;
  color: var(--t-danger) !important;
}
.text-danger {
  color: var(--t-danger) !important;
}
.bgl-success {
  background-color: var(--t-success-bg) !important;
  color: var(--t-success) !important;
}
.text-success {
  color: var(--t-success) !important;
}

/* Pagination */
.pagination .page-item.active .page-link {
  background-color: var(--t-primary) !important;
  border-color: var(--t-primary) !important;
  color: var(--t-on-primary) !important;
}
.pagination .page-link {
  color: var(--t-text-2) !important;
  border-color: var(--t-border) !important;
}
.pagination .page-link:hover {
  background-color: var(--t-primary-soft) !important;
  color: var(--t-primary) !important;
}

/* Form Controls */
.form-control:focus {
  border-color: var(--t-primary) !important;
  box-shadow: 0 0 0 0.2rem rgba(79, 11, 217, 0.25) !important;
}
`;

  return out;
}

const presetsCss = generatePresetsCss();
const adminCss = generateAdminThemeCss();
const isCheck = process.argv.includes('--check');

const presetTargets = [
  path.join(rootDir, 'laravel_api', 'src', 'theme', 'presets.css'),
  path.join(rootDir, 'src', 'theme', 'presets.css'),
];

const adminTargets = [
  path.join(rootDir, 'laravel_api', 'backend', 'public', 'admin-theme', 'css', 'tit-theme.css'),
];

if (isCheck) {
  let hasDiff = false;
  for (const target of presetTargets) {
    if (!fs.existsSync(target)) {
      console.error(`Check failed: ${target} does not exist.`);
      hasDiff = true;
    } else {
      const current = fs.readFileSync(target, 'utf8');
      if (current !== presetsCss) {
        console.error(`Check failed: ${target} is out of date with presets.json.`);
        hasDiff = true;
      }
    }
  }
  for (const target of adminTargets) {
    if (!fs.existsSync(target)) {
      console.error(`Check failed: ${target} does not exist.`);
      hasDiff = true;
    } else {
      const current = fs.readFileSync(target, 'utf8');
      if (current !== adminCss) {
        console.error(`Check failed: ${target} is out of date with presets.json.`);
        hasDiff = true;
      }
    }
  }
  if (hasDiff) {
    process.exit(1);
  }
  console.log('All theme CSS files are up to date with presets.json.');
  process.exit(0);
}

for (const target of presetTargets) {
  const dir = path.dirname(target);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  fs.writeFileSync(target, presetsCss, 'utf8');
  console.log(`Generated: ${path.relative(rootDir, target)}`);
}

for (const target of adminTargets) {
  const dir = path.dirname(target);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  fs.writeFileSync(target, adminCss, 'utf8');
  console.log(`Generated: ${path.relative(rootDir, target)}`);
}

console.log('Theme CSS generation complete.');
