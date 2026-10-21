# RAG Study Assistant Production Go-Live & Operations Guide

This guide covers deployment, configuration, queue management, verification, rollback procedures, and known operational limitations for the Student AI Study Assistant (Retrieval-Augmented Generation) on production cPanel / Vercel environments.

---

## 1. Database Migrations

Run all RAG migrations in strict chronological order. Each migration is idempotently guarded (`Schema::hasTable` or `Schema::hasColumn`).

```bash
cd /home/<cpanel_user>/public_html/laravel_api/backend

# Run migrations in chronological sequence:
php artisan migrate --path=database/migrations/2026_10_12_010000_add_subject_to_learning_materials_table.php
php artisan migrate --path=database/migrations/2026_10_12_020000_create_material_chunks_table.php
php artisan migrate --path=database/migrations/2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php
php artisan migrate --path=database/migrations/2026_10_12_040000_add_rag_status_to_learning_materials_table.php
php artisan migrate --path=database/migrations/2026_10_12_050000_add_rag_enabled_and_distress_flags.php
php artisan migrate --path=database/migrations/2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php
php artisan migrate --path=database/migrations/2026_10_15_030000_add_file_hash_to_learning_materials_table.php
php artisan migrate --path=database/migrations/2026_10_21_040000_create_material_pages_table.php
```

### Verification Script

Log into cPanel **phpMyAdmin** or run via MySQL CLI:

