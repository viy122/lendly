from pathlib import Path
from collections import Counter
from xml.etree import ElementTree as ET
import re

ROOT = Path(r'C:\Users\Admin\lendly')
OUT = ROOT / 'audit-evidence'

def link(file, line=None, label=None):
    target = (ROOT / file).as_posix() + (f':{line}' if line else '')
    return f'[{label or file}](<{target}>)'

# Status, implementation assessment, primary source location.
fr = {
1: ('Implemented', 'Registration validates name, unique email and confirmed password. One member account has both capabilities, which satisfies the and/or account capability; the separate login role requirement is assessed under FR-03.', 'resources/views/livewire/pages/auth/register.blade.php', 25),
2: ('Partial', 'Verification links and signed verification routes exist, but verification is optional for member features. An unverified renter successfully submitted a request in audit probe A1. The current local mailer logs messages instead of delivering email.', 'routes/web.php', 46),
3: ('Partial', 'Credential validation, session regeneration, throttling and logout exist. There is no Owner/Renter selector at login. Every member passes both role checks and sees a combined dashboard, contrary to the selected-role-only requirement.', 'app/Models/User.php', 86),
4: ('Partial', 'Token-based password recovery and reset are implemented and existing password-reset tests pass. Actual recovery email delivery is unavailable with the current local log mailer; a real inbox delivery test is outstanding.', 'resources/views/livewire/pages/auth/forgot-password.blade.php', 19),
5: ('Implemented', 'Owners can create listings with category, description, condition, rates, availability flag and other fields. Available date windows are not modeled; that specific process-flow step is assessed in B.2.', 'app/Livewire/Owner/Listings/Form.php', 153),
6: ('Implemented', 'Multiple image uploads are validated and stored with listing image records. Uploads are optional, so the B.2 process expectation of at least one photo is not enforced.', 'app/Livewire/Owner/Listings/Form.php', 153),
7: ('Implemented', 'Owners can edit, deactivate, reactivate and soft-delete their listings. Ownership checks exist. Edits/reactivation require admin approval again. The effect of deletion on existing rentals is a separate history defect.', 'app/Livewire/Owner/Listings/Index.php', 17),
8: ('Partial', 'Owners can set is_available and pause listings, but there are no actual Available/Rented/Reserved item lifecycle states. Rental hand-over and payment do not synchronize the listing flag with a rented or reserved status.', 'app/Models/Listing.php', 47),
9: ('Implemented', 'Published, available listings support keyword, category/subcategory, price, condition and brand filtering plus sorting. Existing ListingSearchTest exercises the main filters.', 'app/Livewire/Listings/Browse.php', 54),
10: ('Implemented', 'The details page includes photos, description, rates, an availability calendar based on approved date ranges and owner average rating. Calendar and owner-rating code are present; browser interaction was not exercised in this audit.', 'app/Livewire/Listings/Show.php', 29),
11: ('Partial', 'Rental-period validation, maximum duration, fulfillment selection and approved-overlap checks exist. Availability is checked on mount only: an already-open form can submit after the listing becomes inactive/unavailable (A9).', 'app/Livewire/RentalRequests/Create.php', 77),
12: ('Implemented', 'Owners can review, approve and reject requests, with ownership authorization and overlap rejection. Atomic protection against simultaneous approvals is missing and is assessed under NFR-01/NFR-03.', 'app/Livewire/Owner/RentalRequests/Index.php', 41),
13: ('Partial', 'Submission notifies the owner; approval and rejection notify the renter. Both parties can view statuses, but both do not receive notifications for each current request status.', 'app/Livewire/Owner/RentalRequests/Index.php', 83),
14: ('Partial', 'Approval creates the booking record. The normal form path captures renter acceptance first, but the approval action never checks the persisted renter acceptance timestamp; A2 confirms a request with no renter acceptance can be booked.', 'app/Livewire/Owner/RentalRequests/Index.php', 41),
15: ('Partial', 'Renter and owner checkboxes are enforced in their respective UI actions and timestamps are recorded. Cancellation terms are shown. Approval does not enforce the combined persisted acceptance invariant (A2); the process order also differs from B.4.', 'app/Livewire/Owner/RentalRequests/Index.php', 41),
16: ('Implemented', 'Inclusive rental days times daily rate determine rental fee; commission and deposit are shown separately in the total. Existing RentalRequestTest validates the cost breakdown.', 'app/Livewire/RentalRequests/Create.php', 44),
17: ('Implemented', 'Payment records have unique receipt references, amount and paid time; both rental detail pages show receipts. Existing PaymentTest checks receipt visibility for both parties. This is a simulated payment ledger.', 'app/Livewire/Renter/Rentals/Show.php', 58),
18: ('Partial', 'A pickup/delivery choice and request-linked chat allow coordination, and both parties can confirm transfer. There is no persisted agreed exchange time/address workflow or notification of a confirmed pickup/delivery schedule.', 'app/Livewire/RentalRequests/Create.php', 77),
19: ('Partial', 'Active, Due Soon and Overdue displays exist. Overdue state changes only when the scheduled command runs; rental views do not poll/broadcast updates, so continuously open sessions do not show real-time changes.', 'app/Models/Rental.php', 199),
20: ('Implemented', 'Daily scheduled commands send due-tomorrow reminders to the renter and new-overdue notifications to both parties. The automated logic exists; continuous execution of the deployment scheduler was not verified.', 'routes/console.php', 12),
21: ('Implemented', 'Owners can confirm return and record returned condition, notes and photos, including damage. The condition action requires the Returned state, which currently requires both parties to confirm return.', 'app/Livewire/Owner/Rentals/Show.php', 121),
22: ('Partial', 'After both return confirmations, a recorded after-condition and owner inspection complete the rental and retain its row in history. Retention is not robust: account deletion cascades away the other party\'s rental history (A11), and listing deletion loses the history relation (A7).', 'app/Services/RentalLifecycle.php', 89),
23: ('Partial', 'Owner-to-renter and renter-to-owner ratings/comments are available after completion. Renter review actions do not verify that the actor is the renter: the owner can post a renter-to-owner self-review (A8).', 'app/Livewire/Renter/Rentals/Show.php', 151),
24: ('Partial', 'Both rental histories and request lists exist, with ownership filtering and pagination. Soft-deleted listings disappear from rental relationships (A7), and hard-deleting one account destroys the shared rental record (A11), preventing complete retained history.', 'app/Models/Rental.php', 77),
25: ('Partial', 'The member dashboard provides owner earnings and rental summaries. Full rental fees from paid cancelled bookings remain in totalEarnings, including a zero-fee cancellation (A12); refunds and cancellation settlements are not reconciled.', 'app/Livewire/Member/Dashboard.php', 47),
26: ('Implemented', 'Admin can search/view users and suspend/reactivate non-admin accounts. Suspended users are blocked on subsequent requests and login. Existing RoleAccessTest covers these controls.', 'app/Livewire/Admin/Dashboard.php', 31),
27: ('Implemented', 'Admin can approve, reject with a reason and deactivate listings, with status filtering. Existing ListingTest exercises moderation. Admin routes require the admin role.', 'app/Livewire/Admin/Listings/Index.php', 27),
28: ('Partial', 'Admin can list/filter/search all rentals and view aggregate values. The admin transactions component has no rental management actions or dedicated transaction detail/action screen.', 'app/Livewire/Admin/Rentals/Index.php', 45),
29: ('Implemented', 'Owners and renters can file rental disputes; renters can dispute damage claims. Disputes record reason, description and rental context. Standalone item/user reporting shown in the flow diagram is not implemented.', 'app/Livewire/Owner/Rentals/Show.php', 204),
30: ('Implemented', 'Admin can inspect dispute context and damage photos, choose a resolution and record notes; damage outcomes update deposits. General refund decisions are recorded recommendations, not executed money transfers, consistent with the limited payment scope.', 'app/Livewire/Admin/Disputes/Index.php', 42),
31: ('Implemented', 'Owner and renter can exchange direct messages about a rental request and its item. Chat is request-bound; there is no pre-request listing inquiry thread.', 'app/Livewire/Messages/Show.php', 35),
32: ('Implemented', 'Messages are grouped by rental request, with inbox previews/unread counts and recipient notifications. Existing NotificationsAndChatTest and MessagesInboxTest cover these behaviors.', 'app/Livewire/Messages/Concerns/ListsThreads.php', 15),
33: ('Implemented', 'Each request conversation shows its stored message history and the inbox exposes conversations with the other party. Different requests with the same person remain separate threads. New incoming messages require page refresh/re-entry; live delivery is not specified in this requirement.', 'app/Livewire/Messages/Show.php', 18),
34: ('Partial', 'Pending requests and pre-active bookings can be cancelled. The guard checks rental status, not actual owner hand-over: cancellation succeeds after the owner has logged hand-over while renter confirmation is pending (A5).', 'app/Models/Rental.php', 215),
35: ('Implemented', 'The policy computes 20 percent of rental fee within 48 hours of start and zero earlier, and records the fee on cancellation. Tests cover chargeable/free timing. Applying actual monetary deductions is outside the simulated ledger and is not verified.', 'app/Services/CancellationPolicy.php', 19),
36: ('Partial', 'Confirmed booking cancellation notifies the owner and changes the request from Approved to Cancelled, freeing overlap dates. Pending request cancellation sends no owner notification (A4). No explicit listing status is restored to Available.', 'app/Livewire/Renter/RentalRequests/Index.php', 17),
37: ('Partial', 'Confirmed cancellations retain reason, timestamp and fee in both rental views. Pending requests retain only Cancelled status, without reason/time/fee capture. The separate history-retention defects also apply.', 'app/Services/RentalLifecycle.php', 28),
38: ('Implemented', 'Profile editing supports avatar, name, address, email, phone and password; changing email clears verification. Existing ProfileTest and PasswordUpdateTest validate profile actions.', 'resources/views/livewire/profile/update-profile-information-form.blade.php', 40),
39: ('Partial', 'Admin credential login/logout and protected admin routes exist. In the current local environment, the demo switcher lets a member enter a seeded admin account without administrator credentials (A10); this path is gated off outside local environments.', 'resources/views/livewire/layout/sidebar.blade.php', 22),
40: ('Partial', 'The renter receives RequestApproved when the booking is created. No owner booking confirmation is sent (A3); there is no equivalent both-party booking notification.', 'app/Livewire/Owner/RentalRequests/Index.php', 83),
41: ('Partial', 'The renter can confirm a simulated payment and create payment/deposit records. No real funds are collected or offline payment proof verified. The document excludes live gateway integration, so the missing gateway itself is not a defect; payment completion remains demonstrational.', 'app/Livewire/Renter/Rentals/Show.php', 58),
42: ('Missing', 'Payment changes only rental status to Paid. There is no Reserved listing status or payment-driven reservation state. Approved requests already block date ranges before payment, which is a different implementation.', 'app/Livewire/Renter/Rentals/Show.php', 58),
43: ('Implemented', 'Separate owner and renter pickup-confirmation timestamps record each transfer acknowledgment. The UI uses Confirm pickup for both roles and fulfillment types rather than the document\'s Handed Over/Received wording.', 'app/Services/RentalLifecycle.php', 54),
44: ('Implemented', 'Both transfer confirmations move the rental into Active, the implementation\'s equivalent of Ongoing. This audit treats the equivalent state name as acceptable; activation waits for both acknowledgments.', 'app/Services/RentalLifecycle.php', 54),
45: ('Partial', 'Completed rentals expose optional review forms and the renter receives a review request. There is no owner review notification and no public user profile route for publishing received owner/renter reviews; public listing reviews are a different feature.', 'app/Services/RentalLifecycle.php', 89),
46: ('Implemented', 'Dispute resolution records the decision and sends in-app resolution notifications to both involved parties. Existing dispute tests exercise resolution; no external email/SMS channel is required here.', 'app/Services/RentalLifecycle.php', 156),
47: ('Implemented', 'Listing form supports a location string and latitude/longitude via an interactive picker, so owners can place an item on the map. Coordinates are optional; a text-only location does not produce a pin.', 'app/Livewire/Owner/Listings/Form.php', 153),
48: ('Implemented', 'Leaflet displays pins for published available listings with coordinates. Browser tile loading was not verified. Reserved/rented filtering shares the missing item-state issue under FR-08/FR-42.', 'resources/js/map.js', 166),
49: ('Implemented', 'Use my location invokes browser geolocation, centers the map and adds a location marker on success. This is permission-controlled browser API code; a live permission prompt was not exercised.', 'resources/js/map.js', 214),
50: ('Partial', 'A distance selector offers 5/10/25/50 km and backend filtering works for ordinary coordinates. Valid latitude or longitude equal to zero is treated as no location, disabling distance/radius behavior (A6).', 'app/Livewire/Listings/Map.php', 71),
51: ('Partial', 'Computed markers are nearest-first after location is set, but a zero-valued coordinate bypasses both distance computation and sorting (A6). No ordered distance result list is exposed beyond the pin data.', 'app/Livewire/Listings/Map.php', 75),
52: ('Implemented', 'Map keyword matching searches name, description and brand; category also checks subcategory, matching Browse query logic. Both search inputs trigger Livewire updates.', 'app/Livewire/Listings/Map.php', 33),
53: ('Partial', 'Pin popups show name, price, distance and a View listing link. The supplied image field is never rendered in the popup. Selecting the pin opens only the popup; a second click on the link opens details.', 'resources/js/map.js', 174),
}

