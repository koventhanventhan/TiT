# RAG Study Assistant Production Go-Live & Deployment Guide

This guide outlines the complete, step-by-step production deployment procedure for the **AI Student Study Assistant (RAG Pipeline)** on a cPanel / shared-hosting (Litespeed/Apache) or Linux VPS production environment for TiT Academy.

---

## 1. Database Migrations

Run these migrations in **exact chronological order**. Do not run them out of order.

### Migration Sequence

| Step | Migration File | Description | Verification SQL |
|------|----------------|-------------|------------------|
| **1** | `2026_10_12_010000_add_subject_to_learning_materials_table.php` | Adds nullable `subject` column to `learning_materials` | `SHOW COLUMNS FROM learning_materials LIKE 'subject';` |
| **2** | `2026_10_12_020000_create_material_chunks_table.php` | Creates `material_chunks` table for document embeddings with packed float vector BLOB | `SHOW TABLES LIKE 'material_chunks';` |
| **3** | `2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php` | Creates `student_ai_queries` telemetry table and `student_ai_feedback` rating table | `SHOW TABLES LIKE 'student_ai_%';` |
| **4** | `2026_10_12_040000_add_rag_status_to_learning_materials_table.php` | Adds indexing lifecycle columns (`rag_status`, `rag_indexed_at`, `rag_error`) to `learning_materials` | `SHOW COLUMNS FROM learning_materials LIKE 'rag_status';` |
| **5** | `2026_10_12_050000_add_rag_enabled_and_distress_flags.php` | Adds `rag_enabled` boolean to `institutes` and `has_distress_flag` to `student_ai_queries` | `SHOW COLUMNS FROM institutes LIKE 'rag_enabled';` |
| **6** | `2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php` | Sets default `rag_enabled = 1` for institutes and adds `used_ocr` boolean to `material_chunks` | `SHOW COLUMNS FROM material_chunks LIKE 'used_ocr';` |

### Execution Commands (SSH / Terminal)

```bash
cd /home/<cpanel_user>/public_html/laravel_api/backend

# Run migrations step by step
php artisan migrate --path=database/migrations/2026_10_12_010000_add_subject_to_learning_materials_table.php
php artisan migrate --path=database/migrations/2026_10_12_020000_create_material_chunks_table.php
php artisan migrate --path=database/migrations/2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php
php artisan migrate --path=database/migrations/2026_10_12_040000_add_rag_status_to_learning_materials_table.php
php artisan migrate --path=database/migrations/2026_10_12_050000_add_rag_enabled_and_distress_flags.php
php artisan migrate --path=database/migrations/2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php
```

### Verification Script

Log into cPanel **phpMyAdmin** or run via MySQL CLI:

```sql
-- 1. Check learning_materials columns
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'learning_materials' 
  AND COLUMN_NAME IN ('subject', 'rag_status', 'rag_indexed_at', 'rag_error');

-- 2. Check material_chunks schema
DESCRIBE material_chunks;

-- 3. Check student_ai_queries & feedback
DESCRIBE student_ai_queries;
DESCRIBE student_ai_feedback;

-- 4. Check institutes flag
SELECT id, name, rag_enabled FROM institutes;
```

---

## 2. Production Environment Configuration (`.env`)

Add the following environment variables to your production backend `.env` file (`/home/<cpanel_user>/public_html/laravel_api/backend/.env`).

```ini
# ==============================================================================
# STUDENT AI STUDY ASSISTANT (RAG) CONFIGURATION
# ==============================================================================

# Global Master Kill Switch (Set to false to instantly deactivate feature across all tenants)
RAG_ENABLED=true

# Google Gemini API Key (Obtain from Google AI Studio / GCP Console)
GEMINI_API_KEY=AIzaSyYourProductionApiKeyHere

# AI Models
# - Embedding: gemini-embedding-2 (768 dimensions)
# - LLM Grounded Answers: gemini-3.8-flash (Recommended stable GA model with extended support)
# - Primary OCR Engine: gemini-3.8-flash (Latest multimodal document vision model)
# - Fallback OCR Engine: gemini-3.5-flash-lite (Cost-effective automated failover)
RAG_EMBEDDING_MODEL=gemini-embedding-2
RAG_EMBEDDING_DIMENSIONS=768
RAG_LLM_MODEL=gemini-3.8-flash
RAG_OCR_MODEL=gemini-3.8-flash
RAG_OCR_FALLBACK_MODEL=gemini-3.5-flash-lite
RAG_OCR_TEMPERATURE=0.0
RAG_OCR_PAGE_TIMEOUT=120
RAG_OCR_TOTAL_TIMEOUT=120

# Ingestion & Downscaling Settings
# Scanned PDF page images exceeding 1600px are automatically downscaled to 80% JPEG quality,
# reducing transmission payloads by 83%+ and preventing gateway timeouts.
RAG_OCR_MAX_DIMENSION=1600
RAG_OCR_JPEG_QUALITY=80
RAG_OCR_MAX_PAGES=20
RAG_OCR_MIN_WORDS_PER_PAGE=20
RAG_MAX_PAGES_PER_MATERIAL=50
RAG_MAX_FILE_SIZE_MB=50
RAG_GEMINI_TIMEOUT=60

# Retrieval & Grounding Thresholds
# If cosine similarity between student query and teacher chunks < 0.70,
# the assistant returns a polite out-of-scope fallback without calling LLM.
RAG_SIMILARITY_THRESHOLD=0.70
RAG_TOP_K=5

# Rate Limits & Abuse Quotas
RAG_RATE_LIMIT_PER_MINUTE=10
RAG_DAILY_QUESTION_LIMIT=30

# Student Safety & Crisis Support Helplines (Shown when student expresses distress)
RAG_HELPLINE_PRIMARY_NAME="Sri Lanka National Mental Health Helpline"
RAG_HELPLINE_PRIMARY_NUMBER="1926"
RAG_HELPLINE_PRIMARY_HOURS="24/7 Toll-Free"

RAG_HELPLINE_SECONDARY_NAME="Sumithrayo Suicide Prevention Helpline"
RAG_HELPLINE_SECONDARY_NUMBER="" # Note: Verify contact numbers with local authorities before populating
RAG_HELPLINE_SECONDARY_HOURS="9:00 AM - 8:00 PM Daily"

RAG_HELPLINE_INSTITUTE_NAME="TiT Institute Student Support Desk"
RAG_HELPLINE_INSTITUTE_NUMBER="+94 77 123 4567"
RAG_HELPLINE_INSTITUTE_HOURS="Monday - Saturday 8:00 AM - 5:00 PM"
```

