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
  const isRoyal = p.id === 'royal';
  const glassBg = isDark ? 'rgba(21, 27, 46, 0.85)' : 'rgba(255, 255, 255, 0.85)';
  const glow = isDark ? `0 0 25px rgba(79, 70, 229, 0.35)` : `0 0 25px rgba(79, 11, 217, 0.25)`;

  let primaryRgb = '79, 11, 217';
  if (p.primary && p.primary.startsWith('#') && p.primary.length === 7) {
    const r = parseInt(p.primary.slice(1, 3), 16);
    const g = parseInt(p.primary.slice(3, 5), 16);
    const b = parseInt(p.primary.slice(5, 7), 16);
    primaryRgb = `${r}, ${g}, ${b}`;
  }

  const primaryLightVal = isRoyal ? '#6d28d9' : (isDark ? '#6366f1' : (p.primary_hover || p.primary));

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
  --t-muted: ${isDark ? '#94a3b8' : (p.text_2 || '#475569')};
  --t-bg-muted: ${isDark ? '#1e2447' : '#f8fafc'};
  --t-radius-card: ${p.radius_card};
  --t-radius-control: ${p.radius_control};
  --t-success: ${p.success};
  --t-success-bg: ${p.success_bg};
  --t-warning: ${p.warning};
  --t-warning-bg: ${p.warning_bg};
  --t-danger: ${p.danger};
  --t-danger-bg: ${p.danger_bg};

  /* Admin Dark Console Tokens */
  --adm-bg: ${p.adm_bg || '#2c254a'};
  --adm-surface: ${p.adm_surface || '#3b3363'};
  --adm-sidebar: ${p.adm_sidebar || '#3b3363'};
  --adm-border: ${p.adm_border || '#473f72'};
  --adm-text: ${p.adm_text || '#f1f5f9'};
  --adm-text-2: ${p.adm_text_2 || '#cbd5e1'};
  --adm-text-3: ${p.adm_text_3 || '#a8b3c7'};
  --adm-accent: ${p.adm_accent || '#EB8153'};
  --adm-accent-strong: ${p.adm_accent_strong || (isRoyal ? '#fb923c' : p.adm_accent || '#EB8153')};
  --adm-btn: ${p.adm_btn || '#EB8153'};
  --adm-btn-text: ${p.adm_btn_text || '#0f172a'};
  --adm-on-accent: ${p.adm_on_accent || '#0f172a'};
  --adm-input-bg: ${p.adm_input_bg || '#241e3d'};
  --adm-surface-2: ${p.adm_surface_2 || '#332b56'};
  --adm-success: ${p.adm_success || '#34d399'};
  --adm-danger: ${p.adm_danger || '#f87171'};
  --adm-warning: ${p.adm_warning || '#fbbf24'};
  --adm-info: ${p.adm_info || '#38bdf8'};

  /* Legacy & Semantic Aliases (Unified Source of Truth - fully self-sufficient) */
  --primary-color: var(--t-primary);
  --primary-dark: var(--t-sidebar);
  --primary-light: ${primaryLightVal};
  --secondary-color: var(--t-primary-hover);
  --accent-color: var(--t-primary);
  --accent-color-rgb: ${primaryRgb};
  --accent-glow: ${glow};
  --gradient-1: linear-gradient(135deg, var(--t-sidebar) 0%, var(--t-primary) 100%);
  --gradient-2: linear-gradient(135deg, var(--t-primary) 0%, var(--t-primary-hover) 100%);
  --gradient-3: linear-gradient(135deg, var(--t-primary) 0%, var(--t-primary-hover) 100%);
  --gradient-hero: linear-gradient(135deg, var(--t-sidebar) 0%, var(--t-primary) 100%);
  --gradient-glass: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
  --shadow-sm: 0 0.0625rem 0.1875rem rgba(15, 23, 42, 0.06);
  --shadow-md: 0 0.25rem 1rem rgba(15, 23, 42, 0.08);
  --shadow-lg: 0 0.75rem 2.5rem rgba(15, 23, 42, 0.12);
  --shadow-xl: 0 1.25rem 3.75rem rgba(15, 23, 42, 0.15);
  --shadow-glow: ${glow};
  --glass-bg: ${glassBg};
  --glass-border: var(--t-border);
  --glass-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.12);
  --bg-light: var(--t-primary-soft);
  --bg-white: var(--t-surface);
  --bg-dark: #0c0a09;
  --bg-navy: var(--t-sidebar);
  --text-dark: var(--t-text);
  --text-medium: var(--t-text-2);
  --text-light: var(--t-text-3);
  --text-muted: var(--t-text-3);
  --color-text: var(--t-text);
  --color-muted: var(--t-text-2);
  --surface: var(--t-surface);
  --surface-2: var(--t-bg-muted);
  --text: var(--t-text);
  --text-2: var(--t-text-2);
  --text-3: var(--t-text-3);
  --border: var(--t-border);
  --primary: var(--t-primary);
  --on-primary: var(--t-on-primary);
  --card-bg: var(--t-surface);
  --success: var(--t-success);
  --warning: var(--t-warning);
  --danger: var(--t-danger);
  --border-color: var(--t-border);
  --border-light: ${isDark ? '#2a3350' : '#f1f5f9'};
  --radius-sm: 0.5rem;
  --radius-md: 0.75rem;
  --radius-lg: 1rem;
  --radius-xl: 1.5rem;
  --radius-2xl: 2rem;
  --safe-area-top: env(safe-area-inset-top, 0px);
  --safe-area-bottom: env(safe-area-inset-bottom, 0px);
  --tool-color: var(--t-primary);
  --tool-color-rgb: ${primaryRgb};
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
   Maps DzEn / Avasta template styles to ONE unified dark token system (--adm-*)
   ========================================================================== */

