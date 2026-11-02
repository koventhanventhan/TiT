# Local Development & Preview Guide

This document describes how to run and preview the TiT frontend and backend locally with unified proxy configuration for development (`npm run dev`) and production preview (`npm run preview`).

---

## Architecture & Port Mapping

- **Backend (Laravel API):** `http://127.0.0.1:8000`
- **Frontend Dev Server (`vite dev`):** `http://localhost:4000`
- **Frontend Preview Server (`vite preview`):** `http://localhost:4173`
- **Database / Web Server:** XAMPP (Apache + MySQL / MariaDB on standard ports)

### Proxied Endpoints
Both `server.proxy` and `preview.proxy` in `laravel_api/vite.config.mjs` route the following endpoints directly to the backend target:
- `/api` → `http://127.0.0.1:8000/api`
- `/storage` → `http://127.0.0.1:8000/storage`
- `/receipts` → `http://127.0.0.1:8000/receipts`
- `/sitemap.xml` → `http://127.0.0.1:8000/sitemap.xml`
- `/robots.txt` → `http://127.0.0.1:8000/robots.txt`

The proxy target can be overridden via the environment variable `VITE_DEV_API_TARGET` (e.g. `VITE_DEV_API_TARGET=http://127.0.0.1:8000`).

---

## 1. Windows (PowerShell) Workflow

### Step 1: Start XAMPP Services
Open the XAMPP Control Panel and start:
- **Apache**
- **MySQL**

### Step 2: Start Laravel Backend Server
In a PowerShell terminal:
```powershell
# Navigate to backend directory
cd laravel_api\backend

# Ensure dependencies are installed and database is ready
php artisan serve --port=8000
```
Backend will be listening on `http://127.0.0.1:8000`.

### Step 3: Run Frontend

#### Option A: Development Mode (Hot-Reloading on :4000)
In a second PowerShell terminal:
```powershell
cd laravel_api
npm run dev
```
Open `http://localhost:4000` in your browser.

#### Option B: Production Build & Local Preview (on :4173)
To verify the exact production bundle served with Vite preview:
```powershell
cd laravel_api

# 1. Build the production bundle
npm run build

# 2. Preview the production build with full API proxy
npm run preview
```
Open `http://localhost:4173` in your browser. All `/api/settings` and data requests are proxied seamlessly to `http://127.0.0.1:8000`.

#### Option C: Publish Bundle to Root Assets (for Deployment / Tests)
From the repository root:
```powershell
node scripts/publish_bundle.cjs
```
This runs `npm run lint:css-vars`, copies dist assets to root `assets/`, updates root `index.html`, and verifies asset walker integrity (0 missing, no multi-hash names).

---

## 2. Linux / macOS (Bash) Workflow

### Step 1: Start MySQL / Web Services
Ensure MariaDB/MySQL is running:
```bash
sudo systemctl start mariadb # or mysql
```

### Step 2: Start Laravel Backend Server
```bash
cd laravel_api/backend
php artisan serve --port=8000
```

### Step 3: Run Frontend

#### Option A: Development Mode
```bash
cd laravel_api
npm run dev
```

#### Option B: Production Build & Local Preview
```bash
cd laravel_api
npm run build
npm run preview
```

#### Option C: Publish Bundle
```bash
node scripts/publish_bundle.cjs
```

---

## Linting & Validation

To verify all CSS custom properties and ensure no circular dependencies or undefined variables exist:
```bash
# In laravel_api:
npm run lint:css-vars

# In root:
npm run lint:css-vars
```
Both exit code 0 when all custom properties are valid, acyclic, and defined.
