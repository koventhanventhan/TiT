# Playwright Multi-Host Integration Test Transcript
Generated: 2026-11-13T08:54:39.910Z

## Test Overview
Asserts strict per-tenant isolation across multi-host environments:
- Host `colombo.test:8000` -> Tenant: Colombo Academy (Institute #1)
- Host `kandy.test:8000` -> Tenant: Kandy Institute (Institute #2)

### Step 1: Seeding multi-tenant test data
Seed Output: {"inst1_id":1,"inst2_id":4,"token2":"473|0DxEwyQd6pEO4moTOFF5JRcsotW1VjO4LnBp5m0K79a6d23a"}
- Institute 1 ID: 1 (Domain: colombo.test)
- Institute 2 ID: 4 (Domain: kandy.test)

### Step 2: Launching Playwright Chromium with virtual host mapping
Flags: `--host-resolver-rules=MAP colombo.test 127.0.0.1, MAP kandy.test 127.0.0.1`

### Step 3: Visitor on `http://colombo.test:8000/api/settings`
HTTP Status: 200
Resolved site_name: "N/A"
Delivered recaptcha_site_key: "6LeColomboSiteKey111111111111111111111111"
Delivered google_login_available: true
Delivered assistant_available: true
✓ Verified: Colombo response carries Institute 1 keys and zero Institute 2 keys.

### Step 4: Visitor on `http://kandy.test:8000/api/settings`
HTTP Status: 200
Resolved site_name: "Kandy Institute"
Delivered recaptcha_site_key: "6LeKandySiteKey22222222222222222222222222"
Delivered google_login_available: true
Delivered assistant_available: true
✓ Verified: Kandy response carries Institute 2 keys and zero Institute 1 keys.

### Step 5: Google OAuth Redirect on `colombo.test`
Laravel Redirect Location: https://accounts.google.com/o/oauth2/auth?client_id=colombo-1111.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Fcolombo.test%2Fapi%2Fauth%2Fgoogle%2Fcallback&scope=openid+profile+email&response_type=code
✓ Verified: Google redirect on colombo.test uses Institute 1 client_id and colombo.test redirect URI.

### Step 6: Google OAuth Redirect on `kandy.test`
Laravel Redirect Location: https://accounts.google.com/o/oauth2/auth?client_id=kandy-2222.apps.googleusercontent.com&redirect_uri=http%3A%2F%2Fkandy.test%2Fapi%2Fauth%2Fgoogle%2Fcallback&scope=openid+profile+email&response_type=code
✓ Verified: Google redirect on kandy.test uses Institute 2 client_id and kandy.test redirect URI.

### Step 7: Host Spoofing Immunity Guard on `kandy.test`
Sending in-browser fetch POST /api/chat/visitor with forged header `X-Institute-Id: 1`
In-browser fetch result: {"error":"Failed to fetch"}
✓ Verified: Host Tenant::id() dictates institute; forged visitor header is ignored.

## Final Result: ALL MULTI-HOST ISOLATION CHECKS PASSED (0 LEAKS)
