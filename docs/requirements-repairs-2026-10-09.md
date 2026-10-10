# Requirements repair results — 9 October 2026

The selected functional repairs are implemented in the local system. The original requirements document was preserved. This report describes the repaired cases and their verification; it does not declare every requirement in the document satisfied.

## Functional requirements

| Code | What the system now does | Verification |
| --- | --- | --- |
| FR-07, FR-24 | Removing a listing preserves transaction history and historical item links. Closing an account anonymizes and soft-deletes it while retaining the other party's rentals, receipts, messages, reviews, and evidence. Outstanding rentals, payments, claims, or disputes block closure with an explanation. | History preservation, profile, and maintained acceptance tests. |
| FR-08, FR-42 | Shows Available, Unavailable, Reserved, or Rented from owner settings and actual booking dates. Paid dates are Reserved; listing details also show future reserved ranges. Dates outside a reservation remain available. | Availability/date-boundary tests and browser listing checks. |
| FR-11 | An open request form rechecks current publication, availability, fulfillment options, duration, dates, and overlap before saving. Changed prices or terms require review and acceptance again. | Stale form, changed terms, and Livewire hydration regressions. |
| FR-13, FR-40 | Both participants receive request status and booking confirmation notifications. Notifications are saved with the corresponding change. | Request/notification and interrupted approval tests. |
| FR-14 | Approval saves the booking and approved request together. Locked current records, overlap checks, and a unique booking-per-request constraint protect against interrupted and overlapping approvals. | Injected interruption, overlapping approval, and current fulfillment/duration tests. |
| FR-17 | Payment, deposit, paid status, and both notifications commit together. Retrying payment does not create another payment or deposit. Both participants can see the recorded receipt reference, amount, and payment date. | Payment interruption/retry regressions; owner receipt browser check. |
| FR-19 | Past-due active rentals display Overdue, current overdue days, and the current late fee immediately. Filters and dashboards use the same rule. The daily job rechecks locked records so it cannot reopen a completed rental. | Status/dashboard tests, controlled job interleaving, and overdue browser check. |
| FR-22 | The owner confirms return and records the returned condition. Either action may come first; both together automatically complete/archive the rental and prompt reviews. Renter acknowledgment is optional. Existing damage and deposit safeguards still apply. | Return closure/order/authorization and settlement regressions; browser completion without a separate inspection action. |
| FR-23 | Only the actual renter can review the owner/item; only the actual owner can review the renter. Reviews require completion. | Unauthorized-author and completed-rental review tests. |
| FR-28 | Admin transaction details show participants, booked costs, receipt, deposit, lifecycle, cancellation, condition evidence, damage claims, and dispute decisions. Links open existing dispute/account management with the correct filters. | Admin-only detail/filter tests and browser checks. |
| FR-31 | A renter can contact an owner directly from a listing before making a rental request. Listing inquiries and existing request conversations share the inbox. Threads remain readable after removal; sending to closed/suspended accounts is blocked. | Inquiry reuse, access, notifications, history, and existing chat regressions; browser inquiry/inbox check. |
| FR-34 | Cancellation is blocked as soon as either handover acknowledgment exists, including cancellation attempted through the request page. | Fresh/stale handover and both cancellation-path regressions. |
| FR-36 | Request-only and booking cancellation notify the owner and renter. Booking cancellation updates the linked request and releases its reservation. | Cancellation/notification/availability tests. |
| FR-45 | Completion prompts both participants to review. Public profile pages show received peer reviews from completed rentals, without private contact or transaction details. | Public profile/privacy/pagination tests and browser publication check. |
| FR-47, FR-50, FR-51 | Explicit null checks preserve valid zero coordinates in the picker and map. Radius filtering, distances, and nearest-first ordering work with zero latitude, zero longitude, and both zero. Browsing still works without location. | PHP coordinate/filter/order tests and JavaScript picker/map tests. |
| FR-53 | Map previews include the listing photo when available, literal name, and daily price. Selecting a pin by click, Enter, or Space opens its details directly. Listing text is inserted as text rather than executable HTML. | JavaScript preview/navigation/security tests; browser single-click navigation. |

