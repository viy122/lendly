# Google sign-in

Prepare Google sign-in so the user only supplies OAuth credentials in `.env` and registers the documented callback in Google Cloud. Preserve email/password, Gmail-only registration, member renter/owner interfaces, account lockouts and suspension controls, and the separate identity verification step. No Drive access, stored OAuth tokens or administrator Google login.

Use Laravel Socialite's stateful server redirect/callback flow with only OpenID/email/profile scopes. Login uses the existing interface picker before leaving for Google; registration defaults to renter as the current signup does. Buttons appear when client ID, secret and callback are configured. Missing configuration and cancelled/invalid callbacks return a clear login error.

Add a nullable unique, hidden, non-mass-assignable `users.google_id`. Match Google's immutable ID and authoritative verified Gmail to the current canonical account email. Existing verified active member accounts may be linked without overwriting their local name, password or identity record. Unverified/conflicting accounts are blocked rather than merged. Reject admins, suspended/closed accounts, mismatched IDs/emails and ambiguous legacy aliases. Clear a Google link when email changes or an account closes. New accounts receive verified email, an unusable random hashed password, and pending identity verification. Existing password reset remains available for setting a local password.

OAuth state and the selected interface are single-use and session bound; fresh account checks and login occur together under account locks, then the session is rotated. New or identity-pending members continue to identity.verify; identity-verified members continue to their chosen dashboard. No ID/selfie success is inferred from Google login.

Root owns dependency setup, routes/config, environment placeholders, migration/User lifecycle and setup docs. Backend worker owns controller and authentication tests. UI worker owns login/register/shared button and UI tests. Independent review checks account linking, callback/state, profile and password integration. Use isolated compiled view paths per PHP test process. Apply migrations locally, run focused auth/lifecycle tests, then final full suite, Pint, Blade compilation and Vite build. Live Google login cannot be tested without the user's credentials; do not fabricate credentials or log secrets.

## Completion

- Complete PHPUnit suite passed: **906 tests, 6,932 assertions**.
- Real Socialite GoogleProvider with simulated HTTP covered OAuth state, cancellation, replay, account linking and fresh eligibility. UI, profile, recovery and identity regression checks passed.
- Google account migration applied locally as batch 6. `.env` and `.env.example` contain blank credentials and the prepared callback; existing environment values were preserved.
- Vite production build, Blade compilation, scoped Pint checks, Composer validation and whitespace checks passed. Independent security review found no remaining actionable issues.
- Installed Socialite 5.31.0. Compatible updates to CommonMark 2.10.4 and HttpFoundation 7.4.21 cleared the pre-existing security advisories; Composer reported none remaining. The optional optimized Composer scan stalled, so standard autoloading was generated successfully and the repository's existing optimization setting restored.
- Live Google consent/login and browser interaction were not tested. User-supplied Google credentials and the registered callback are still required. Setup is documented in `docs/google-sign-in.md`.

## Google-first presentation follow-up

The user chose Google first with email/password below. The shared login/register control now uses a white pill button with the colored Google logo, hover and keyboard-focus states. It remains visible and natively disabled with a short availability message while credentials are missing; registration has no Google link until configured. Existing email/password, admin login and OAuth actions are preserved.

Verified after the presentation change: **62 focused tests, 669 assertions passed**, including absent/partial/complete Google settings and existing login/registration/admin flows. Blade compilation, production Vite build, scoped Pint and whitespace checks passed. Browser visual checks and live Google login remain unavailable.
