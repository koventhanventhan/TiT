# Firebase Multi-Tenant Isolation Audit Report

**Date:** November 13, 2026  
**Auditor:** DeepMind Antigravity Engineering  
**Scope:** Firebase Authentication, Cloud Storage, Security Rules, Backend Token Minting, and Database Storage URLs in repository `TiT`.  
**Context:** Prompt 29 (Part F) – Code and Architecture Audit of Cross-Institute Isolation in Firebase.

---

## 1. Executive Summary

The platform currently operates on a **single shared Firebase project and Cloud Storage bucket** (`config('services.firebase.web.storageBucket')`). While Prompt 26 and Prompt 29 have successfully decoupled and isolated PayHere, Meta WhatsApp, SMTP Mail, Zoom, Google Gemini, Google reCAPTCHA, and Google Sign-in OAuth per institute, Firebase Cloud Storage remains centrally provisioned.

This audit evaluates the three key isolation surfaces:
1. **Token Minting & Custom Claims** (`FirebaseTokenService`, `SiteSettingController::firebaseToken`)
2. **Cloud Storage Security Rules** (`firebase/storage.rules`)
3. **Storage URL Validation & Database Persistence** (`FirebaseStorageUrlValidator`, API controllers, models)

### Key Audit Findings:
* **Tokens are Admin-Only:** Custom Firebase Auth tokens are only minted for authenticated `admin` and `super_admin` users. Students and parents have no custom token endpoint and are unauthenticated in Firebase.
* **Write Isolation Enforced for Learning Suite:** Admins can only write and delete within `/learning-suite/i{institute_id}/...`. An admin from Institute 1 cannot upload to or delete from Institute 2's folder in Storage.
* **Public Read Exposure:** `firebase/storage.rules` specifies `allow read: if true;` for all files under `/learning-suite/`. Any file URL is publicly downloadable without institute authentication.
* **Backend Validation Inconsistency:** `SiteSettingController` validates that uploaded URLs match `learning-suite/i{institute_id}/`, but `TeacherMaterialController` and `MasterAdminController` only check bucket membership via `isValidFilePathOrUrl()`, allowing cross-institute file link injection.
* **Shared Quota Risk:** All Storage bandwidth and storage size are billed to the single central Firebase project (TiT).

---

## 2. Token Minting and Custom Claims Audit

### 2.1 Service Implementation
The custom token generation logic resides in `App\Services\FirebaseTokenService`:
* **Algorithm:** RS256 JWT signed with the service account private key (`services.firebase.private_key`).
* **Claims Handling:** Accepts arbitrary custom claims via `createCustomToken(string $uid, array $claims = [], int $ttlSeconds = 3600)`.

### 2.2 Token Issuance Endpoint
Tokens are minted in `App\Http\Controllers\Admin\SiteSettingController::firebaseToken`:
```php
public function firebaseToken(Request $request)
{
    $user = $request->user();
    if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
        return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
    }
    ...
    $instituteId = (int) ($user->institute_id ?? 1);
    $token = $tokenService->createCustomToken(
        'admin-' . $user->id,
        [
            'admin' => true,
            'institute' => 'i' . $instituteId,
        ]
    );

    return response()->json([
        'token' => $token,
        'institute_id' => $instituteId,
        'web_config' => config('services.firebase.web'),
    ])->header('Cache-Control', 'no-store');
}
```

### 2.3 Evaluation of Claims:
1. **Claim Key Naming:** The claim key is `institute` (with value string `"i{$id}"`, e.g., `"i1"` or `"i2"`). It is **not** `institute_id` as an integer.
2. **Student & Public Isolation:**
   * Students, teachers, and visitors **cannot** call `/admin/firebase-token` (gated by `role: ['admin', 'super_admin']`).
   * There is **no student token minting endpoint** in the backend.
   * Therefore, students never receive a Firebase custom token. When students access learning materials or submit assignments, they interact as unauthenticated visitors from Firebase's perspective.
