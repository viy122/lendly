# Lendly requirements test report — 9 October 2026

**What this repo does:** Lendly lets members list items, request rentals, accept terms, record simulated payments, confirm pickup and return, review transactions, and raise disputes. Administrators moderate listings, suspend accounts, and resolve disputes. This audit assumes ordinary local use; the performance sample uses 1,000 listings and one sequential user at a time.

**Verdict:** The ordinary workflows work, but the app does **not** fully satisfy the supplied 53 functional and 20 non-functional requirements. Fix the map security issue, review permissions, booking/payment consistency, and history preservation first. Passing the existing tests does not establish full compliance.

## Scope and evidence

The source was the attached `Pasted text.txt` under attachment `aa5215d1-f6cd-47d1-973e-8ccfe7f589cd`. The original document and application source were not edited during this audit. The audit adds diagnostic tests and this report. Earlier authentication changes and other existing workspace changes remain outside this audit's edits.

Checks ran against the current working tree, on Windows with PHP 8.4.13, Laravel 12, Livewire/Volt, and the installed frontend dependencies:

| Check | Result | Meaning |
| --- | --- | --- |
| Existing PHP suite | **204 passed; 808 assertions; zero failures/errors** | Existing backend expectations hold; raw evidence is in `audit/backend-suite-junit.xml`. |
| Additional acceptance probes | **19 run; 5 passed; 14 failed; 39 assertions; zero execution errors** | See `audit/requirements-probes-junit.xml` and `audit/RequirementsAuditTest.php`. These assert the supplied requirements and edge cases missing from the ordinary suite. Failing probes are findings. They are deliberately outside normal test discovery. |
| Password strength JavaScript tests | **3 passed** | Strength progression, missing requirements, and Unicode handling. |
| Production frontend build | **Passed** | Vite compiled the current assets successfully. |
| Performance probe | **1 passed; 20 assertions** | Collected five samples each for search, map, request submission, and approval with 1,000 listings. This is not a production load test. |
| Browser checks | **Completed in the Codex in-app browser** | Map tiles/pins, keyword filtering, popup/detail navigation, fictional-account login/logout, combined dashboard, and mobile layout spot checks. |

PHP tests used disposable SQLite databases and array/fake email. Browser testing used fictional users in `storage/framework/testing/requirements-audit.sqlite`, a separate session cookie, and disabled outgoing email. The temporary preview was closed. Existing application rentals and accounts were not changed.

There is earlier live SMTP evidence from this chat: Gmail authenticated and accepted a test email to the configured sender at approximately **11:38 AM Asia/Manila on 9 October**. That proves SMTP acceptance, not inbox arrival or a completed email-link/reset-code round trip. No additional live email was sent in this audit.

## Must fix

### 1. A map pin can execute a listing owner's JavaScript

- **What this is:** Map popups insert the listing name into HTML in `resources/js/map.js:106`.
- **Problem:** A fictional approved listing name containing a harmless image error handler executed when its pin opened. It replaced itself with “Audit script executed”; screenshot below. This is stored cross-site scripting: user-supplied text becomes executable browser content.
- **Fix:** Build the popup with DOM elements and set the name using `textContent`. Use safe element properties for links and photos rather than interpolating user text into HTML.
- **If we skip it:** An approved malicious listing can run actions in a visiting user's browser. This prevents signing off NFR-07.

![Harmless map security reproduction](C:/laragon/www/lendly/docs/audit/map-script-proof.jpg)

### 2. An owner can write the renter's review of themselves

- **What this is:** `app/Livewire/Renter/Rentals/Show.php:151` and `:171` submit owner and item reviews.
- **Problem:** The owner is allowed to view their rental through the renter component, then `submitOwnerReview` accepts their five-star self-review. The acceptance probe expected 403 and received 200. The item-review method has the same missing participant check by inspection.
- **Fix:** Authorize these actions against the actual `renter_id`, in addition to checking completion and duplicate reviews.
- **If we skip it:** Owners can fabricate ratings attributed to renters. FR-23 and NFR-07 fail this case.

### 3. Approval can create inconsistent or overlapping bookings

