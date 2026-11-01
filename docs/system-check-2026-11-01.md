# Local system check — November 1, 2026

Lendly's email/password login works against the repaired MySQL `tala` database. The renter, owner, and administrator demo accounts were tested through the browser, including interface selection and logout. Invalid credentials were correctly rejected.

## Automated results

- Full PHP suite: 1,006 tests executed using isolated in-memory SQLite and fake mail. Initially 938 passed and 68 errored due to missing Socialite and disabled GD.
- After repairing dependencies, all 111 tests in the affected classes passed, with 864 assertions. This rerun covers all 68 original errors, including Google authentication, manual identity review, photo upload, and rental return closure. No original failures remain unresolved.
- JavaScript: 39 tests passed, covering calendars, map/location behavior, identity-verification controls, and password strength.
- Vite production build passed.
- PHP syntax and Git whitespace checks passed.

JUnit evidence is in `storage/logs/system-check-phpunit.xml` and `storage/logs/system-check-retest.xml`.

The existing PHP tests cover registration, email verification, password recovery and updates, authentication lockouts, role access, listings, carts, booking rules, rental requests and agreements, payments, cancellations, exchanges, returns, damage claims, disputes, messages, notifications, reviews, and history preservation. Automated tests use synthetic data, not the live MySQL records.

## Browser checks against MySQL

The following pages loaded successfully:

- Renter dashboard, cart, requests, rentals, and messages.
- Public listings and map, with all 44 listings represented on the map.
- Owner dashboard, listings, listing creation form, rental requests, rentals, payment settings, and profile.
- Administrator dashboard, listings, identity-review queue, transactions, damage reports, disputes, and payment settings.
- Login and registration screens.

Listing photos were checked after repairing the storage link; all seven images on the sampled listing page loaded. No new live booking or payment was submitted. Opening the existing rental pages invokes the application's automatic overdue-status updates for historical past-due rentals.

## Local setup repairs

- Installed declared npm dependencies; `jsqr` was missing.
- Installed the versions already recorded in `composer.lock`; Socialite and its dependencies were missing, and two installed packages were out of sync with the lock file.
- Enabled the installed PHP GD extension. Original PHP configuration is preserved at `storage/app/private/backups/php-before-system-check.ini`.
- Recreated `public/storage` to target this project's `storage/app/public`; it previously pointed to the old `Tala` project.
- Installed the project's pinned private Python/OpenCV runtime and official hash-verified YuNet/SFace models. Both models loaded successfully and a blank image was correctly rejected. No real identity documents were used.
- Restarted the local PHP server at `http://127.0.0.1:8123` to pick up the runtime changes.

## External-service limits

- SMTP connection and authentication passed without sending an email. Actual inbox delivery, verification-link receipt, and reset-code receipt were not tested.
- Google OAuth client credentials are absent. The UI correctly disables Google sign-in. Its application logic passed automated tests using mocked Google responses; a real Google sign-in still requires OAuth setup.
- Real ID/selfie comparison and a complete administrator identity review were not performed with personal documents. Runtime loading and synthetic-image rejection were verified, and the review workflow passed automated tests.
- Real transfers and recipient payment confirmation were not performed. Payment flows passed automated tests with synthetic records.
