# Owner e-wallet QR payments

User request: each owner uploads their own e-wallet QR image (for example Maya); renters use it on that owner's bookings. Reuse the current offline proof/review flow; no payment gateway integration or automatic money movement.

Scope: owner Payment QR settings (Maya/GCash, account name, optional instructions, JPG/PNG up to 5 MB, enable/disable and remove); private images; immutable per-booking payment destination; renter QR display and proof upload; owner receipt acknowledgement; existing final admin review. Existing centralized bank/cash remains the fallback when no owner wallet is enabled. Never mark a booking paid merely for uploading a QR, proof or acknowledging receipt.

Data contract:
- `owner_payment_settings`: unique user_id, provider (`maya`/`gcash`), account_name (120), nullable instructions (1000), qr_path, enabled, timestamps.
- `rentals.payment_destination` nullable JSON. Freeze the destination when payment instructions are first issued to the eligible renter. Owner changes must not change a destination already shown.
- `payment_submissions.payment_destination` nullable JSON copied from rental; nullable `recipient_confirmed_at` timestamp. Legacy submissions without this JSON retain existing bank/cash behavior.
- Owner destination: `source=owner_wallet`, `method=maya|gcash`, `payee_user_id` (owner ID), `account_name`, `instructions`, `qr_path` (private disk). Platform destination: `source=platform`, `method=bank_transfer|cash`, `instructions`.
- Preserve old QR files while rental/submission snapshots reference them. Serialize settings replacement and snapshot creation on the settings row; delete newly stored files on database failure and unreferenced replacements after commit.

Interfaces:
- `OwnerPaymentSetting::currentFor(int $userId): ?self`.
- `RentalPayments::destinationFor(Rental $rental, User $renter): ?array`: authorized renter; freeze an enabled eligible owner's wallet or current enabled platform instructions, or return existing snapshot. No binding from other viewers or before accepted agreement. Source cannot be chosen by the client.
- `RentalPayments::submit(...)`: preserve signature; use frozen destination; copy it onto the submission. Existing admin notification plus owner notification for owner-wallet proof.
- `RentalPayments::confirmOwnerReceipt(Rental $rental, User $owner, int $submissionId, bool $fundsReceived): void`: only active email-verified snapshotted recipient, pending owned submission, matching full amount, stored proof, explicit receipt acknowledgement. Sets recipient_confirmed_at only; no paid/payment/deposit state.
- Admin `RentalPayments::review(...)`: existing approval boundary plus owner receipt required for owner-wallet submissions. Centralized submissions unchanged.
- Owner Livewire settings: `provider`, `account_name`, `instructions`, `enabled`, `qr_image`; actions `save()`, `removeQr()`; view receives `settings` current server model. All mutation/preview derive user ID from auth and reject admin/unverified/suspended/deleted accounts.
- Authenticated QR preview routes: `owner.payments.qr` current own settings; `rentals.payment-qr` for rental's stored destination only (renter, snapshotted recipient, verified active admin). No path parameter. Private/no-store + nosniff.
- Proof download extends to snapshotted recipient for wallet submissions only; centralized proofs remain renter/admin.

Ownership:
- Root: migration/models, authenticated QR controller/routes/navigation, renter/owner Livewire integration, admin view/payment labels, fee/deposit copy, docs and overall integration.
- Backend worker: RentalPayments.php and tests/Feature/OwnerWalletPaymentTest.php only.
- Settings worker: app/Livewire/Owner/Payments/Settings.php, resources/views/livewire/owner/payments/settings.blade.php, tests/Feature/OwnerWalletSettingsTest.php only.
- UI worker: renter/owner rental Blade payment sections and shared payment destination component only; no service or PHP class changes.

Checks: owner isolation; real image/size/MIME validation; replacement/removal and rollback cleanup; pre-payment snapshot stability; QR/proof access boundaries; receipt acknowledgement never pays; admin approval blocked until owner receipt; duplicate/cancelled/expired/unaccepted booking behavior; unchanged centralized proof flow. Run relevant payment/admin/history suites, build, Pint and Blade compile. Browser inspection may be disabled; no real wallet or money is used.

Financial scope: the full amount remains rental fee + platform fee + refundable deposit. For wallet payments the owner receives those funds. Platform-fee remittance and deposit refunds are manual; dashboards must describe earned/accrued fees rather than imply a cash balance. Preserve accepted agreement snapshots and existing audited admin approval.

## Completion and verification

Implemented settings/navigation, private QR/proof routes, frozen destinations, renter proof submission, owner receipt acknowledgement, final audited admin approval and custody disclosures. Account deletion also removes current wallet configuration and unreferenced QR images while preserving private transaction evidence. Applied the required pending schema updates and wallet migration to local MySQL (batch 4).

Final targeted verification passed: 57 upload/access/account/history/profile checks (496 assertions), plus 48 centralized/wallet payment and migration checks (282 assertions). Pint, Blade compilation and diff checks passed. The broad regression run executed 812 tests with two errors: the older payment migration test needed dependent wallet migration rollback ordering, and concurrent Windows view compilation caused a file-lock conflict. Corrected the migration test, isolated compiled-view caches, and reran all affected suites successfully. The broad suite was not repeated after these final fixes.

No real wallet transaction, QR ownership verification or physical scan was attempted. Browser visual inspection remains disabled in Work settings. Payments, fee remittance and deposit refunds remain manual; the module does not connect to wallet transaction APIs.
