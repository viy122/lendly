<?php

namespace App\Http\Controllers;

use App\Models\PaymentSubmission;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    public function __invoke(PaymentSubmission $submission)
    {
        $user = User::find(auth()->id());
        abort_unless($user && $user->hasVerifiedEmail() && ! $user->isSuspended(), 403);
        $destination = $submission->payment_destination;
        $isRecipient = ($destination['source'] ?? null) === 'owner_wallet'
            && $user->id === ($destination['payee_user_id'] ?? null);
        abort_unless($user->id === $submission->rental->renter_id || $isRecipient
            || Gate::forUser($user)->allows('viewAny', Rental::class), 403);
        abort_unless(Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->download($submission->proof_path, 'payment-proof-'.$submission->id.'.'.pathinfo($submission->proof_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