- **What this is:** `app/Livewire/Owner/RentalRequests/Index.php:41` checks availability, approves a request, then creates its rental.
- **Problem:** Injecting a booking-storage failure leaves the request Approved with no rental. A controlled interleaving of two approvals also produced **two rentals for the same item and dates**. The overlap check and writes are not protected as one operation.
- **Fix:** Use one database transaction, lock the shared listing before checking overlaps, recheck request/terms state under that lock, and constrain one rental per request.
- **If we skip it:** Dates can be blocked without a booking, or promised to two renters. NFR-01/NFR-03 and reliable FR-14 behavior fail.

### 4. Payment can be duplicated or only partly recorded

- **What this is:** `app/Livewire/Renter/Rentals/Show.php:58` creates payment and deposit rows, then updates rental status.
- **Problem:** Failing deposit creation leaves a payment record while the rental remains Payment Pending. Two actions loaded before either completes create **two payments**. The ordinary “pay twice” test covers a later sequential retry, which passes; it does not cover overlapping actions.
- **Fix:** Put these writes in one transaction, lock/reload the rental before checking payment state, and add uniqueness for a rental's payment and deposit. Notify after the successful commit.
- **If we skip it:** Receipts, deposits, and rental status disagree. NFR-01/NFR-03 and FR-17 cannot be signed off.

### 5. Deleting one account deletes the other party's history

- **What this is:** The profile deletion action hard-deletes the user. Rental foreign keys use cascading deletion in `database/migrations/2026_09_06_171728_a_create_rentals_table.php:15`.
- **Problem:** Deleting the fictional renter through the real profile action also removed their completed rental from the owner's records. Related payment records are configured to cascade with the rental.
- **Fix:** Preserve historical transaction rows and participant snapshots when closing an account. Block account closure while active obligations remain; define a retention/anonymization policy before deleting financial history.
- **If we skip it:** Another user's transaction history can disappear. FR-24 and NFR-01 fail. This is a data-loss finding, not a legal-compliance conclusion.

### 6. Removing a listing breaks rental history

- **What this is:** Owners soft-delete listings in `app/Livewire/Owner/Listings/Index.php`; rental pages still dereference the default listing relation.
- **Problem:** Remove a listing with a completed rental, then open the renter's rental history: the acceptance probe gets **HTTP 500**. The rental row exists, but its listing relation excludes the removed listing.
- **Fix:** Make historical rental relations include removed listings, preserve the agreed listing details, and handle missing historical data in views. Apply the shared relationship fix to history, detail, admin, and dispute callers.
- **If we skip it:** A normal FR-07 action breaks FR-24 and reliable access to old transactions.

### 7. Cancellation remains possible after the owner hands over the item

- **What this is:** `app/Models/Rental.php:215` allows cancellation while status is Paid, until both pickup confirmations make it Active.
- **Problem:** The owner marks pickup/handover first. Before the renter confirms receipt, the renter can cancel successfully; the acceptance probe expected 403 and received 200.
- **Fix:** Reject cancellation after either handover/receipt timestamp is recorded. Keep the existing status restriction as an additional check.
- **If we skip it:** The item can be physically released while the booking is cancelled and its dates freed. FR-34 fails this boundary case.

### 8. An open request form accepts an item made unavailable

- **What this is:** `app/Livewire/RentalRequests/Create.php:33` checks publication and availability when the form opens.
- **Problem:** Open the form, mark the listing unavailable from another session, then submit. A new request is still created because submission does not recheck that state.
- **Fix:** Reload and validate publication, availability, fulfillment options, and rate/terms before saving. Coordinate that check with booking updates where necessary.
- **If we skip it:** Renters can request withdrawn items. FR-11 and synchronized availability do not hold across sessions.

### 9. Login does not implement the required role choice

- **What this is:** FR-03 explicitly requires selecting Owner or Renter at **each** login and accessing only that role's modules.
- **Problem:** The current login has no choice. A successful fictional member login opened a dashboard containing both Owner and Renter features. `User::isOwner()` and `isRenter()` both mean “not an admin.” Existing tests intentionally expect this combined behavior.
- **Fix:** Add the selected role to the authenticated session and enforce it in navigation and route/action checks while retaining one account with both capabilities. Alternatively, the requirement owner must formally revise FR-03 to accept the combined design.
- **If we skip it:** The app conflicts with the supplied document even though current role tests pass. Do not change the requirement document merely to make the app appear compliant.

