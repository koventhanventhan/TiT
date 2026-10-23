# Production Release Plan: TiT Learning Academy RAG & Public Assistant
**Release Version:** Round 6 Final Production Release  
**Branch:** `fixes/round6-final`  
**Target Environment:** cPanel / Apache / MariaDB & Static Frontend Hosting  

---

## ⚠️ CRITICAL DATABASE INTEGRITY WARNING
> [!CAUTION]
> **NEVER run any command or automated test that wipes, refreshes, or resets the production/development database (`titjaffn_titjaffnas`)!**  
> Specifically:
> - **NEVER** run `php artisan migrate:fresh`
> - **NEVER** run `php artisan migrate:refresh`
> - **NEVER** run `php artisan migrate:reset`
> - **NEVER** run tests with `RefreshDatabase` against a MySQL connection pointing to `titjaffn_titjaffnas`.
> Automated test suites must strictly use SQLite `:memory:` or a dedicated empty test database (e.g. `tit_rag_test_round6`).

---

## 1. Branch Commits & Lineage

The current branch `fixes/round6-final` contains the integrated RAG Study Assistant, Unified Public Assistant, Teacher/Admin tooling, and Round 1–6 verification fixes:

| Commit Hash | Description |
| :--- | :--- |
| `64a5a0b` | `docs(deploy)`: update deployment guide with strict ordering, Vercel/reCAPTCHA docs, and sync production bundle |
| `0c78e8e` | `fix(safety)`: remove 1990 reference and test helpline configuration isolation |
| `38c1c70` | `fix(rag)`: rewrite Rule 7 to prohibit outside facts and add grounding verification post-check |
| `aac5242` | `fix(security)`: configure env-driven TRUSTED_PROXIES with spoofed header rate limit tests |
| `ac5f30a` | `feat(ui)`: ensure fail-safe feature flag handling for study assistant and public widget |
| `c8ce8eb` | `feat(materials)`: widen learning_materials medium enum with sinhala and all, with MariaDB tests |
| `7f59b0d` | `Merge remote-tracking branch 'origin/main' into fixes/round5-verification` |
| `d41f945` | `fix(admin)`: resolve route shadowing for student index tools and fix import route name in student list |
| `a5891b0` | `feat(students)`: bulk index number assignment, grade-batch mapping, and CSV import |
| `9e13eda` | `docs`: add comprehensive step-by-step production deployment guide |
| `e17c85c` | `build(assets)`: update production bundle and student mobile test mock for RAG |
| `71b7832` | `fix(test)`: bypass DNS lookup and time-limit clamping in testing environment |
| `02d1589` | `build`: rebuild production bundle with multilingual fonts and assistant widgets |
| `e3f7eaa` | `fix(migrations)`: add comprehensive hasTable and hasColumn idempotency guards to all RAG migrations |
| `e9d7ce4` | `Merge feature/teacher-admin-tooling-polish into merge/rag-full-integration` |

---

## 2. Pending Database Migrations (12 RAG Migrations)

Run `php artisan migrate:status` to verify pending migrations before executing `migrate --force`.  
The 12 RAG migrations introduced in this release are:

1. `2026_10_12_010000_add_subject_to_learning_materials_table.php` (adds `subject` varchar)
2. `2026_10_12_020000_create_material_chunks_table.php` (creates chunks table with 768-dim embeddings)
3. `2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php` (creates audit and feedback logging)
4. `2026_10_12_040000_add_rag_status_to_learning_materials_table.php` (adds `rag_status`, `rag_indexed_at`, `rag_error`)
5. `2026_10_12_050000_add_rag_enabled_and_distress_flags.php` (adds student safety distress flags & institute rag_enabled)
6. `2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php` (sets default `rag_enabled = 0` and adds OCR metadata)
7. `2026_10_15_030000_add_file_hash_to_learning_materials_table.php` (adds SHA256 document deduplication hash)
8. `2026_10_21_040000_create_material_pages_table.php` (creates page-level OCR cache table)
9. `2026_10_22_010000_add_public_assistant_enabled_to_institutes_table.php` (adds `public_assistant_enabled` default false)
10. `2026_10_22_020000_create_faqs_table.php` (creates public FAQ table)
11. `2026_10_22_030000_create_assistant_visitor_logs_table.php` (creates anonymized visitor rate-limiting audit log)
12. `2026_10_22_040000_widen_learning_materials_medium_enum.php` (widens medium enum to `'english','tamil','both','sinhala','all'`)

