# Runbook: Adding a Customer's Custom Domain to TiT SaaS

This runbook describes the step-by-step operational procedure for binding a tuition institute customer's custom domain (e.g. `abc-tuition.lk`, `xyz.lk`) to the TiT platform.

All institutes share the **same codebase, same database, and same document root (`public_html`)**. The incoming HTTP `Host` header resolves the institute dynamically at runtime.

---

## Architecture Overview

```
Customer Domain (e.g. abc-tuition.lk)
        │ (DNS A Record)
        ▼
Server IP / LiteSpeed Web Server
        │
        ├── Document Root: public_html (SAME as titjaffna.lk)
        │
        ├── Root .htaccess (strips www., passes static assets / front-controller)
        │
        └── Laravel Middleware: ResolveTenant
                │
                ├── Normalizes host (e.g. abc-tuition.lk)
                ├── Queries `institute_domains` table (cached 5 min)
                └── Binds Institute #X to Tenant Context & Global Scope
```

---

## Step 1: Customer DNS Configuration

Have the institute's domain registrar configure DNS records pointing to the hosting server IP:

1. **A Record (Apex Domain)**:
   - Name: `@` (or `abc-tuition.lk`)
   - Type: `A`
   - Value: `<SERVER_IP>` (e.g. `162.241.xxx.xxx`)
   - TTL: `300` or `Automatic`

2. **CNAME or A Record (`www` Subdomain)**:
   - Name: `www`
   - Type: `CNAME`
   - Value: `abc-tuition.lk` (or an `A` record pointing to the same `<SERVER_IP>`)

---

## Step 2: cPanel / LiteSpeed Domain Alias Configuration

> **CRITICAL REQUIREMENT**:
> The domain must point to the **exact same Document Root** (`public_html`) as the primary domain. Never assign it to a subfolder like `public_html/abc-tuition.lk`.

### Instructions in cPanel:
1. Log into **cPanel**.
2. Navigate to **Domains** -> **Domains** (or **Aliases** / **Parked Domains** on older cPanel themes).
3. Click **Create A New Domain**.
4. Enter the customer's domain name: `abc-tuition.lk`.
5. **Deselect / Uncheck** *"Share document root (public_html) with 'titjaffna.lk'"* if prompted, BUT ensure the **Document Root** field is explicitly set to:
   ```
   public_html
   ```
6. Click **Submit**.

---

## Step 3: SSL Certificate Issuance (AutoSSL / Let's Encrypt)

1. In cPanel, navigate to **SSL/TLS Status**.
2. Search for the customer's domain (`abc-tuition.lk` and `www.abc-tuition.lk`).
3. Click **Run AutoSSL** for the selected domains.
4. AutoSSL will automatically verify the HTTP DCV challenge files and issue a free SSL certificate within 2-5 minutes.

---

## Step 4: Bind Domain in TiT via Artisan CLI

Connect to the server via SSH (or use cPanel Terminal):

```bash
cd /home/titjaffn/public_html/laravel_api/backend
```

### 1. Add Domain
To bind the domain and set it as the primary domain:
```bash
php artisan institutes:add-domain <institute_id_or_slug> abc-tuition.lk --primary
```

To add `www` alias:
```bash
php artisan institutes:add-domain <institute_id_or_slug> www.abc-tuition.lk
```

### 2. Verify List of Domains
```bash
php artisan institutes:list-domains
```
Verify that the institute shows the new domain as **Primary Domain**.

---

## Step 5: Verification & Automated Health Check

Run the built-in diagnostic tool:
```bash
php artisan institutes:check-domain abc-tuition.lk
```

Expected diagnostic output:
- `[+] Database: Mapped to Institute #X (...) [Primary: YES]`
- `[+] DNS: Resolved to IP(s): ... Points correctly to server IP`
- `[+] HTTP: Reached portal at https://abc-tuition.lk/api/settings (Status: 200)`
- `[+] Routing: Resolved Institute Name matches: '...'`
- `[+] SSL: HTTPS connection active and valid`

Finally, open `https://abc-tuition.lk` in a browser:
- Check that the title, favicon, and colors adapt to the institute.
- Try logging in as an admin or student of that institute.

---

## Troubleshooting & Common Questions

### 1. 404 Error: `{"message":"Unknown institute","code":"tenant_not_found"}`
- **Cause**: The incoming domain is not registered in the `institute_domains` table.
- **Fix**: Run `php artisan institutes:add-domain <institute-id> <domain>`. Verify with `institutes:list-domains`.

### 2. Infinite Redirect Loop on `www`
- **Cause**: Conflicting `.htaccess` rule redirecting non-www back to www.
- **Fix**: TiT's root `.htaccess` enforces non-www canonical URL:
  ```apache
  RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
  RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301]
  ```
  Ensure DNS for both `www` and root point to the same server IP.

### 3. Cross-Origin Resource Sharing (CORS) Issues
- **None expected**: The React SPA and the Laravel API are served from the **same origin** (`/api` relative paths on the customer's domain). No cross-origin headers are required for standard web app usage.

### 4. SSL Warning / Insecure Connection
- **Cause**: AutoSSL has not completed yet or DNS has not propagated.
- **Fix**: Verify DNS propagation with `nslookup abc-tuition.lk`. Then trigger **Run AutoSSL** in cPanel SSL/TLS Status.

### 5. Flash of Wrong Institute / Old Brand
- **Cause**: Browser cached previous site settings in `localStorage`.
- **Fix**: TiT's `SettingsContext` automatically invalidates `cached_site_settings` whenever `window.location.host` changes. Clearing the browser cache or hard refreshing (`Ctrl+F5`) will also clear any stale cache.