## Should fix

### 10. Paid bookings do not show Reserved item status

- **What this is:** FR-42 requires the item's Reserved status for the paid booking period.
- **Problem:** A paid rental covering today still has no Reserved indication on its listing. Item availability is a boolean plus moderation status; neither provides Reserved/Rented. Approved-date overlap checks and the availability calendar do work.
- **Fix:** Derive date-specific Reserved/Rented availability from paid/active bookings and use it consistently in listing views. Preserve availability for dates outside a booking.
- **If we skip it:** FR-42 fails, and FR-08 is only partly represented.

### 11. Request and booking notifications reach only one party

- **What this is:** FR-13 and FR-40 explicitly require notifications to both parties.
- **Problem:** Submission notifies the owner; approval/rejection notifies the renter. Approving a booking sends no owner confirmation. The extra approval-notification probe fails for the owner.
- **Fix:** Send the required status/booking notifications to both participants after successful persistence, using the existing notification class.
- **If we skip it:** One participant has no notification record for the same booking event.

### 12. The owner receives an earnings summary instead of a traceable receipt

- **What this is:** FR-17 requires a digital receipt for each party.
- **Problem:** The renter page shows a transaction receipt and reference. The owner's page shows an earnings breakdown but omits the payment reference. The existing owner-receipt test only asserts “Earnings breakdown”; the extra reference check fails.
- **Fix:** Render the recorded payment reference, amount, payment date, and participants as the owner's receipt, reusing the existing payment record.
- **If we skip it:** The owner cannot identify their receipt against a particular recorded payment. FR-17 remains partial.

### 13. Reviews are not published to public user profiles

- **What this is:** FR-45 requires each participant's submitted rating/review on the respective public profile.
- **Problem:** Both participants already see optional review forms after completion; the renter also receives a notification. However, there is no public user-profile route or screen. Peer ratings are aggregated, and item reviews appear on item detail pages, which does not fulfill the public-profile requirement.
- **Fix:** Add a public profile showing only approved public identity/rating/review fields. Keep contact/account details private and fix finding 2 before trusting submitted reviews.
- **If we skip it:** FR-45 remains incomplete. Lack of a second review notification alone is **not** treated as a failure, because the owner already sees a review prompt.

### 14. Map-pin previews omit the photo and require a second click for details

- **What this is:** FR-53 describes a photo/name/daily-price preview and opening details when the pin is selected.
- **Problem:** A normal test pin showed name, price, and a “View listing” link, with **zero image elements**. Clicking the pin opens only the popup; clicking its link then opens the correct details page. The server supplies an image URL, but the popup does not use it.
- **Fix:** Include the item photo safely and agree whether pin selection should open details directly or whether the two-step popup/link flow is acceptable. Implement the approved interaction.
- **If we skip it:** The photo requirement is unmet, and the detail-opening behavior differs from the document's literal wording.

### 15. An overdue rental can still display Active Rental

- **What this is:** Due Soon is derived at display time, but Overdue depends on a stored status updated by a daily command.
- **Problem:** An Active rental whose end date was yesterday still returns “Active Rental” before that job runs. There is also no polling/broadcast refresh on the rental pages. The overdue command correctly updates status, fees, and notifications when explicitly run by tests.
- **Fix:** Derive past-due display state from dates, schedule persistent status updates at an agreed cadence, and refresh visible state appropriately.
- **If we skip it:** FR-19's real-time status claim is inaccurate. FR-20 also depends on deployment actually running the scheduler.

### 16. Valid zero coordinates disable distance features

- **What this is:** Map and location-picker code uses truthiness to decide whether latitude/longitude exists.
- **Problem:** At latitude 0, a 5 km radius still includes a listing about 111 km away; marker distances become null. Longitude 0 has the same code path. The listing edit form also turns a stored zero coordinate into null.
- **Fix:** Check for `null` explicitly on the server and in map initialization/UI conditions.
- **If we skip it:** FR-50/FR-51 and NFR-19 fail valid coordinate cases. Normal Manila nearest-first and radius cases pass.