/* Template CSS Variables */
:root {
  --primary: var(--adm-accent);
  --primary-hover: var(--adm-accent);
  --primary-dark: var(--adm-sidebar);
  --primary-light: var(--adm-surface);
}

/* Base Body & Content */
html,
body {
  background-color: var(--adm-bg) !important;
  color: var(--adm-text) !important;
  color-scheme: dark;
}

#main-wrapper {
  background-color: var(--adm-bg) !important;
}

.content-body {
  background-color: var(--adm-bg) !important;
  color: var(--adm-text) !important;
}

/* Top Header Navbar */
.header {
  background-color: var(--adm-sidebar) !important;
  border-bottom: 1px solid var(--adm-border) !important;
}
.header .header-content {
  background-color: var(--adm-sidebar) !important;
}
.header .navbar-nav .nav-link {
  color: var(--adm-text) !important;
}
.header .navbar-nav .nav-link:hover {
  color: var(--adm-accent) !important;
}
.header .navbar-nav .nav-link svg path,
.header .navbar-nav .nav-link svg polyline,
.header .navbar-nav .nav-link svg line {
  stroke: var(--adm-text) !important;
}
.header .navbar-nav .nav-link svg[fill] path {
  fill: var(--adm-text) !important;
}
.header .navbar-nav .nav-link:hover svg path,
.header .navbar-nav .nav-link:hover svg polyline,
.header .navbar-nav .nav-link:hover svg line {
  stroke: var(--adm-accent) !important;
}
.header .navbar-nav .nav-link:hover svg[fill] path {
  fill: var(--adm-accent) !important;
}

/* Hamburger */
.custom-hamburger span,
.hamburger .line {
  background-color: var(--adm-text) !important;
}
.nav-control {
  background-color: transparent !important;
  color: var(--adm-text) !important;
}

/* Sidebar & Brand Navigation */
.nav-header {
  background-color: var(--adm-sidebar) !important;
  border-bottom: 1px solid var(--adm-border) !important;
}
.nav-header .brand-title {
  color: var(--adm-text) !important;
}
.nav-header .brand-logo svg .svg-logo-rect {
  fill: var(--adm-accent) !important;
}
.nav-header .brand-logo svg .svg-logo-path {
  fill: #ffffff !important;
}
.deznav {
  background-color: var(--adm-sidebar) !important;
  border-right: 1px solid var(--adm-border) !important;
}
.deznav .metismenu > li > a,
.deznav .metismenu ul a,
.deznav .nav-text {
  color: var(--adm-text-2) !important;
}
.deznav .metismenu > li > a i {
  color: var(--adm-text-2) !important;
}
.deznav .metismenu > li:hover > a,
.deznav .metismenu > li.mm-active > a,
.deznav .metismenu ul a:hover,
.deznav .metismenu ul a.mm-active {
  background-color: rgba(0, 0, 0, 0.25) !important;
  color: var(--adm-text) !important;
}
.deznav .metismenu > li:hover > a i,
.deznav .metismenu > li.mm-active > a i,
.deznav .metismenu ul a:hover i,
.deznav .metismenu ul a.mm-active i {
  color: var(--adm-accent) !important;
}
.deznav .metismenu > li:hover > a .nav-text,
.deznav .metismenu > li.mm-active > a .nav-text,
.deznav .metismenu ul a:hover .nav-text,
.deznav .metismenu ul a.mm-active .nav-text {
  color: var(--adm-text) !important;
}
.deznav .metismenu > li.mm-active > a {
  border-left: 4px solid var(--adm-accent) !important;
}

