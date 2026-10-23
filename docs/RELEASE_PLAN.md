# Production Release Plan: TiT Learning Academy RAG & Public Assistant
**Release Version:** Round 7 Final Production Release  
**Branch:** `fixes/round7-final`  
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

The current branch `fixes/round7-final` builds upon `fixes/round6-final` with the following key additions:
- Hybrid retrieval score boost formula (never lowers score; cross-lingual query keeps exact embedding score).
- Multilingual normalizer redesign (suffix stripping, configurable variants, length-bounded fuzzy matching).
- Extracted-text review & correction tool for teachers and admins (with verified page status and audit logging).
- Guarded enum widening for `users.medium` (`sinhala`, `english`, `tamil`) and schedules.
- Script-generated bundle manifest with verified 64-character SHA-256 hashes.
- Production acceptance test checklist (`docs/ACCEPTANCE_TEST.md`).

---

## 2. All Pending Migrations & Comprehensive Risk Analysis

When deploying to the live production server (`titjaffna.lk`), run `php artisan migrate:status` to verify pending migrations. The following is the complete list of all migrations that may run on the live server with their risk profile:

| # | Migration File | Description | Risk Level | Risk Analysis & Safeguards |
| :-: | :--- | :--- | :---: | :--- |
| 1 | `2026_10_12_000000_add_submitted_at_to_assignment_submissions_table.php` | Adds nullable `submitted_at` timestamp | **Very Low** | Additive column with `hasColumn` check; non-breaking for existing submissions. |
| 2 | `2026_10_12_010000_add_subject_to_learning_materials_table.php` | Adds nullable `subject` string column | **Low** | Additive with `hasColumn` guard; existing materials default to null without error. |
| 3 | `2026_10_12_020000_create_material_chunks_table.php` | Creates `material_chunks` table with LONGBLOB embeddings | **Low** | Brand new table; guarded by `hasTable`. No impact on existing tables. |
| 4 | `2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php` | Creates audit & feedback logging tables | **Low** | New logging tables; guarded by `hasTable`. |
| 5 | `2026_10_12_040000_add_rag_status_to_learning_materials_table.php` | Adds `rag_status`, `rag_indexed_at`, `rag_error` | **Low** | Additive status tracking columns; default `'pending'`/`'ready'`. Guarded by `hasColumn`. |
| 6 | `2026_10_12_050000_add_rag_enabled_and_distress_flags.php` | Adds `rag_enabled` and `safety_distress_count` | **Low** | Default `rag_enabled = false` guarantees fail-closed behavior. Guarded by `hasColumn`. |
| 7 | `2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php` | Sets default `rag_enabled = 0` on institutes and adds chunk OCR metadata | **Low** | Ensures strict opt-in per institute. Idempotent column check. |
| 8 | `2026_10_15_030000_add_file_hash_to_learning_materials_table.php` | Adds indexed `file_hash` SHA-256 column | **Low** | Enables document deduplication. Nullable and indexed. |
| 9 | `2026_10_16_000000_cleanup_duplicate_pending_payments.php` | Cleans duplicate pending payment rows before unique constraints | **Medium** | Alters payments; backed by soft delete or status transition. Pre-migration backup mandatory. |
| 10 | `2026_10_19_000001_create_student_index_system.php` | Student index numbers, sequences, and CSV import table | **Medium** | Adds index number sequence logic. Guarded by `hasTable` and `hasColumn`. |
| 11 | `2026_10_19_000002_create_payment_receipt_system.php` | Payment receipt sequence numbering | **Low** | Additive columns for receipt numbering. |
| 12 | `2026_10_21_040000_create_material_pages_table.php` | Creates `material_pages` OCR caching table | **Low** | New table for page-level OCR caching; guarded by `hasTable`. |
| 13 | `2026_10_22_010000_add_public_assistant_enabled_to_institutes_table.php` | Adds `public_assistant_enabled` flag (default false) | **Low** | Additive flag; ensures public assistant is strictly opt-in. |
| 14 | `2026_10_22_020000_create_faqs_table.php` | Creates institutional FAQ table | **Low** | New table; guarded by `hasTable`. |
| 15 | `2026_10_22_030000_create_assistant_visitor_logs_table.php` | Creates visitor session and rate-limit tracking table | **Low** | New table; guarded by `hasTable`. |
| 16 | `2026_10_22_040000_widen_learning_materials_medium_enum.php` | Widens `learning_materials.medium` enum to include `'sinhala'` and `'all'` | **Low** | Widening enum definition; existing rows remain valid. |
| 17 | `2026_10_23_020000_widen_users_and_schedules_medium_enum.php` | Widens `users.medium` (`english`, `tamil`, `sinhala`) and zoom schedules | **Medium-Low** | Alters enum column. Tested thoroughly on MariaDB test database without data loss. |
| 18 | `2026_10_23_030000_add_verification_to_material_pages_table.php` | Adds `teacher_verified`, `last_edited_by`, `last_edited_at` | **Low** | Additive columns for text review & correction tool. Guarded by `hasColumn`. |

