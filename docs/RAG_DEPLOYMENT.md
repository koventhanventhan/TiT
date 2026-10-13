# Student AI Study Assistant (RAG) — Production Deployment & Operations Guide

This guide details the deployment, server configuration, background jobs, operational commands, gradual rollout strategy, and kill-switch procedures for the **Student AI Study Assistant (RAG)** pipeline.

---

## 1. System Architecture Overview

The Student AI Study Assistant is an enterprise-grade Retrieval-Augmented Generation (RAG) system providing grounded answers strictly from teacher-uploaded notes and past papers.

- **Embedding & Search**: Multilingual chunking with `gemini-embedding-2` stored in MariaDB packed float vector blobs, queried via cosine similarity with strict threshold filtering ($0.70$ default).
- **Generation**: `gemini-3.5-flash` streaming grounded answers over Server-Sent Events (SSE).
- **Child Protection & Safety**: First-person self-harm/distress detection in English, Tamil, Sinhala, and romanized forms (Tanglish/Singlish) with localized pastoral helplines and instant institute admin alerts.
- **Privacy & Security**: 90-day automatic text redaction (flagged distress queries excluded for pastoral review), multi-tenant isolation, prompt injection escaping, and strict atomic daily quotas.

---

## 2. Required Environment Configuration (`.env`)

Add and verify the following environment variables in your server's `.env`:

```dotenv
# Core Timezone (Essential for daily quota resets at midnight Sri Lanka time)
APP_TIMEZONE=Asia/Colombo

# Global Feature Switch (Instant kill switch across all tenants)
RAG_ENABLED=true

# Google Gemini API Key
GEMINI_API_KEY=your_gemini_api_key_here

# AI Models & Dimensions
RAG_EMBEDDING_MODEL=gemini-embedding-2
RAG_EMBEDDING_DIMENSIONS=768
RAG_LLM_MODEL=gemini-3.5-flash

# Retrieval Thresholds
RAG_SIMILARITY_THRESHOLD=0.70
RAG_TOP_K=5

# Rate Limits & Abuse Quotas
RAG_RATE_LIMIT_PER_MINUTE=10
RAG_DAILY_QUESTION_LIMIT=30

# LLM Generation & Timeout
RAG_LLM_TEMPERATURE=0.2
RAG_LLM_MAX_OUTPUT_TOKENS=1500
RAG_LLM_REQUEST_TIMEOUT=45

# Gemini Child Safety Filter Thresholds
RAG_SAFETY_HARASSMENT=BLOCK_LOW_AND_ABOVE
RAG_SAFETY_HATE_SPEECH=BLOCK_LOW_AND_ABOVE
RAG_SAFETY_SEXUALLY_EXPLICIT=BLOCK_LOW_AND_ABOVE
RAG_SAFETY_DANGEROUS_CONTENT=BLOCK_LOW_AND_ABOVE

# Data Privacy Retention (Days before redaction)
RAG_PRIVACY_RETENTION_DAYS=90

# Crisis Helplines & Pastoral Contacts (Empty by default; supply verified numbers)
RAG_HELPLINE_PRIMARY_NAME=
RAG_HELPLINE_PRIMARY_NUMBER=
RAG_HELPLINE_PRIMARY_HOURS=
RAG_HELPLINE_SECONDARY_NAME=
RAG_HELPLINE_SECONDARY_NUMBER=
RAG_HELPLINE_SECONDARY_HOURS=
RAG_HELPLINE_INSTITUTE_NAME="Institute / Teacher Support"
RAG_HELPLINE_INSTITUTE_NUMBER=
RAG_HELPLINE_INSTITUTE_HOURS=
```

---

## 3. Database Migrations

Run database migrations to ensure all RAG tables, columns, and indexes are active:

```bash
php artisan migrate --force
```

This ensures the following tables and columns are present:
- `material_chunks`: Stores packed vector embeddings, page numbers, multilingual chunk content, and `is_ocr` tracking.
- `student_ai_queries`: Stores telemetry, query status (`processing`, `answered`, `no_notes_found`, `aborted`, `blocked_safety`, `error`, `distress_blocked`), citation metadata, ratings, and distress flags.
- `institutes.rag_enabled`: Boolean flag enabling/disabling the assistant per institute (Default: `false` / `0`). Existing institutes are set to `false`.