nfr = {
1: ('Partial', 'Confirmed defects: missing relations/history after deletion (A7/A11), requests accepted after availability changed (A9), missing Reserved state and incorrect cancelled-booking earnings (A12). Multi-record booking/payment/cancellation updates lack database transactions and overlap locking; simultaneous-operation integrity was not tested.', 'app/Livewire/Owner/RentalRequests/Index.php', 41),
2: ('Implemented', 'Rental cost and cancellation calculations use the requested duration, stored agreed rental fee and displayed policy; existing calculation and timing tests pass. Exactly 48 hours is not explicitly classified consistently in the prose and is an acceptance detail to clarify. Actual cash settlement is not validated.', 'app/Services/CancellationPolicy.php', 19),
3: ('Partial', 'Approved-overlap checks and persisted payment/status records exist. No polling/broadcast refresh keeps open rental pages synchronized, and approval/payment operations are not atomic. A9 also reproduces submission after another session changes availability.', 'app/Livewire/RentalRequests/Create.php', 77),
4: ('Unverified', 'No deployment uptime records, availability monitoring, recovery evidence or advance maintenance communication workflow were available. A local application and health route cannot establish continuous availability.', 'bootstrap/app.php', 17),
5: ('Unverified', 'No defined response-time threshold or normal-load profile is provided, and no load benchmarks were available/run. Functional test timing does not prove user response-time performance.', 'app/Livewire/Listings/Browse.php', 54),
6: ('Implemented', 'Registration/reset use Hash::make and User has a hashed password cast; passwords are hidden from serialization. Existing authentication/password tests pass.', 'app/Models/User.php', 72),
7: ('Partial', 'Admin middleware, ownership policies and suspension controls exist. Required Owner/Renter session separation is absent; owner self-review is possible (A8); the current local demo switcher permits credential-free admin impersonation (A10).', 'app/Http/Middleware/EnsureUserHasRole.php', 19),
8: ('Unverified', 'Rental terms and cancellation policy are displayed. No completed standards/compliance assessment, privacy notice, consent/retention review or payment compliance evidence was supplied. Legal compliance cannot be certified from this code audit.', 'resources/views/livewire/rental-requests/create.blade.php', 72),
9: ('Unverified', 'The core forms and navigation exist, but there are no user usability results or a defined minimal-step criterion. B.2, B.4 and B.7 also differ from the required process; usability acceptance needs real user journey review.', 'resources/views/livewire/layout/sidebar.blade.php', 98),
10: ('Partial', 'Main forms have field validation and specific overlap, duration and suspension errors. Geolocation denial/unavailability exits silently; deleted listing relations can break history views instead of displaying an actionable message. No complete frontend error-case review was run.', 'resources/js/map.js', 216),
11: ('Implemented', 'Controllers/components, models, enums, policies, services and migrations are separated. Shared lifecycle, fee, geography and chat-thread logic can be changed independently. This is architectural evidence rather than a maintainability score.', 'app/Services/RentalLifecycle.php', 22),
12: ('Unverified', 'The relational schema and modular architecture support extension, but there are no capacity goals or load/volume results. Map retrieves every matching listing, and thread listing performs per-thread unread queries; these are scaling concerns, not proof of capacity failure.', 'app/Livewire/Listings/Map.php', 33),
13: ('Partial', 'Automated backend HTTP/Livewire tests are available and executed. No Selenium, Playwright, Cypress or equivalent frontend workflow suite was found. Browser automation/manual test cases and evidence required by the test plan are outstanding.', 'phpunit.xml', 7),
14: ('Implemented', 'Standalone geographic unit tests and independently testable auth/listing/booking Livewire components exist. Existing unit/feature tests and the audit probes exercised independent functions.', 'tests/Unit/GeoTest.php', 8),
15: ('Unverified', 'Responsive Tailwind breakpoints and a mobile sidebar exist. No live Chrome/Edge/other-browser matrix, mobile viewport checks or automated frontend compatibility suite was executed, so cross-browser behavior is not established.', 'resources/views/livewire/layout/sidebar.blade.php', 58),
16: ('Partial', 'Laravel mail service interfaces and Leaflet tile-service integration exist. The current mailer is log, so no real verification/recovery delivery is demonstrated; real map service loading is also untested. Payment gateway/courier integrations are excluded by the document.', 'config/mail.php', 17),
17: ('Implemented', 'Device location is obtained through navigator.geolocation after an explicit Use my location action; browser permission governs access. No direct device-location bypass was found. Browser permission interaction still needs frontend verification.', 'resources/js/map.js', 214),
18: ('Unverified', 'Map data reactively changes after location, radius, keyword and category edits. There is no latency threshold or normal-load benchmark, and the whole matching result set is loaded for marker computation.', 'app/Livewire/Listings/Map.php', 33),
19: ('Partial', 'Geo uses a standard great-circle calculation and existing tests validate ordinary points. The map treats zero latitude/longitude as absent, so valid coordinates can yield null distances and skip nearest-first ordering (A6).', 'app/Livewire/Listings/Map.php', 54),
20: ('Implemented', 'Browse is independent of device location. With null coordinates, the map retains keyword/category results and skips distance filtering/sorting; backend code supports the fallback. Silent permission errors are assessed under NFR-10.', 'app/Livewire/Listings/Map.php', 33),
}