---

## 3. Explicit Migration Rollback Procedures (No `--step=12`)

Blindly running `migrate:rollback --step=N` is dangerous in production because the batch number depends on how many migrations ran together. Instead, choose one of the two explicit rollback methods below:

### Method A: Full Database Restoration from Backup (Recommended & Safest)
Prior to running any migration, take an atomic snapshot:
```bash
mysqldump -u titjaffn_admin -p titjaffn_titjaffnas > /backups/db_pre_round7_release.sql
```
If a critical failure occurs during deployment, restore the clean snapshot immediately:
```bash
mysql -u titjaffn_admin -p titjaffn_titjaffnas < /backups/db_pre_round7_release.sql
```

### Method B: Explicit Reverse-Order Path Rollback
If rolling back via Artisan, execute each migration explicitly by file path in exact reverse chronological order:
```bash
cd laravel_api/backend

php artisan migrate:rollback --path=database/migrations/2026_10_23_030000_add_verification_to_material_pages_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_23_020000_widen_users_and_schedules_medium_enum.php
php artisan migrate:rollback --path=database/migrations/2026_10_22_040000_widen_learning_materials_medium_enum.php
php artisan migrate:rollback --path=database/migrations/2026_10_22_030000_create_assistant_visitor_logs_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_22_020000_create_faqs_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_22_010000_add_public_assistant_enabled_to_institutes_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_21_040000_create_material_pages_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_19_000002_create_payment_receipt_system.php
php artisan migrate:rollback --path=database/migrations/2026_10_19_000001_create_student_index_system.php
php artisan migrate:rollback --path=database/migrations/2026_10_16_000000_cleanup_duplicate_pending_payments.php
php artisan migrate:rollback --path=database/migrations/2026_10_15_030000_add_file_hash_to_learning_materials_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_13_060000_update_institutes_rag_enabled_default_and_chunks_ocr.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_050000_add_rag_enabled_and_distress_flags.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_040000_add_rag_status_to_learning_materials_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_030000_create_student_ai_queries_and_feedback_tables.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_020000_create_material_chunks_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_010000_add_subject_to_learning_materials_table.php
php artisan migrate:rollback --path=database/migrations/2026_10_12_000000_add_submitted_at_to_assignment_submissions_table.php
```

---

## 4. Canary Pilot with Real Consenting Students (Zero Test Accounts in Live DB)

To prevent data contamination in the live database, **never insert artificial mock or dummy accounts** into the live environment. The pilot must follow this strict real-world protocol:

### Step 1: Cohort Selection & Consent
- Select a small cohort of 3–5 active Grade 10 students (e.g. studying Tamil or English medium).
- Obtain written parental/student consent for participating in the AI Study Assistant trial.

### Step 2: Normal Account Registration
- The consenting student registers through the standard public registration portal (`https://titjaffna.lk/register`) using their real email, name, and phone number.
- No bypassing of standard registration forms or manual database inserts.

### Step 3: Standard Admin Approval
- The institute administrator logs into the Admin Dashboard (`/admin/students`).
- The administrator verifies the registration, approves the account, assigns the official student index number, and assigns their Grade 10 batch.

### Step 4: Selective Pilot Activation
- Enable RAG for the pilot tenant:
  ```bash
  php artisan rag:institute 1 on
  ```
- Toggle `rag_enabled = true` on the consenting student's profile via admin controls (or via profile settings).
- The student logs in through the standard portal (`/login`), accesses the Study Assistant, and verifies syllabus queries.

### Step 5: Post-Pilot De-provisioning & Cleanup
- When the pilot concludes, toggle `rag_enabled = false` on the pilot account.
- If the account was a temporary evaluation student:
  - Admin deactivates the account via Admin Dashboard (`/admin/students`), ensuring no orphaned test artifacts remain.
  - Query audit logs in `student_ai_queries` remain intact for academic quality review without polluting core student rosters.

---

## 5. Production Assets & Stale File Handling

All production assets and verified 64-character SHA-256 hashes are recorded in `docs/BUNDLE_MANIFEST.md`.
- **Active Entrypoints:** `index.html`, `assets/index-BqsalW5r.css`, `assets/index-sehll5mo.js`.
- **Stale Asset Policy:** When updating production assets in `public_html/assets/`, delete or archive existing files before extracting the new bundle to avoid accumulating dead, unreferenced chunks on the web server.
