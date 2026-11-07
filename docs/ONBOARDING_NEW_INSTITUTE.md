# Onboarding a New Institute — Owner & First-Admin Runbook

This guide walks the **platform owner** (and the new institute's first administrator) through launching a new institute on the shared platform in one guided workflow — without touching database tables, running terminal commands, or deploying a second copy of the codebase.

---

## Before You Start (One-Time Live Server Check)

Make sure the live server's root `/home/titjaffn/public_html/.htaccess` contains **`super-admin`** in its Laravel backend routing rule (`.htaccess` on the live server is maintained manually so deployments never overwrite it):

```apache
RewriteCond %{REQUEST_URI} ^/(admin|api|super-admin|super-panel|livewire|receipts|sitemap\.xml|robots\.txt) [NC]
```

Also ensure `.env` in `laravel_api/backend/.env` has `TENANT_SERVER_IPS` set to your cPanel server's public IPv4 address (for example `TENANT_SERVER_IPS=203.0.113.55`) so the **Check DNS now** button can compare live `A` records automatically.

---

## Step 1 — Create the Institute in the Super-Admin Wizard

1. Sign in as a `super_admin` at `https://titjaffna.lk/super-admin/login`.
2. In the left sidebar, click **Institutes**, then click **New institute** (top right) to open the 6-step wizard:
   1. **Institute**: Enter the official **Institute name**, **Short brand name** (shown in the header, receipts, and outgoing e-mails), optional **Logo** (PNG/JPG/WebP up to 2 MB), and optional public **Contact e-mail** and **Contact phone**.
   2. **Website address**: Enter the institute's custom domain (for example `northernacademy.lk` — `https://` and `www.` are stripped automatically), or tick **I'll add it later** if the domain is not ready yet. You can click **Check DNS now** immediately or wait until Steps 2–3 are done.
   3. **Plan**: Choose the subscription plan (student and teacher limits are shown in the summary card) and set the **Subscription expiry date** using **30 days (trial)**, **1 year**, or **Custom** date.
   4. **First admin**: Keep **Create the first admin now** ticked and enter the new administrator's **Full name**, **E-mail address** (must not already be used by any other account on the platform), and **Phone number**.
   5. **Look & feel**: Pick a colour theme (**Royal**, **Ocean**, or **Emerald**) and confirm the **Student index prefix** (2–6 uppercase letters/digits, auto-suggested from the institute name) and **Index number format** (**By grade** `NSA/08/001` or **By batch year** `NSA/28/100`).
   6. **Review & create**: Review all choices on one screen and click **Create institute**.
3. On the **Institute ready** screen:
   - Click **Copy password** (or **Copy both**) inside the amber **One-time admin sign-in details** box and store the password safely right away.
   - Tick **I have saved this password in a safe place** — the password is generated once, never stored in plain text, never logged, and never sent by e-mail or WhatsApp.

---

## Step 2 — Point the Domain at Your Server (Domain Registrar)

Ask the institute owner (or open your domain registrar's DNS control panel, such as LK Domain Registry, Cloudflare, Namecheap, or GoDaddy) to add two **A records** pointing to your server IP:

| Type | Host / Name | Points To (Value) | TTL |
| :--- | :--- | :--- | :--- |
| `A` | `@` *(root domain, e.g. `northernacademy.lk`)* | Your cPanel server IPv4 address | Automatic / `3600` |
| `A` | `www` *(or `CNAME` → `northernacademy.lk`)* | Your cPanel server IPv4 address | Automatic / `3600` |

> **Cloudflare tip:** Start with **DNS only** (grey cloud) until cPanel AutoSSL issues the HTTPS certificate in Step 4.

---

## Step 3 — Add the Domain as an Alias / Parked Domain in cPanel

> **Important:** This step is done by the platform owner inside **cPanel**, not inside the Super-Admin panel. Do **not** create a separate Laravel installation or a separate `public_html/subfolder`.

1. Log in to **cPanel** on the hosting server.
2. Open **Domains** (or **Aliases / Parked Domains** in older cPanel themes) and click **Create A New Domain**.
3. Enter the bare domain name (for example `northernacademy.lk`).
4. **Share Document Root:** Make sure **"Share document root (`/home/titjaffn/public_html`) with `titjaffna.lk`"** is **TICKED** (checked).
   - Both `titjaffna.lk` and the new institute's domain **must** point to the exact same folder: `/home/titjaffn/public_html`.
   - Laravel inspects the incoming `Host` header automatically and serves only that institute's data, branding, and settings.
5. Click **Submit**.

---

## Step 4 — Issue the HTTPS Certificate (AutoSSL)

1. In cPanel, open **Security → SSL/TLS Status**.
2. Select `northernacademy.lk` and `www.northernacademy.lk`, then click **Run AutoSSL**.
3. Wait 1–5 minutes until both show a green padlock (**AutoSSL Domain Validated**).
   - If AutoSSL says DNS does not resolve yet, wait 10–30 minutes for registrar DNS propagation and click **Run AutoSSL** again.

---

## Step 5 — Verify DNS in Super-Admin ("Check DNS now")

1. Return to **Super-Admin → Institutes** and click **Checklist** (or open the **Institute ready** screen) for the new institute.
2. Next to **Website domain**, click **Check DNS now**.
3. When DNS points to your server IP, the badge turns green (**Verified — points to this server**) and records the verification timestamp.
   - If you skipped adding a domain during the wizard, click **Add domain** on the Setup checklist first, save the domain, and then click **Check DNS now**.

---

## Step 6 — Sign In as the New Institute Admin

1. Click **Open this institute's admin** on the Setup checklist — or open `https://<new-domain>/admin/login` directly in an incognito/private browser window.
2. Sign in with the admin e-mail address and the one-time password saved in Step 1.
3. Confirm that the dashboard opens cleanly with **0 students**, **0 payments**, and the new institute's own name and colour theme.

---

## Step 7 — Configure Institute Settings (Payments, Messaging & Appearance)

Inside the new institute's Admin Panel (`https://<new-domain>/admin`):

1. **Online Payments (`Payment Settings → Online Payments` / `/admin/settings/online-payments`)**
   - Optional: only needed if this institute wants online card/QR checkout via PayHere.
   - Until configured, online card checkout is automatically hidden for this institute's students while bank-slip and cash payments work out of the box.
   - Approve `<new-domain>` in the institute's own PayHere Merchant account, paste their **Merchant ID** and **Merchant Secret**, save, and click **Check Configuration**. (Full guide: `docs/INSTITUTE_INTEGRATIONS.md`.)
2. **WhatsApp & Outgoing E-mail (`Settings → Messaging & E-mail` / `/admin/settings/messaging`)**
   - Enter the institute's **Meta WhatsApp Cloud API** Phone Number ID + Permanent Access Token (if using automated WhatsApp notifications) and click **Send Test Message**.
   - Enter the institute's **Outgoing E-mail (SMTP)** credentials (Gmail App Password or cPanel mailbox) and click **Send Test E-mail**.
   - Even before custom SMTP is added, outgoing system e-mails use the new institute's own brand name and reply-to address — never TiT's name.
3. **Appearance & Public Website (`Settings → Appearance` and `Settings → General / Home / Contact / Footer`)**
   - Upload or update the institute logo, hero banner text, contact address, map embed, Privacy Policy, Terms & Conditions, and Refund Policy.

---

## Step 8 — What the New Institute Admin Should Do First

Share this short checklist with the new institute administrator once you hand over their login:

1. **Change your password immediately** via the profile menu in the top-right corner of `/admin`.
2. **Set up Grades & Subjects** under **Academic Management** (add grades, subjects, and monthly tuition fees).
3. **Add Teachers** under **Teachers → Add New Teacher** and assign them to their subjects/timetables.
4. **Create Class Schedules / Zoom Links** under **Classes / Timetable**.
5. **Review Public Pages** by opening `https://<new-domain>/` and `/register` on desktop and mobile to confirm logos, contact phone/e-mail, and policies look right.
6. **Test Student Registration** by submitting one test registration at `https://<new-domain>/register` and approving/confirming an offline or sandbox payment from `/admin/students`.

---

## Troubleshooting ("Something Went Wrong")

| Symptom / Error Message | Cause | Fix |
| :--- | :--- | :--- |
| **Opening `<new-domain>` shows the TiT website** (or shows a default cPanel "Index of /" / CGI page) | Either (a) the domain in cPanel was pointed to a separate folder instead of `/home/titjaffn/public_html`, or (b) `<new-domain>` was accidentally added to `TENANT_FALLBACK_HOSTS` in `.env`. | 1. In **cPanel → Domains**, confirm the domain's Document Root is `/home/titjaffn/public_html`.<br>2. In **Super-Admin → Institutes → Edit → Domains**, confirm `<new-domain>` belongs to the new institute (not Institute #1). |
| **`404 Unknown institute` (or JSON `{"message":"Unknown institute","code":"tenant_not_found"}`)** | The domain points to the server, so Apache + Laravel received the request, **but** there is no matching row in the institute's **Domains** list (for example, "I'll add it later" was used or there is a typo in the domain). | Open **Super-Admin → Institutes → Checklist** for the institute, click **Add domain**, enter the exact domain (`example.lk`), and save. Domain cache clears automatically. |
| **`403 Institute access is suspended` or `403 Institute subscription has expired`** | Either the institute's status was set to `suspended`/`inactive`, or its `expires_at` trial/subscription date is in the past. | Open **Super-Admin → Institutes → Edit** for that institute: set **Status** to `Active` and extend the **Subscription expiry date** to a future date, then save. |
| **`This e-mail is already used by another account on the platform – use a different address`** | User e-mail addresses are globally unique across the platform (`users.email`), so an existing admin, teacher, or student already uses that e-mail. | Enter a dedicated e-mail address for the new institute's admin (for example `admin@<new-domain>` or a separate mailbox). |
| **Clicking `/super-admin` on the live domain shows a blank React page or 404** | The live server's manual `/home/titjaffn/public_html/.htaccess` still only lists `super-panel` and does not yet include `super-admin`. | Edit `/home/titjaffn/public_html/.htaccess` on cPanel and update the backend `RewriteCond` line to:<br>`RewriteCond %{REQUEST_URI} ^/(admin|api|super-admin|super-panel|livewire|receipts|sitemap\.xml|robots\.txt) [NC]` |
| **Forgot or lost the one-time admin password** | For security, initial and reset passwords are shown only once and never stored in plain text or logs. | Open **Super-Admin → Institutes → Checklist** for that institute, click **Reset password** on the **First admin account** row, confirm, and copy the newly generated one-time password. |
| **"Check DNS now" says `Points elsewhere` or `Not found`** | Registrar DNS `A` record has not been saved yet, points to a registrar parking IP / Cloudflare proxy IP, or DNS propagation is still in progress. | Verify `@` and `www` `A` records at the registrar match `TENANT_SERVER_IPS`, wait 10–30 minutes, and click **Check DNS now** again. (Unverified status never blocks admin login once real DNS resolves.) |
| **Online card payment option does not appear on `/register` or student dashboard** | PayHere credentials are not configured (or failed check) for this institute — by design, tenant institutes never fall back to TiT's PayHere merchant account. | Log in to `https://<new-domain>/admin/settings/online-payments`, save the institute's own PayHere **Merchant ID** and **Merchant Secret**, and click **Check Configuration**. |