flows = [
('B.1', 'User registration and login', 'Partial', 'Sign Up, input validation, duplicate-email checks and verification-link generation exist. Verification is optional before platform use; successful verification redirects to dashboard rather than login; returning login has no Owner/Renter selector. Mail currently goes to logs. FR-02/03/04; A1.'),
('B.2', 'Item listing and management', 'Partial', 'My Listings, Add Item, details, location picker, photo upload and edit/pause/remove exist. Owners cannot set an available-date window; at least one photo and map coordinates are not required. Publishing waits for admin approval rather than publishing immediately after validation as described. Removed listings also affect retained history. FR-05/06/07/47; A7.'),
('B.3', 'Item search and browsing', 'Partial', 'Browse keyword/category/price filters, map pins, permission-based location, radius selector, nearest-first marker data and detail calendar exist. Textual location filtering in Browse is absent. Map previews omit photos and pin selection requires a second link click. Zero coordinates break proximity behavior. FR-09/10/48-53; A6.'),
('B.4', 'Rental request approval and booking', 'Partial', 'Request submission notifies owner; owner can approve/decline; booking and costs are created. Renter accepts terms during submission and owner during approval, instead of both being prompted after approval. Persisted renter acceptance is not rechecked, and only renter gets booking confirmation. Availability can change after form mount. FR-11-16/40; A2/A3/A9.'),
('B.5', 'Payment and transaction', 'Partial', 'Computed charges, simulated renter payment, payment record and both-party receipt exist. No actual supported cash-transfer/verified offline collection is shown, and the item does not change to Reserved after payment. Approved date ranges already block bookings before payment. Live gateway integration is explicitly outside scope. FR-16/17/41/42.'),
('B.6', 'Item pickup or delivery', 'Partial', 'Fulfillment choice, chat and individual transfer acknowledgments exist; both acknowledgments activate the rental. There is no recorded confirmed exchange schedule or both-party schedule notification. Labels use Confirm pickup even for delivery, rather than Handed Over/Received. FR-18/43/44.'),
('B.7', 'Rental tracking and return', 'Partial', 'Status badges, scheduled reminders/overdue checks, return confirmations, owner after-condition and inspection completion exist. Open pages do not refresh in real time; overdue is scheduled daily. Owner cannot proceed to condition/completion until renter also confirms return, adding a gate absent from the described owner-inspection flow. History retention fails after deletions. FR-19-22/24; A7/A11.'),
('B.8', 'Ratings and reviews', 'Partial', 'Completed rentals expose star/comment forms to both parties; renter gets a review prompt. No equivalent owner notification/public user review profile exists. An owner can submit the renter-to-owner review for their own rental. FR-23/45; A8.'),
('B.9', 'Admin management and dispute resolution', 'Partial', 'Dashboard monitoring, user suspension, listing moderation, rental disputes, damage evidence and resolution notifications exist. Admin rental management is viewing/filtering only. Standalone reports of items/users, as shown in Figure 2, are absent; general disputes have text descriptions without separate evidence uploads. FR-26-30/46.'),
('B.10', 'Rental cancellation', 'Partial', 'Request/booking cancellation, timing-based fee, booking-owner notification and recorded confirmed cancellation exist. Cancellation remains possible after owner hand-over until renter confirms; pending-request cancellation omits owner notification/reason/time/fee. Dates are freed by request status, but no explicit Available item state is restored. Earnings retain full cancelled rental fees. FR-34-37; A4/A5/A12.'),
]