3. **Cross-Tenant Admin Collision Risk:**
   * An administrator of Institute 1 receives a token with `institute: "i1"`.
   * Can this admin talk to Institute 2's Storage? **No, for writes.** The Storage rules explicitly require `request.auth.token.institute == institute`.
   * Can this admin talk to Firestore? **There are no Firestore security rules defined in the repo (`firebase/storage.rules` only covers Storage).** If Cloud Firestore is enabled on this Firebase project without strict rules, any token holder with service account claims could potentially read/write Firestore collections.

---

## 3. Storage Rules Security Analysis (`firebase/storage.rules`)

The active storage security rules in `firebase/storage.rules`:

```javascript
rules_version = '2';

service firebase.storage {
  match /b/{bucket}/o {

    function isInstituteAdmin(institute) {
      return request.auth != null
          && request.auth.token.admin == true
          && request.auth.token.institute == institute;
    }

    function isAllowedUpload(folder) {
      return (
        (folder in ['notes', 'past-papers'])
        && request.resource.size < 50 * 1024 * 1024
        && request.resource.contentType in [
             'application/pdf',
             'application/msword',
             'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
           ]
      ) || (
        folder == 'recordings'
        && request.resource.size < 500 * 1024 * 1024
        && request.resource.contentType.matches('video/.*')
      );
    }

    match /learning-suite/{institute}/{folder}/{grade}/{file} {
      allow read: if true;
      allow create: if isInstituteAdmin(institute) && isAllowedUpload(folder);
      allow update: if false;
      allow delete: if isInstituteAdmin(institute);
    }

    match /{allPaths=**} {
      allow read, write: if false;
    }
  }
}
```

### 3.1 Rule Breakdown & Vulnerabilities
1. **Read Permission (`allow read: if true;`):**
   * **Vulnerability:** Unauthenticated public read access. Any user who knows or guesses the URL `https://firebasestorage.googleapis.com/v0/b/.../o/learning-suite%2Fi2%2Fnotes%2F...` can download Institute 2's materials without authentication.
   * **Impact:** No confidentiality between institutes for published learning materials. A competitor or student enrolled in Institute 1 can directly download Institute 2's premium past papers or recordings if URLs are shared or enumerated.
2. **Write & Delete Permission (`isInstituteAdmin(institute)`):**
   * **Strength:** Strictly compares path segment `{institute}` with `request.auth.token.institute`. Admin of `i1` cannot upload or delete in `/learning-suite/i2/...`.
   * **Immutability:** `allow update: if false;` prevents overwriting existing files.
3. **Catch-All Block (`match /{allPaths=**} { allow read, write: if false; }`):**
   * All other paths outside `/learning-suite/{institute}/{folder}/{grade}/{file}` fail closed.
   * **Side Effect:** Note that `laravel_api/src/services/firebaseStorageService.js` attempts helper methods for `assignments/submissions`, `avatars/`, and `payment-slips/`. Because of this catch-all rule, those direct client-side uploads to Firebase fail, and the system falls back to Laravel local disk upload (`Storage::disk('public')`).

---

## 4. Backend URL Validation & Database Persistence

### 4.1 Validator Implementation (`FirebaseStorageUrlValidator`)
`App\Services\FirebaseStorageUrlValidator` provides two core validation methods:
1. `validate(string $url, ?int $instituteId = null)` / `isValidForInstitute(string $url, int $instituteId)`:
   * Validates scheme is `https`.
   * Host is strictly `firebasestorage.googleapis.com`.
   * Bucket matches `services.firebase.web.storageBucket`.
   * Object path does not contain path traversal (`..`).
   * When `$instituteId` is passed: **strictly enforces** `str_starts_with($objectPath, "learning-suite/i{$instituteId}/")`.
2. `isValidFilePathOrUrl(?string $value)`:
   * Only checks if string is a relative path or an HTTPS URL on the configured Firebase bucket.
   * **CRITICAL GAP:** Does **NOT** enforce institute ID path isolation!

### 4.2 Endpoint-by-Endpoint Analysis

