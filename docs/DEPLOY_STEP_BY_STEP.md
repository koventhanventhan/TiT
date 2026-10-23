# Complete Production Deployment Guide (Step-by-Step)
**Target Audience:** Non-expert System Administrators & DevOps Engineers  
**System:** TiT Learning Academy - RAG Study Assistant & Unified Public Assistant  
**Release:** Rounds 1–5 Integrated Production Release (Strict Ordering & Verification)

---

## 1. Overview & Architecture Summary

This release deploys the **RAG Study Assistant**, **Unified Public Assistant**, **Teacher/Admin Material Management**, and **Hotfix Controller Whitelisting**.

### Committed-Bundle & Hosting Architecture
1. **Live Production Server (cPanel / Apache / MariaDB):**
   - The production server serves compiled frontend assets directly from the repository root:
     - Entrypoint: `index.html`
     - Compiled Assets: `assets/*` (JS/CSS bundles)
     - Backend API: `laravel_api/backend/`
   - You do **not** need Node.js or `npm run build` on the live cPanel server; the production bundle is committed in the repository.
2. **Vercel Hosting Notice:**
   - **Pushing to `main` on GitHub may trigger an automatic Vercel build** if your repository is connected to Vercel.
   - **What Vercel deploys:** Vercel builds and hosts only the static Single Page Application (SPA) frontend.
   - **Backend Dependency:** The Vercel frontend routes all API calls to your live cPanel/MariaDB backend (`VITE_API_URL`).
   - **Safe Decoupling:** Even if Vercel deploys the new frontend before your cPanel backend migrations are executed, **the fail-safe feature flags ensure the Study Assistant and Public Widget remain completely hidden** until the backend explicitly returns `rag_enabled: true` and `public_assistant_enabled: true`. However, to avoid visual desynchronization, always execute database migrations before routing live traffic.

---

## 2. STRICT DEPLOYMENT EXECUTION ORDER

To prevent downtime, service interruption, or broken student requests, execute the deployment in this **exact order**:

```
[1. Full Backups (DB + Files)]
          ↓
[2. Deploy 2 Hotfix Controllers (Zoom + Assignments)]
          ↓
[3. Run migrate:status & Review Pending Migrations]
          ↓
[4. Upload New Backend Files & Update .env]
          ↓
[5. Run php artisan migrate --force]
          ↓
[6. Verify OLD Frontend Still Works with NEW Backend]
          ↓
[7. Publish New Frontend Bundle (index.html + assets/)]
          ↓
[8. Canary Rollout: Enable ONE Pilot Institute]
```

---

## Step 1: Full Backups

Always create full backups before modifying files or running migrations.

### 1.1: Database Backup
Run `mysqldump` from your server shell:
```bash
# Replace [db_user] and [db_name] with your production credentials
mysqldump -u [db_user] -p --single-transaction --quick --lock-tables=false [db_name] > /backups/db_backup_pre_rag_$(date +%F_%H%M%S).sql
```
*Verify:* Check that the dump file exists and has size > 0 (`ls -lh /backups/`).

### 1.2: Environment & Asset Backup
```bash
mkdir -p /backups/app_pre_rag
cp laravel_api/backend/.env /backups/app_pre_rag/.env.backup
tar -czvf /backups/app_pre_rag/assets_backup.tar.gz index.html assets/
```

---

## Step 2: Deploy the Two Hotfix Controllers First

Deploy the two field-whitelisting hotfix controllers before anything else so active student zoom join requests and assignment submissions remain completely stable:
1. `laravel_api/backend/app/Http/Controllers/Api/StudentZoomController.php`
2. `laravel_api/backend/app/Http/Controllers/Api/StudentAssignmentController.php`

*(Source: branch `hotfix/zoom-link-leak`, commit `75eca66`)*

*Why:* These controllers whitelist incoming client fields, preventing validation failures on active student requests.

---

## Step 3: Run `migrate:status` and Review Pending Migrations

