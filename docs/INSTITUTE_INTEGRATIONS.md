# Per-Institute Integrations Guide (PayHere, WhatsApp & Outgoing E-mail)

Each institute on the TiT platform manages its own encrypted **PayHere**, **Meta WhatsApp Cloud API**, and **Outgoing E-mail (SMTP)** credentials so student fee collections and automated messages are isolated per tenant.

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
- **Administration → Institute Integrations** (`/super-admin/institute-integrations-overview`): Read-only status matrix across all institutes (`verified`, `configured`, `failing`, `using platform fallback`, `unconfigured`) with sanitised error summaries and a **Clear credentials** emergency action. Plaintext secrets are never shown.
- **Platform Settings** (`/super-admin/platform-settings`): Clearly labelled *"Platform defaults (only used where fallback is allowed)"*.
