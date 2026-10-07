<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\RentalStatus;
use App\Models\OfflinePaymentSetting;
use App\Models\Payment;
use App\Models\PaymentSubmission;
use App\Models\Rental;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RentalPayments
{
    public static function submit(Rental $rental, User $renter, string $reference, UploadedFile $proof): PaymentSubmission
    {
        $path = null;
        try {
            return DB::transaction(function () use ($rental, $renter, $reference, $proof, &$path) {
                $rental = Rental::lockForUpdate()->findOrFail($rental->id);
                Gate::forUser($renter)->authorize('pay', $rental);
                $settings = OfflinePaymentSetting::current();
                if (! $settings?->enabled || trim($settings->instructions ?? '') === '') {
                    throw ValidationException::withMessages(['payment_proof' => 'Payment instructions are not available yet. Please contact support.']);
                }
                $reference = strtoupper(trim($reference));
                validator(['payment_reference' => $reference, 'payment_proof' => $proof], [
                    'payment_reference' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9][A-Z0-9._\/-]*$/'],
                    'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
                ])->validate();
                if (Payment::where('external_reference', $reference)->exists()
                    || PaymentSubmission::where('external_reference', $reference)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['payment_reference' => 'This reference is already recorded or awaiting verification.']);
                }
                $path = $proof->store('payment-proofs', 'local');
                if (! $path || ! Storage::disk('local')->exists($path) || Storage::disk('local')->size($path) === 0) {
                    throw ValidationException::withMessages(['payment_proof' => 'Unable to store payment proof. Please try again.']);
                }
                $submission = $rental->paymentSubmissions()->create([
                    'submitted_by' => $renter->id, 'method' => $settings->method,
                    'instructions' => $settings->instructions, 'external_reference' => $reference,
                    'amount' => $rental->total_amount, 'proof_path' => $path, 'status' => 'pending',
                ]);
                foreach (User::where('role', 'admin')->whereNotNull('email_verified_at')->get() as $admin) {
                    if (Gate::forUser($admin)->allows('viewAny', Rental::class)) {
                        $admin->notify(new TalaNotification(NotificationType::PaymentSubmitted->value, 'Payment proof awaiting verification',
                            'Review payment proof for booking #'.$rental->id.'.', route('admin.rentals.show', $rental)));
                    }
                }

                return $submission;
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    /** Called inside the audited administrator transaction. */
    public static function review(Rental $rental, User $admin, ?int $submissionId, bool $approve, string $notes, bool $fundsReceived): void
    {
        DB::transaction(function () use ($rental, $admin, $submissionId, $approve, $notes, $fundsReceived) {
            $rental = Rental::lockForUpdate()->findOrFail($rental->id);
            Gate::forUser($admin)->authorize($approve ? 'adminVerifyPayment' : 'adminRejectPayment', $rental);
            validator(['notes' => trim($notes)], ['notes' => ['required', 'max:1000']])->validate();
            $submission = $rental->paymentSubmissions()->lockForUpdate()->find($submissionId);
            abort_unless($submission, 404);
            abort_unless($submission->status === 'pending', 403, 'This submission has already been reviewed.');
            if ($approve) {
                validator(['funds_received' => $fundsReceived], ['funds_received' => ['accepted']])->validate();
                if (! Storage::disk('local')->exists($submission->proof_path)) {
                    throw ValidationException::withMessages(['reason' => 'The uploaded proof is missing. Request new proof before verifying payment.']);
                }
                if ($submission->amount !== $rental->total_amount || Payment::where('external_reference', $submission->external_reference)->exists()) {
                    throw ValidationException::withMessages(['reason' => 'The amount or payment reference does not match an eligible unpaid booking.']);
                }
                $paidAt = now();
                Payment::create([
                    'rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(),
                    'amount' => $rental->total_amount, 'status' => 'paid', 'paid_at' => $paidAt,
                    'method' => $submission->method, 'external_reference' => $submission->external_reference,
                    'payment_submission_id' => $submission->id, 'verified_by' => $admin->id,
                ]);
                SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => $rental->security_deposit]);
                $rental->update(['status' => RentalStatus::Paid, 'paid_at' => $paidAt]);
            }
            $submission->update([
                'status' => $approve ? 'verified' : 'rejected', 'reviewed_by' => $admin->id,
                'review_notes' => trim($notes), 'reviewed_at' => now(),
            ]);
        });
    }
}
