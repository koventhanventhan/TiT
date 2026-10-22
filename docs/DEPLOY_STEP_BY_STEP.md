# Complete Production Deployment Guide (Step-by-Step)
**Target Audience:** Non-expert System Administrators & DevOps Engineers  
**System:** TiT Learning Academy - RAG Study Assistant & Unified Public Assistant  
**Release:** Rounds 1–4 Integrated Production Release  

---

## 1. Overview & Architecture Summary

This release deploys the **RAG Study Assistant**, **Unified Public Assistant**, and **Teacher/Admin Material Management** features.

### Important: Committed-Bundle Workflow
The live production server serves compiled frontend assets **directly from the repository root**:
- Root Entrypoint: `index.html`
- Root Bundle Assets: `assets/*` (JavaScript, CSS, and dynamic chunks)
- Backend API: `laravel_api/backend/`

You do **not** need to install Node.js or run `npm run build` on the live production server. The production bundle has already been built, tested, and committed to git.

---

## 2. Pre-Deployment: Full Backups

Always create full backups before changing code or running database migrations.

### Step 2.1: Database Backup
Run `mysqldump` from the server terminal:
```bash
# Replace [db_user] and [db_name] with your production database credentials
mysqldump -u [db_user] -p --single-transaction --quick --lock-tables=false [db_name] > /backups/db_backup_pre_rag_$(date +%F_%H%M%S).sql
```
*Verify the backup:* Ensure the file size is greater than 0 (`ls -lh /backups/`).

### Step 2.2: Filesystem & Environment Backup
Backup your existing `.env` file, storage folder, and root assets:
```bash
# In your web application root (e.g., /var/www/tit/ or c:\xampp\htdocs\)
mkdir -p /backups/app_backup_pre_rag
cp laravel_api/backend/.env /backups/app_backup_pre_rag/.env.backup
tar -czvf /backups/app_backup_pre_rag/storage_app.tar.gz laravel_api/backend/storage/app/
tar -czvf /backups/app_backup_pre_rag/assets_backup.tar.gz index.html assets/
```

---

## 3. Code Upload & File Deployment Order

When deploying via Git or SFTP/rsync, follow this exact sequence:

### Option A: Deployment via Git (Recommended)
1. **Pull the release branch:**
   ```bash
   git fetch origin
   git checkout merge/rag-full-integration
   # OR fast-forward merge into main if you have merged the branch
   ```
2. **Never overwrite server `.env`:** The `.gitignore` prevents `.env` from being touched, but verify your `laravel_api/backend/.env` remains intact.

### Option B: Deployment via SFTP / rsync (Direct File Upload)
If uploading files manually via SFTP or rsync, upload in this order:
1. **Root Frontend Assets:**
   - Upload `index.html` to root.
   - Upload `assets/` directory to root.
2. **Backend Application Files:**
   - Upload `laravel_api/backend/app/`
   - Upload `laravel_api/backend/routes/`
   - Upload `laravel_api/backend/database/migrations/`
   - Upload `laravel_api/backend/config/`
3. **DO NOT OVERWRITE:**
   - Never overwrite `laravel_api/backend/.env`
   - Never overwrite `laravel_api/backend/storage/`

---

## 4. Backend Dependencies: Composer

### If Composer is installed on the server:
Navigate to the backend directory and run:
```bash
cd laravel_api/backend
composer install --no-dev --optimize-autoloader --no-interaction
```

### If Composer is NOT available on the server:
1. On your local deployment machine, run:
   ```bash
   cd laravel_api/backend
   composer install --no-dev --optimize-autoloader
   ```
2. Compress the generated `vendor/` folder into a zip/tar:
   ```bash
   tar -czvf vendor.tar.gz vendor/
   ```
3. Upload `vendor.tar.gz` to the server under `laravel_api/backend/` and extract it:
   ```bash
   tar -xzvf vendor.tar.gz
   ```

---

## 5. Environment Variables (`.env`) Configuration

Open `laravel_api/backend/.env` and add or verify the following configuration keys:

```dotenv
# ==============================================================================
# Google Gemini API & RAG Settings
# ==============================================================================
# Required: Your Google Gemini API Key for embeddings and generative responses
GEMINI_API_KEY=your_actual_gemini_api_key_here

# Embedding model (text-embedding-004 produces 768-dimensional vectors)
GEMINI_EMBEDDING_MODEL=text-embedding-004

# Generative model for generating answers
GEMINI_GENERATE_MODEL=gemini-1.5-flash

# Daily query limit per student (default 30)
RAG_DAILY_QUERY_LIMIT=30

# Similarity threshold for matching chunks (0.70 strict cutoff)
RAG_SIMILARITY_THRESHOLD=0.70

# Safety & Distress Resources (1926 National Helpline in Sri Lanka)
RAG_DISTRESS_KEYWORDS_ENABLED=true

# Queue configuration (ensure QUEUE_CONNECTION is database, redis, or sync)
QUEUE_CONNECTION=database
RAG_QUEUE_NAME=rag
```

