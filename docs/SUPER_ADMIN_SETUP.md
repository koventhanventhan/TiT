# Platform Super-Admin Setup & Recovery Guide

## Overview
Platform Super Administrators manage SaaS subscriptions, tenant provisioning, system domains, and platform-managed security integrations (Google reCAPTCHA v3 and Google OAuth credentials).

In accordance with the multi-tenant architecture:
- Super administrators have `institute_id = NULL` in the `users` table.
- They are global across all tenant domains and are never restricted to any single tenant.
- Normal institute administrators, teachers, and students have `institute_id = <id>` and are strictly bound to their respective tenant domain by `InstituteScope` and `ResolveTenant`.

---

## 1. Creating or Resetting a Super-Admin Account

On any production or staging environment, run:

```bash
php artisan make:super-admin {email}
```

### Safety Features
1. **Interactive Hidden Input**:
   - The password prompt uses `secret()` (hidden input in the terminal).
   - Password is never echoed in plain text and never written to logs or history.
2. **Double-Hash Prevention**:
   - The `User` model defines `'password' => 'hashed'` in its model casts.
   - The command assigns `$user->password = $plainPassword;` directly.
   - **Never** call `Hash::make()` before assignment, as this triggers the double-hash trap where the bcrypt hash is hashed a second time, locking out the user.
3. **Platform Scope**:
   - The command guarantees `institute_id = NULL` and `role = 'super_admin'`.
   - Clears `deactivated_at` if the account was previously suspended.

---

## 2. Authentication Architecture & Tenant Isolation

### How Super-Admin Login Works on Tenant Hosts
Filament's Super-Admin panel is configured with a dedicated authentication guard and user provider:
- **Guard**: `super_admin` (`session` driver)
- **Provider**: `SuperAdminUserProvider` (queries `User::withoutGlobalScope(InstituteScope::class)->where('role', 'super_admin')->whereNull('deactivated_at')`)
- **Tenant Resolver Bypass**: `ResolveTenant` detects super-admin panel routes (`/super-admin*` and associated Livewire requests) and does not bind a tenant (`Tenant::set(null)`).

This allows the platform owner to access `/super-admin` on:
- Primary platform domain (`platform.lk`)
- Any tenant domain (`second-academy.lk`)
- Any `www.` alias (`www.second-academy.lk`)

### Tenant Isolation Guarantee
- Normal user authentication (login for admins, teachers, and students) continues to run through the `web` guard and `/admin/login` or `/api/auth/login`.
- `ResolveTenant` binds the tenant host, and `InstituteScope` enforces `where users.institute_id = <current_tenant_id>`.
- An institute-1 user attempting to authenticate on institute-2's host is strictly rejected.
- Non-super users who attempt to submit credentials to `/super-admin/login` are rejected by `SuperAdminUserProvider` with "These credentials do not match our records."
- Logged-in normal administrators navigating to `/super-admin` receive a branded 403 page ("This area is for the platform owner") with a direct link back to `/admin`.