probes = [
('A1', 'Unverified renter can submit a rental request', 'FR-02, NFR-07, B.1', 'test_gap_unverified_renter_can_submit_request'),
('A2', 'Approval creates a booking without persisted renter terms acceptance', 'FR-14/15, B.4', 'test_gap_approval_does_not_recheck_renter_terms'),
('A3', 'Owner receives no booking confirmation notification', 'FR-13/40, B.4', 'test_gap_owner_has_no_booking_confirmation_notification'),
('A4', 'Pending-request cancellation does not notify the owner', 'FR-36/37, B.10', 'test_gap_pending_request_cancellation_does_not_notify_owner'),
('A5', 'Cancellation is allowed after the owner records hand-over', 'FR-34, B.10', 'test_gap_cancellation_is_allowed_after_owner_logged_handover'),
('A6', 'A valid zero coordinate causes null map distance', 'FR-50/51, NFR-19, B.3', 'test_gap_zero_coordinate_does_not_compute_distance'),
('A7', 'Soft-deleted listing is missing from the rental relationship', 'FR-22/24, NFR-01', 'test_gap_soft_deleted_listing_is_missing_from_rental_relation'),
('A8', 'Owner submits a renter-to-owner self-review', 'FR-23, NFR-07, B.8', 'test_gap_owner_can_submit_renter_to_owner_review_on_own_rental'),
('A9', 'An open request form submits after listing becomes unavailable/inactive', 'FR-11, NFR-01/03', 'test_gap_submit_does_not_recheck_listing_availability'),
('A10', 'A member can switch into seeded admin without admin credentials in local mode', 'FR-39, NFR-07', 'test_gap_local_demo_switcher_can_enter_admin_without_admin_password'),
('A11', 'Deleting one account removes the other party\'s shared rental record', 'FR-22/24, NFR-01', 'test_gap_account_deletion_removes_other_partys_rental_history'),
('A12', 'A paid zero-fee cancelled booking still counts its full rental fee as owner earnings', 'FR-25, NFR-01, B.10', 'test_gap_paid_cancelled_booking_remains_in_full_owner_earnings'),
]

