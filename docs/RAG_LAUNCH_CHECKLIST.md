# RAG Study Assistant Production Launch Checklist

An ordered, step-by-step numbered checklist for launching the Student AI Study Assistant on production cPanel / Vercel.

---

### Step 1: Pre-Deployment Backup
- [ ] 1.1 Create full MySQL database backup via cPanel phpMyAdmin or `mysqldump`:
  ```bash
  mysqldump -u <db_user> -p <db_name> > pre_rag_launch_backup_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] 1.2 Back up existing backend `.env` file and frontend assets.

---

### Step 2: Database Migrations (In Chronological Sequence)
- [ ] 2.1 Run all 8 RAG migrations sequentially from the backend directory:
  ```bash
  cd /home/<cpanel_user>/public_html/laravel_api/backend

  php artisan migrate --path=database/migrations/2026_10_12_010000_add_subject_to_learning_materials_table.php
  php artisan migrate --path=database/migrations/2026_10_12_020000_create_material_chunks_table.php
  php artisan migrate --path=database/migrations/2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php
  php artisan migrate --path=database/migrations/2026_10_12_040000_add_rag_status_to_learning_materials_table.php
  php artisan migrate --path=database/migrations/2026_10_12_050000_add_rag_enabled_and_distress_flags.php
  php artisan migrate --path=database/migrations/2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php
  php artisan migrate --path=database/migrations/2026_10_15_030000_add_file_hash_to_learning_materials_table.php
  php artisan migrate --path=database/migrations/2026_10_21_040000_create_material_pages_table.php
  php artisan migrate --path=database/migrations/2026_10_22_010000_add_public_assistant_enabled_to_institutes_table.php
  php artisan migrate --path=database/migrations/2026_10_22_020000_create_faqs_table.php
  php artisan migrate --path=database/migrations/2026_10_22_030000_create_assistant_visitor_logs_table.php
  ```
- [ ] 2.2 Verify migration status:
  ```bash
  php artisan migrate:status
  ```
  Ensure all 8 RAG migrations are marked `Ran`.

---

### Step 3: Production Environment (`.env`) Configuration
- [ ] 3.1 Verify and set required `.env` keys in `/home/<cpanel_user>/public_html/laravel_api/backend/.env`:
  ```ini
  RAG_ENABLED=true
  GEMINI_API_KEY=AIzaSy...YourProductionKey...
  RAG_EMBEDDING_MODEL=gemini-embedding-2
  RAG_EMBEDDING_DIMENSIONS=768
  RAG_LLM_MODEL=gemini-3.6-flash
  RAG_OCR_MODEL=gemini-3.6-flash
  RAG_OCR_FALLBACK_MODEL=gemini-3.5-flash-lite
  RAG_OCR_TEMPERATURE=0.0
  RAG_OCR_PAGE_TIMEOUT=240
  RAG_OCR_TOTAL_TIMEOUT=240
  RAG_MIN_CANDIDATE_SIMILARITY=0.50
  RAG_SIMILARITY_THRESHOLD=0.70
  RAG_MIN_SIMILARITY=0.70
  RAG_TOP_K=5
  RAG_RATE_LIMIT_PER_MINUTE=10
  RAG_DAILY_QUESTION_LIMIT=30
  DB_QUEUE_RETRY_AFTER=300
  ```
- [ ] 3.2 Clear cached configuration:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

---

### Step 4: Queue Worker & Overlap-Safe Cron Setup
- [ ] 4.1 Set up cPanel cron job with mutex lock and 240s timeout:
  ```bash
  * * * * * /usr/bin/flock -n /tmp/cpanel_rag_queue.lock -c "cd /home/<cpanel_user>/public_html/laravel_api/backend && /usr/local/bin/php artisan queue:work --queue=rag,default --stop-when-empty --tries=2 --timeout=240 >> storage/logs/queue.log 2>&1"
  ```
- [ ] 4.2 Verify Laravel scheduler cron is active:
  ```bash
  * * * * * cd /home/<cpanel_user>/public_html/laravel_api/backend && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] 4.3 Ensure queue `retry_after` in `config/queue.php` (or `DB_QUEUE_RETRY_AFTER`) is set to 300 (above 240).

---

### Step 5: Frontend Build & Asset Deployment
- [ ] 5.1 On build workstation, install dependencies and build production React bundle:
  ```bash
  cd laravel_api
  npm run build
  ```
- [ ] 5.2 Commit and deploy built assets to production Vercel/cPanel web root.
- [ ] 5.3 Verify browser loads the new bundle without cached chunk mismatches.

---

### Step 6: Post-Deployment Smoke Tests
- [ ] 6.1 Test Gemini API connectivity and credentials:
  ```bash
  php artisan rag:ping
  ```
  Ensure all models (embedding, LLM, OCR) return healthy status.
- [ ] 6.2 Check institute assistant status:
  ```bash
  php artisan rag:status
  ```
- [ ] 6.3 Test retrieval with sample query via CLI:
  ```bash
  php artisan rag:status --list
  ```
- [ ] 6.4 Log in as an active student and ask a sample question in Tamil/Sinhala/English.
  Verify citations, OCR notice badges, and answer quality.

---

### Step 7: Emergency Procedures & Rollback
- [ ] 7.1 **Single Institute Kill Switch**:
  ```bash
  php artisan rag:institute 1 off
  ```
- [ ] 7.2 **Global Master Kill Switch**:
  In `.env`: `RAG_ENABLED=false` then `php artisan config:clear`.
- [ ] 7.3 **Database Rollback**:
  ```bash
  php artisan migrate:rollback --step=8
  ```
---

### Step 7: Unified Public Assistant Rollout & Verification
- [ ] 7.1 Verify environment variables in `.env`:
  ```ini
  RAG_PUBLIC_ENABLED=true
  RECAPTCHA_SITE_KEY=your_recaptcha_v3_site_key
  RECAPTCHA_SECRET_KEY=your_recaptcha_v3_secret_key
  RECAPTCHA_SCORE_THRESHOLD=0.5
  RAG_ASSISTANT_VISITOR_PER_MINUTE=10
  RAG_ASSISTANT_VISITOR_PER_DAY=60
  RAG_ASSISTANT_VISITOR_DAILY_CAP=500
  RAG_ASSISTANT_VISITOR_MAX_CHARS=200
  RAG_PUBLIC_KNOWLEDGE_TTL=300
  ```
- [ ] 7.2 Configure dedicated contact details in `site_settings` under group `assistant_public_contact`:
  - `assistant_public_contact_phone`
  - `assistant_public_contact_whatsapp`
  - `assistant_public_contact_email`
  - `assistant_public_contact_address`
  - `assistant_public_contact_hours`
- [ ] 7.3 Enable the public assistant for the institute via artisan:
  ```bash
  php artisan rag:public 1 on
  ```
  *(Verify command succeeds and passes the contact / helpline validation gate)*
- [ ] 7.4 Test public floating chat widget on Home, About, Classes, and Contact pages:
  - Test general questions (fee inquiries, timetable, registration at `/register`).
  - Test academic question in public mode (must return state-specific locked message with link to `/register`, no hallucinated academic answers).
  - Test crisis helpline trigger (must show 1926/1333/1990 crisis numbers without logging raw text).
- [ ] 7.5 Verify emergency kill switches:
  - Disable single institute: `php artisan rag:public 1 off` (widget disappears immediately).
  - Global master kill switch: `RAG_PUBLIC_ENABLED=false` in `.env`.