> [!CRITICAL]
> Running `php artisan migrate --force` executes **ALL pending migrations in the `database/migrations` directory**, NOT only the RAG migrations!

Before running any migration, connect to the server and inspect the migration status:
```bash
cd laravel_api/backend
php artisan migrate:status
```
- Review the list of pending migrations (marked `[No]` or `Pending`).
- Ensure no unexpected migrations from unrelated feature branches are present in the migrations folder.
- The 12 migrations expected in this release are:
  1. `2026_10_12_010000_add_subject_to_learning_materials_table`
  2. `2026_10_12_020000_create_material_chunks_table`
  3. `2026_10_12_030000_create_student_ai_queries_and_feedback_tables`
  4. `2026_10_12_040000_add_rag_status_to_learning_materials_table`
  5. `2026_10_12_050000_add_rag_enabled_and_distress_flags`
  6. `2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr`
  7. `2026_10_15_030000_add_file_hash_to_learning_materials_table`
  8. `2026_10_21_040000_create_material_pages_table`
  9. `2026_10_22_010000_add_public_assistant_enabled_to_institutes_table`
  10. `2026_10_22_020000_create_faqs_table`
  11. `2026_10_22_030000_create_assistant_visitor_logs_table`
  12. `2026_10_22_040000_widen_learning_materials_medium_enum`

---

## Step 4: Upload New Backend Application Files

Upload the backend code to `laravel_api/backend/`:
- `app/`
- `bootstrap/app.php` (includes env-driven `TRUSTED_PROXIES`)
- `config/`
- `database/migrations/`
- `routes/`

### Configure Environment Variables (`.env`)
Add these verified settings to `laravel_api/backend/.env` (do not share credentials):
```dotenv
# Gemini API Key (never share or commit)
GEMINI_API_KEY=your_gemini_api_key_here

# Proxy Trust: Set comma-separated IPs or leave empty if directly on cPanel
TRUSTED_PROXIES=

# Distress Helplines (Verify verified crisis contacts; leave numbers empty if unset)
RAG_HELPLINE_PRIMARY_NAME="National Mental Health Helpline"
RAG_HELPLINE_PRIMARY_NUMBER="1926"
RAG_HELPLINE_PRIMARY_HOURS="24/7"
RAG_HELPLINE_SECONDARY_NAME="CCCline"
RAG_HELPLINE_SECONDARY_NUMBER="1333"
RAG_HELPLINE_SECONDARY_HOURS="24/7"
RAG_HELPLINE_INSTITUTE_NAME="Institute / Teacher Support"
RAG_HELPLINE_INSTITUTE_NUMBER=""
```

### Install Backend Dependencies
```bash
cd laravel_api/backend
composer install --no-dev --optimize-autoloader --no-interaction
```

---

## Step 5: Run Database Migrations

```bash
cd laravel_api/backend
php artisan migrate --force
```
All tables and columns are safely created. `learning_materials.medium` is widened to `enum('english','tamil','both','sinhala','all')` via an `INPLACE` MySQL/MariaDB metadata operation. Note that while this does not rebuild table data rows, it typically takes ~0.15s–0.25s (around 0.2s) due to MariaDB acquiring an exclusive metadata lock and updating the table definition cache.

---

## Step 6: Verify OLD Frontend Still Works with NEW Backend (Checklist)

Before publishing the new frontend bundle, perform this verification using the **existing live frontend** in your browser:

### What to Click & Verify on OLD Frontend:
- [ ] **Student Login (`/login`):** Authenticate with an existing student account.
- [ ] **Student Dashboard (`/student/dashboard`):** Dashboard loads without errors or blank screens.
- [ ] **Live Zoom / Timetable (`/student/classes`):** Verify join buttons and meeting details render correctly.
- [ ] **Student Materials (`/student/materials`):** View list of existing notes in English/Tamil/Both and verify file downloads.
- [ ] **Student Assignments (`/student/assignments`):** View active assignments and verify submission modal opens.
- [ ] **Student Payments (`/student/payments`):** Verify payment history table and receipts display correctly.
- [ ] **Teacher Materials (`/teacher/materials`):** Log in as teacher, verify materials list and upload modal.
- [ ] **Admin Student List (`/admin/students`):** Verify student approvals and grade promotion workflows.
- [ ] **Browser Console:** Ensure zero 500 API responses and zero uncaught JavaScript exceptions.