requirements = {}
for line in (OUT / 'requirements-extracted.txt').read_text(encoding='utf-8').splitlines():
    match = re.match(r'^(FR|NFR)-(\d+) \| (.*?) \| ', line)
    if match:
        requirements[(match[1], int(match[2]))] = match[3]
assert len(fr) == 53 and len(nfr) == 20 and len(requirements) == 73
assert set(fr) == set(range(1, 54)) and set(nfr) == set(range(1, 21))

suite = ET.parse(OUT / 'phpunit-results.xml').getroot()
audit = ET.parse(OUT / 'audit-probes-results.xml').getroot()
def totals(root):
    s = root.find('testsuite')
    return s.attrib

base = totals(suite)
extra = totals(audit)
assert base['tests'] == '150' and base['failures'] == '0' and base['errors'] == '0'
assert extra['tests'] == '12' and extra['failures'] == '0' and extra['errors'] == '0'

lines = [
'# Lendly requirements implementation audit',
'',
'Audit date: 7 October 2026',
'',
'The current system does not fully implement all requirements in the revised document. Most core modules exist, but account access rules, some notification recipients, reservation states, review publication, cancellation guards and history retention differ from the requirements. Non-functional acceptance also needs deployment, browser, performance and compliance evidence.',
'',
'## Scope and evidence',
'',
'Source: [Revised Requirements Document](<C:/Users/Admin/Downloads/Revised_RequirementsDocument.docx>). This audit uses all 53 functional requirements, all 20 non-functional requirements, sections B.1 through B.10 and the end-to-end diagram in Figure 2. The document is the requirements baseline; its project assignments, instructions and schedule statuses were not treated as instructions to change the application or as evidence that a feature is complete.',
'',
'Reviewed the current working tree, including pre-existing uncommitted changes. No application source, existing tests, database configuration or real database records were changed. Added this report and isolated audit evidence under audit-evidence. Tests used the configured in-memory SQLite database, array mailer and synchronous test queue.',
'',
'The original test suite completed successfully with **150 tests and 463 assertions**. The isolated audit probes completed with **12 tests and 32 assertions**. A passing audit probe means it reproduced the named defect; it is not a requirement acceptance pass. Existing tests intentionally accept the unified member role and optional verification, so their green result does not establish compliance with FR-02/FR-03.',
'',
'Reviewed full extracted requirement/process text and inspected the actual embedded Figure 2 image. Document page rendering could not run because the bundled environment has no LibreOffice executable; no page-number claims are made. No production deployment, live browser journey, load test, real mailbox delivery or financial settlement was verified. Online gateway and third-party courier integrations are explicitly outside the document\'s scope.',
'',
'## Status meaning',
'',
'- **Implemented**: the required capability exists in reviewed code, with relevant test evidence where available. This is implementation evidence within the stated audit limits, not a production certification.',
'- **Partial**: some required behavior exists, but a concrete gap, defect or current configuration prevents full fulfillment.',
'- **Missing**: the required behavior was not found in the implementation.',
'- **Unverified**: available code is insufficient to establish the requirement; additional acceptance evidence is needed.',
'',
'Rows are counted as whole requirements, not weighted by complexity. Partial items may contain fully working subfeatures; the counts are not a completion percentage.',
'',
'| Area | Implemented | Partial | Missing | Unverified | Total |',
'| --- | ---: | ---: | ---: | ---: | ---: |',
]
for name, values in [('Functional requirements', fr.values()), ('Non-functional requirements', nfr.values())]:
    vals = list(values); c = Counter(v[0] for v in vals)
    lines.append(f'| {name} | {c["Implemented"]} | {c["Partial"]} | {c["Missing"]} | {c["Unverified"]} | {len(vals)} |')