/* Buttons */
.btn-primary {
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active,
.btn-primary.active {
  background-color: var(--adm-accent) !important;
  border-color: var(--adm-accent) !important;
  color: var(--adm-on-accent, #ffffff) !important;
}
.btn-outline-primary {
  color: var(--adm-text) !important;
  border-color: var(--adm-accent) !important;
  background-color: transparent !important;
}
.btn-outline-primary:hover,
.btn-outline-primary:focus,
.btn-outline-primary:active,
.btn-outline-primary.active {
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}
.btn-outline-secondary,
.btn-outline-light {
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
  background-color: transparent !important;
}
.btn-outline-secondary:hover,
.btn-outline-secondary:focus,
.btn-outline-secondary:active,
.btn-outline-secondary.active,
.btn-outline-light:hover,
.btn-outline-light:focus,
.btn-outline-light:active,
.btn-outline-light.active {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-outline-info {
  color: #7dd3fc !important;
  border-color: #38bdf8 !important;
  background-color: transparent !important;
}
.btn-outline-info:hover,
.btn-outline-info:focus,
.btn-outline-info:active,
.btn-outline-info.active {
  background-color: #0284c7 !important;
  border-color: #0284c7 !important;
  color: #ffffff !important;
}
.btn-outline-success {
  color: #4ade80 !important;
  border-color: #4ade80 !important;
  background-color: transparent !important;
}
.btn-outline-success:hover,
.btn-outline-success:focus,
.btn-outline-success:active,
.btn-outline-success.active {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
  color: #ffffff !important;
}
.btn-outline-warning {
  color: #fbbf24 !important;
  border-color: #fbbf24 !important;
  background-color: transparent !important;
}
.btn-outline-warning:hover,
.btn-outline-warning:focus,
.btn-outline-warning:active,
.btn-outline-warning.active {
  background-color: #f59e0b !important;
  border-color: #f59e0b !important;
  color: #0f172a !important;
}
.btn-outline-danger {
  color: #fca5a5 !important;
  border-color: #f87171 !important;
  background-color: transparent !important;
}
.btn-outline-danger:hover,
.btn-outline-danger:focus,
.btn-outline-danger:active,
.btn-outline-danger.active {
  background-color: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
}
.btn-outline-dark {
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
  background-color: transparent !important;
}
.btn-outline-dark:hover,
.btn-outline-dark:focus,
.btn-outline-dark:active,
.btn-outline-dark.active {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-secondary {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-secondary:hover,
.btn-secondary:focus,
.btn-secondary:active,
.btn-secondary.active {
  background-color: rgba(255, 255, 255, 0.16) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-info {
  background-color: #0369a1 !important;
  border-color: #0369a1 !important;
  color: #ffffff !important;
}
.btn-info:hover,
.btn-info:focus,
.btn-info:active,
.btn-info.active {
  background-color: #0284c7 !important;
  border-color: #0284c7 !important;
  color: #ffffff !important;
}
.btn-warning {
  background-color: #f59e0b !important;
  border-color: #f59e0b !important;
  color: #0f172a !important;
  font-weight: 600 !important;
}
.btn-warning:hover,
.btn-warning:focus,
.btn-warning:active,
.btn-warning.active {
  background-color: #d97706 !important;
  border-color: #d97706 !important;
  color: #0f172a !important;
}
.btn-success {
  background-color: #15803d !important;
  border-color: #15803d !important;
  color: #ffffff !important;
}
.btn-success:hover,
.btn-success:focus,
.btn-success:active,
.btn-success.active {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
  color: #ffffff !important;
}
.btn-danger {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
  color: #ffffff !important;
}
.btn-danger:hover,
.btn-danger:focus,
.btn-danger:active,
.btn-danger.active {
  background-color: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
}
.btn-light,
.btn-white {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-light:hover,
.btn-light:focus,
.btn-light:active,
.btn-light.active,
.btn-white:hover,
.btn-white:focus,
.btn-white:active,
.btn-white.active {
  background-color: var(--adm-input-bg) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-dark {
  background-color: #0f172a !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-dark:hover,
.btn-dark:focus,
.btn-dark:active,
.btn-dark.active {
  background-color: #1e293b !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-orange {
  background-color: var(--adm-accent) !important;
  border-color: var(--adm-accent) !important;
  color: var(--adm-on-accent) !important;
}
.btn-orange:hover,
.btn-orange:focus,
.btn-orange:active,
.btn-orange.active {
  background-color: var(--adm-accent-hover, var(--adm-accent)) !important;
  border-color: var(--adm-accent-hover, var(--adm-accent)) !important;
  color: var(--adm-on-accent) !important;
}
.btn-blue {
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
  color: var(--adm-btn-text) !important;
}
.btn-blue:hover,
.btn-blue:focus,
.btn-blue:active,
.btn-blue.active {
  background-color: var(--adm-btn-hover, var(--adm-btn)) !important;
  border-color: var(--adm-btn-hover, var(--adm-btn)) !important;
  color: var(--adm-btn-text) !important;
}

/* Loading state class */
.is-loading,
.btn.is-loading {
  position: relative;
  cursor: wait !important;
  pointer-events: none !important;
  opacity: 1 !important;
  color: var(--adm-btn-text, #ffffff) !important;
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
}
.btn.is-loading * {
  color: inherit !important;
}
.btn-secondary.is-loading {
  color: var(--adm-text) !important;
  background-color: var(--adm-surface-2) !important;
}
.btn-secondary.is-loading * {
  color: inherit !important;
}
.btn-success.is-loading {
  color: #ffffff !important;
  background-color: #15803d !important;
}
.btn-success.is-loading * {
  color: inherit !important;
}
.btn-danger.is-loading {
  color: #ffffff !important;
  background-color: #dc2626 !important;
}
.btn-danger.is-loading * {
  color: inherit !important;
}
.btn-warning.is-loading {
  color: #0f172a !important;
  background-color: #f59e0b !important;
}
.btn-warning.is-loading * {
  color: inherit !important;
}

/* Disabled buttons - rule: opacity 0.75, explicit high-contrast labels >= 3:1 on button background */
.btn:disabled,
.btn.disabled,
fieldset:disabled .btn {
  opacity: 0.75 !important;
  cursor: not-allowed !important;
  pointer-events: none;
}
.btn-primary:disabled,
.btn-primary.disabled {
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}
.btn-secondary:disabled,
.btn-secondary.disabled {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-2) !important;
}
.btn-outline-primary:disabled,
.btn-outline-primary.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-2) !important;
}
.btn-outline-secondary:disabled,
.btn-outline-secondary.disabled,
.btn-outline-light:disabled,
.btn-outline-light.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-2) !important;
}
.btn-outline-success:disabled,
.btn-outline-success.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: #4ade80 !important;
}
.btn-outline-danger:disabled,
.btn-outline-danger.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: #fca5a5 !important;
}
.btn-outline-warning:disabled,
.btn-outline-warning.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: #fbbf24 !important;
}
.btn-outline-info:disabled,
.btn-outline-info.disabled {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: #7dd3fc !important;
}
.btn-success:disabled,
.btn-success.disabled {
  background-color: #15803d !important;
  border-color: #15803d !important;
  color: #ffffff !important;
}
.btn-danger:disabled,
.btn-danger.disabled {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
  color: #ffffff !important;
}
.btn-warning:disabled,
.btn-warning.disabled {
  background-color: #f59e0b !important;
  border-color: #f59e0b !important;
  color: #0f172a !important;
}
.btn-info:disabled,
.btn-info.disabled {
  background-color: #0369a1 !important;
  border-color: #0369a1 !important;
  color: #ffffff !important;
}
.btn-light:disabled,
.btn-light.disabled,
.btn-white:disabled,
.btn-white.disabled {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-2) !important;
}
.btn-dark:disabled,
.btn-dark.disabled {
  background-color: #0f172a !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-2) !important;
}

/* Cards & Card Headers */
.card {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  border-radius: var(--t-radius-card, 16px) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
  color: var(--adm-text) !important;
}
.card-header,
.card-header-1 {
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border-bottom: 1px solid var(--adm-border) !important;
}
.card-header .card-title,
.card-header-1 .card-title,
.card-header h4,
.card-header-1 h4,
.card-title {
  color: var(--adm-text) !important;
}
.card-header-secondary {
  color: var(--adm-text-2) !important;
}
.card-body {
  color: var(--adm-text) !important;
}

/* Stat Cards / Widget Stat */
.widget-stat {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
}
.widget-stat .media-body h4,
.widget-stat h4 {
  color: var(--adm-text) !important;
}
.widget-stat .media-body p,
.widget-stat p {
  color: var(--adm-text-2) !important;
}
.widget-stat .media > span,
.widget-stat span.bgl-primary {
  background-color: rgba(255, 255, 255, 0.08) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-accent) !important;
}
.widget-stat .media > span i,
.widget-stat span.bgl-primary i {
  color: var(--adm-accent) !important;
}