## Functional requirement matrix

**Tested** means the stated behavior has relevant passing runtime checks within the limits listed here. **Partial** means some required behavior works but a gap or interpretation remains. **Fail** means a concrete mismatch was reproduced or the required feature is absent. **Reviewed** means code was inspected but the full user/service interaction was not exercised. These are not coverage percentages or production guarantees.

| ID | Result | Evidence and remaining gap |
| --- | --- | --- |
| FR-01 | Tested | Registration tests: name/email/password create one member account with both capabilities. Gmail-only policy is an additional accepted requirement. |
| FR-02 | Tested | EmailVerificationTest and RoleAccessTest exercise signed verification and restricted unverified access. Live inbox/link delivery is not a fresh audit test. |
| FR-03 | **Fail** | Login/logout work; per-login role choice and selected-role restriction are absent. Finding 9; browser login confirms combined dashboard. |
| FR-04 | Tested | PasswordResetTest exercises email code delivery through test transport, expiration, replacement, reset, invalid codes, and lockout. |
| FR-05 | Tested | ListingTest, form validation, and the extra upload probe create listings with required descriptive/pricing/availability fields. |
| FR-06 | Tested | Extra probe uploads two images, records both rows, and confirms both files exist in disposable storage. |
| FR-07 | Partial | Edit/resubmit, pause/reactivate, and removal exist; removing a transacted listing breaks history. Finding 6. |
| FR-08 | Partial | Owners can toggle the availability boolean; explicit/derived Rented and Reserved item status is missing. Finding 10. |
| FR-09 | Tested | ListingSearchTest checks publication/availability, keyword, category/subcategory, price, condition, and sorting. |
| FR-10 | Tested | Listing detail renders photos, description, price, owner rating data, and approved-date calendar; normal calendar/details inspected in browser. See local photo-serving limitation below. |
| FR-11 | **Fail** | Normal requests, valid dates, max duration, self-rental rejection, and overlap checks pass. An open form still accepts an item subsequently made unavailable. Finding 8. |
| FR-12 | Tested | RentalRequestTest/TermsAcceptanceTest cover owner approval/rejection and another owner's denied access. Concurrency weakness is listed under NFR-01. |
| FR-13 | Partial | NotificationsAndChatTest verifies the other participant is notified, but not both for every required status. Finding 11. |
| FR-14 | Partial | Approval normally creates a rental after terms acceptance; interrupted/interleaved approvals break integrity. Finding 3. |
| FR-15 | Tested | Both acceptance timestamps and validation are tested; UI states the 48-hour/20% cancellation policy. Legal suitability is unverified. |
| FR-16 | Tested | Inclusive rental days, agreed price, commission, deposit, and rounded totals are tested in RentalRequestTest/PaymentTest. |
| FR-17 | Partial | Payment records and renter receipt work in the normal flow. Owner reference is absent; overlapping/interrupted writes fail. Findings 4 and 12. |
| FR-18 | Tested | Fulfillment choice, participant messaging, and pickup confirmations are exercised; no courier integration is claimed. |
| FR-19 | **Fail** | Active/Due Soon display works, but past-due Active rentals remain Active before the daily job; live page refresh is absent. Finding 15. |
| FR-20 | Partial | Lifecycle/notification tests run overdue logic; reminder command is registered and logic inspected. Actual scheduled execution and delivery timing were not verified. |
| FR-21 | Tested | ConditionAndDamageTest/RentalLifecycleTest cover return confirmation and returned condition/damage records. |
| FR-22 | Partial | Rental becomes Returned after both confirmations and Completed after owner condition/inspection. It is kept in history. Immediate closure at return alone differs from literal wording; confirm this extra inspection step. |
| FR-23 | **Fail** | Normal reciprocal reviews and duplicate prevention pass; owner can submit a renter-attributed self-review. Finding 2. |
| FR-24 | **Fail** | Ordinary history includes all rental statuses and excludes other renters. Account deletion removes the other party's history; listing removal makes history return 500. Findings 5–6. |
| FR-25 | Tested | DashboardAnalyticsTest checks paid earnings, completed transactions, monthly figures, and listing analytics. Refund accounting beyond the implemented simulated workflow was not validated. |
| FR-26 | Tested | RoleAccessTest checks admin account viewing, suspension/reactivation, admin restrictions, and suspended-user access denial. |
| FR-27 | Tested | ListingTest checks admin approval/rejection with reason and public visibility; moderation implementation reviewed. |
| FR-28 | Partial | AdminTransactionsTest checks access, all-transaction viewing, search/status filters, and totals. Transaction page has no detail or management actions; clarify what “manage” must permit. |
| FR-29 | Tested | ReviewsAndDisputeTest exercises damage-related and general disputes; both participant components provide filing actions. |
| FR-30 | Tested | Admin dispute workflow, resolution validation, deposits, and unauthorized access checks run in ReviewsAndDisputeTest. |
| FR-31 | Partial | Participants can message about rental requests and ensuing rentals. No independent pre-request listing inquiry exists; clarify whether that is required by “item listing or rental transaction.” |
| FR-32 | Tested | NotificationsAndChatTest/MessagesInboxTest exercise request threads, incoming-message notifications, latest message, unread counts, and read marking. Open threads do not automatically refresh incoming messages. |
| FR-33 | Tested | Request-specific message history persists; inbox provides all participating threads and excludes unrelated users. History is separated by request, not merged by person. |
| FR-34 | **Fail** | Pending requests and pre-pickup bookings can cancel; Active rentals cannot. Owner-only handover still allows cancellation. Finding 7. |
| FR-35 | Tested | CancellationTest covers timing, 48-hour cutoff policy, 20% fee, and no-fee advance cancellation. No external money movement is claimed. |
| FR-36 | Partial | Confirmed-booking cancellation notifies owner and changes approved request status to free dates. Item Reserved status is absent; pending-request cancellation sends no owner notification by inspection. |
| FR-37 | Tested | Booking cancellation persists time/reason/fee and displays them to both participants. Request-only cancellation records status without a reason/fee; optional reason is not treated as mandatory. |
| FR-38 | Tested | ProfileTest covers name/contact/address/email/photo and deletion; PasswordUpdateTest covers confirmed compliant password changes. Deletion side effect fails FR-24. |
| FR-39 | Tested | AuthenticationTest and RoleAccessTest cover credentials/logout, admin/member boundaries, and administrator dashboard access. |
| FR-40 | **Fail** | Extra approval probe finds a renter notification but no owner booking confirmation. Finding 11. |
| FR-41 | Tested | PaymentTest exercises the explicitly supported simulated payment action. Third-party payment gateways are excluded by the supplied NFR-16 scope. |
| FR-42 | **Fail** | Paid booking covering today still displays no Reserved item status. Calendar/overlap protection is not the required status. Finding 10. |
| FR-43 | Tested | Separate owner and renter pickup timestamps are exercised by RentalLifecycleTest/NotificationsAndChatTest. |
| FR-44 | Tested | Both confirmations transition to Active Rental, the implementation's equivalent of Ongoing. |
| FR-45 | **Fail** | Extra probe verifies optional forms for both after closure. Public user profiles publishing peer reviews are absent. Finding 13. |
| FR-46 | Reviewed | `RentalLifecycle::resolveDispute` sends resolution notifications to both participants. Resolution behavior runs in existing tests; both-recipient delivery was checked in code rather than a dedicated assertion. |
| FR-47 | Reviewed | Owner form/map picker saves coordinates and validates their ranges. Browser permission/picker interaction was not exercised. Zero-coordinate edit behavior needs finding 16's fix. |
| FR-48 | Tested | Map test filtering plus browser tiles/pins work for published available listings with coordinates. Finding 1 makes popup rendering unsafe. |
| FR-49 | Reviewed | `getCurrentPosition` is called by the user control; success centers the map and adds a location marker. Actual permission grant/device position was not requested in this audit. |
| FR-50 | Partial | Manila radius test passes and UI offers 5/10/25/50 km. Valid zero coordinates bypass filtering. Finding 16. |
| FR-51 | Partial | Extra Manila nearest-first probe passes; zero coordinates disable sorting/distances. Finding 16. |
| FR-52 | Tested | Shared keyword behavior has an extra passing probe; category/subcategory query conditions match browsing; browser keyword updates pins. |
| FR-53 | **Fail** | Popup has name/price/link but no photo. Pin opens popup; detail opens only after clicking link. Finding 14. |