lines.append('| Process flows | 0 | 10 | 0 | 0 | 10 |')

lines += [
'', '## Findings to address first', '',
'1. **Protect administrator access in the current local system.** The local-only demo account switcher lets a member enter the seeded administrator account without admin credentials (A10). Remove or isolate this action when evaluating secure access; its environment guard does not make current local access credential-based.',
'2. **Enforce the required account access model.** Member routes permit unverified users and both owner/renter modules. Add verification gating and login-time role selection with session role enforcement to match FR-02/03 and B.1.',
'3. **Check the actor for every review/action.** The renter review action permits the owner to submit their own owner rating (A8). Route labels and the general view policy do not establish the actor\'s rental role.',
'4. **Preserve shared transaction history.** Account deletion cascades rental deletion (A11); soft-deleted listings disappear from rental relations (A7). Retain/anonymize historical parties and support archived listing relationships/snapshots so removal does not break the other party\'s history.',
'5. **Close booking and cancellation state gaps.** Recheck listing availability and both accepted terms before approval/submission, prevent cancellation once owner hand-over is logged, and implement payment-driven Reserved date state. Wrap related writes in transactions with concurrency protection; the concurrency risk is visible in code but has not been reproduced under simultaneous requests.',
'6. **Complete notifications and public reviews.** Notify both booking parties, notify owners about pending-request cancellation, notify owners to review, record/notify the exchange schedule and publish received reviews on public user profiles.',
'7. **Reconcile earnings and payment scope.** Cancelled paid rentals remain counted at full rental fee (A12). Specify the supported prototype/offline payment method, fee/refund ledger behavior and actual owner earnings after cancellation without adding an out-of-scope live gateway.',
'8. **Finish map and acceptance evidence.** Handle zero coordinates correctly, show a photo in each pin preview, resolve the extra detail-opening click, and verify browser permissions, real email delivery, responsiveness, scheduler execution and response times.',
'', '## Functional requirements', '',
'Requirements below reproduce the document\'s wording. Assessment and code evidence are separate from the source requirement.', '',
'| ID | Document requirement | Status | Assessment and evidence |',
'| --- | --- | --- | --- |',
]
for number in sorted(fr):
    status, note, file, line = fr[number]
    req = requirements[('FR', number)].replace('|', '\\|')
    lines.append(f'| FR-{number:02} | {req} | **{status}** | {note} Source: {link(file, line)}. |')