Two workflow decisions used for this implementation:

- FR-22: owner-confirmed return plus a returned-condition record closes the rental automatically; renter acknowledgment is optional.
- FR-28: reuse existing dispute resolution and account management. No new forced cancellation, refund, or financial override was added.

## Related non-functional requirements

| Code | Updated status | Evidence and remaining scope |
| --- | --- | --- |
| NFR-01, NFR-03 | Listed consistency defects repaired. | Atomic approval/payment, uniqueness constraints, overlap checks, fresh action validation, retained history, and current availability/status are covered by maintained regressions. Genuine simultaneous MySQL requests have not been stress-tested. |
| NFR-07 | Listed review and map execution loopholes repaired. | Participant checks prevent fabricated review authors. Map previews use DOM text content rather than parsing listing HTML. Admin/thread/profile access tests pass. The broader selected-role design from the earlier audit remains outside these repairs. |
| NFR-10 | History failures repaired; full requirement remains partial. | Removed listings/accounts no longer break the tested history/receipt pages. Stale terms, availability, fulfillment, dates, and account obligations have actionable messages. Unexpected infrastructure/database exceptions still need a broader recovery-message review. |
| NFR-13 | Partial. | Maintained backend and JavaScript tests exist, and disposable browser workflows were checked. A committed, repeatable complete browser workflow suite and the document's named Postman/Selenium deliverables are still missing. |
| NFR-19 | Listed coordinate/distance defects repaired. | Zero-coordinate radius and nearest-first cases pass. Real device geolocation and a browser/device matrix were not tested. |

The earlier comparison and CSV are retained as the pre-repair audit baseline. Use this report for the current status of these selected findings.

## Verification

- Final full PHP suite: **332 tests passed, 1,620 assertions**.
- JavaScript tests: **14 passed** (`password-strength.test.js` and `map-popup.test.js`).
- Production Vite build: passed, 69 modules transformed.
- The original 19 acceptance probes passed during implementation; their maintained counterparts are included in the final PHP suite.
- Scoped PHP formatting checks and `git diff --check` passed.
- An independent integration review found five related edge cases. Each received a regression test and a fix; focused rereview confirmed them resolved.

Browser verification used a separate named SQLite fixture with fictional owner/renter/admin accounts and array mail. It covered login, direct inquiry/inbox, immediate overdue display, receipt details, return/condition completion, public peer reviews, direct map-pin navigation, and admin transaction/account-filter details. It did not add test transactions to the application's MySQL database or send live email.

![Verified completed transaction and receipt](C:/laragon/www/lendly/storage/framework/testing/requirements-repairs-admin.jpg)

## Local database and source preservation

Three additive migrations were applied to the configured local MySQL database:

1. User soft deletion.
2. Listing inquiry conversations and their message association.
3. One booking per request, one payment per rental, and one deposit per rental.

Preflight and post-migration checks found zero duplicate groups, zero approved requests without a booking, zero paid rentals without a payment, and zero pending-payment rentals with a payment. All three unique indexes and the user soft-delete column are present. No refresh, reseed, or financial-row deletion was used.

The original `Revised_RequirementsDocument.docx` SHA-256 remains `373783799e0839ac40e5ea7ecab27854e04dc749ba615bbeefe40e4a306c0921`. Earlier authentication/UI changes were preserved; changes remain uncommitted.

## Limits

- SQLite regressions include controlled interruption/interleaving cases; they do not replace simultaneous MySQL load/concurrency testing.
- Payments remain the project's existing simulated payment flow. No live payment provider or real-money transaction was tested.
- These notifications use the existing database notification channel. Live Gmail inbox delivery was not retested for this task.
- The browser checks used one Chromium-based browser and synthetic locations. Real device permission handling, all browsers/devices, and a photo-bearing map preview were not visually verified.
- Uploaded condition-photo files are written outside SQL transactions. A failed save can leave an orphan file even though transaction/history metadata rolls back; cleanup should be added if failed-upload volume warrants it.
- This is a selected repair report, not a full security, operational, legal, or document-compliance sign-off.