## Non-functional requirement matrix

| ID | Result | Evidence and remaining gap |
| --- | --- | --- |
| NFR-01 | **Fail** | Controlled booking/payment failures, overlapping actions, and deletion probes contradict always-synchronized data. Findings 3–6. |
| NFR-02 | Tested | Cost and cancellation arithmetic pass ordinary/boundary tests. This does not validate consistency of the surrounding payment writes. |
| NFR-03 | **Fail** | Overlapping payment/approval actions, stale availability, and broken historical listing relations fail consistent session behavior. Findings 3–4, 6, 8. |
| NFR-04 | Unverified | Local app was accessible. No production uptime history, hosting resilience, backups/restore, maintenance communications, or monitoring evidence was available. |
| NFR-05 | Measured, not signed off | Local timings below. No numerical target or defined normal concurrent load was supplied. SQLite test-harness timings cannot certify deployed MySQL behavior. |
| NFR-06 | Tested | User password hashed cast and Hash::make/Hash::check are exercised by registration/reset/update tests; plaintext passwords are not stored by these paths. |
| NFR-07 | **Fail** | Most admin/participant access tests pass, but review permission bypass and executable map content remain. Per-login role restriction is also absent. Findings 1–2, 9. |
| NFR-08 | Unverified | No legal compliance sign-off. Agreements, privacy/retention policy, operating practices, and payment handling need review against the applicable jurisdiction and deployment. |
| NFR-09 | Partially checked | Browser login/logout, map search/detail navigation, desktop/mobile layouts, and backend listing/booking actions were checked. No representative-user usability study or end-to-end browser booking study was performed. |
| NFR-10 | Partial | Validation-specific messages and SMTP retry messages are tested/reviewed. Removing a listing produces a 500 history page; injected storage failures have no safe recoverable booking/payment outcome. |
| NFR-11 | Reviewed | Components, policies, services, models, and enums are separated. Main auth/rental/map/admin/message paths reviewed; no claim of a whole-repo maintainability certification. |
| NFR-12 | Unverified capacity | Listings paginate, but the map loads all matching listings/images and computes distances in PHP on every render. Messages also load whole histories/threads. No concurrent/growth test proves capacity or a no-redesign limit. |
| NFR-13 | Partial | Backend suite and strength-unit tests exist; this audit automated several browser interactions. No maintained, repeatable full browser-workflow test suite was found. |
| NFR-14 | Tested | Registration/listing validation, Geo, cancellation, and lifecycle behaviors can be tested independently; current suite demonstrates that separation. |
| NFR-15 | Partially checked | In-app Chromium browser only. Listing/dashboard checked at 390×844 viewport, with content width equal to available width. All major browsers and real devices were not tested. |
| NFR-16 | Partial | Current tests exercise email notifications with test transport; earlier live Gmail SMTP acceptance succeeded. Browser loaded external map tiles. Actual verification/reset inbox round trips and service-outage resilience remain unverified. |
| NFR-17 | Reviewed | Browser geolocation API is called on user action and uses the browser's permission mechanism. No device location was collected or permission prompt accepted during this audit. |
| NFR-18 | Measured, not signed off | Browser keyword filtering changed pins successfully; timings below sample server-side map renders/updates. No agreed SLA, production load, or full permission/location/radius browser timing exists. |
| NFR-19 | **Fail** | Geo's ordinary distances and Manila ordering pass; zero-latitude probe includes an out-of-radius item and returns null distances. Finding 16. |
| NFR-20 | Tested | Extra probe retains listings and null distances when no location is supplied even if radius has a value; browser browsing works without sharing location. |

