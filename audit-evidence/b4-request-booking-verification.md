# B.4 rental request approval and booking verification

Verified on 7 October 2026. Current status: **Implemented** for FR-11–16 and FR-40. A2, A3 and A9 are resolved in the current working tree.

## Required process

1. The renter submits the desired rental period and fulfillment method. No agreement acceptance is recorded during submission. Charges use inclusive rental days, daily rate, commission, and refundable deposit.
2. The request is pending, and both owner and renter receive in-app status notifications. The owner can review, approve, or decline; unrelated owners cannot act on it.
3. Approval saves the offered agreement and clears any acceptance recorded before approval. Both parties receive links to review the agreement. Approval alone creates no booking.
4. Both parties review the same saved dates, charges, item rules, cancellation policy, fees, and deposit conditions, then explicitly accept. Either party may accept first.
5. Booking creation checks both persisted acceptance timestamps under transaction locks. It creates one booking only after both accept; retries preserve the existing booking and notifications.
6. Both parties receive distinct booking confirmation notifications, each linking to their booking page. Payment follows confirmation.

## Submission integrity added in this follow-up

Request submission now locks the current listing using the same listing lock as approval. Availability, approved date overlaps, availability window, maximum duration, and fulfillment options are rechecked before saving. Charges read the commission rate once so the saved rate, fee, and total remain consistent. The request and both pending notifications commit together; a notification-storage failure rolls everything back. Success and redirect occur after commit.

## Verification results

`php artisan test --compact tests/Feature/RentalRequestTest.php tests/Feature/TermsAcceptanceTest.php tests/Feature/BookingConfirmationNotificationTest.php tests/Feature/NotificationsAndChatTest.php`: **68 tests passed, 653 assertions**.

Coverage includes:

- Pending, approved, manual decline and automatic overlap decline notifications for both parties.
- Post-approval agreement prompts, cancellation policy and fees, immutable offered terms, explicit checkbox validation, either acceptance order, saved-state rechecks, missing legacy agreement, unauthorized users, cancellation, retry/idempotence, and failure rollback.
- Persisted booking confirmations, booking links, notification-center display, and rollback if the second confirmation fails.
- Stale request forms after pause, removal, publication-status changes, shortened availability/duration, or withdrawn pickup; approval after listing availability changes.
- Updated listing charges and commission rate at submission; pending-notification failure and successful retry.

PHP syntax checks and whitespace checks passed for the changed files. Tests use isolated in-memory SQLite and in-app database notifications. No production data or external messages were created. Real concurrent MySQL sessions and a complete live two-account browser journey were not exercised in this follow-up.