After updating `.env`, clear the configuration cache:
```bash
php artisan config:clear
php artisan cache:clear
```

---

## 3. Queue Worker & Cron Scheduling

### A. Queue Worker (Processes PDF Extraction & Ingestion)

The RAG ingestion pipeline runs asynchronously through Laravel queues (`rag` and `default` queues) so teachers uploading large files experience zero UI delay.

#### Option 1: Supervisor (Recommended for VPS / Dedicated Servers)
Create `/etc/supervisor/conf.d/tit-worker.conf`:
```ini
[program:tit-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/bin/php /home/<user>/public_html/laravel_api/backend/artisan queue:work --queue=rag,default --sleep=3 --tries=3 --timeout=180
autostart=true
autorestart=true
user=<user>
numprocs=2
redirect_stderr=true
stdout_logfile=/home/<user>/public_html/laravel_api/backend/storage/logs/worker.log
stopwaitsecs=3600
```
Update supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tit-worker:*
```

#### Option 2: cPanel Cron Job Fallback (Shared Hosting)
If Supervisor is unavailable on shared cPanel hosting, add a cron job that processes queued jobs every minute and exits when idle.

> [!IMPORTANT]
> **Prevent Overlapping Cron Execution & Ensure Safe Timeout Limits:**
> 1. **Worker Overlap Prevention (`flock -n`)**: When OCR jobs take 30-60+ seconds per page, a naive 1-minute cron job will spawn concurrent competing workers. Use `/usr/bin/flock -n /tmp/cpanel_rag_queue.lock` so a new worker immediately exits if a previous worker is still processing.
> 2. **Job Timeout (`--timeout=120`)**: Give OCR jobs sufficient execution budget (120 seconds per page job).
> 3. **Queue `retry_after` Setting**: In `config/queue.php` (or `.env` via `DB_QUEUE_RETRY_AFTER`), `retry_after` MUST be strictly higher than worker `--timeout` (e.g. `DB_QUEUE_RETRY_AFTER=180` for `--timeout=120`). If `retry_after <= timeout`, the queue driver will assume the job stalled and release it back to another worker while still actively processing OCR.

- **Interval**: Once Per Minute (`* * * * *`)
- **Command**:
```bash
* * * * * /usr/bin/flock -n /tmp/cpanel_rag_queue.lock -c "cd /home/<cpanel_user>/public_html/laravel_api/backend && /usr/local/bin/php artisan queue:work --queue=rag,default --stop-when-empty --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1"
```

### B. Laravel Task Scheduler (Data Retention & Redaction)
Add the master Laravel scheduler cron entry:
- **Interval**: Once Per Minute (`* * * * *`)
- **Command**:
```bash
* * * * * cd /home/<cpanel_user>/public_html/laravel_api/backend && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Frontend Build & Asset Deployment

### Step 1: Build the Modern React Dashboard
On your build machine or local workspace:
```bash
cd laravel_api
npm install
npm run build
```
This produces optimized production assets in `laravel_api/dist/`.

### Step 2: Upload Files to Server
1. Copy the contents of `laravel_api/dist/assets/` to your public server assets directory (e.g. `/home/<cpanel_user>/public_html/assets/` or `laravel_api/public/assets/`).
2. Verify `index.html` or the Laravel blade view `resources/views/app.blade.php` references the latest generated JS and CSS hash bundles.

---

## 5. Rollback & Emergency Procedures

If any unexpected issues arise after deployment:

### 1. Instant Kill Switch (< 10 seconds)
To immediately hide the Study Assistant from all students and disable all RAG endpoints without rolling back code or database:
1. Open `.env` in cPanel File Manager or SSH.
2. Change:
   ```ini
   RAG_ENABLED=false
   ```
3. Run `php artisan config:clear`.
4. **Result:** The "Study Assistant" tab immediately vanishes from the student navigation menu. Any direct requests to `/api/student/study-assistant/*` immediately return `503 Service Unavailable` with a user-friendly maintenance message. Core LMS functionality (Zoom, Payments, Assignments, Materials) continues functioning without interruption.

### 2. Database Rollback
If you need to revert the database tables:
```bash
cd /home/<cpanel_user>/public_html/laravel_api/backend
php artisan migrate:rollback --step=6
```

### 3. Code Rollback
To restore code to pre-RAG state:
```bash
git checkout origin/main
php artisan optimize:clear
```