lines += ['', '## Non-functional requirements', '',
'Performance, uptime, usability, compatibility, scalability and compliance require measurable acceptance criteria or operational evidence. A missing benchmark is classified Unverified rather than automatically calling the application defective.', '',
'| ID | Document requirement | Status | Assessment and evidence |',
'| --- | --- | --- | --- |']
for number in sorted(nfr):
    status, note, file, line = nfr[number]
    req = requirements[('NFR', number)].replace('|', '\\|')
    lines.append(f'| NFR-{number:02} | {req} | **{status}** | {note} Source: {link(file, line)}. |')

lines += ['', '## Process flows', '',
'Every B.1-B.10 flow has at least one missing step, changed ordering, added gate or confirmed defect. This does not mean the flow is unusable; it means its complete described behavior is not yet matched.', '',
'| Document section | Process flow | Status | Step coverage and deviations |',
'| --- | --- | --- | --- |']
for id, name, status, note in flows:
    lines.append(f'| {id} | {name} | **{status}** | {note} |')

lines += ['', '## Reproduced audit defects', '',
'All probes are isolated characterization checks against in-memory test data. The named conditions were reproduced successfully; no real account was impersonated, deleted or charged.', '',
'| Probe | Confirmed observation | Requirements | Evidence |',
'| --- | --- | --- | --- |']
probe_lines = (OUT / 'RequirementsAuditTest.php').read_text(encoding='utf-8').splitlines()
for id, observation, ids, method in probes:
    line = next(i for i, value in enumerate(probe_lines, 1) if f'function {method}(' in value)
    lines.append(f'| {id} | {observation} | {ids} | {link("audit-evidence/RequirementsAuditTest.php", line, method)} |')

