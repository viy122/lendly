# Phase 1: Rental rules and availability

Status: implemented locally on November 1, 2026. The user delegated the two outstanding choices; the selected rules are recorded below. See `phase-1-before-after.md` for implementation and verification results. The additive reopening-control migration is applied to MySQL; existing rental prices, agreed periods, and payment records were not rewritten.

## Requested behavior

- An owner controls whether an item is open for new requests.
- The owner sees exact booking start/end times and available periods.
- A renter sees general date availability rather than the owner's detailed booking schedule.
- Hourly and daily rentals use their applicable rates. An hourly request is not automatically charged for a full day.
- An item can become available again on the same day after it has actually returned, been inspected, and the owner explicitly reopens it.
- The passage of the scheduled return time alone must not reopen an unreturned item.
- A manual availability switch must not erase history or override a conflicting reservation or an item still in another renter's possession.
- Rental periods, calendar restrictions, cart checks, request submission, agreement finalization, and owner approval must use the same server-side rules.

## Selected rules

1. **Maximum duration:** retain one clearly labeled owner setting, **Maximum rental days**, and remove the separate minimum/maximum-hour settings. This resolves the conflicting duration instructions in favor of the explicit requirement that calendar selection obey an owner-set maximum. Maximum means a cap, not a minimum charge: a one-day maximum can still permit a two-hour rental at the hourly rate. Daily bookings count selected calendar dates inclusively; hourly bookings use exact elapsed hours capped at maximum days × 24, and both must fit the available date and opening-hour windows.
2. **Renter time visibility:** use a date-only availability calendar; select start/end times in the rental request form. Only the owner sees the detailed schedule of existing bookings and free periods. The renter sees the relevant opening/closing hours as booking guidance and their own requested/agreed times, without seeing other renters' exact booking intervals. Requests must validate the chosen times before submission.

The incomplete map-popup instruction does not block Phase 1; it belongs to Phase 3.

## Implemented availability flow

| Situation | Renter-facing availability | Owner action / view |
| --- | --- | --- |
| Published, within the availability window, owner has opened requests, no blocking commitment | Available | Exact schedule and open/close control |
| Owner has closed requests or the listing is outside its availability window | Unavailable | Owner may adjust settings; existing bookings stay intact |
| Blocking reservation or rental exists for the selected date | Unavailable for that date; do not expose another renter's schedule | Show exact reserved/rented interval and its status |
| Scheduled end passes but item has not returned | Remains unavailable for reuse | Show overdue/unreturned item; reopening cannot bypass possession |
| Item has returned but inspection is unfinished | Remains unavailable for reuse | Complete the return/inspection workflow |
| Item returned and inspected | Wait for the owner's explicit reopening | Owner may reopen for a new future start time on the same day |
| Owner reopens after return/inspection | Available for remaining eligible time, provided no other blocking commitment exists | Preserve the old booking and all financial/dispute history |

This follows the detailed request that a renter sees a date as unavailable while it is committed, even if another hourly interval on that date would mathematically fit. Exact non-overlap alone is not permission to bypass the owner's reopening decision. Confirmed future bookings remain protected when an earlier booking is returned early. A midnight end does not occupy the following calendar date.

Return/inspection availability and financial settlement are separate. Reopening does not mark a deposit as received, waive charges, resolve a dispute, or modify the original agreed schedule. Existing condition/damage rules must still establish whether the item is suitable for another rental.

## Time and duration rules

- Use Asia/Manila for all user-facing dates, times, and validation; store exact instants consistently in UTC.
- Allow same-day requests with a start time still in the future, subject to owner availability and the return/inspection/reopening conditions above.
- Reject past starts and end times that do not follow their start time.
- Enforce the owner's opening/closing window. An overnight period must be validated across every covered date and opening window.
- Maintain both dates and exact times where needed; calendar date boundaries and billing duration must not be treated as interchangeable.
- Retain the owner's maximum-days cap as defined above, in addition to the availability window. Apply the same rule in the calendar and backend.
- Owner-configured min/max-hour controls and their validation are removed. Legacy database columns remain for compatibility but no longer limit new rentals. Hourly billing supports whole hours; no new minute-based billing or rounding policy has been introduced.
- Requests, cart previews, and final confirmations must revalidate current availability rather than trusting a previously displayed calendar.
- Do not rewrite existing rental prices, agreed times, or historical terms to apply new rules retroactively.

## Original implementation findings (before Phase 1)

| Area | Current behavior | Required follow-up |
| --- | --- | --- |
| `app/Services/RentalPeriod.php` | New requests start no earlier than tomorrow; configurable min/max hours enforced; daily requests have no exact instants | Add consistent same-day/future-time validation and implement the selected duration/time policy |
| `app/Models/Listing.php` | Requestable-date checks start tomorrow; availability combines owner switch, reservations, and rentals | Align earliest date and date-level availability with explicit reopening |
| `app/Livewire/RentalRequests/Create.php` | Request form defaults to tomorrow | Align defaults with requestable times and available window |
| `resources/views/livewire/listings/show.blade.php` and `resources/js/calendar.js` | Date calendar disables whole booked/reserved dates; no maximum-selected-range check in calendar | Enforce the chosen duration cap and same-day rules without exposing owner-only schedule details |
| `app/Livewire/Owner/Listings/Index.php` | Availability switch directly changes `is_available` | Validate reopening eligibility; coordinate with return/inspection state |
| `app/Services/RentalLifecycle.php` | Has return confirmation and inspection/completion flow | Connect physical readiness to an explicit reopening action without changing financial history |
| `tests/Feature/RentalBookingRulesTest.php` | Adjacent hourly bookings are accepted based on non-overlap | Update expectations to account for the requested date-level blocking and manual reopening policy |

Actual payment confirmation and agreement approval status transitions are addressed in Phases 4–5. Their final availability checks must honor the Phase 1 rules.

## Acceptance scenarios for implementation

1. At 17:00, a published/open item can be requested for 18:00–20:00 today if its window permits it and no blocking commitment exists.
2. While that rental is reserved or in progress, the renter sees today as unavailable; the owner sees its exact interval.
3. At 20:01, an item still with the renter remains unavailable even though its scheduled rental has ended.
4. At 22:00, after actual return and inspection, the owner explicitly reopens it. A new eligible start later that day is allowed if the owner's closing time permits it.
5. Return without inspection, inspection without actual return confirmation, or merely toggling availability cannot bypass physical readiness checks.
6. Reopening does not overwrite the first rental's agreed dates, charges, payment, deposit, or dispute records.
7. Another confirmed future booking remains protected after early return/reopening.
8. A direct request or stale cart that bypasses the calendar receives the same validation as the visible form.
9. Overlapping or concurrently finalized bookings cannot both reserve the item.
10. The selected duration rule is identical in the calendar, request form, cart, and backend; requests at the boundary pass and requests beyond it fail.
11. A period crossing midnight uses Philippine dates consistently and cannot bypass availability-window or opening-hour checks.

## Completion criterion

Phase 1's backend, duration controls, renter time fields, calendar limits, owner schedule, and reopening checks are implemented and verified locally. Other category, map, notification, dashboard, payment, and admin changes remain in their respective later phases.
