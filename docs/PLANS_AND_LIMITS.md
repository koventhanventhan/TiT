# Platform Plans, Usage Limits & Subscription Management

This document explains how subscriptions, usage limits, plan features, expiry schedules, and renewals work across the platform.

---

## 1. What Counts Toward Plan Limits

The platform tracks and enforces limits on two primary user dimensions: **Students** and **Teachers**. The counting logic is standardized across the entire application in `App\Services\PlanLimits` and `App\Services\PlanUsage`:

### Students
A user counts as a student toward their institute's quota if and only if all of the following conditions are met:
- **Role:** `role === 'user'`
- **Tenant Scope:** Belongs to the specific institute (`institute_id = $institute->id`)
- **Active Record:** Not soft-deleted (`deleted_at IS NULL`)
- **Approval Decision:** Users whose registration was rejected (`admin_confirmed_at IS NULL` with rejection note or marked as rejected) do not occupy active learning seats. Pending signups awaiting confirmation are evaluated at the point of approval or creation.

### Teachers
A user counts as a teacher if and only if:
- **Role:** `role === 'teacher'`
- **Tenant Scope:** Belongs to the specific institute (`institute_id = $institute->id`)
- **Active Record:** Not soft-deleted (`deleted_at IS NULL`)

> [!NOTE]
> Users from other institutes never affect another institute's quota. Soft-deleted accounts immediately release their seat.

---

## 2. Meaning of "Unlimited"

In the database table `subscription_plans`, the limit columns `max_students` and `max_teachers` define capacities:
- **Unlimited:** Represented by `0`, `-1`, or `NULL`. Any non-positive value indicates that no ceiling is imposed.
- **Capped:** A positive integer (e.g. `50`, `250`, `1000`) represents the exact maximum number of active accounts allowed.

When an institute's plan has unlimited capacity, all limit pre-checks succeed automatically without locking database rows.

---

## 3. Expiry, Grace Period & Institute #1 Safety

### Institute #1 Immunity (Platform Safety Guard)
Institute #1 is the primary platform host (TiT Platform). By architectural design:
- Institute #1 **never expires**, regardless of the value in `institutes.expires_at`.
- Even if `expires_at` is set to yesterday or a date in the past, Institute #1 remains fully active and returns HTTP 200 on all public, admin, and API routes.
- Institute #1 is protected against automatic suspension or deletion.

### Expiry Grace Period
When an institute reaches its `expires_at` date:
1. **During Grace Period (Days 0 to `TENANT_EXPIRY_GRACE_DAYS`, default 7 days):**
   - The institute's website, student portal, and admin portal remain operational.
   - Institute administrators see an alert banner on `/admin/dashboard` and throughout the admin console:
     `"Your subscription expired on {date}. Access will stop on {date + grace}. Contact the platform owner to renew."`
2. **Beyond Grace Period (Expired & Blocked):**
   - **Browser/HTML Requests:** Visitors see a branded, self-contained error page with HTTP status **402 Payment Required** titled *"Subscription has ended"*. No developer traces, stack frames, or internal identifiers are visible.
   - **JSON/API Requests:** API calls return HTTP **402** with `{ "message": "Institute subscription has expired", "status": "expired", "code": "institute_expired" }`.
   - **Admin Access Exception:** The institute administrator (`role === 'admin'`) can still log in to view the read-only **Settings → Subscription** page (`/admin/subscription`) to view expiry details and platform contact lines. All other admin pages automatically redirect to `/admin/subscription`.
   - **Suspended Institutes:** Institutes marked with status `suspended` return HTTP **403 Forbidden** titled *"This institute is temporarily unavailable"*, or JSON `{ "code": "institute_suspended" }`.

---

## 4. Platform Contact Environment Variables

The platform contact information displayed on blocked pages, expiry banners, and the admin subscription screen is driven by the following environment variables:

| Environment Variable | Description |
|----------------------|-------------|
| `TENANT_EXPIRY_GRACE_DAYS` | Number of days after `expires_at` an institute remains operational before blocking (default: `7`). |
| `TENANT_PLATFORM_CONTACT_EMAIL` | Platform administrator email address shown for renewals and upgrades. |
| `TENANT_PLATFORM_CONTACT_PHONE` | Platform support telephone number. |
| `TENANT_PLATFORM_CONTACT_WHATSAPP` | Platform WhatsApp support contact number. |