## Authentication requirements from the earlier request

The fresh ordinary suite also covers the earlier authentication work: Gmail-only validation; case/dot/plus alias duplicate checks; confirmed passwords with minimum eight characters and upper/lower/number/symbol requirements; verification before full access; hashed six-digit reset codes; exact ten-minute expiry; single use/replacement; and the agreed three failures / fifteen-minute lockout. Password-form markup and server validation tests pass, and the three strength-meter JavaScript tests pass. Clipboard blocking was browser-tested earlier in this chat; this audit's browser login used typed fictional credentials.

Mailbox verification proves control of an email address. It cannot establish one account per human: a person can own several different Gmail addresses. The code enforces unique canonical Gmail mailboxes, not real-world identity uniqueness.

## Local response-time samples

Five sequential samples, with 1,000 listings, SQLite `:memory:`, warm compiled views, and array email:

| Operation | Median | Observed range | Measurement scope |
| --- | --- | --- | --- |
| Search | 177.27 ms | 169.02–196.95 ms | Component mount plus keyword update and assertions. |
| Map | 2,260.75 ms | 2,106.95–2,770.01 ms | Component mount plus latitude, longitude, and radius updates: four renders combined, not one HTTP response. |
| Submit rental request | 16.50 ms | 14.97–151.91 ms | Submit action and render; setup excluded. |
| Approve request | 21.43 ms | 21.24–52.49 ms | Approval action and render; setup excluded. |