/* Tables */
.table {
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
}
.table thead th {
  border-bottom: 2px solid var(--adm-border) !important;
  border-top: none !important;
  color: var(--adm-text-2) !important;
  background-color: rgba(255, 255, 255, 0.03) !important;
  font-weight: 600 !important;
}
.table tbody td {
  border-top: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.table strong {
  color: var(--adm-text) !important;
}
.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255, 255, 255, 0.02) !important;
}
.table-hover tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
  color: var(--adm-text) !important;
}

/* Form Controls & Inputs */
.form-control,
.custom-select,
.input-group-text {
  background-color: var(--adm-input-bg) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.form-control:focus,
.custom-select:focus {
  border-color: var(--adm-accent) !important;
  box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
  background-color: var(--adm-input-bg) !important;
  color: var(--adm-text) !important;
}
.form-control:disabled,
.form-control[disabled],
.form-control:read-only,
.form-control[readonly],
.custom-select:disabled {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text-2) !important;
  border-color: var(--adm-border) !important;
  opacity: 0.8 !important;
}
.form-control::placeholder,
input::placeholder,
textarea::placeholder {
  color: var(--adm-text-2) !important;
  opacity: 0.8 !important;
}
.header-left .search_bar .form-control {
  background-color: var(--adm-input-bg) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.header-left .search_bar .form-control::placeholder {
  color: var(--adm-text-3) !important;
}
.header-left .search_bar .search_icon {
  color: var(--adm-text-3) !important;
}

/* Bootstrap Select */
html [data-theme-version="dark"] .bootstrap-select .dropdown-toggle,
html [data-theme-version="dark"] .bootstrap-select .filter-option,
html [data-theme-version="dark"] .bootstrap-select .filter-option-inner,
html [data-theme-version="dark"] .bootstrap-select .filter-option-inner-inner,
.bootstrap-select,
.bootstrap-select .dropdown-toggle,
.bootstrap-select .filter-option,
.bootstrap-select .filter-option-inner,
.bootstrap-select .filter-option-inner-inner,
.filter-option-inner-inner,
.bootstrap-select .dropdown-toggle:focus {
  color: var(--adm-text) !important;
}
.bootstrap-select .dropdown-toggle {
  background-color: var(--adm-input-bg) !important;
  border-color: var(--adm-border) !important;
}
.all-notification,
a.all-notification {
  color: var(--adm-text) !important;
  font-weight: 600 !important;
}
.all-notification:hover,
a.all-notification:hover {
  color: var(--adm-accent) !important;
}
.bootstrap-select.btn-group .dropdown-toggle.bs-placeholder,
.bootstrap-select.btn-group .dropdown-toggle.bs-placeholder .filter-option,
.bootstrap-select.btn-group .dropdown-toggle.bs-placeholder .filter-option-inner,
.bootstrap-select.btn-group .dropdown-toggle.bs-placeholder .filter-option-inner-inner,
.bootstrap-select .bs-placeholder,
.bootstrap-select .bs-placeholder .filter-option-inner-inner,
.bootstrap-select .filter-option-placeholder {
  color: var(--adm-text-3) !important;
}
.bootstrap-select .dropdown-menu {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
}
.bootstrap-select .dropdown-menu li a {
  color: var(--adm-text) !important;
}
.bootstrap-select .dropdown-menu li a:hover,
.bootstrap-select .dropdown-menu li.selected a {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: var(--adm-accent) !important;
}
.bootstrap-select .bs-actionsbox .btn,
.bootstrap-select .bs-actionsbox button {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
  font-weight: 600 !important;
}
.bootstrap-select .bs-actionsbox .btn:hover,
.bootstrap-select .bs-actionsbox button:hover {
  background-color: var(--adm-btn) !important;
  color: var(--adm-on-accent) !important;
}

/* Modals */
.modal-content {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
}
.modal-header {
  background-color: var(--adm-surface) !important;
  border-bottom: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.modal-body {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
}
.modal-footer {
  background-color: var(--adm-surface-2) !important;
  border-top: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.modal-title {
  color: var(--adm-text) !important;
}
.modal-header .close,
.modal .close {
  color: var(--adm-text) !important;
  text-shadow: none !important;
  opacity: 0.8 !important;
}
.modal-header .close:hover,
.modal .close:hover {
  opacity: 1 !important;
  color: var(--adm-text) !important;
}

/* Neutralise Bootstrap Utilities inside .modal, .card, .dropdown-menu */
.modal .bg-light,
.card .bg-light,
.dropdown-menu .bg-light,
.modal .bg-white,
.card .bg-white,
.dropdown-menu .bg-white {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
}

.modal .border-light,
.card .border-light,
.dropdown-menu .border-light {
  border-color: var(--adm-border) !important;
}

.modal .table-light,
.card .table-light,
.table .thead-light th,
.table-light > th,
.table-light > td {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
}

.modal .text-dark,
.card .text-dark,
.dropdown-menu .text-dark {
  color: var(--adm-text) !important;
}

.modal .text-muted,
.card .text-muted,
.dropdown-menu .text-muted,
.modal .text-secondary,
.card .text-secondary,
.dropdown-menu .text-secondary {
  color: var(--adm-text-2) !important;
}

.modal .text-white:not(.btn):not(.badge):not(.badge *),
.card .text-white:not(.btn):not(.badge):not(.badge *):not([style*="background"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-success"]):not([class*="bg-info"]),
.dropdown-menu .text-white:not(.btn):not(.badge):not(.badge *) {
  color: var(--adm-text) !important;
}

/* Dropdowns */
.dropdown-menu {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
}
.dropdown-item {
  color: var(--adm-text) !important;
}
.dropdown-item:hover,
.dropdown-item:focus,
.dropdown-item.active {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: var(--adm-accent) !important;
}

/* Navigation Tabs & Pills */
.nav-tabs {
  border-bottom: 1px solid var(--adm-border) !important;
}
.nav-tabs .nav-link {
  color: var(--adm-text-2) !important;
  border: 1px solid transparent !important;
}
.nav-tabs .nav-link:hover {
  color: var(--adm-text) !important;
  border-color: transparent !important;
}
.nav-tabs .nav-link.active,
.nav-tabs .nav-item.show .nav-link {
  background-color: transparent !important;
  border: none !important;
  border-bottom: 3px solid var(--adm-accent) !important;
  color: var(--adm-text) !important;
  font-weight: 600 !important;
}
.nav-pills .nav-link {
  color: var(--adm-text-2) !important;
  border-radius: 0.5rem !important;
}
.nav-pills .nav-link:hover {
  color: var(--adm-accent) !important;
}
.nav-pills .nav-link.active,
.nav-pills .show > .nav-link {
  background-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}

/* Pagination */
.pagination .page-item.active .page-link {
  background-color: var(--adm-btn) !important;
  border-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}
.pagination .page-link {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text-2) !important;
  border-color: var(--adm-border) !important;
}
.pagination .page-link:hover {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: var(--adm-accent) !important;
}
.pagination .page-item.disabled .page-link {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text-3) !important;
  border-color: var(--adm-border) !important;
}

/* Alerts */
.alert-primary {
  background-color: rgba(235, 129, 83, 0.15) !important;
  border-color: rgba(235, 129, 83, 0.3) !important;
  color: var(--adm-text) !important;
}
.alert-secondary {
  background-color: rgba(255, 255, 255, 0.08) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.alert-success {
  background-color: rgba(34, 197, 94, 0.15) !important;
  border-color: rgba(34, 197, 94, 0.3) !important;
  color: #86efac !important;
}
.alert-warning {
  background-color: rgba(245, 158, 11, 0.15) !important;
  border-color: rgba(245, 158, 11, 0.3) !important;
  color: #fde047 !important;
}
.alert-danger {
  background-color: rgba(239, 68, 68, 0.15) !important;
  border-color: rgba(239, 68, 68, 0.3) !important;
  color: #fca5a5 !important;
}
.alert-info {
  background-color: rgba(14, 165, 233, 0.15) !important;
  border-color: rgba(14, 165, 233, 0.3) !important;
  color: #7dd3fc !important;
}

/* Badges */
.badge-primary {
  background-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}
.badge-primary.light {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}
.badge-outline-primary {
  background-color: transparent !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-accent) !important;
}
.badge-success {
  background-color: #15803d !important;
  color: #ffffff !important;
}
.badge-success.light {
  background-color: rgba(34, 197, 94, 0.15) !important;
  color: #4ade80 !important;
  border: 1px solid rgba(34, 197, 94, 0.3) !important;
}
.badge-warning,
.badge-warning.text-dark,
.badge-warning * {
  background-color: #f59e0b !important;
  color: #0f172a !important;
  font-weight: 700 !important;
}
.badge-warning.light {
  background-color: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
  border: 1px solid rgba(245, 158, 11, 0.3) !important;
}
.badge-light,
.badge-light.text-dark,
.badge-light * {
  background-color: #e2e8f0 !important;
  color: #0f172a !important;
  font-weight: 700 !important;
}
.badge-secondary {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}
.badge-secondary.light {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}
.badge-danger {
  background-color: #dc2626 !important;
  color: #ffffff !important;
}
.badge-danger.light {
  background-color: rgba(239, 68, 68, 0.15) !important;
  color: #fca5a5 !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
}
.badge-info {
  background-color: #0369a1 !important;
  color: #ffffff !important;
}
.badge-info.light {
  background-color: rgba(14, 165, 233, 0.15) !important;
  color: #7dd3fc !important;
  border: 1px solid rgba(14, 165, 233, 0.3) !important;
}

/* List Groups */
.list-group-item {
  background-color: transparent !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}

/* Typography & Helpers */
.text-primary {
  color: var(--adm-accent-strong, var(--adm-accent)) !important;
}
.text-dark {
  color: var(--adm-text) !important;
}
.text-muted {
  color: var(--adm-text-3) !important;
}
.text-white {
  color: var(--adm-text) !important;
}
.badge-warning,
.badge-warning *,
.badge-warning.text-white,
.badge-warning.text-dark,
.badge-light,
.badge-light *,
.badge-light.text-white,
.badge-light.text-dark {
  color: #0f172a !important;
}

code, pre {
  color: var(--adm-text) !important;
  background-color: rgba(0, 0, 0, 0.35) !important;
  border-radius: 4px;
}
code {
  padding: 0.15rem 0.35rem !important;
}

.custom-file-label {
  background-color: var(--adm-input-bg) !important;
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
}
.custom-file-label::after {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border-left-color: var(--adm-border) !important;
}
h1, h2, h3, h4, h5, h6 {
  color: var(--adm-text) !important;
}
label {
  color: var(--adm-text-2) !important;
}
small {
  color: var(--adm-text-3) !important;
}
.alert small {
  color: inherit !important;
}
.breadcrumb {
  background-color: transparent !important;
}
.breadcrumb-item a {
  color: var(--adm-text-3) !important;
}
.breadcrumb-item.active {
  color: var(--adm-accent) !important;
}
hr {
  border-top-color: var(--adm-border) !important;
}

/* SweetAlert2 Dark Styling */
.swal2-popup {
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}
.swal2-title,
.swal2-html-container {
  color: var(--adm-text) !important;
}

/* Toastr Dark Styling */
#toast-container > div {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important;
}

/* Chartist Dark Grid & Labels */
.ct-grid {
  stroke: var(--adm-border) !important;
  stroke-dasharray: 2px !important;
}
.ct-label {
  fill: var(--adm-text-3) !important;
  color: var(--adm-text-3) !important;
  font-size: 0.75rem !important;
}
.ct-series-a .ct-bar,
.ct-series-a .ct-line,
.ct-series-a .ct-point {
  stroke: var(--adm-accent) !important;
}
.ct-series-a .ct-area {
  fill: var(--adm-accent) !important;
}

/* Footer */
.footer {
  background-color: var(--adm-surface) !important;
  border-top: 1px solid var(--adm-border) !important;
}
.footer .copyright p {
  color: var(--adm-text-3) !important;
}

/* Preloader */
#preloader {
  background-color: var(--adm-bg) !important;
}
#preloader .sk-child {
  background-color: var(--adm-accent) !important;
}

