# Lendly requirements repairs implementation plan

> **For agentic workers:** Apply the authorized repairs task by task using regression tests. Independent map, history, and inquiry tasks run in parallel under the dispatching-parallel-agents skill; the primary agent integrates and verifies them.

**Goal:** Fix the FR groups selected by the user, preserving the existing Gmail authentication work and the original requirements document.

**Architecture:** Keep the Laravel/Livewire structure. Historical relations include removed listings/accounts; account closure retains transaction rows. Serialize booking/payment/lifecycle writes and derive availability from booking dates. Extend existing messaging and reviews with listing inquiry threads and public review profiles.

**Tech stack:** Existing Laravel 12, Livewire/Volt, PHP, SQLite test databases, MySQL application database, Leaflet, and installed Vite/Node dependencies. No new package dependencies.

**Spec:** The user-selected FR groups in this chat and `docs/revised-requirements-system-comparison-2026-10-09.md`; exclude the unrequested FR-03 role redesign. Return closure uses owner confirmation plus returned condition; admin actions reuse existing dispute and account management.

## Constraints and review focus

- Preserve the original Word document and all earlier dirty authentication/UI changes.
- Do not delete existing financial/history rows to make constraints pass. Additive migrations must reject duplicate legacy records with a useful explanation if uniqueness cannot be added safely.
- Protect participant/admin actions after stale page state, handover, account closure, and listing removal.
- Notifications use the existing database-only class; no live test emails or real account messages.
- Do not expose phone, email, address, credentials, or private transaction details on public profiles.
- Test historical page access after listing/account removal, injected persistence failures, overlapping actions, duplicate retries, zero coordinates, malicious map text, unrelated chat/profile viewers, and return-condition closure order.

## Tasks

### 1. Preserve history (FR-07/24)
- [x] Prove account/listing removal regression with `docs/audit/RequirementsAuditTest.php` and a maintained history test.
- [x] Add User soft deletion and account-obligation checks; retain historical participant identity and include removed listings/participants in historical relations.
- [x] Verify history, request/chat, admin/dispute, and receipt views remain readable; closed users cannot authenticate and their listings cannot receive new requests.

### 2. Requests, approval, and notifications (FR-11/13/14/40)
- [x] Add maintained regressions for stale publication/availability/options, interrupted approval, overlapping approvals, and both-recipient status notifications.
- [x] Reload and lock the listing at submission/approval. Revalidate current options and date overlap. Create rental and approved request in one database transaction; add one-rental-per-request uniqueness.
- [x] Notify both participants of pending/approved/declined and booking confirmation. Confirm invalid/stale actions do not change records or create misleading notifications.

### 3. Payment, availability, and handover (FR-08/17/34/36/42)
- [x] Reuse failing payment/deposit and handover audit probes and add retained regressions for retry, cancellation paths, and date-specific availability.
- [x] Lock/reload rental before payment; commit payment, deposit, rental status, and notifications together. Enforce one payment/deposit per rental. Show the recorded receipt reference and totals to both participants.
- [x] Add `Listing::availabilityLabel($date = null)` and `availabilityColor($date = null)` from paid/active booking dates; keep owner unavailable override and availability outside booked dates.
- [x] Block cancellation after either handover timestamp; cancel approved requests through the booking lifecycle rather than only changing the request. Notify owner for pending-request cancellation too.

### 4. Rental display, returns, and reviews (FR-19/22/23/45)
- [x] Add red regressions for past-due Active display, unauthorized owner/item reviews, public profile privacy, and accepted owner-confirmed closure sequence.
- [x] Derive overdue display without waiting for the daily job and refresh visible pages periodically.
- [x] Apply the selected return workflow without dropping manual condition/damage/deposit safeguards. Prompt both participants when complete; keep condition evidence and history.
- [x] Restrict each review to its true author and add public profile pages containing public identity plus submitted peer ratings/reviews only.

### 5. Admin transaction management (FR-28)
- [x] Test admin-only transaction details and access to existing dispute/account management actions.
- [x] Add a detail page with dates, both participants, payment/receipt, lifecycle timestamps, condition/damage evidence, cancellation and dispute history. Reuse existing dispute/account management without adding forced financial overrides.

### 6. Listing inquiries (FR-31)
- [x] Add red regressions for conversation reuse, participants-only access, notifications/read marking, combined inbox, and removed listing/account handling.
- [x] Extend existing Message storage with a separate listing-conversation association while preserving rental-request threads.
- [x] Add contact-owner and thread routes, listing link, and combined inbox display. Sending to inactive/closed accounts is blocked.

### 7. Map repair (FR-47/50/51/53)
- [x] Add red PHP/Node regressions for zero coordinates, safe preview text/photo, direct pin detail navigation, and no-location browsing.
- [x] Replace truthy checks with explicit null checks in PHP/JS/picker; construct preview DOM safely; navigate to the correct item on pin selection.

### 8. Integration and validation
- [x] Run each focused suite as its task completes, then the entire PHPUnit suite, retained acceptance probes, JavaScript tests, and production build.
- [x] Review diff and all callers for cross-task conflicts. Check browser flows using disposable fixtures and disabled outgoing mail.
- [x] Inspect local database compatibility/duplicate counts before applying additive migrations; never use destructive refresh or reseed commands on the application database.
- [x] Save a final FR-by-FR implementation/verification checklist and report any remaining deployment or concurrency limits.

## Commands

Use unique compiled view folders for simultaneous PHP runs and SQLite extensions:

```powershell
$env:VIEW_COMPILED_PATH = 'C:/laragon/www/lendly/storage/framework/views/requirements-repairs-tests'
New-Item -ItemType Directory -Path $env:VIEW_COMPILED_PATH -Force | Out-Null
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit docs/audit/RequirementsAuditTest.php
node --test tests/password-strength.test.js tests/map-popup.test.js
npm run build
```

Database changes are applied with `php artisan migrate`, after the compatibility checks. Never run `migrate:fresh` against the configured application database.
