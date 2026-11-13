# Per-Institute Integrations Guide (All 7 Providers)

Each institute on the TiT platform manages its own isolated, encrypted credentials across all 7 supported integration providers:
1. **PayHere Online Payments** (`payhere`)
2. **Meta WhatsApp Cloud API** (`whatsapp`)
3. **Outgoing E-mail / SMTP** (`mail`)
4. **Google Gemini AI Assistant** (`gemini`)
5. **Zoom Meetings** (`zoom`)
6. **Google reCAPTCHA v3** (`recaptcha`)
7. **Google Sign-in / OAuth 2.0** (`google`)

This architecture guarantees that tenant institutes **never spend TiT's Gemini quota, consume TiT's Zoom account, or depend on TiT's reCAPTCHA/Google credentials**.

---

## Part 1: Guide for Institute Administrators

Sign in as an institute `admin` and open the management pages from the left sidebar:
- **Payment Settings → Online Payments (PayHere)** (`/admin/settings/online-payments`)
- **Settings → Messaging & E-mail** (`/admin/settings/messaging`)
- **Settings → AI & Security** (`/admin/settings/ai-security`)
- **Zoom → Zoom Accounts** (`/admin/zoom-accounts`)

All secrets and keys are **encrypted at rest** (`APP_KEY`) using authenticated AES-256 encryption. Secrets are **masked** in Blade templates and API outputs (`••••xxxx`). Submitting a form with an empty secret field keeps the existing saved secret unchanged; click **Replace** only when rotating a secret.

---

### 1. Online Payments (PayHere)
- **Settings:** Merchant ID, App ID, Sandbox toggle, Currency (`LKR` or `USD`).
- **Secret:** Merchant Secret, App Secret (masked `••••xxxx`).
- **Features:** Format verification via "Check Configuration", status upgrade to `Verified` on first webhook notification.
- **Notify URL:** `https://<institute-primary-domain>/api/payhere/notify`.
- **Behavior without keys:** Online card payment options are automatically hidden on student forms; cash, bank transfer, and advance payments remain active.

---

### 2. WhatsApp Notifications (Meta Cloud API)
- **Settings:** Phone Number ID, API version (default `v21.0`), Template Name Overrides.
- **Secret:** Permanent System User Access Token (masked `••••xxxx`).
- **Features:** Rate-limited test messaging (maximum 3 test messages per hour per institute).
- **Behavior without keys:** WhatsApp notifications are skipped safely; student alerts continue via e-mail and in-app notifications.

---

### 3. Outgoing E-mail (SMTP)
- **Settings:** Host, Port, Encryption (`TLS` / `SSL` / `None`), Username, From Address, From Name, Reply-To.
- **Secret:** SMTP Password or Google Workspace App Password (masked `••••xxxx`).
- **Features:** Send Test E-mail to logged-in administrator.
- **Behavior without keys:** When `INTEGRATIONS_MAIL_PLATFORM_FALLBACK=true`, falls back to platform SMTP mailer with institute name and contact reply-to branding.

---

### 4. AI Assistant (Google Gemini)
- **Settings:** Visitor Daily Question Cap (minimum 50, institute 1 defaults to 500, others default to 100).
- **Secret:** Google AI Studio Gemini API Key (starts with `AIza`, masked `••••xxxx`).
- **Features:**
  - **Daily Questions Counter:** Real-time counter of `AI questions today: N / cap`.
  - **Test Key Button:** Validates the API key directly with Google Gemini (`https://generativelanguage.googleapis.com/v1beta/models`). Marks status as `Verified` upon success.
- **Behavior without keys:**
  - Public assistant chatbot is disabled (HTTP 403 `{code: "ai_not_configured"}`).
  - Study assistant, OCR ingestion, and translation endpoints return HTTP 422 `{code: "ai_not_configured"}` with an administrator-readable message.
  - Public site hides assistant widgets via `/api/settings` flag `assistant_available`.

---