---

## 4. Background Queue Worker Setup

Document processing (PDF parsing, Vision OCR, embedding generation) runs asynchronously via Laravel Queues.

### Supervisor Configuration (`/etc/supervisor/conf.d/tit-worker.conf`)

```ini
[program:tit-rag-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tit-education/laravel_api/backend/artisan queue:work --queue=rag,default --sleep=3 --tries=3 --timeout=120 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/tit-rag-worker.log
```

Apply and start the worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 5. Web Server Configuration for SSE Streaming

The AI Study Assistant streams tokens in real-time over HTTP Server-Sent Events (`text/event-stream`). Web servers must be configured not to buffer the response.

### Nginx Configuration

In your Nginx server block (`/etc/nginx/sites-available/tit-education.conf`):

```nginx
location /api/student/ai-chat {
    proxy_pass http://127.0.0.1:8000;
    proxy_http_version 1.1;

    # Critical SSE Streaming Directives
    proxy_set_header Connection '';
    proxy_buffering off;
    proxy_cache off;
    chunked_transfer_encoding off;

    # Timeouts for streaming responses
    proxy_read_timeout 300s;
    proxy_send_timeout 300s;

    # Pass client headers
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

# If using PHP-FPM directly:
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

    # Disable FastCGI buffering for SSE
    fastcgi_buffering off;
    fastcgi_read_timeout 300s;
}
```

### Apache Configuration

In your Apache virtual host (`/etc/apache2/sites-available/tit-education.conf`):

```apache
<Location "/api/student/ai-chat">
    # Disable output compression and proxy buffering for streaming
    SetEnv no-gzip 1
    SetEnv proxy-nokeepalive 1
    SetEnv proxy-sendchunked 1
</Location>
```

---

## 6. Scheduled Commands & Cron

Add the Laravel scheduler to your server's crontab (`crontab -e -u www-data`):

```cron
* * * * * cd /var/www/tit-education/laravel_api/backend && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler automatically manages two RAG-critical recurring jobs:
1. **`rag:cleanup-stuck-queries` (Runs every 5 minutes)**:
   Scans for queries that remained in `processing` status for more than 5 minutes (due to unexpected server crash or connection drop) and cleanly marks them as `error`.
2. **`rag:prune-queries` (Runs daily at 03:00)**:
   Redacts student question and answer text older than 90 days (`[REDACTED]`) to satisfy privacy compliance while preserving operational telemetry and feedback ratings.
   *Note: Flagged distress queries are strictly excluded from redaction to retain critical pastoral safety records.*

---

## 7. Operational Artisan Commands

### A. Institute Control (`rag:institute`)
Manage tenant enablement and view current status:

```bash
# Check status of all institutes:
php artisan rag:institute

# Enable RAG for Institute #1:
php artisan rag:institute 1 on

# Disable RAG for Institute #1:
php artisan rag:institute 1 off
```

*Note: The default value for `institutes.rag_enabled` is `false` (OFF). New institutes must be explicitly enabled via `php artisan rag:institute {id} on`.*

---

### B. Material Backfill & Reindex (`rag:reindex`)
Reindex teacher learning materials into the vector store. Fully resumable and supports rate limiting.

```bash
# Dry run to estimate OCR, pages, and embedding calls without executing:
php artisan rag:reindex --institute=1 --dry-run

# Backfill only missing/failed materials for Grade 10 with a 2-second delay:
php artisan rag:reindex --institute=1 --grade=grade-10 --only-missing --delay=2

# Full backfill for a specific material type:
php artisan rag:reindex --institute=1 --type=note --limit=50
```

---

### C. Operational Telemetry & Monitoring (`rag:status`)
View system health, document indexing counts, failure logs, and daily query metrics:

```bash
# View global operational telemetry:
php artisan rag:status

