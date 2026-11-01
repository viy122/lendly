# Phase 7 — complete local system verification

Date: 1 November 2026. Result: **PASS for the local application checks**.

## Final results

- Complete PHP suite: **1,075 tests, 8,204 assertions, zero failures, errors, or skipped tests** in one final run.
- JavaScript: **50 tests passed**, zero failures.
- Production Vite build, changed-file PHP formatting, and whitespace checks passed.
- Six new scenarios: two complete rental journeys and four stale-dispute access checks. Both complete journeys were also rechecked using the exact marketplace search URL parameter: **2 passed, 259 assertions**.

## Passed / failed checklist

| Flow | Result | Evidence / scope |
| --- | --- | --- |
| Registration, email verification, password recovery, login lockouts, logout | PASS | Full regression suite; email delivery mocked. Demo renter, owner, and administrator browser login/logout checked. |
| Owner/renter same-account switch and role restrictions | PASS | HTTP switch and authorization tests; existing browser controls retained. |
| Identity submission and admin review | PASS | Synthetic photos and mocked face-comparison result; full approval/rejection/access suite. Real documents were not used. |
| Listing creation, photos, custom categories, pricing, availability | PASS | Full listing suites; complete journeys publish a listing through the Livewire form. |
| Browse search, calendar, quote, and identity gating | PASS | Actual Canon search, Nov 5–9 calendar selection, five-day quote, and disabled submission for pending identity review. |
| Cart checkout and direct requests | PASS | Daily delivery via cart and hourly pickup via direct submission in complete journeys. |
| Owner approval, two-party agreement, one booking | PASS | Journey assertions verify no booking after first acceptance, then one approved booking after both. |
| Payment proof, owner confirmation, private QR access, processing fee | PASS | Synthetic wallet/proof uploads; rental stays unpaid until owner confirmation. Full payment suites include rejection, legacy payments, and access checks. |
| Pickup/delivery arrangements and preparation | PASS | Both parties confirm the same schedule; packing/courier/shipping branch exercised for delivery. |
| Handover, return, inspection, and reopening | PASS | Two confirmations activate a rental; owner return and condition record complete it. Listing remains closed until owner explicitly reopens it. |
| Deposit release and renter receipt | PASS | Separate actions and timestamps, authorization checks; no real funds moved. |
| Ratings, reviews, histories, receipts | PASS | Three reviews per integrated rental; actual renter/owner completed transaction screens inspected. |
| Disputes and damage resolutions | PASS | Full dispute/damage suites, including Other explanation, stale decisions, and new stale-account checks. |
| Messages and notifications | PASS | Full message/notification suites; interface scoping, unread filters, and activity sorting covered. |
| Admin listings, identity queue, transactions, damage reports, disputes | PASS | Full admin access/action suites; browser page checks recorded below. |
| External Google sign-in | NOT CONFIGURED | Google client ID/secret are absent; real sign-in unavailable. Mocked application tests passed. |
| Real inbox delivery | NOT EXERCISED | Verification/reset behavior tested with fake mail. Actual receipt in a mailbox still needs a separate delivery check. |
| Real wallet transfer / personal ID comparison | NOT EXERCISED | Payment/review logic tested with synthetic data; no real transfer, identity submission, or existing dispute decision was made. |

No application test remains failed. External checks above are distinguished from passing local application logic.

## Bugs fixed during this phase

| Before | After |
| --- | --- |
| A stale renter dispute form could create a dispute and notify a participant before the subsequent render rejected the new account; an administrator could also reach this action from that stale form. | Both member dispute actions reload the rental and check the current actor's exact participant role before validation, writes, or notifications. Four regression scenarios assert 403 and zero disputes/notifications after switching to a stranger/admin. |
| Browser and PHPUnit processes shared compiled Blade views. On Windows, simultaneous replacement could produce an Access denied page error. | PHPUnit uses its own compiled-view directory before application boot. Product page successfully reloaded while the final suite ran; all tests passed. |
| Marketplace result count said every listed item was accepting requests, including rented/unavailable items shown in results. | Count now says items found and directs the renter to each item's availability. |

The frozen-clock journey tests disable temporary-upload cleanup within those tests only, so advancing to a future rental date does not make new synthetic files look expired. Normal application cleanup remains enabled.

## Complete journey exercised

For each hourly pickup / daily delivery scenario: synthetic identity submission → administrator approval → email/password owner login → listing/photo publication → private wallet setup → renter login/search → direct request or cart checkout → owner approval for agreement → owner acceptance → renter acceptance → booking → proof upload → owner funds confirmation → schedule proposal and both confirmations → preparation → both handover confirmations → renter return → owner return and inspection → deposit release → renter receipt confirmation → renter/owner/item reviews → explicit listing reopening → logout and denied private access.

## Screenshots

- [Calendar date range](phase-7-evidence/calendar-selected-range.jpg)
- [Request quote and ID gate](phase-7-evidence/request-quote-and-identity-gate.jpg)
- [Renter completed receipt](phase-7-evidence/renter-completed-receipt.jpg)
- [Return, deposit, and saved reviews](phase-7-evidence/return-deposit-reviews.jpg)
- [Owner payment settings](phase-7-evidence/owner-payment-settings.jpg)
- [Owner completed transaction](phase-7-evidence/owner-completed-transaction.jpg)
- [Admin dispute queue](phase-7-evidence/admin-dispute-queue.jpg)
- [Corrected marketplace availability wording](phase-7-evidence/marketplace-availability-label.jpg)
- [Recorded owner/admin page checks](phase-7-evidence/browser-record-checks.json)

## Browser walkthrough details

Verified renter history and completed booking #31; owner dashboard, listings, request queue, wallet settings, and completed booking #25; admin dashboard, listings, ID review queue, transactions, damage reports, and disputes. All inspected pages loaded successfully after test-cache isolation. Responsive navigation, account menus, and three demo-account sign-in/logout flows worked. The original demo renter session was restored.

The current demo owner/renter have not submitted identity reviews. Browsing and existing history work; the app correctly blocks new request submission until the required identities are approved. Verified-member creation and booking were exercised in the isolated complete journeys.

## Retained local data and raw evidence

MySQL still contains **34 rentals, 30 payments, 0 payment submissions, 30 deposits, and 2 disputes**, matching the pre-check transaction counts. Browser reads can increment listing views and synchronize overdue states as designed. No existing financial confirmation or dispute resolution was submitted.

Raw evidence: `storage/logs/phase-7-full.xml`, `phase-7-full.txt`, `phase-7-javascript.txt`, `phase-7-build.txt`, `phase-7-journey-final.xml`, and `phase-7-verification-summary.json`. Full-run results supersede the intermediate diagnostic runs used to reproduce the issue and correct synthetic fixture setup.