### 5. Zoom Meetings (Zoom Server-to-Server OAuth)
- **Accounts Table:** Managed under `/admin/zoom-accounts`.
- **Fields:** Host Email, Account ID, Client ID, Max Concurrent Meetings.
- **Secret:** Client Secret (encrypted at rest via `SafeEncryptedString` cast, masked `••••xxxx`).
- **Features:**
  - **Test Connection Button:** Requests an access token and queries `GET /users/me` from Zoom. Shows a `TitNotify` toast with sanitized error reasons and updates integration status (`Verified` / `Failing`).
  - **Multi-Tenant Token Cache:** Cache keys are strictly isolated per account: `zoom:token:{institute_id}:{account_id}`.
- **Behavior without keys:**
  - Schedule creation fails with typed error `zoom_not_configured` ("Add a Zoom account under Settings → Zoom Accounts").
  - Zero outbound HTTP calls are made.

---

### 6. Google reCAPTCHA v3
- **Settings:** Public Site Key, Score Threshold (range 0.1 to 1.0, default 0.5).
- **Secret:** Secret Key (starts with `6L`, masked `••••xxxx`).
- **Features:**
  - **Test Keys Button:** Tests the secret key against Google reCAPTCHA siteverify.
  - Delivered to React frontends dynamically at runtime via `/api/settings` (`recaptcha_site_key`).
- **Behavior without keys (Fallback institutes vs non-fallback):**
  - Registration, login, and contact forms **function without captcha**, but operate under a **strict throttle** (5 submissions / 10 minutes / IP / institute, returning HTTP 429 `too_many_requests`).
  - Public AI assistant is **disabled** to protect against automated cost abuse.

---

### 7. Google Sign-in (OAuth 2.0)
- **Settings:** Client ID (ending with `.apps.googleusercontent.com`).
- **Secret:** Client Secret (starts with `GOCSPX-`, masked `••••xxxx`).
- **Features:**
  - **Authorized Redirect URI:** Read-only display of the exact callback URL with a one-click copy button:
    ```text
    https://<institute-primary-domain>/api/auth/google/callback
    ```
- **Behavior without keys:**
  - Google Sign-in button is hidden on login and registration pages (`/api/settings` flag `google_login_available: false`).
  - Direct hits to `/api/auth/google/redirect` return HTTP 422 `{code: "google_not_configured"}`.

---

## Part 2: Platform Owner & DevOps Guide

### 1. Multi-Tenant Fallback Policy (`config/integrations.php`)

Fallback to `.env` variables is strictly restricted:
```dotenv
# Comma-separated list of institute IDs permitted to fall back to .env platform keys.
# Default is 1 (TiT). Other institutes NEVER fall back to .env under any circumstances.
INTEGRATIONS_ENV_FALLBACK_INSTITUTES=1
```

| Provider | Has Database Row | No DB Row & In Fallback List (Inst 1) | No DB Row & Non-Fallback Institute (Inst 2+) |
| :--- | :--- | :--- | :--- |
| **PayHere** | Uses institute credentials | Uses `.env` `PAYHERE_*` | `null` (online payments disabled) |
| **WhatsApp** | Uses institute token/phone | Uses `.env` `META_WHATSAPP_*` | Skipped safely (no HTTP) |
| **Mail (SMTP)** | Uses custom institute SMTP | Uses platform SMTP | Uses platform SMTP with institute name |
| **Gemini AI** | Uses institute API key | Uses `.env` `GEMINI_API_KEY` | Disabled (HTTP 403/422 `ai_not_configured`, no HTTP) |
| **Zoom** | Uses `ZoomAccount` rows | Uses `.env` `ZOOM_*` virtual account | Error (HTTP 422 `zoom_not_configured`, no HTTP) |
| **reCAPTCHA** | Validates with institute secret | Uses `.env` `RECAPTCHA_*` | Strict throttle (5/10 min), assistant disabled |
| **Google OAuth**| Uses institute client ID/secret | Uses `.env` `GOOGLE_*` | Button hidden, HTTP 422 `google_not_configured` |

---

### 2. Security Key Rotation Steps (CRITICAL)