Raw samples and method: `audit/local-timings.json`. Sample count is small; cold work explains some variation, and another test process was active during part of this run. These figures are evidence for local behavior only. Browser/network/tile downloads, concurrent users, SMTP latency, production storage, and deployment resources were not represented.

## Reproduction

From `C:\laragon\www\lendly`, use a unique compiled-view directory for each simultaneous test process on Windows:

```powershell
$env:VIEW_COMPILED_PATH = 'C:/laragon/www/lendly/storage/framework/views/requirements-audit-probes'
New-Item -ItemType Directory -Path $env:VIEW_COMPILED_PATH -Force | Out-Null
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit docs/audit/RequirementsAuditTest.php
node --test tests/password-strength.test.js
npm run build
```

The acceptance file is intentionally **not** included in normal PHPUnit suite discovery. Run it explicitly to see the unmet requirements; it should not be described as a passing regression suite. Simulated storage failures use model hooks, overlapping approvals use a controlled interleaving, and overlapping payments use two component instances loaded before payment. These reproduce vulnerable action ordering without claiming a full concurrent MySQL stress test.

`audit/BrowserFixture.php` is restricted to the named disposable SQLite path and refuses other database configurations. Its map security fixture must stay in a local disposable environment. Its photo URL is deliberately a fixture placeholder; the broken image in the mobile layout screenshot is not counted as an application defect.

## Remaining verification and setup

- Agree acceptable response times and expected simultaneous users, then benchmark the deployed database and hosting environment.
- Confirm the intended per-login role design, admin transaction-management actions, pre-request listing messaging, pin-to-detail interaction, and return/inspection closure sequence. These points should be resolved with the requirement owner, not silently rewritten.
- Verify the deployment scheduler. `routes/console.php` schedules overdue/reminder commands daily; `config/app.php` currently uses UTC, so scheduled midnight is 8 AM in Manila. Confirm the intended business timezone/cadence. This audit did not run a production scheduler.
- `public/storage` was absent in this checkout. Uploaded photo storage is tested, but local public photo serving needs the normal storage link/deployment equivalent. No live storage link or hosting settings were changed for this audit.
- Complete inbox-level verification/reset testing with a controlled mailbox. Prior SMTP acceptance alone does not prove inbox arrival.
- Run maintained browser workflows in Chrome/Edge/Firefox/Safari and on representative phones/tablets. This audit only spot-checked one browser and one mobile breakpoint.
- Obtain privacy/consumer-policy review for the actual deployment. No legal conclusion is claimed here.

**Not checked:** Production MySQL concurrency, real concurrent load, long-term availability/maintenance, backup restoration, all major browsers/devices, real geolocation permission/success, inbox delivery round trips, dependency-wide security scanning, and legal compliance. Main requirements paths were reviewed; vendor internals, demo seeders, and every auxiliary UI screen were not audited line by line.

**Risk:** The reproduced browser-script, authorization, transaction-consistency, and history-loss defects should be resolved before using the app with real users or payments.