If an environment variable is left blank or unset, that contact line is automatically hidden from public and admin screens.

---

## 5. Plan Features Registry

The platform provides granular feature gating defined in `config/plans.php`:

| Feature Key | Human Label | Enforced Scope |
|-------------|-------------|----------------|
| `zoom` | Zoom Integration | Zoom classes schedule, join URLs, and sidebar menu. |
| `ai_assistant` | AI Learning Assistant & RAG | Student AI queries, admin document chunking, and public chatbot. |
| `whatsapp` | WhatsApp Alerts | Automated message dispatching via WhatsAppService (gracefully skipped if plan lacks feature). |
| `custom_domain` | Custom Domains | Ability to attach custom domain names beyond the primary provisioned host. |
| `online_payments` | Online Payments | Payment gateway integration (e.g. PayHere). |
| `advanced_reports` | Advanced Analytics | Advanced reporting and metrics dashboards. |

### Legacy Label Compatibility
Existing plans with free-text tags (such as `"Zoom Integration"` or `"WhatsApp Alerts"`) are mapped without requiring database migrations:
- `"Zoom Integration"` maps automatically to `zoom`
- `"WhatsApp Alerts"` maps automatically to `whatsapp`
- Unknown custom tags remain preserved in read-only mode so no legacy plan data is discarded.
- Institutes with no plan assigned (`subscription_plan_id === null`) have all features enabled for backwards compatibility.

---

## 6. Expiry Reminders Schedule

The console command `institutes:expiry-reminders` runs daily at **09:30 AM** (configured in `routes/console.php` with failure alerting).

- **Notification Cadence:** Reminders are dispatched to institute administrators at **14 days**, **7 days**, **3 days**, **1 day**, and **on the expiry day (0 days)**.
- **Idempotency:** Every dispatch is recorded in `institute_subscription_notices` (`institute_id`, `kind`, `expires_on`, `sent_at`). Running the command multiple times on the same day never sends duplicate emails.
- **Platform Owner Digest:** When reminders are dispatched, a summary digest is sent to `TENANT_PLATFORM_CONTACT_EMAIL`.
- **Dry-run Mode:** Run `php artisan institutes:expiry-reminders --dry-run` to preview scheduled notices without sending emails or modifying the database.

---

## 7. How to Renew an Institute (One-Click Renewal)

Super-administrators can renew any institute subscription from the SuperAdmin Panel:

1. Navigate to **Institutes** in the SuperAdmin Panel (`/admin` on platform host).
2. Click the **Renew** action on the institute's row in the table, or open the institute's view page and click **Renew** in the top action bar.
3. Select an extension option:
   - **+30 days:** Extends the expiry date by 30 days from the current expiry (if in the future) or from today (if already expired).
   - **+1 year:** Extends the expiry date by 365 days.
   - **Custom date:** Allows picking an explicit future datetime.
4. *(Optional)* Select a new subscription plan from the dropdown to upgrade or downgrade the institute.
5. *(Optional)* Enter an administrative note (e.g. invoice number or payment reference).
6. Click **Confirm Renewal**.
   - If the institute was previously expired or in grace, its status is immediately restored to `active`.
   - An audit record is written to `activity_logs` with action `institute_subscription_renewed`.
   - The institute's site becomes immediately accessible without restarting web servers or caches.

---

## 8. Pre-Deployment Database Checklist

Before deploying this release to production, run the following verification query on the production database:

```sql
SELECT id, name, status, expires_at, subscription_plan_id FROM institutes;
```

**Verification Items:**
1. Verify that `id = 1` represents the main TiT platform. (Even if `expires_at` is NULL or in the past, Institute #1 will never be blocked by code guards, but verifying data integrity is standard operational procedure).
2. Ensure active tenant institutes have valid `subscription_plan_id` references and realistic `expires_at` dates.
3. If an existing tenant should have unlimited access, confirm their `expires_at` is set to `NULL` or a distant future date.