lines += ['', '## Validation and remaining acceptance work', '',
f'Existing suite: {link("audit-evidence/phpunit-output.txt", label="test output")} and {link("audit-evidence/phpunit-results.xml", label="JUnit results")}. Audit probes: {link("audit-evidence/audit-probes-output.txt", label="probe output")} and {link("audit-evidence/audit-probes-results.xml", label="probe JUnit results")}.', '',
'Commands used:', '', '```powershell',
'php vendor/phpunit/phpunit/phpunit --do-not-cache-result --log-junit audit-evidence\\phpunit-results.xml',
'php vendor/phpunit/phpunit/phpunit --do-not-cache-result --log-junit audit-evidence\\audit-probes-results.xml audit-evidence\\RequirementsAuditTest.php',
'```', '',
'The first baseline execution reached all 150 tests but PHPUnit then failed while writing its result cache. The recorded successful run disabled result caching and completed cleanly. An initial audit-probe run had an audit-script namespace error; that probe was corrected and the complete final 12-probe run passed.', '',
'Before acceptance, run a browser journey for a verified owner and renter through listing, request, agreement, payment, exchange, return, review and cancellation; include administrator moderation/dispute handling and unauthorized-role checks. Exercise location permission accepted/denied/unavailable, zero-valued coordinates, mobile layouts and supported browser versions. Verify real verification/recovery mail delivery and scheduler notifications. Define normal load and response-time thresholds, test concurrent overlapping approvals/payments/cancellations, and verify shared history survives account/listing removal.', '',
'The document\'s test-plan completion, run-rate, severity and sign-off conditions are broader than this audit. Passing the repository suite does not establish execution of its manual/Selenium/Postman cases, closure of severe defects, or project-leader sign-off.', '',
'No implementation fixes were made as part of this review.', '',
]
(ROOT / 'REQUIREMENTS_IMPLEMENTATION_AUDIT.md').write_text('\n'.join(lines), encoding='utf-8')
for label, values in [('FR', fr.values()), ('NFR', nfr.values())]:
    print(label, dict(Counter(v[0] for v in values)))
print('Report written:', ROOT / 'REQUIREMENTS_IMPLEMENTATION_AUDIT.md')