*(Note: Other migrations committed between Oct 16 and Oct 20 such as `create_student_index_system` and `create_payment_receipt_system` will also be executed if not already migrated).*

---

## 3. Six-Stage Rollout Plan

```
[STAGE A: Hotfix Controllers]
              ↓
[STAGE B: Backend with Features OFF]
              ↓
[STAGE C: OLD Frontend Verification]
              ↓
[STAGE D: NEW Frontend Bundle Deployment]
              ↓
[STAGE E: Canary Pilot - Institute 1 Study Assistant]
              ↓
[STAGE F: Public Assistant Activation]
```

---

### STAGE A: Hotfix Controllers Deployment
**Goal:** Prevent active student Zoom join and Assignment submission failures caused by strict unwhitelisted request validation.

- **Exact Files:**
  - `laravel_api/backend/app/Http/Controllers/Api/StudentZoomController.php`
  - `laravel_api/backend/app/Http/Controllers/Api/StudentAssignmentController.php`
  *(Commit `75eca66` from branch `hotfix/zoom-link-leak`)*
- **Upload / Deploy Command:**
  Copy both controller files to `laravel_api/backend/app/Http/Controllers/Api/`.
- **Verification Checks:**
  1. Test Student Zoom Endpoint:
     ```bash
     curl -i -H "Authorization: Bearer <STUDENT_TOKEN>" \
       https://titjaffna.lk/laravel_api/backend/public/api/student/zoom/classes
     ```
     *Expectation:* HTTP 200 with classes array.
  2. Test Student Assignment Endpoint:
     ```bash
     curl -i -H "Authorization: Bearer <STUDENT_TOKEN>" \
       https://titjaffna.lk/laravel_api/backend/public/api/student/assignments
     ```
     *Expectation:* HTTP 200 with assignments list.
- **Rollback:**
  Revert the two controller files from backup:
  ```bash
  git checkout HEAD~1 -- app/Http/Controllers/Api/StudentZoomController.php app/Http/Controllers/Api/StudentAssignmentController.php
  ```

---

### STAGE B: Backend with Features OFF (Safe Baseline)
**Goal:** Upgrade backend database schema, models, services, and routes while keeping RAG and Public Assistant strictly disabled.

- **Exact Files:**
  - `laravel_api/backend/app/`
  - `laravel_api/backend/bootstrap/app.php`
  - `laravel_api/backend/config/rag.php`
  - `laravel_api/backend/config/database.php`
  - `laravel_api/backend/database/migrations/`
  - `laravel_api/backend/routes/api.php`
- **Configuration Check (`.env`):**
  Ensure safe defaults are configured:
  ```dotenv
  RAG_ENABLED=false
  RAG_PUBLIC_ENABLED=false
  TRUSTED_PROXIES=
  ```
- **Deployment Commands:**
  ```bash
  cd laravel_api/backend
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate:status
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  ```
  *Note on Enum Migration:* Widening `learning_materials.medium` (`enum('english','tamil','both','sinhala','all')`) is an `INPLACE` metadata update without rebuilding data rows. On production MariaDB, expect this step to take ~0.15s–0.25s (around 0.2s) due to table metadata locking and dictionary cache sync.