```sql
-- 1. Check learning_materials columns
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'learning_materials' 
  AND COLUMN_NAME IN ('subject', 'rag_status', 'rag_indexed_at', 'rag_error', 'file_hash');

-- 2. Check material_chunks schema
DESCRIBE material_chunks;

-- 3. Check material_pages schema
DESCRIBE material_pages;

-- 4. Check student_ai_queries & feedback
DESCRIBE student_ai_queries;
DESCRIBE student_ai_feedback;

-- 5. Check institutes flag
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

# AI Models (Verified GA October 2026)
# - Embedding: gemini-embedding-2 (768 dimensions)
# - LLM Grounded Answers: gemini-3.6-flash (Production GA model with high Tamil/Sinhala fidelity)
# - Primary OCR Engine: gemini-3.6-flash (Multimodal document vision model)
# - Fallback OCR Engine: gemini-3.5-flash-lite (Cost-effective automated failover)
RAG_EMBEDDING_MODEL=gemini-embedding-2
RAG_EMBEDDING_DIMENSIONS=768
RAG_LLM_MODEL=gemini-3.6-flash
RAG_OCR_MODEL=gemini-3.6-flash
RAG_OCR_FALLBACK_MODEL=gemini-3.5-flash-lite
RAG_OCR_TEMPERATURE=0.0
RAG_OCR_PAGE_TIMEOUT=240
RAG_OCR_TOTAL_TIMEOUT=240

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
RAG_OCR_ENABLE_SAFEGUARD=true
RAG_OCR_COMPLETENESS_DIFF_THRESHOLD=0.10

# Two-Level Retrieval & Grounding Thresholds
# Chunks with similarity >= 0.50 are candidate context for the LLM.
# Chunks with similarity >= 0.70 form confident grounded answers.
RAG_MIN_CANDIDATE_SIMILARITY=0.50
RAG_SIMILARITY_THRESHOLD=0.70
RAG_MIN_SIMILARITY=0.70
RAG_TOP_K=5

# Rate Limits & Abuse Quotas
RAG_RATE_LIMIT_PER_MINUTE=10
RAG_DAILY_QUESTION_LIMIT=30

# Queue Worker Configuration
DB_QUEUE_RETRY_AFTER=300

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

### A. Queue Worker (Processes Page-by-Page OCR & Ingestion Pipeline)

The RAG ingestion pipeline processes documents page-by-page through chained jobs:
1. `IngestLearningMaterialJob`: inspects document, classifies pages.
2. `ProcessMaterialOcrPageJob`: runs per-page OCR (with reuse from `material_pages` and identical `file_hash`).
3. `FinalizeMaterialIngestionJob`: stores extracted pages to `material_pages`, chunks questions/notes, generates batch embeddings, and transitions status to `ready`.

#### Option 1: Supervisor (Recommended for VPS / Dedicated Servers)
Create `/etc/supervisor/conf.d/tit-worker.conf`:
```ini
[program:tit-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/bin/php /home/<user>/public_html/laravel_api/backend/artisan queue:work --queue=rag,default --sleep=3 --tries=2 --timeout=240
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
> 1. **Worker Overlap Prevention (`flock -n`)**: When OCR jobs take 30-75 seconds per page, a naive 1-minute cron job will spawn concurrent competing workers. Use `/usr/bin/flock -n /tmp/cpanel_rag_queue.lock` so a new worker immediately exits if a previous worker is still running.
> 2. **Job Timeout (`--timeout=240`)**: Per-page OCR jobs require a 240-second execution budget.
> 3. **Queue `retry_after` Setting**: In `config/queue.php` (or `.env` via `DB_QUEUE_RETRY_AFTER`), `retry_after` MUST be strictly higher than worker `--timeout` (e.g. `DB_QUEUE_RETRY_AFTER=300` for `--timeout=240`). If `retry_after <= timeout`, the database queue driver assumes the job stalled and releases it back to another worker while still actively processing OCR.

- **Interval**: Once Per Minute (`* * * * *`)
- **Command**:
```bash
* * * * * /usr/bin/flock -n /tmp/cpanel_rag_queue.lock -c "cd /home/<cpanel_user>/public_html/laravel_api/backend && /usr/local/bin/php artisan queue:work --queue=rag,default --stop-when-empty --tries=2 --timeout=240 >> storage/logs/queue.log 2>&1"
```

### B. Laravel Task Scheduler (Cleanup & Retention)
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

### 1. Instant Institute or Global Kill Switch (< 10 seconds)
To immediately hide the Study Assistant from students without rolling back code or database:
- **Single Institute Kill Switch**:
  ```bash
  php artisan rag:institute 1 off
  ```
- **Global Master Kill Switch**:
  In `.env`:
  ```ini
  RAG_ENABLED=false
  ```
  Then run `php artisan config:clear`.
- **Result:** The "Study Assistant" tab immediately disappears from the student navigation menu. Direct requests return `503 Service Unavailable`. Core LMS functionality (Zoom, Payments, Assignments, Materials) continues functioning without interruption.

### 2. Database Rollback
If you need to revert the database tables:
```bash
cd /home/<cpanel_user>/public_html/laravel_api/backend
php artisan migrate:rollback --step=8
```

---

## 6. Known Operational Limitations

1. **Past Papers Without Answers**:
   When students ask questions from uploaded past papers or exam question papers that do not include an answer key or marking scheme, the assistant is strictly instructed not to hallucinate answers. It informs the student that the notes contain the question but not the solution, and invites the student to attempt it or consult their teacher.
2. **OCR Variance & Legibility**:
   OCR text extraction relies on computer vision. Low-resolution camera scans, curved page bindings, and unusual fonts may result in minor character variances. The pipeline automatically marks uncertain pages (`ocr_uncertain = true`) when discrepancies exceed the threshold and surfaces warnings in `rag:inspect` and `rag:status`.
3. **Project-Wide API Quotas**:
   Google Gemini API rate limits (RPM, TPM, RPD) apply across the entire Google Cloud Project. Heavy batch re-indexing should be scheduled during off-peak hours using `rag:reindex --limit=...` to avoid consuming quotas needed for live student answering.
4. **Free Tier Unsuitable for Real Students**:
   The Google AI Studio Free Tier has strict 15 RPM and daily request quotas. Simultaneous classroom usage will immediately trigger HTTP 429 throttling. Production deployment requires a Google Cloud billing account with Pay-As-You-Go pricing.