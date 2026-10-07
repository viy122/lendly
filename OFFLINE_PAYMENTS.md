# Verified offline payments

Lendly supports bank transfer or cash collection with private proof submission and administrator verification. Live gateway integration is outside this workflow.

1. As an admin, open **Transactions → Configure offline payment instructions**.
2. Select the method and enter actual recipient details or the collection location, hours and receipt instructions. The collection process must cover the full booking total, including the platform fee and security deposit. Enable submissions and save.
3. After both parties accept a confirmed booking agreement, the renter follows those instructions and uploads a transfer confirmation or official cash receipt with its reference.
4. In the transaction detail page, download the private proof and match its reference and full amount against the actual collection records. A screenshot alone is insufficient. Confirm the receipt checkbox, record verification notes and select **Verify received payment**.
5. Only verification creates the paid transaction, digital receipt and held security deposit, and makes hand-over available. Both parties receive the outcome. Rejection records the reason and allows new proof; old submissions remain in history.

Submissions are disabled until actual instructions are published. Changing or disabling instructions does not erase existing proof or prevent admins from reviewing it. Proof is stored under `storage/app/private/payment-proofs`, with authenticated downloads restricted to the booking renter and active verified admins.

Cancelled bookings cannot become paid through proof verification. If funds arrived before cancellation, arrange their return through the offline collection process, record the handling in the rejection reason or admin notes, and reject the pending proof. Recording a status never initiates a bank transfer or refund.

Existing payment records are retained with their original metadata. They are not retroactively labelled as verified offline payments.