> **Security Note:** Never commit your real `GEMINI_API_KEY` to Git or share it in support tickets.

---

## 6. Database Migrations

Run database migrations using the force flag:
```bash
cd laravel_api/backend
php artisan migrate --force
```

### New Migrations Applied (In Order):
All 11 migrations are **fully guarded** with `Schema::hasTable` and `Schema::hasColumn` checks, making them completely safe and idempotent (they will never fail or duplicate if already run):

1. `2026_10_12_010000_add_subject_to_learning_materials_table.php`  
   *Risk: None.* Adds indexed `subject` column to `learning_materials` if missing.
2. `2026_10_12_020000_create_material_chunks_table.php`  
   *Risk: None.* Creates `material_chunks` table for chunk text and 768d vector embeddings.
3. `2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php`  
   *Risk: None.* Creates `student_ai_queries` and `student_ai_feedback` tables.
4. `2026_10_12_040000_add_rag_status_to_learning_materials_table.php`  
   *Risk: None.* Adds `rag_status`, `rag_indexed_at`, `rag_chunks_count`, and `rag_error` columns.
5. `2026_10_12_050000_add_rag_enabled_and_distress_flags.php`  
   *Risk: None.* Adds `rag_enabled` to institutes/users and `distress_flagged` to queries.
6. `2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php`  
   *Risk: None.* Ensures institutes default to `rag_enabled = false` and adds OCR metadata.
7. `2026_10_15_030000_add_file_hash_to_learning_materials_table.php`  
   *Risk: None.* Adds indexed `file_hash` (SHA-256) column to prevent duplicate files.
8. `2026_10_21_040000_create_material_pages_table.php`  
   *Risk: None.* Creates `material_pages` table for page-level OCR caching.
9. `2026_10_22_010000_add_public_assistant_enabled_to_institutes_table.php`  
   *Risk: None.* Adds `public_assistant_enabled` flag to `institutes`.
10. `2026_10_22_020000_create_faqs_table.php`  
    *Risk: None.* Creates `faqs` table for public institute questions and answers.
11. `2026_10_22_030000_create_assistant_visitor_logs_table.php`  
    *Risk: None.* Creates `assistant_visitor_logs` table for public chat audit trails.

---

## 7. Queue Worker & Cron Configuration

RAG file ingestion (PDF text extraction, OCR, and embedding generation) runs in background queues so teacher uploads are never blocked.

### 7.1: Background Queue Worker
Configure a supervisor or systemd process to run the queue worker.

**Systemd Service Example (`/etc/systemd/system/tit-worker.service`):**
```ini
[Unit]
Description=TiT RAG Background Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/tit/laravel_api/backend/artisan queue:work --queue=rag,default --tries=3 --timeout=300

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable tit-worker
sudo systemctl start tit-worker
```

### 7.2: Scheduled Tasks (Cron)
Add the standard Laravel scheduler line to your server crontab:
```bash
crontab -e
```
Add this single line (replace `/var/www/tit` with your actual project root):
```cron
* * * * * cd /var/www/tit/laravel_api/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Cache Clearing & Optimization

After migrations and environment changes, clear and rebuild Laravel's production caches:
```bash
cd laravel_api/backend

# Clear stale caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Re-optimize for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 9. Canary Rollout: Enabling for ONE Institute

By default, RAG and the Public Assistant are **disabled** for all institutes. You can safely enable them for a single pilot institute (canary rollout) to verify before turning them on for everyone.

### Step 9.1: Find the Institute ID or Code
```bash
cd laravel_api/backend
php artisan tinker --execute="App\Models\Institute::select('id', 'name', 'code', 'rag_enabled')->get()->toArray();"
```

### Step 9.2: Enable RAG and Public Assistant for that Institute
```bash
# Example: Enable for Institute ID 1 (or by code 'PREMIER')
php artisan tinker --execute="App\Models\Institute::where('id', 1)->update(['rag_enabled' => true, 'public_assistant_enabled' => true]); echo 'Enabled!';"
```

### Step 9.3: Enable Students in that Institute
```bash
php artisan tinker --execute="App\Models\User::where('institute_id', 1)->where('role', 'user')->update(['rag_enabled' => true]); echo 'Students updated!';"
```

---

## 10. Post-Deployment Smoke Tests

Perform these three verification tests immediately after deployment:

