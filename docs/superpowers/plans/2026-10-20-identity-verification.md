# Member identity verification implementation plan

> **For agentic workers:** Use the existing Laravel/Livewire patterns; implement and independently review the security boundary before completion.

**Goal:** Add automatic National ID, entered-details and live-selfie verification once per member, covering both renter and owner interfaces.

**Architecture:** The browser decodes the QR locally and uses the official eVerify liveness SDK after consent. A Livewire component binds the submitted details to an expiring server attempt, then a Laravel service authenticates server-to-server and calls the documented `/api/query/qr` endpoint. Only a successful provider result whose demographics match the submitted details and current account name can set the private identity verification fields.

**Tech stack:** Existing Laravel 12, Livewire 3, Alpine, Tailwind, Laravel HTTP client; jsQR for browser QR decoding if needed.

**Spec:** The user's explicit request in this chat: implement real verification for both renters and owners, authenticating National ID, entered information, and live selfie. No provider access is currently available.

## Constraints and decisions

- Preserve the existing rental agreement work and all email verification behavior.
- One member account already supports both roles; do not create separate role verification.
- Do not lock existing rental/listing actions while credentials are unavailable; this request adds verification, not a new transaction-access policy.
- No fake success or admin override. Missing credentials, provider failure, malformed data, mismatched details and stale attempts must not grant verification.
- Use only documented official endpoints and SDK integration. Do not copy reverse-engineered cookies, untrusted signing keys, or incomplete signature checks.
- Do not store ID images, selfies, QR data, DOB, returned demographic payloads, or face URLs. Store attempt input HMACs, one-way liveness-session hashes, statuses, and an encrypted private reference only.
- No credentials or sensitive identity fields in public profiles, logs, or browser configuration. Only the SDK public key is browser-visible.
- Name changes invalidate completed verification and pending attempts; deletion clears verification, and delayed results cannot restore it.
- Sandbox and production use distinct configured environments; show sandbox results as test verification, never public production verification.

## Interfaces and ownership

- Root: migration `2026_10_20_000001_add_identity_verification.php`, model `IdentityVerificationAttempt`, User lifecycle changes, Livewire `Identity/Verify`, routes, profile/public status integration, configuration and setup documentation.
- Backend worker: `app/Services/IdentityVerification.php` and focused `tests/Feature/IdentityVerificationTest.php` only.
- UI worker: `resources/views/livewire/identity/verify.blade.php`, `resources/js/identity-verification.js`, import in `resources/js/app.js`, browser logic self-checks only.

Attempt schema: UUID `id`, foreign `user_id`, string `status` (pending, processing, verified, failed, cancelled), string(64) `input_hash`, nullable unique string(64) `liveness_session_hash`, timestamp `expires_at`, timestamps. No soft deletes. User fields: nullable `identity_verified_at`, nullable encrypted `identity_reference`, nullable `identity_verification_environment` (sandbox or production). All fields hidden and not fillable.

Service API: `available(): bool`, `begin(User $user, array $details): string`, `finish(User $user, string $attemptId, array $details, string $sessionId): void`. Details keys: first_name, middle_name, last_name, suffix, birth_date, qr_value. User methods: `hasVerifiedIdentity(): bool` (production only, active member and timestamp), `hasTestedIdentity(): bool` (sandbox equivalent).

Livewire properties: first_name, middle_name, last_name, suffix, birth_date, qr_value (strings), consent (bool); locked attemptId (nullable string). `startVerification(): ?string` validates consent and calls begin, returning attempt UUID; `completeVerification(string $attemptId, string $sessionId): void` calls finish with current details; `cancelVerification(string $attemptId): void` cancels only that member-owned attempt. Render variables: available (bool), sdkUrl (official HTTPS configured URL), publicKey (SDK key), environment (sandbox/production), verified/testVerified (bool).

Config `services.identity`: base_url, client_id, client_secret, public_key, sdk_url, environment. Environment variables `IDENTITY_BASE_URL`, `IDENTITY_CLIENT_ID`, `IDENTITY_CLIENT_SECRET`, `IDENTITY_PUBLIC_KEY`, `IDENTITY_SDK_URL`, `IDENTITY_ENVIRONMENT` default sandbox. Base URL and SDK URL require HTTPS outside automated testing. No default base URL or credentials. SDK URL defaults to the official documented staging SDK; production requires the assigned production URL.

Official API contract: POST `{base_url}/api/auth` with client_id/client_secret => `data.access_token`; POST `{base_url}/api/query/qr` bearer token with value=qr_value and face_liveness_session_id=UUID. Success requires HTTP success, data.code `AAA001`, meta.result_grade integer 1, nonempty data.reference, and matching first_name/middle_name/last_name/suffix/birth_date. HTTP 200 alone is not success (documented mismatch returns data.verified=false and result_grade=FAILED_FACE). Do not store/log the response body. Disable redirects and set bounded HTTP timeouts.

## Review focus

- Replayed, duplicate, expired, stolen or concurrently superseded attempts cannot verify another account.
- Profile rename, account deletion/suspension, or environment changes during remote processing cannot preserve/grant a badge.
- Client properties and SDK success responses cannot directly grant verification.
- Malformed or partial provider responses and all provider outages fail closed without logging personal data.
- Sandbox success is visible to the account as a test result only; public profiles show only real production identity verification.

## Tasks

### 1. Provider and state boundary
- [x] Write failing tests for documented success, HTTP-200 mismatch, missing credentials, mismatched demographics, replay, expiry, wrong account, renamed/deleted/suspended account, and sandbox classification.
- [x] Run the tests and confirm expected missing-code/schema failures.
- [x] Add schema/models/lifecycle clearing and implement service with strict provider-response validation and transactional attempt claims/finalization.
- [x] Run focused service tests and inspect the actual output.

### 2. Member verification flow
- [x] Add auth+email-verified member route `/identity/verify`, Livewire component, consent validation and server-only attempt identifier.
- [x] Build accessible page with personal details, local QR capture, SDK live-selfie action, retry/cancellation, and honest unavailable/test/verified states.
- [x] Add account navigation/status and production-only public profile badge for the shared member identity.
- [x] Test route authorization, consent, attempted property tampering and private-data exclusion.

### 3. Verify and deliver
- [x] Add credential/environment instructions, data handling limits and provider references to `docs/identity-verification.md` and `.env.example`.
- [x] Run the identity/profile/email/public-profile/account-deletion suites, browser logic checks, production build, Pint and diff checks.
- [ ] Inspect desktop and mobile rendering using synthetic fixtures only; never submit a real ID/selfie.
- [x] Independently review the final security boundary and fix actionable findings.
- [x] Apply the migration to the existing local database after inspecting pending migration scope.
- [x] Report implemented behavior separately from the live provider checks that require credentials.

Visual check limitation: synthetic fixtures render and Blade compiles, but browser inspection is disabled in Work settings. No real identity data or live provider session was used. Local identity migration applied successfully to MySQL (batch 3).

Verification: full PHPUnit run passed (757 tests, 6,000 assertions); final account-flow checks passed separately (13 tests, 61 assertions). JavaScript checks passed (20 tests), production build and Blade compilation passed, and Pint/diff checks passed. Live official-provider and physical-camera behavior still require assigned credentials and a supported device.

Registration follow-up: the user selected an ID/selfie step after email confirmation. Signup now displays the three-step sequence, and signed member email confirmation opens identity verification before intended account destinations. The verified-email screen's continuation uses the same destination; admins and production-verified members retain their previous redirects. There is no new login or transaction gate while the provider remains unavailable. Related signup/email/identity-flow checks passed (47 tests, 276 assertions).