---

## Step 7: Publish New Frontend Bundle

Once Step 6 passes, publish the compiled frontend bundle:
1. Upload `index.html` to the web root.
2. Upload `assets/*` directory to `assets/` in the web root.

### "New Frontend with Old Backend" Fail-Safe Checklist
If the new frontend bundle is deployed before the backend is upgraded, or during an active migration window:
- [ ] **Study Assistant Hidden:** The "Study Assistant" sidebar navigation link in `StudentDashboardLayout` is **completely hidden** because `user.rag_enabled !== true`.
- [ ] **Direct URL Safe:** Navigating directly to `/student/study-assistant` shows a clean "Feature Not Available" notice or redirects safely.
- [ ] **Public Widget Hidden:** The circular assistant widget at the bottom-right of public pages is **completely hidden** because `public_assistant_enabled !== true`.
- [ ] **Zero Console Crashes:** Missing backend endpoints (returning 404 or 500) fail silently without throwing modal popups or blocking navigation.

---

## Step 8: Canary Rollout: Enable ONE Pilot Institute

By default, RAG and the Public Assistant are **globally disabled** for all institutes. Enable them for a single pilot institute:

```bash
cd laravel_api/backend

# 1. Turn on public assistant for Institute 1:
php artisan rag:public 1 on

# 2. Turn on Study Assistant for Institute 1:
php artisan rag:institute 1 on

# 3. Clear application caches:
php artisan config:cache
php artisan route:cache
```

---

## 3. PROXY TRUST & REVERSE PROXY CONFIGURATION

In `laravel_api/backend/bootstrap/app.php`, proxy trust is driven by the `TRUSTED_PROXIES` environment variable:
```php
$trustedProxiesEnv = env('TRUSTED_PROXIES');
$trustedProxies = !empty($trustedProxiesEnv)
    ? array_map('trim', explode(',', $trustedProxiesEnv))
    : [];

$middleware->trustProxies(at: $trustedProxies);
```

### How to Find the Right Value for Your Environment:
1. **cPanel / Direct Apache / Nginx (No Reverse Proxy):**
   - Leave `TRUSTED_PROXIES=` empty.
   - When empty, Laravel uses `$_SERVER['REMOTE_ADDR']` directly and ignores `X-Forwarded-For`. This prevents malicious clients from spoofing their IP to bypass rate limits.