### Test 1: Student Study Assistant Smoke Test
1. Log in to the student dashboard as an approved, paid student belonging to the enabled institute.
2. In the navigation, click **Study Assistant** (`/student/study-assistant`).
3. **Verify:**
   - Header shows "AI Study Assistant" and daily question counter pill (e.g., `Daily: 0 / 30`).
   - First-time disclaimer banner appears: *"AI Study Assistant can make mistakes. Verify important facts with your teachers."*
   - Type a syllabus question (e.g. in English, Tamil, or Sinhala) and press Send.
   - Streamed text response renders word by word.
   - Citation chips appear under the answer displaying material titles and page numbers.
   - Click the Thumbs Up or Thumbs Down feedback button.
   - Type a safety test phrase indicating emotional distress (e.g. *"I want to hurt myself"*): verify that the mental health safety card appears immediately with the **1926** National Mental Health Helpline.

### Test 2: Teacher Materials & Search Status Smoke Test
1. Log in as a teacher (`/teacher/materials`).
2. **Verify:**
   - Materials table displays an **AI Search Status** column badge for each file:
     - 🟢 **Ready** (Indexed)
     - 🔵 **Processing**
     - 🟠 **Low Quality / Needs Clearer File**
     - 🔴 **Failed** (with a "Retry" button)
   - Click **Upload Material**:
     - Grade, Subject, and Medium are required fields.
     - A hint explains that Grade controls student search visibility.
   - Attempt to upload an identical PDF: verify that a warning is displayed notifying that the file hash already exists in this institute.

### Test 3: Public Website Visitor Assistant Smoke Test
1. Open an **Incognito / Private browser window** (not logged in).
2. Visit the public homepage (`/`), Classes (`/classes`), or Contact (`/contact`).
3. **Verify:**
   - Floating circular assistant button appears at the bottom-right of the screen.
   - Click the button: modal chat window opens.
   - Ask: *"What classes are available?"* or *"How do I register?"*
   - Verify the assistant answers using institute public context and FAQs without requiring login.
   - Verify rate limiting: visitors are limited to 10 queries per IP per hour.

---

## 11. 7-Day Monitoring Watchlist

Monitor these items daily for the first 7 days following release:

| Metric / Area | Where to Check | Normal Threshold | What to Do if Exceeded |
|---|---|---|---|
| **Failed Ingestion Jobs** | `failed_jobs` table | 0 failed jobs | Run `php artisan queue:retry all` and inspect `exception` column |
| **Gemini API Errors / Quotas** | `laravel_api/backend/storage/logs/laravel.log` | 0 quota/rate-limit errors | Check Google Cloud Console API billing & rate limits |
| **OCR Uncertainty Rate** | `material_pages` where `ocr_uncertain = 1` | < 10% of pages | Advise teachers to upload scanned PDFs with higher DPI (300 DPI) |
| **Low Quality Chunks** | `learning_materials` where `rag_status = 'low_quality'` | < 5% of uploads | Check uploaded file readability; retry via teacher table |
| **Distress Triggers** | `student_ai_queries` where `distress_flagged = 1` | Handled securely | Verify that crisis hotlines (1926) were presented properly |
| **Server Disk Space** | `df -h` on server | < 80% disk usage | Check size of `storage/app/materials/` and database backups |

---

## 12. Rollback Plan

If an unexpected critical issue occurs, use these procedures to roll back safely.

### 12.1: Instant Kill Switches (Zero Downtime)
You do not need to uninstall code to disable the AI features. You can disable them instantly:

**To disable RAG for all institutes:**
```bash
cd laravel_api/backend
php artisan tinker --execute="App\Models\Institute::query()->update(['rag_enabled' => false, 'public_assistant_enabled' => false]); echo 'Disabled!';"
```
**To disable in `.env`:**
Set `RAG_DAILY_QUERY_LIMIT=0` or revoke `GEMINI_API_KEY`.

### 12.2: Migration Rollback
If you need to roll back the 11 database migrations:
```bash
cd laravel_api/backend
# Rollback the last 11 migrations
php artisan migrate:rollback --step=11
```

### 12.3: Restoring Database and File Backups
To fully restore the previous pre-deployment state:
1. **Restore Database:**
   ```bash
   mysql -u [db_user] -p [db_name] < /backups/db_backup_pre_rag_*.sql
   ```
2. **Restore Frontend Bundle & Assets:**
   ```bash
   cd /var/www/tit/
   tar -xzvf /backups/app_backup_pre_rag/assets_backup.tar.gz
   ```
3. **Restore Environment:**
   ```bash
   cp /backups/app_backup_pre_rag/.env.backup laravel_api/backend/.env
   ```
4. **Re-clear Caches:**
   ```bash
   cd laravel_api/backend
   php artisan config:clear
   php artisan route:clear
   ```
