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

  /* Admin Dark Console Tokens */
  --adm-bg: ${p.adm_bg || '#2c254a'};
  --adm-surface: ${p.adm_surface || '#3b3363'};
  --adm-sidebar: ${p.adm_sidebar || '#3b3363'};
  --adm-border: ${p.adm_border || '#473f72'};
  --adm-text: ${p.adm_text || '#f1f5f9'};
  --adm-text-2: ${p.adm_text_2 || '#cbd5e1'};
  --adm-text-3: ${p.adm_text_3 || '#a8b3c7'};
  --adm-accent: ${p.adm_accent || '#EB8153'};
  --adm-btn: ${p.adm_btn || '#EB8153'};
  --adm-btn-text: ${p.adm_btn_text || '#0f172a'};
  --adm-input-bg: ${p.adm_input_bg || '#241e3d'};

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
.btn-primary:active {
  filter: brightness(0.92);
  color: var(--adm-btn-text, #ffffff) !important;
}
.btn-outline-primary {
  color: var(--adm-text) !important;
  border-color: var(--adm-accent) !important;
  background-color: transparent !important;
}
.btn-outline-primary:hover {
  background-color: var(--adm-btn) !important;
  color: var(--adm-btn-text, #ffffff) !important;
}
.btn-outline-secondary,
.btn-outline-light {
  color: var(--adm-text) !important;
  border-color: var(--adm-border) !important;
  background-color: transparent !important;
}
.btn-outline-secondary:hover,
.btn-outline-light:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  color: #ffffff !important;
}
.btn-outline-info {
  color: #38bdf8 !important;
  border-color: #38bdf8 !important;
  background-color: transparent !important;
}
.btn-outline-info:hover {
  background-color: #0284c7 !important;
  color: #ffffff !important;
}
.btn-outline-success {
  color: #4ade80 !important;
  border-color: #4ade80 !important;
  background-color: transparent !important;
}
.btn-outline-success:hover {
  background-color: #16a34a !important;
  color: #ffffff !important;
}
.btn-outline-warning {
  color: #fbbf24 !important;
  border-color: #fbbf24 !important;
  background-color: transparent !important;
}
.btn-outline-warning:hover {
  background-color: #f59e0b !important;
  color: #0f172a !important;
}
.btn-outline-danger {
  color: #f87171 !important;
  border-color: #f87171 !important;
  background-color: transparent !important;
}
.btn-outline-danger:hover {
  background-color: #ef4444 !important;
  color: #ffffff !important;
}
.btn-secondary {
  background-color: rgba(255, 255, 255, 0.1) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-secondary:hover {
  background-color: rgba(255, 255, 255, 0.18) !important;
  color: #ffffff !important;
}
.btn-info {
  background-color: #0369a1 !important;
  border-color: #0369a1 !important;
  color: #ffffff !important;
}
.btn-info:hover {
  background-color: #0284c7 !important;
  color: #ffffff !important;
}
.btn-warning {
  background-color: #f59e0b !important;
  border-color: #f59e0b !important;
  color: #0f172a !important;
  font-weight: 600 !important;
}
.btn-warning:hover {
  background-color: #d97706 !important;
  color: #0f172a !important;
}
.btn-success {
  background-color: #15803d !important;
  border-color: #15803d !important;
  color: #ffffff !important;
}
.btn-success:hover {
  background-color: #16a34a !important;
  color: #ffffff !important;
}
.btn-danger {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
  color: #ffffff !important;
}
.btn-danger:hover {
  background-color: #ef4444 !important;
  color: #ffffff !important;
}
.btn-light,
.btn-white {
  background-color: var(--adm-surface) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
}
.btn-light:hover,
.btn-white:hover {
  background-color: var(--adm-input-bg) !important;
  color: var(--adm-text) !important;
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
.form-control::placeholder,
input::placeholder,
textarea::placeholder {
  color: var(--adm-text-3) !important;
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
.bootstrap-select .dropdown-toggle,
.bootstrap-select .filter-option,
.bootstrap-select .filter-option-inner,
.bootstrap-select .filter-option-inner-inner,
.bootstrap-select .dropdown-toggle:focus {
  background-color: var(--adm-input-bg) !important;
  border-color: var(--adm-border) !important;
  color: var(--adm-text) !important;
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

/* Modals */
.modal-content {
  background-color: var(--adm-surface) !important;
  border: 1px solid var(--adm-border) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
}
.modal-header,
.modal-footer {
  border-color: var(--adm-border) !important;
}
.modal-title,
.modal-header .close {
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
.dropdown-item:focus {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: var(--adm-accent) !important;
}

/* Navigation Tabs */
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
.badge-danger {
  background-color: #dc2626 !important;
  color: #ffffff !important;
}
.badge-danger.light {
  background-color: rgba(239, 68, 68, 0.15) !important;
  color: #f87171 !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
}
.badge-info {
  background-color: #0369a1 !important;
  color: #ffffff !important;
}
.badge-info.light {
  background-color: rgba(14, 165, 233, 0.15) !important;
  color: #38bdf8 !important;
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
  color: var(--adm-accent) !important;
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
  background-color: var(--adm-surface) !important;
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