/* Scrollbars */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
::-webkit-scrollbar-track {
  background: var(--adm-bg);
}
::-webkit-scrollbar-thumb {
  background: var(--adm-border);
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: var(--adm-text-3);
}

/* TitNotify Toasts */
.tit-toast-container {
  position: fixed;
  z-index: 1090;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  pointer-events: none;
}

@media (min-width: 768px) {
  .tit-toast-container {
    top: 1.5rem;
    right: 1.5rem;
    width: 360px;
    max-width: calc(100vw - 2rem);
  }
}

@media (max-width: 767px) {
  .tit-toast-container {
    bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
    left: 1rem;
    right: 1rem;
    width: auto;
    max-width: none;
  }
}

.tit-toast {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background-color: var(--adm-surface);
  color: var(--adm-text);
  border: 1px solid var(--adm-border);
  border-left-width: 4px;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
  pointer-events: auto;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.tit-toast--hiding {
  opacity: 0;
  transform: translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .tit-toast,
  .tit-toast--hiding {
    transition: none !important;
    animation: none !important;
  }
}

.tit-toast--success {
  border-left-color: var(--adm-success);
}
.tit-toast--success .tit-toast-icon {
  color: var(--adm-success);
}

.tit-toast--error {
  border-left-color: var(--adm-danger);
}
.tit-toast--error .tit-toast-icon {
  color: var(--adm-danger);
}

.tit-toast--warning {
  border-left-color: var(--adm-warning);
}
.tit-toast--warning .tit-toast-icon {
  color: var(--adm-warning);
}

.tit-toast--info {
  border-left-color: var(--adm-info);
}
.tit-toast--info .tit-toast-icon {
  color: var(--adm-info);
}

.tit-toast-icon-wrap {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-top: 0.125rem;
  font-size: 1.2rem;
}

.tit-toast-content {
  flex: 1;
  min-width: 0;
}

.tit-toast-title {
  font-size: 0.95rem;
  line-height: 1.3;
  color: var(--adm-text);
  margin-bottom: 0.25rem;
}

.tit-toast-message {
  font-size: 0.875rem;
  line-height: 1.4;
  color: var(--adm-text-2);
  word-break: break-word;
}

.tit-toast-close {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  min-height: 44px;
  margin: -0.5rem -0.5rem -0.5rem auto;
  padding: 0;
  background: transparent;
  border: none;
  color: var(--adm-text-3);
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  border-radius: 0.25rem;
  transition: color 0.15s ease;
}

.tit-toast-close:hover,
.tit-toast-close:focus {
  color: var(--adm-text);
  outline: none;
}

/* TitNotify Confirm Dialog */
.tit-confirm-overlay {
  position: fixed;
  inset: 0;
  z-index: 1099;
  background-color: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.tit-confirm-dialog {
  background-color: var(--adm-surface);
  color: var(--adm-text);
  border: 1px solid var(--adm-border);
  border-radius: 0.75rem;
  padding: 1.5rem;
  max-width: 440px;
  width: 100%;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
}

.tit-confirm-title {
  color: var(--adm-text) !important;
  font-weight: 700;
  font-size: 1.15rem;
  margin: 0 0 0.5rem 0;
}

.tit-confirm-body {
  color: var(--adm-text-2) !important;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 1.25rem;
}

.tit-confirm-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.tit-confirm-btn {
  min-height: 44px;
  min-width: 44px;
  padding: 0.625rem 1.25rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 0.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Select2 Dark Theme & States */
.select2-container--default .select2-selection--single,
.select2-container--default .select2-selection--multiple {
  background-color: var(--adm-input-bg) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  border-radius: 0.5rem !important;
  min-height: 42px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  color: var(--adm-text) !important;
  line-height: 40px !important;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: var(--adm-text-2) !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
  border-color: var(--adm-text) transparent transparent transparent !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
  background-color: var(--adm-surface-2) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.select2-dropdown {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
  background-color: var(--adm-input-bg) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  border-radius: 0.25rem !important;
}
.select2-container--default .select2-results__option {
  color: var(--adm-text) !important;
  background-color: transparent !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected],
.select2-container--default .select2-results__option--highlighted {
  background-color: var(--adm-accent) !important;
  color: var(--adm-on-accent, #ffffff) !important;
}
.select2-container--default .select2-results__option[aria-selected="true"] {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-accent) !important;
}
.select2-container--default.select2-container--disabled .select2-selection {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text-3) !important;
  opacity: 0.75 !important;
}

/* DateRangePicker / Datepicker Dark Theme */
.daterangepicker {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
}
.daterangepicker:before,
.daterangepicker:after {
  border-bottom-color: var(--adm-surface) !important;
}
.daterangepicker .calendar-table {
  background-color: var(--adm-surface) !important;
  border-color: var(--adm-border) !important;
}
.daterangepicker td,
.daterangepicker th {
  color: var(--adm-text) !important;
}
.daterangepicker td.available:hover,
.daterangepicker th.available:hover {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-accent) !important;
}
.daterangepicker td.active,
.daterangepicker td.active:hover {
  background-color: var(--adm-accent) !important;
  color: var(--adm-on-accent, #ffffff) !important;
}
.daterangepicker td.off,
.daterangepicker td.off.in-range,
.daterangepicker td.off.start-date,
.daterangepicker td.off.end-date {
  background-color: transparent !important;
  color: var(--adm-text-3) !important;
}
.daterangepicker td.in-range {
  background-color: rgba(99, 102, 241, 0.2) !important;
  color: var(--adm-text) !important;
}
.daterangepicker .drp-buttons {
  background-color: var(--adm-surface-2) !important;
  border-top: 1px solid var(--adm-border) !important;
}
.daterangepicker .drp-selected {
  color: var(--adm-text-2) !important;
}
.daterangepicker select.monthselect,
.daterangepicker select.yearselect {
  background-color: var(--adm-input-bg) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}

/* DataTables */
.dataTables_wrapper {
  color: var(--adm-text) !important;
}
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
  color: var(--adm-text) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
  border-radius: 4px !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background: var(--adm-surface-2) !important;
  color: var(--adm-accent) !important;
  border-color: var(--adm-accent) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
  background: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
  border-color: var(--adm-btn) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
  background: var(--adm-surface-2) !important;
  color: var(--adm-text-3) !important;
  border-color: var(--adm-border) !important;
  opacity: 0.6 !important;
}
table.dataTable thead .sorting:before,
table.dataTable thead .sorting:after,
table.dataTable thead .sorting_asc:before,
table.dataTable thead .sorting_asc:after,
table.dataTable thead .sorting_desc:before,
table.dataTable thead .sorting_desc:after {
  color: var(--adm-text-3) !important;
}

/* Popover & Tooltip */
.popover {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}
.popover-header {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border-bottom: 1px solid var(--adm-border) !important;
}
.popover-body {
  color: var(--adm-text) !important;
}
.bs-popover-auto[x-placement^="bottom"] .arrow:after,
.bs-popover-bottom .arrow:after {
  border-bottom-color: var(--adm-surface-2) !important;
}
.bs-popover-auto[x-placement^="top"] .arrow:after,
.bs-popover-top .arrow:after {
  border-top-color: var(--adm-surface) !important;
}
.tooltip .tooltip-inner {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
}

/* Toastr Re-Skin */
#toast-container > div {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border: 1px solid var(--adm-border) !important;
  border-left-width: 4px !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important;
  opacity: 1 !important;
}
#toast-container > .toast-success {
  border-left-color: var(--adm-success) !important;
}
#toast-container > .toast-error {
  border-left-color: var(--adm-danger) !important;
}
#toast-container > .toast-warning {
  border-left-color: var(--adm-warning) !important;
}
#toast-container > .toast-info {
  border-left-color: var(--adm-info) !important;
}
.toast-title {
  color: var(--adm-text) !important;
  font-weight: 700 !important;
}
.toast-message {
  color: var(--adm-text-2) !important;
}
.toast-close-button {
  color: var(--adm-text-2) !important;
  text-shadow: none !important;
  min-width: 32px;
  min-height: 32px;
}

/* Mobile Modals <= 576px */
@media (max-width: 576px) {
  .modal-dialog {
    margin: 16px !important;
    max-width: calc(100% - 32px) !important;
    width: calc(100% - 32px) !important;
    overflow-x: hidden !important;
  }
  .modal-content {
    width: 100% !important;
    overflow-x: hidden !important;
  }
  .modal-footer {
    display: flex !important;
    flex-direction: column-reverse !important;
    gap: 8px !important;
    padding: 12px 16px !important;
  }
  .modal-footer > * {
    margin: 0 !important;
    width: 100% !important;
    min-height: 44px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
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