- **Verification Checks:**
  1. Verify All Migrations Ran:
     ```bash
     php artisan migrate:status
     ```
     *Expectation:* All 12 RAG migrations marked `Ran`.
  2. Verify Safe Defaults (Features OFF):
     ```bash
     curl -i -H "Authorization: Bearer <STUDENT_TOKEN>" \
       https://titjaffna.lk/laravel_api/backend/public/api/student/ai/status
     ```
     *Expectation:* Returns `{"rag_enabled": false}` or HTTP 503.
  3. Verify Public Assistant Disabled:
     ```bash
     curl -i https://titjaffna.lk/laravel_api/backend/public/api/assistant/settings
     ```
     *Expectation:* Returns `{"public_assistant_enabled": false}`.
- **Rollback:**
  ```bash
  php artisan migrate:rollback --step=12
  ```

---

### STAGE C: Old Frontend Check (Backward Compatibility)
**Goal:** Confirm the existing live frontend functions without any regressions against the upgraded backend before deploying the new frontend assets.

- **Checklist on Existing Live Frontend (`/`):**
  - [ ] **Student Login (`/login`):** Successful authentication.
  - [ ] **Student Dashboard (`/student/dashboard`):** Loads cleanly with zero exceptions.
  - [ ] **Zoom Classes (`/student/classes`):** Join button works as expected.
  - [ ] **Materials (`/student/materials`):** Existing notes in English/Tamil/Both render and download.
  - [ ] **Assignments (`/student/assignments`):** Submission modal opens.
  - [ ] **Payments (`/student/payments`):** History table renders.
  - [ ] **Teacher Materials (`/teacher/materials`):** Notes table and upload modal function.
  - [ ] **Admin Student List (`/admin/students`):** Student approvals and grade promotions work.
  - [ ] **Browser Console:** Zero 500 errors or uncaught JS exceptions.
- **Rollback:**
  If any regression is observed, investigate backend error logs (`storage/logs/laravel.log`) before proceeding to Stage D.

---

### STAGE D: New Frontend Bundle Deployment
**Goal:** Deploy the compiled modern React single-page application bundle.

- **Exact Files:**
  - `index.html` (Web root entrypoint)
  - `assets/*` (Compiled JS and CSS bundles)
- **Asset Manifest & SHA256 Digests:**

| File Name | Size (Bytes) | SHA256 Digest |
| :--- | :--- | :--- |
| `index.html` | 1,968 | `283ED20E24B4195F3472EE7E20DED30BAE65F637C8EC55E1C38BDCE3A0E5A7DE` |
| `assets/index-CViz80YL.js` | 709,542 | `46B4A1015CCFD2C2A4691158EE515CE1B57B4BD3C53DF8D316A7B0C5A8510876` |
| `assets/index-BqsalW5r.css` | 262,057 | `A32146BF254C8B392C5E1D2F2EB500E8CBE833B32DCC0DEF459CBDD39B3FCF9D` |
| `assets/StudentStudyAssistant-CNVblyJo.js` | 20,183 | `0CDCB91B6C1390D21743CC4BD7D30CBA101BAA23C3905BA6CF59D5D3FA9F8432` |
| `assets/StudentStudyAssistant-BPjtZF2k.css` | 15,612 | `A483FD4EC909D678C6C4C1BD0E5F779ECF96A9FDC648050FC390DA658854C884` |
| `assets/StudentDashboard-Ca22NXPO.js` | 35,032 | `B4D450ECBB98BF1385A3AF042B43D4BF221926072AB485E87160A85E86D742F8` |
| `assets/SidebarShell-B6sC2q3Q.js` | 9,818 | `D651DEFB21086E53807A0624F88E2FDA8B4FE77243B4FDFBD098FAF0C875FA75` |
| `assets/SidebarShell-DE6MP47N.css` | 13,152 | `A406E1CC9C9DDDB98F410C69F48EC5486F5D472DE7F9A11FE91C91D1BA5BFFB80` |