# View telemetry filtered for a specific institute over the last 30 days:
php artisan rag:status --institute=1 --days=30
```

Telemetry includes:
- Materials breakdown by `rag_status` (`ready`, `processing`, `pending`, `failed`, `unsupported`)
- List of failed materials with exact `rag_error` messages
- Total queries, grounded answer rate, and `no_notes_found` rate
- Thumbs-up vs. thumbs-down student ratings
- Recent flagged safety/distress queries

*Also accessible to administrators via API: `GET /api/admin/rag/status`.*

---

### D. Benchmark Accuracy Evaluation (`rag:eval`)
Evaluate retrieval accuracy against test question suites:

```bash
# Test retrieval with mock embeddings:
php artisan rag:eval tests/Fixtures/eval_questions.example.json --institute=1

# Test against live Gemini embedding API:
php artisan rag:eval tests/Fixtures/eval_questions.example.json --institute=1 --live
```

---

### E. Material Deep Inspection (`rag:inspect`)
Inspect a learning material's indexing status, total pages, chunk count, pages using OCR fallback, and read the first 3 chunks in full:

```bash
php artisan rag:inspect {material_id}
```

Output includes:
- RAG status (`ready`, `processing`, `failed`, `skipped`) and error message (if any).
- Total page count and generated chunk count.
- List of pages that required Gemini Vision OCR fallback (due to scanned pages or legacy non-Unicode font mojibake).
- First 3 chunks in full with word count and OCR flags.

---

## 8. Phased Gradual Rollout Strategy

To roll out the AI Study Assistant smoothly without unexpected API spikes:

1. **Step 1: Pilot Institute Selection**:
   Select one pilot institute (e.g. Institute ID `1`). Disable RAG on all other institutes:
   ```bash
   php artisan rag:institute 1 on
   # Confirm other institutes are off:
   php artisan rag:institute 2 off
   ```
2. **Step 2: Dry Run & Pre-Flight Check**:
   Run dry run to estimate quota needs for the pilot institute's notes:
   ```bash
   php artisan rag:reindex --institute=1 --dry-run
   ```
3. **Step 3: Execute Pilot Backfill**:
   Run the backfill with a rate-limit delay:
   ```bash
   php artisan rag:reindex --institute=1 --only-missing --delay=1
   ```
4. **Step 4: Verify Pilot Health**:
   Check telemetry to ensure materials are `ready`:
   ```bash
   php artisan rag:status --institute=1
   ```
5. **Step 5: Run Benchmark Evaluation**:
   Verify retrieval hits and confidence scores:
   ```bash
   php artisan rag:eval tests/Fixtures/eval_questions.example.json --institute=1
   ```
6. **Step 6: Monitor Student Activity**:
   Observe student queries for 48 hours using `php artisan rag:status --institute=1`. Check:
   - `no_notes_found` rate (target: $< 25\%$)
   - Thumbs down rate (target: $< 15\%$)
   - Zero stuck queries in `processing`
7. **Step 7: Scale to Additional Institutes**:
   Enable the next institute following steps 2-6:
   ```bash
   php artisan rag:institute 2 on
   ```

---

## 9. Emergency Procedures & Instant Kill Switches

### Global Emergency Kill Switch
To immediately disable the AI Study Assistant across all institutes without deploying code:

Set in `.env`:
```dotenv
RAG_ENABLED=false
```
Then clear config cache:
```bash
php artisan config:clear
```
*Result: Every `/api/student/ai-chat` request immediately returns HTTP 503 (`The AI Study Assistant is temporarily disabled`). Frontend gracefully displays friendly translated maintenance notice.*

---

### Per-Institute Emergency Kill Switch
To disable the assistant for a single institute immediately:

```bash
php artisan rag:institute {id} off
```
*Result: Students of that institute immediately receive HTTP 403 (`The AI Study Assistant is disabled for your institute`). Other institutes continue functioning normally.*

---

## 10. Pastoral Care & Safety Response Protocol

When a student question contains direct-intent or first-person distress keywords:
1. The AI model is **never** invoked.
2. The student immediately receives a localized, supportive message with only officially configured crisis helpline numbers (configured via `.env`; no unverified fallback numbers are displayed).
3. The query is persisted with `is_flagged = true` and `status = 'distress_blocked'`.
4. An urgent email notification is dispatched to the institute administrator (`EventNotifier::studentDistressAlert`).
5. Flagged rows are permanently preserved and excluded from the 90-day privacy redaction.
6. Only authenticated administrators can inspect flagged queries via `GET /api/admin/rag/status` or `php artisan rag:status`.

---

## 11. Pre-Launch & Post-Deployment Manual Verification Checklist

Before opening the Study Assistant to live students, execute each verification step below and sign off on completion:

- [ ] **1. Institute Switch & Navigation Guard (`institutes.rag_enabled`)**:
  - Run `php artisan rag:institute` and confirm all institutes default to `OFF (Disabled)`.
  - Log in as a student belonging to a disabled institute.
  - Verify that the **Study Assistant** navigation item is **not** visible in `StudentSidebar`.
  - Navigate directly in browser address bar to `/student/study-assistant`.
  - Confirm the page automatically redirects to `/student/dashboard` (or displays the institute disabled notice) and cannot initiate queries.
  - Run `php artisan rag:institute {id} on`. Refresh student browser; confirm **Study Assistant** appears in the sidebar and opens the chat UI.

- [ ] **2. Non-Hardcoded Helpline Numbers**:
  - Confirm that `.env` helpline keys (`RAG_HELPLINE_PRIMARY_NUMBER`, etc.) are empty by default.
  - Trigger a test distress message (e.g., `"I feel like ending my life"`).
  - Verify the returned pastoral message contains zero unverified numbers.
  - Add verified numbers to `.env` (e.g., `RAG_HELPLINE_PRIMARY_NAME="National Helpline"`, `RAG_HELPLINE_PRIMARY_NUMBER="1926"`).
  - Re-trigger the message and confirm only the configured verified helplines appear.

- [ ] **3. Atomic Daily Quota & Row Locking**:
  - Set `RAG_DAILY_QUESTION_LIMIT=3` in `.env` for testing.
  - Ask 3 questions successively. Verify each succeeds and receives an answer.
  - Ask a 4th question. Verify the UI receives HTTP 429 stating the daily quota has been reached.
  - Check the database: confirm the student has exactly 3 counted rows (`processing`, `answered`, `no_notes_found`, `aborted`) and 0 orphan queries.
  - Verify `APP_TIMEZONE=Asia/Colombo` resets quota calculation at midnight Sri Lanka local time.

- [ ] **4. Child Safety Filters (`SAFETY` & `promptFeedback`)**:
  - Test questions that trigger Gemini safety blocks.
  - Confirm the UI displays the friendly translated safety filter guidance without throwing an uncaught 500 server error.
  - Verify the database row records status `'blocked_safety'`, `is_flagged = true`, and does not increment operational failure metrics.

- [ ] **5. Legacy Font Mojibake & Vision OCR Fallback**:
  - Upload a PDF containing legacy Sri Lankan font text (such as Bamini Tamil or FMAbhaya Sinhala).
  - Run `php artisan rag:inspect {material_id}`.
  - Verify that pages with mojibake quality ratio below threshold are detected by `isTextGarbled()` and automatically routed to Gemini Vision OCR.
  - Confirm the OCR page count and inspected chunks contain readable Sinhala or Tamil script rather than mojibake (`ÀÁÂÃÄ...`).

- [ ] **6. Material Deep Inspection CLI (`rag:inspect`)**:
  - Run `php artisan rag:inspect {material_id}` on a known uploaded note.
  - Verify it prints:
    - Status (`ready`)
    - Total page count
    - Chunk count
    - Pages using OCR fallback
    - Full text of the first 3 chunks.

- [ ] **7. Text Privacy Verification (`laravel.log`)**:
  - Tail `storage/logs/laravel.log` while interacting with the chat assistant.
  - Ask test questions in English, Tamil, and Sinhala.
  - Search `laravel.log` for any fragment of the student's question or answer text.
  - Confirm that **zero** student question or answer text appears in `laravel.log` (only query IDs, statuses, and token counts are logged).
