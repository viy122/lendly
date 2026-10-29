# Rental booking rules

Owners can offer daily rentals, hourly rentals, or both. Daily prices retain inclusive-date billing. Hourly periods use Philippine time, whole-hour durations, an hourly rate, owner minimum/maximum hours, the existing maximum-day limit, and the owner’s available dates and hours. Hourly instants are stored in UTC. Adjacent hourly periods are allowed; daily bookings block their full Philippine calendar dates. Hourly late fees use the agreed hourly price for each started overdue hour.

The marketplace has a day/hour price filter. Request forms and cart drafts preserve the selected unit and times. Cart drafts can contain reserved periods, but checkout checks current availability and prices again and cannot reserve an overlapping booking.

## Fees and cancellation

Administrators configure future rules under Payment settings: processing percentage, cancellation percentage, chargeable cancellation hours and payment reservation hours. Defaults are 10%, 20%, 24 hours and 24 hours respectively. The processing fee is the existing commission, charged once against the rental fee. Existing accepted agreements keep their saved rates, wording and legacy cancellation rules.

For new agreements, an unpacked reservation can be cancelled without a fee outside the chargeable window. Within the window or once packed, the renter supplies a reason and requests owner/admin review. The dates remain reserved; fulfillment and handover pause. Approval records the quoted rental-only fee and releases the dates. Rejection preserves the booking. The fee applies once even if both late and packed. Waiting for courier, Shipped, or either party’s handover confirmation closes ordinary cancellation. Problems after that use support/disputes. Pending payment proof must be reviewed before cancellation to avoid losing track of a transfer.

The cancellation reference is the saved hourly start, or the owner’s saved available-from time on the first daily date, in Philippine time. A pickup/delivery schedule confirmed by both participants replaces it. For a paid cancellation, the refund to arrange manually is the full booking total, including processing fee and deposit, less the cancellation fee. A financial status is a record, not an automatic transfer or confirmation that money was returned.

## Payments and reservations

Both parties accept the paper agreement before payment. The booking displays Pay now and Pay later, with a deadline. Owners configure GCash or Maya QR details in their existing Payment QR settings. Renters send money in their wallet app and upload the reference and proof. The wallet recipient confirms receipt and an administrator verifies payment. The full amount reaches the configured recipient; owners remit the platform fee manually and hold/refund deposits according to the agreement. Automatic gateway checkout, split settlements and refunds require a separately configured payment provider and are not simulated here.

New unpaid reservations expire after the saved payment window or at the rental start reference, whichever comes first, without a cancellation fee. Legacy reservations without a deadline are unchanged. Pending payment proof pauses expiry until reviewed; rejected proof does not hold dates indefinitely. Once expired, payment submission is blocked. A renter who transferred money without submitting proof before expiry must contact support for reconciliation rather than pay again.

## Availability waitlist and scheduler

Verified renters can join an exact-date/time waitlist from a request or saved cart draft. There is one entry per member/item and a maximum of 50 items. Rejoining the same period is idempotent; changing periods rejoins at the back. Members can review or leave their entries in My rental cart.

When dates become available, eligible entries are notified in joining order, once per entry. These are availability alerts, not exclusive offers: they do not reserve dates, automatically book an item, move the requested dates or collect payment. The owner still reviews requests. Submitting a request for the same period removes its waitlist entry.

Run Laravel’s scheduler continuously in development/hosting:

```powershell
php artisan schedule:work
```

Every minute, `rentals:process-reservations` expires unpaid reservations and sends availability alerts. The existing overdue command also handles hourly deadlines. The task can be run manually with `php artisan rentals:process-reservations`.

Apply `database/migrations/2026_10_29_000001_add_rental_booking_rules.php` on other installations before serving the updated screens. It adds fields/tables and does not rewrite saved agreements or existing rental charges.
