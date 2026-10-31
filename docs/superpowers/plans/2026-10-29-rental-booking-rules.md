# Rental booking rules implementation plan

> Implement inline using executing-plans, with focused failing tests before each behavior and a final independent review.

**Goal:** Complete hourly/daily bookings, date waitlists, expiring payment reservations, cancellation review, processing-fee settings, and the existing paper agreement.

**Architecture:** Extend the existing Laravel services and Livewire screens. Keep daily bookings and saved agreements compatible. Store hourly instants in UTC and display Philippine time. Continue actual owner-wallet QR payments with proof and verified receipt; no simulated gateway or transfers.

**Tech stack:** Laravel 12, Livewire 3, Carbon, PHPUnit, existing Tailwind components.

**Spec:** The user's notes and implementation authorization in this chat.

## Decisions and constraints

- New agreements: 24-hour chargeable window, 20% rental-only cancellation fee, 10% processing fee unless the administrator changes future rates.
- A packed or late booking needs a reason and owner/admin cancellation approval. Waiting for courier, shipped, and handed-over bookings cannot use ordinary cancellation. A pending request freezes fulfillment advancement.
- Saved legacy agreements retain their cancellation wording and percentages. A processing fee is the existing commission, not an additional charge. Refunds remain manual and must be described as such.
- New payment reservations expire after 24 hours or at rental start, whichever comes first; pending proof pauses expiry until review. No fee for expiry. Legacy bookings without a deadline remain unchanged.
- Waitlist entries cover exact dates/times, ordered by creation/id. Notify eligible renters when those dates are free; notification does not reserve dates, charge money, or automatically change requested dates. Owner approval still decides the booking.
- Hourly rentals use whole hours, at least one hour, within owner opening/closing times and minimum/maximum hours. Daily rentals retain inclusive-date billing. Existing owner date and maximum-day limits remain.
- Work in the supplied checkout because its substantial uncommitted authentication/identity changes are required dependencies. Preserve those changes; no commits, reset, or new worktree.

## Review focus

- Adjacent hourly bookings must not overlap; daily bookings must block the entire local dates.
- All mutations recheck ownership, identity for new business, latest state and price under database locks.
- A pending cancellation cannot race with packing/courier advancement, duplicate approval, or handover.
- Payment proof must not lose a reservation while being reviewed; rejected proof must not hold it forever.
- Waitlist notifications must not disclose another renter, repeat indefinitely, or promise a reservation.

## Tasks

- [x] 1. Add failing booking tests; extend schema, shared period validation, request/cart snapshots, owner limits and booking forms; verify hourly price, limits, overlaps and late fees.
- [x] 2. Add failing cancellation/fee tests; implement fulfillment stages, reviewed cancellations, configurable future fee rates and preserved agreement terms; verify authorization, state transitions and refunds displayed.
- [x] 3. Add failing waitlist/expiry tests; implement exact-period waitlist and notifications, payment deadlines, scheduler and user controls; verify release, duplicate handling and pending-proof protection.
- [x] 4. Update paper agreement and money/date displays, payment prompts, cart editing and documentation. Run focused tests, full PHP suite, JavaScript checks, build and independent code review; fix material findings.

## Execution record

- Baseline: PHP is_readable reports false under the Windows sandbox even for readable files. PHP verification runs outside that restriction, with in-memory SQLite for tests.
- Focused new-feature and agreement tests: 25 passing, 96 assertions; JavaScript checks: 20 passing.
- Local additive migration applied successfully. Existing accepted agreements and rental charges were not rewritten.
- Ruling: preserve the current acceptance-checkbox behavior when editing request dates; adding an unrelated reset broke existing callers and was unnecessary for this task.
- Ruling: the waitlist sends exact-period availability alerts in joining order, with no exclusive hold; owner approval remains the reservation mechanism.
- Ruling: payment collection, refunds and owner remittance use the existing manual QR flow; no gateway credentials were supplied.

- Independent review: fixed four findings with failing-then-passing regressions: hourly late-fee wording, midnight adjacency in both booking orders, legacy cancellation rights at courier stages, and payment-deadline crossing during proof submission.
- Final full PHP suite: 1005 tests, 7603 assertions, zero failures/errors (7m 40.644s). New booking-rules class: 28 tests, 76 assertions.
- JavaScript: 20 checks passed; production Vite build passed; targeted Pint formatting and git diff --check passed.
- Browser smoke check: day/hour marketplace filter and responsive search layout verified against the local application. No real money was transferred.
- Final scope ruling: payment expiry starts after both agreement acceptances; pre-acceptance owner-approved holds retain their existing behavior. Gateway processing and legal validity were outside this implementation; manual payment/refund handling is explicit in the UI and documentation.