- **Verification of Bundle Cleanliness:**
  - [x] Zero references to `localhost:*`
  - [x] Zero references to `127.0.0.1:*`
  - [x] API URL points to `https://titjaffna.lk/laravel_api/backend/public/api`
- **Fail-Safe UI Verification:**
  - With features OFF, verify "Study Assistant" link is hidden from student sidebar.
  - Verify floating assistant widget is hidden from public homepage (`/`).
- **Rollback:**
  Restore prior `index.html` and `assets/` from backup archive (`/backups/app_pre_rag/assets_backup.tar.gz`).

---

### STAGE E: Canary Pilot - Enable Institute 1 Study Assistant
**Goal:** Enable the RAG Study Assistant for a single pilot institute (Institute 1) and verify live student queries.

- **Activation Commands:**
  ```bash
  cd laravel_api/backend
  php artisan rag:institute 1 on
  php artisan tinker --execute="
  \App\Models\User::where('email', 'test_rag_student_g8@tit.edu')->update(['rag_enabled' => true, 'role' => 'user', 'admin_confirmed_at' => now()]);
  "
  php artisan config:cache
  ```
- **Verification Checks:**
  1. Verify Gemini Connectivity:
     ```bash
     php artisan rag:ping
     ```
     *Expectation:* Embedding, LLM, and OCR models report healthy.
  2. Student Login & Chat:
     - Log in as `test_rag_student_g8@tit.edu` (`http://localhost:4000/student/study-assistant`).
     - Ask syllabus question: *"மங்கல வாத்தியங்கள் எவை?"*
     - Verify word-by-word streaming response and citation chips ([1], [2]).
  3. Grounding Verification (Rule 7):
     - Ask out-of-context question (e.g. asking for facts not in uploaded notes).
     - Verify safe localized not-found message is returned without hallucinations.
  4. Student Distress Safety:
     - Submit distress query (e.g. expressing self-harm or deep distress).
     - Verify crisis helplines (1926 National Mental Health Helpline, 1333 CCCline) display immediately and query is logged with distress flag.
- **Rollback:**
  ```bash
  php artisan rag:institute 1 off
  ```

---

### STAGE F: Public Assistant Activation
**Goal:** Activate the Unified Public Assistant widget on the public website with reCAPTCHA v3 protection and dedicated contact info.

- **Configure Contact Details (5 Dedicated Keys in `site_settings`):**
  ```bash
  cd laravel_api/backend
  php artisan tinker --execute="
  \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_phone'], ['value' => '0212223344', 'group' => 'assistant_public_contact']);
  \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_whatsapp'], ['value' => '0771234567', 'group' => 'assistant_public_contact']);
  \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_email'], ['value' => 'info@tit.edu', 'group' => 'assistant_public_contact']);
  \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_address'], ['value' => 'Jaffna, Sri Lanka', 'group' => 'assistant_public_contact']);
  \App\Models\SiteSetting::updateOrCreate(['key' => 'assistant_public_contact_hours'], ['value' => 'Monday - Saturday 8:00 AM - 5:00 PM', 'group' => 'assistant_public_contact']);
  "
  ```
- **Activation Command:**
  ```bash
  php artisan rag:public 1 on
  ```
  *(The command will strictly refuse to activate if contact settings or crisis helplines are missing).*
- **Verification Checks:**
  1. Open Incognito window at `http://localhost:4000/`.
  2. Floating circular widget appears at bottom-right.
  3. General inquiry: *"How do I enroll?"* -> Instant FAQ response.
  4. Academic query: In public mode, academic question returns locked message directing visitor to `/register`.
  5. reCAPTCHA Fail-Closed Verification: In staging/production, requests without valid token or with low score are rejected with HTTP 422/503.
  6. Rate Limit Check: 11 rapid requests trigger HTTP 429 after 10 requests per minute.
- **Rollback:**
  ```bash
  php artisan rag:public 1 off
  ```
  Or global kill switch: set `RAG_PUBLIC_ENABLED=false` in `.env` and run `php artisan config:clear`.