#### A. Gemini API Key Rotation
> **CRITICAL ACTION REQUIRED:**
> The previous Gemini API key was exposed and must be rotated immediately:
> 1. Open [Google AI Studio](https://aistudio.google.com/) and revoke the exposed API key.
> 2. Create a new Gemini API key.
> 3. Update the key **only in production server `.env`** (`GEMINI_API_KEY=AIza...`) for Institute 1, or input it directly via **Settings → AI & Security** in the admin panel.
> 4. **Never commit any Gemini key to the Git repository.**

#### B. Zoom Client Secret Encryption Command
Live `zoom_accounts` rows containing legacy plaintext client secrets must be encrypted:
```bash
# Dry-run: counts rows to encrypt without writing to database:
php artisan integrations:encrypt-zoom-secrets --dry-run

# Execute: encrypts plaintext secrets at rest using APP_KEY (idempotent):
php artisan integrations:encrypt-zoom-secrets
```

#### C. Importing `.env` Credentials to Database
To import environment variables for Institute 1 into encrypted database storage:
```bash
# Dry-run preview:
php artisan integrations:import-env 1 --dry-run

# Import all 7 providers:
php artisan integrations:import-env 1
```

#### D. Inspecting Integration Status (Masked)
```bash
php artisan integrations:status
php artisan integrations:status --institute=1
```

---

### 3. Super-Admin Panel (`/super-admin`)

- **Overview Matrix:** `/super-admin/institute-integrations-overview` displays real-time operational status for all 7 providers across all institutes (`Verified`, `Configured`, `Failing`, `Using platform fallback`, `Unconfigured`) with sanitized error details and emergency credential revocation. Plaintext secrets are never displayed.
- **Setup Checklist:** `/super-admin/institutes/{record}` includes checklist rows for AI & Security and Zoom accounts.

---

### 4. Firebase Storage Multi-Tenant Isolation Audit

For details on the current Firebase service account, storage security rules, token minting, and cross-tenant risks, consult the full audit report:
👉 [docs/FIREBASE_ISOLATION_AUDIT.md](FIREBASE_ISOLATION_AUDIT.md)


---

## Part 1: Guide for Institute Administrators

Sign in as an institute `admin` and open the settings pages from the left sidebar:
- **Payment Settings → Online Payments (PayHere)** (`/admin/settings/online-payments`)
- **Settings → Messaging & E-mail** (`/admin/settings/messaging`)

All secret keys, passwords, and tokens are **encrypted at rest** (`APP_KEY`) and **write-only** in the browser: once saved, only a masked summary (`••••1234`) is displayed. Submitting a form with an empty secret field keeps the existing saved secret unchanged; click **Replace** only when rotating a secret.

---

### 1. Online Payments (PayHere)

#### Step A: Approve Your Domain & Get Your Merchant Secret
1. Sign in to your [PayHere Merchant Portal](https://www.payhere.lk) (or [PayHere Sandbox](https://sandbox.payhere.lk) for testing).
2. Copy your **Merchant ID** from **Side Menu → Settings → Domains & Credentials** (for example `1228450`).
3. Click **Add Domain / App**:
   - Enter the exact **Domain to approve in PayHere** shown in your TiT admin panel (your institute's primary domain, e.g. `portal.yourinstitute.lk`).
   - Request approval and copy the generated **Merchant Secret** specific to that domain.
4. *(Optional)* If you use PayHere APIs requiring OAuth, copy your **App ID** and **App Secret** from the same screen.

#### Step B: Enter Credentials & Configure Webhook Notify URL
1. In TiT Admin, open **Payment Settings → Online Payments (PayHere)**.
2. Copy the read-only **Notify URL for your PayHere dashboard**:
   ```text
   https://<your-institute-primary-domain>/api/payhere/notify
   ```
   Paste this URL in your PayHere dashboard notification settings if prompted (TiT also sends this `notify_url` automatically with every checkout request).
3. Enter your **Merchant ID**, paste your **Merchant Secret**, select **Currency** (`LKR` or `USD`), and choose **Sandbox (Test Mode)** or **Live (Real Payments)**.
   - Switching to **Live** prompts a confirmation dialog before real student card/QR payments are accepted.
4. Click **Save PayHere Settings**, then click **Check Configuration** to validate the format and generate a local checkout signature (`status = Configured`).
5. Once the first real or sandbox payment webhook is verified by TiT using your Merchant Secret, the status automatically upgrades to **Verified**.

> **If PayHere is not configured:** Online card payments are automatically hidden on your student registration, monthly fee, and wallet top-up screens (`online_payments_enabled = false`), while bank transfer, cash, and advance offline payment workflows continue working normally.

---

### 2. WhatsApp Notifications (Meta Cloud API)

#### Step A: Get Your Phone Number ID & Permanent Access Token
1. Open [Meta for Developers](https://developers.facebook.com/) and create or select a **Business** app with the **WhatsApp** product enabled.
2. In **WhatsApp → API Setup**, select your institute's registered WhatsApp Business number and copy the **Phone Number ID** (digits only, e.g. `104928374650192`).
3. In [Meta Business Settings](https://business.facebook.com/settings):
   - Go to **Users → System Users**, create an **Admin System User**, and assign your WhatsApp Business Account and App with `whatsapp_business_messaging` and `whatsapp_business_management` permissions.
   - Click **Generate New Token**, select **Never expire** (or your rotation policy), and copy the **Access Token**.

#### Step B: Approve Templates in Meta WhatsApp Manager
Meta requires pre-approved message templates for business-initiated notifications. Create and approve templates in **WhatsApp Manager → Message templates** (or map your own approved template names in **Template Name Overrides** on the TiT form):

| Event Key | Default Template Name | Purpose |
| :--- | :--- | :--- |
| `welcome` | `tit_welcome` | Welcome & registration confirmation |
| `payment_receipt` | `tit_payment_receipt` | Official fee payment receipt |
| `payment_reminder` | `tit_payment_reminder` | Monthly tuition reminder |
| `account_deactivated` | `tit_account_deactivated` | Account deactivation notice |
| `general_update` | `tit_general_update` | General alerts & test messages |
| `otp_verification` | `tit_otp_verification` | Security / OTP verification code |

#### Step C: Save & Send a Test Message
1. Open **Settings → Messaging & E-mail** and locate the **WhatsApp (Meta Cloud API)** card.
2. Paste your **Access Token**, enter your **Phone Number ID**, keep **API Version** at `v21.0` (unless instructed otherwise), and optionally fill any **Template Name Overrides**.
3. Click **Save WhatsApp Settings**.
4. Enter a recipient phone number in international **E.164** format (e.g. `+94771234567`) and click **Send Test Message** (rate-limited to **3 test messages per hour per institute**).

---

### 3. Outgoing E-mail (SMTP)

Open **Settings → Messaging & E-mail** and locate the **Outgoing E-mail (SMTP)** card.

#### Example A: Google Workspace / Gmail (App Password)
1. Enable **2-Step Verification** on the Google account (`noreply@yourinstitute.lk` or `@gmail.com`).
2. Visit **Google Account → Security → 2-Step Verification → App passwords**, generate a 16-character **App Password**, and copy it without spaces.
3. Fill in the TiT E-mail form:
   - **SMTP Host:** `smtp.gmail.com`
   - **Port:** `587` (with **Encryption:** `TLS`) or `465` (with **Encryption:** `SSL`)
   - **Username:** `youraddress@gmail.com` (or Google Workspace address)
   - **Password:** *(16-character App Password)*
   - **From Address:** `youraddress@gmail.com`
   - **From Name:** `Your Institute Name`
   - **Reply-To Address:** `support@yourinstitute.lk`
4. Click **Save E-mail Settings**, then click **Send Test E-mail** to send a verification message to your logged-in admin e-mail address.

#### Example B: cPanel / Custom Domain Mailbox
1. In **cPanel → Email Accounts**, create a mailbox such as `noreply@yourinstitute.lk` and open **Connect Devices** to confirm your mail server hostname.
2. Fill in the TiT E-mail form:
   - **SMTP Host:** `mail.yourinstitute.lk` *(or the server hostname shown in cPanel)*
   - **Port:** `465` (**Encryption:** `SSL`) or `587` (**Encryption:** `TLS`)
   - **Username:** `noreply@yourinstitute.lk`
   - **Password:** *(mailbox password)*
   - **From Address:** `noreply@yourinstitute.lk`
   - **From Name:** `Your Institute Name`
   - **Reply-To Address:** `info@yourinstitute.lk`
3. Click **Save E-mail Settings**, then **Send Test E-mail**.

---

## Part 2: Guide for the Platform Owner (Super-Admin & DevOps)

### 1. Multi-Tenant Fallback Policy (`config/integrations.php`)

Money and WhatsApp messages **never** fall back to the platform owner's account for tenant institutes:

| Provider | DB Row `configured` or `verified` | DB Row `unconfigured` or `failing` | No DB Row Exists |
| :--- | :--- | :--- | :--- |
| **PayHere** | Uses institute's encrypted DB credentials | Returns `null` (`online_payments_enabled = false`) | Falls back to `.env` **ONLY** if institute ID is listed in `INTEGRATIONS_ENV_FALLBACK_INSTITUTES` (default: `1`). All other institutes get `null` (422 `online_payments_unavailable`). |
| **WhatsApp** | Uses institute's encrypted DB token & phone ID | Skips WhatsApp channel safely (no exception) | Falls back to `.env` **ONLY** if institute ID is listed in `INTEGRATIONS_ENV_FALLBACK_INSTITUTES` (default: `1`). All other institutes log once per day (`WhatsApp not configured for institute N`) and continue via e-mail/in-app. |
| **E-mail (SMTP)** | Uses institute's encrypted DB SMTP transport, `From`, and `Reply-To` | If `failing` or `unconfigured` and `INTEGRATIONS_MAIL_PLATFORM_FALLBACK=true`, uses platform mailer with institute branding | When `INTEGRATIONS_MAIL_PLATFORM_FALLBACK=true` (default), uses platform SMTP transport while overriding **From Name** to the institute's name and **Reply-To** to the institute's contact e-mail. |

#### Environment Variables (`.env`)
```dotenv
# Comma-separated institute IDs permitted to fall back to .env PayHere & WhatsApp credentials
# when no DB row exists (default: 1 for TiT legacy compatibility; set empty once migrated to DB).
INTEGRATIONS_ENV_FALLBACK_INSTITUTES=1

# Allow institutes without custom SMTP to send transactional mail via platform SMTP
# with the institute's From Name and Reply-To address.
INTEGRATIONS_MAIL_PLATFORM_FALLBACK=true
```

### 2. Artisan CLI Commands

Run these commands from `laravel_api/backend`:

1. **Inspect Integration Status Across All Institutes (Masked):**
   ```bash
   php artisan integrations:status
   php artisan integrations:status --institute=1
   ```
   Prints a table of `Institute × Provider × Status × Source (db|env|none) × Masked Credentials (••••1234) × Last Verified`. Never prints plaintext secrets.

2. **Import Legacy `.env` Credentials into Encrypted DB Storage:**
   ```bash
   # Preview what would be imported (prints masked ••••1234 values only, writes nothing):
   php artisan integrations:import-env 1 --dry-run

   # Copy .env PayHere, WhatsApp, and SMTP credentials into institute #1's encrypted DB rows:
   php artisan integrations:import-env 1
   ```
   Once imported and verified for institute `1`, you can remove `PAYHERE_*` and `META_WHATSAPP_*` from `.env` and set `INTEGRATIONS_ENV_FALLBACK_INSTITUTES=` (empty).

3. **Send an Authenticated CLI WhatsApp Test Message:**
   ```bash
   php artisan integrations:test-whatsapp 1 +94771234567
   ```
   *(Replaces the removed unauthenticated `/api/test-admin-whatsapp` route.)*

### 3. Super-Admin Panel (`/super-admin`)
- **Canonical Panel Path & Live `.htaccess` Routing:** The Filament Super-Admin panel path is single-sourced from `config('filament.path')` (default `super-admin`). Ensure the live root `.htaccess` backend routing rule includes both `super-admin` and `super-panel` (any legacy `/super-panel/*` request is redirected via HTTP 301 to `/super-admin/*` by Laravel):
  ```apache
  RewriteCond %{REQUEST_URI} ^/(admin|api|super-admin|super-panel|livewire|receipts|sitemap\.xml|robots\.txt) [NC]
  ```
- **Administration → Institute Integrations** (`/super-admin/institute-integrations-overview`): Read-only status matrix across all institutes (`verified`, `configured`, `failing`, `using platform fallback`, `unconfigured`) with sanitised error summaries and a **Clear credentials** emergency action. Plaintext secrets are never shown.
- **Platform Settings** (`/super-admin/platform-settings`): Clearly labelled *"Platform defaults (only used where fallback is allowed)"*.
