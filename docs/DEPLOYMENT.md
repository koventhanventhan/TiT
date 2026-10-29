# Production Deployment Guide: TiT Jaffna (`titjaffna.lk`)

This document outlines critical deployment procedures, `.htaccess` requirements, and configuration rules for the production cPanel host.

---

## 1. Production `.htaccess` Configuration

The root `.htaccess` on the live server (`/home/<user>/public_html/.htaccess`) is **maintained by hand** on the host. Git deployments and deployment scripts must **NOT** overwrite or delete it.

### Required Rules in the Live `.htaccess`:
1. **Force HTTPS & Canonical Non-WWW Redirects**: Ensures all traffic is served securely over HTTPS and `www.titjaffna.lk` redirects to `titjaffna.lk`.
2. **Real Files & Folders Bypass**: Existing physical files and directories (including `laravel_api/backend/public/...`, static assets, images) pass through directly without hitting rewrite rules.
3. **Backend & Admin Routes**:
   - `/admin` (Admin Blade dashboard)
   - `/super-panel` (Super Admin Filament panel)
   - `/livewire` (Livewire backend components)
   - `/api` (Direct API calls)
   - `/sitemap.xml` (Dynamically generated sitemap)
4. **SPA (React Frontend) Fallback**: Any non-file/non-directory route is routed to `index.html` so client-side React routing handles student/teacher dashboards and public web pages.

### Exact Recommended Live `.htaccess`

```apache
# =====================================================================
# TiT Jaffna Production Root .htaccess
# Location on server: /home/<user>/public_html/.htaccess
# Do NOT overwrite via automated git pull / deploy script.
# =====================================================================

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # 1. Canonical Redirect: www to non-www
    RewriteCond %{HTTP_HOST} ^www\.titjaffna\.lk$ [NC]
    RewriteRule ^(.*)$ https://titjaffna.lk%{REQUEST_URI} [L,R=301]

    # 2. Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # 3. Pass through real existing files and directories
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # 4. Route Laravel Backend routes to public/index.php
    # Matches: /admin, /api, /super-panel, /livewire, /receipts, /sitemap.xml
    # Public routes like /receipts serve signed PDF receipt downloads and must route directly to Laravel rather than the React SPA.
    RewriteCond %{REQUEST_URI} ^/(admin|api|super-panel|livewire|receipts|sitemap\.xml) [NC]
    RewriteRule ^(.*)$ laravel_api/backend/public/index.php [QSA,L]

    # 5. SPA Fallback: Route all other web routes to React index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>

# php -- BEGIN cPanel-generated handler, do not edit
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php84___lsphp .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
```

---

## 2. API Endpoint Architecture

The built React production frontend communicates with the API at:
```
https://titjaffna.lk/laravel_api/backend/public/api/...
```
Because `laravel_api/backend/public/` is an actual directory on disk, Apache evaluates the `-d` condition in root `.htaccess` and hands off requests to `laravel_api/backend/public/.htaccess`.

> **CRITICAL**: Never add redirects targeting `laravel_api/backend/public/(api|admin|...)` in the root `.htaccess`. If triggered, they would break production API calls made by the built frontend bundle.

---

## 3. Scheduled Tasks & Cron Configuration (`proc_open` disabled)

On standard cPanel shared hosting, PHP `proc_open` is disabled for security reasons.

### Scheduler Implementation:
All tasks in `laravel_api/backend/routes/console.php` must use `Schedule::call(...)` with `Artisan::call(...)` and the `$guard` closure instead of `Schedule::command(...)`.

```php
// In-process execution without proc_open:
Schedule::call($guard('rag:prune-queries', fn() => Artisan::call('rag:prune-queries')))->dailyAt('03:00');
Schedule::call($guard('rag:cleanup-stuck-queries', fn() => Artisan::call('rag:cleanup-stuck-queries')))->everyFiveMinutes();
Schedule::call($guard('rag:cleanup-stuck-materials', fn() => Artisan::call('rag:cleanup-stuck-materials')))->everyTenMinutes();
```

### cPanel Cron Setup:
Run the scheduler once every minute:
```bash
* * * * * /usr/local/bin/php /home/<user>/public_html/laravel_api/backend/artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Admin Index Number Management Commands

When configuring index number series or format from the cPanel terminal (SSH or Terminal UI):

1. **Configure Format & Prefix**:
   ```bash
   php artisan institutes:index-config 1 --format=grade --prefix=TIT --start=1
   ```
2. **Update Next Automatic Running Number**:
   ```bash
   php artisan students:index-counter <series> <next_number> 1
   # Example: Set series 11 next automatic number to 550
   php artisan students:index-counter 11 550 1
   ```
3. **Verify Index Status**:
   ```bash
   php artisan students:index-status
   ```

---

## 5. Frontend Bundle Build & Publishing Workflow

When making any frontend changes in `laravel_api/src/`:

1. **Build the bundle inside `laravel_api/`**:
   ```bash
   cd laravel_api
   npm run build
   cd ..
   ```
   This compiles assets into `laravel_api/dist/assets/` and `laravel_api/dist/index.html`.

2. **Publish and verify bundle using `scripts/publish_bundle.cjs`**:
   ```bash
   node scripts/publish_bundle.cjs
   ```
   **What `publish_bundle.cjs` does:**
   - Detects all newly generated bundle assets in `laravel_api/dist/assets`.
   - Cleans up stale hashed files in root `assets/` that were replaced by the new build.
   - Synchronizes dist assets into root `assets/` and copies `laravel_api/dist/index.html` to root `index.html`.
   - **Recursive Asset Walker**: Validates that all static and dynamic JavaScript and CSS import references starting from root `index.html` exist on disk (verifying "0 missing").
   - **Multi-Hash Verification**: Ensures no two conflicting hashes exist for the same chunk prefix in `assets/`.
   - Guarantees `laravel_api/index.html` and `laravel_api/assets` are never tracked/dirtied in git, leaving only root `index.html` and root `assets/` published.

---

## 6. Payment Security & PayHere Environment Variables

### `PAYHERE_TRUST_CLIENT_CALLBACK` (Default: `false`)
- **Production Setting**: Must be unset or `false`.
- **Purpose**: Previously, the frontend callback (`POST /api/register/payment-success`) directly set payments to `paid` upon client browser redirection. This created a security vulnerability where clients could forge payment confirmation.
- **Secure Flow**: Online payments are only marked `paid` and unlocked via verified PayHere server webhooks (`POST /api/payhere/notify` with MD5 HMAC verification) or admin actions. The `payment-success` endpoint checks caller ownership and returns HTTP `202 { status: 'pending' }` while the frontend polls until the server webhook confirms it.
- **Local/Sandbox Testing Only**: Set `PAYHERE_TRUST_CLIENT_CALLBACK=true` in `.env` only during offline local debugging if server-to-server webhook callbacks cannot reach localhost. Never enable this in production.