2. **Cloudflare:**
   - Cloudflare publishes its official IP ranges: [https://www.cloudflare.com/ips/](https://www.cloudflare.com/ips/)
   - Set `TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22`
3. **Vercel / Reverse Proxy Load Balancers:**
   - Add the specific CIDR or upstream IP of the proxy load balancer. If the proxy runs on local loopback, use `127.0.0.1`.

---

## 4. PUBLIC WIDGET & RECAPTCHA V3 GUIDE

### 4.1: Script Loading and Token Submission
1. **Script Loading:**
   - The script is loaded in `laravel_api/src/components/UnifiedAssistantWidget.jsx`.
   - On component mount, if `VITE_RECAPTCHA_SITE_KEY` is configured and not yet present in `document.head`, it dynamically injects:
     ```html
     <script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY" async></script>
     ```
2. **When it Requests a Token:**
   - Tokens are **never requested on page load** (which wastes tokens and lowers score).
   - A token is requested **on-demand only when an unauthenticated visitor clicks Send / submits a question**.
   - It executes:
     ```js
     window.grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'assistant_chat' })
     ```
3. **Token Transmission:**
   - The token is sent in the JSON body: `{ "question": "...", "recaptcha_token": "<token>" }` via POST to `/api/assistant/chat`.

### 4.2: Local Testing Steps for reCAPTCHA
1. In the [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin) (v3):
   - Add `localhost` and `127.0.0.1` to **Domains**.
   - Copy **Site Key** into `laravel_api/.env` as:
     ```dotenv
     VITE_RECAPTCHA_SITE_KEY=6LeIx0...
     ```
   - Copy **Secret Key** into `laravel_api/backend/.env` as:
     ```dotenv
     RECAPTCHA_SECRET_KEY=6LeIx0...
     ```
2. **What to Expect During Testing:**
   - **Token Missing (or Bot Submissions):** Returns HTTP `422 Unprocessable Entity`:
     ```json
     {
       "message": "reCAPTCHA verification is required for visitor queries.",
       "errors": { "recaptcha": ["Verification token missing."] }
     }
     ```
   - **Low Score (< 0.5):** Returns HTTP `422 Unprocessable Entity`:
     ```json
     {
       "message": "Bot verification failed. Please refresh and try again.",
       "errors": { "recaptcha": ["Verification failed."] }
     }
     ```
   - **Local Development Bypass vs Production Fail-Closed:**
     - In local development (`APP_ENV=local` or `testing`), if `RECAPTCHA_SECRET_KEY` is left empty in `laravel_api/backend/.env`, backend verification is bypassed for easy testing.
     - In production and staging (`APP_ENV != 'local' && APP_ENV != 'testing'`), leaving `RECAPTCHA_SECRET_KEY` empty will cause public assistant endpoints to **fail-closed** with HTTP `503 Service Unavailable` (`public_assistant_enabled: false`) to safeguard against bot spam and abuse.

---

## 5. LOCAL VERIFICATION & SMOKE TEST STEPS

### 5.1: Testing the Public Widget
1. Run migrations: `php artisan migrate`
2. Enable public assistant for Institute 1:
   ```bash
   php artisan rag:public 1 on
   ```
3. Configure public contact (5 dedicated keys) and FAQs:
   ```bash
   php artisan tinker --execute="
   \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_phone'], ['value' => '0212223344', 'group' => 'assistant_public_contact']);
   \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_whatsapp'], ['value' => '0771234567', 'group' => 'assistant_public_contact']);
   \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_email'], ['value' => 'info@tit.edu', 'group' => 'assistant_public_contact']);
   \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_address'], ['value' => 'Jaffna, Sri Lanka', 'group' => 'assistant_public_contact']);
   \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_hours'], ['value' => 'Monday - Saturday 8:00 AM - 5:00 PM', 'group' => 'assistant_public_contact']);
   \App\Models\Faq::updateOrCreate(['institute_id' => 1, 'question' => 'How do I register?'], ['answer' => 'Visit /register to enroll in courses.', 'is_published' => true]);
   "
   ```
4. Open an Incognito / Private browser window.
5. Visit **Exact URL:** `http://localhost:4000/` (or `http://localhost/`).
6. Click the floating circular widget at the bottom right.
7. Ask: *"How do I register?"* -> Verify instant answer from FAQs.
8. Rate Limit Test: Submit 11 rapid messages -> Verify 429 "Too Many Requests" rate limit kicks in after 10 requests per minute.

### 5.2: Testing the Study Assistant
1. Ensure Institute 1 and existing test student are enabled:
   ```bash
   php artisan rag:institute 1 on
   php artisan tinker --execute=\"
   \App\Models\User::where('email', 'test_rag_student_g8@tit.edu')->update(['rag_enabled' => true, 'role' => 'user', 'admin_confirmed_at' => now()]);
   \"
   ```
2. Log in with existing test student:
   - Email: `test_rag_student_g8@tit.edu`
   - Password: `password`
3. Visit **Exact URL:** `http://localhost:4000/student/study-assistant` (or `http://localhost/student/study-assistant`).
4. Ask a syllabus question:
   - Verify word-by-word streaming.
   - Verify numbered citation chips ([1], [2]) appear under the answer.
   - Verify Thumbs Up / Thumbs Down feedback buttons log successfully.
