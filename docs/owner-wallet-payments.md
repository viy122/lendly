# Owner payment QR

In the owner interface, open **Payment QR**. Choose Maya or GCash, enter the recipient account name and optional instructions, upload a JPG or PNG QR image (up to 5 MB), and enable it. Confirm that the uploaded QR belongs to the intended receiving account in the wallet app. Lendly stores and displays the image; it does not authenticate wallet ownership or connect to a payment gateway.

When an eligible renter opens payment instructions for an agreed, unpaid booking, Lendly saves that booking's payment destination. An enabled owner wallet takes precedence over the centralized bank/cash instructions. If neither is available, payment remains unavailable. Changing or removing an owner's current QR does not rewrite a destination already issued for an existing booking. Old images remain private and are retained when transaction records still reference them.

The renter scans/imports that QR in their own e-wallet, checks the recipient and full booking total, pays outside Lendly, then submits a reference and proof. The receiving owner acknowledges that the full amount actually arrived. An administrator still verifies the evidence before the booking becomes paid and handover is allowed. Uploading an image, submitting proof or acknowledging receipt never automatically creates a paid booking.

The full booking total includes the rental fee, platform fee and refundable deposit. For an owner-wallet payment the owner physically receives all of those funds. Platform-fee remittance and deposit refunds are manual; the app does not split, transfer, hold in escrow or automatically refund wallet funds. Dashboard fee totals describe accrued fees rather than a confirmed cash balance.

QR images and proofs use authenticated private routes. A renter can access their own booking's saved QR; the snapshotted receiving owner can access the related QR and payment proof; verified active administrators can review them. Unrelated accounts have no access. Centralized bank/cash proofs keep their existing renter/admin access rule.

Account deletion removes the current wallet configuration and any unreferenced QR image. QR images already referenced by transaction history are retained as private evidence alongside the saved payment destination.

Apply `2026_10_20_000002_add_owner_wallet_payments.php` and build frontend assets when deploying. No Maya/GCash API credentials are required for displaying an owner-supplied QR and manually reviewing payment proof. No real money movement is tested by automated tests.