| Controller & Method | Route | Validation Used | Tenant Enforced? | Risk Level |
|---|---|---|---|---|
| `SiteSettingController::storeMaterial` | `POST /admin/settings/learning/store` | `FirebaseStorageUrlValidator::isValidForInstitute($url, $instituteId)` | **YES** (`learning-suite/i{id}/`) | **Low** |
| `TeacherMaterialController::store` | `POST /api/teacher/materials` | `FirebaseStorageUrlValidator::isValidFilePathOrUrl($value)` | **NO** (Any bucket file accepted) | **Medium / High** |
| `MasterAdminController::storeMaterial` | `POST /api/master/learning/store` | `FirebaseStorageUrlValidator::isValidFilePathOrUrl($value)` | **NO** (Any bucket file accepted) | **Medium** |
| `PdfTextExtractor::extractText` | CLI / Queue worker (RAG) | `FirebaseStorageUrlValidator::isValidForInstitute($filePath, $instituteId)` | **YES** | **Low** |

### 4.3 Database Storage Locations
URLs and file paths pointing to Firebase Storage are persisted in the following database columns:

1. **`learning_materials`:**
   * `file_path`: Full Firebase download URL (`https://firebasestorage.googleapis.com/...`) or local relative path (`uploads/notes/...`).
   * `url`: Video URL (YouTube, Vimeo, or Firebase Storage URL).
   * `file_hash`: SHA-256 content hash (used for deduplication within `institute_id`).
2. **`assignments`:**
   * `file_path`: Homework prompt or assignment attachment URL.
3. **`assignment_submissions`:**
   * `file_path`: Student completed assignment file. Accessor `getFileUrlAttribute()` returns `file_path` directly if prefixed with `http://` or `https://`, otherwise wraps with `Storage::disk('public')->url()`.
4. **`student_payments`:**
   * `slip_url` / `bank_slip_path`: Offline bank deposit receipt image.
5. **`users`:**
   * `profile_picture`: Avatar image path.

---

## 5. Threat Modeling & Risk Matrix

| Threat ID | Threat Description | Attack Vector | Likelihood | Impact | Current Mitigations |
|---|---|---|---|---|---|
| **T-01** | Cross-Institute Learning Material Reference | A teacher in Institute 2 submits `file_path` pointing to `learning-suite/i1/...` via `/api/teacher/materials`. | Medium | Medium | RAG ingestion validates via `PdfTextExtractor::isValidForInstitute`, but web display serves material. |
| **T-02** | Public Discovery of Another Institute's Materials | Visitor enumerates or intercepts Firebase download URLs from Institute 1 and downloads Institute 2's content. | High | Medium | URLs contain timestamp and sanitized names, but Storage rules do not check auth on read. |
| **T-03** | Shared Firebase Project Quota & Invoicing | Institute 2 uploads massive video files (up to 500MB each) consuming bandwidth and storage on TiT's Firebase account. | High | High | Upload max sizes restricted to 50MB (docs) and 500MB (video) in Storage rules; single project billing. |
| **T-04** | Unauthorized Write to Storage | Student or external attacker tries to upload files directly to Firebase Storage bucket. | Low | Low | Prevented by `storage.rules`: writes require valid JWT with `admin: true` and matching `institute` claim. |

---

## 6. Recommendations for Future Per-Institute Firebase Migration

When per-institute Firebase credentials are implemented in a future phase:

1. **Short-Term Hardening (Low effort, high security):**
   * Update `TeacherMaterialController::store` and `MasterAdminController::storeMaterial` to call `isValidForInstitute($url, $user->institute_id)` instead of `isValidFilePathOrUrl($url)`.
   * Add a signed URL or proxy download mechanism if institutes require private, non-public study notes.
2. **Full Per-Institute Architecture (Target architecture):**
   * Extend `InstituteIntegration` table with provider `firebase` (`project_id`, `client_email`, `private_key`, `storage_bucket`).
   * Allow each institute to provide their own Firebase Service Account JSON credentials via **Settings → AI & Security** or a dedicated **Settings → Storage** page.
   * `FirebaseTokenService::forInstitute($instituteId)` resolves the credentials from the database, falling back to central `.env` only for fallback institutes (`INTEGRATIONS_ENV_FALLBACK_INSTITUTES`).
   * Each institute owns their own Firebase Console, Google Cloud billing, and storage quotas.
