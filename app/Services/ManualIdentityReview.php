<?php

namespace App\Services;

use App\Models\IdentitySubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ManualIdentityReview
{
    public function submit(User $member, UploadedFile $front, ?UploadedFile $back, UploadedFile $selfie, bool $consent): IdentitySubmission
    {
        $image = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000'];
        Validator::make(compact('front', 'back', 'selfie', 'consent'), [
            'front' => $image, 'selfie' => $image, 'back' => ['nullable', ...array_slice($image, 1)], 'consent' => ['accepted'],
        ])->validate();

        $paths = [];
        try {
            $member = User::find($member->id);
            $this->eligible($member);
            DB::transaction(function () use ($member) {
                User::whereKey($member->id)->lockForUpdate()->firstOrFail();
                IdentitySubmission::where('user_id', $member->id)->where('status', 'pending')->where('created_at', '<=', now()->subDays(30))
                    ->get()->each(function ($expired) {
                        $expired->update(['status' => 'cancelled']);
                        $expired->purgeDocuments();
                    });
            });
            if ($member->hasVerifiedIdentity() || IdentitySubmission::where('user_id', $member->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['identity' => 'Your account is already approved or awaiting review.']);
            }
            $limitKey = 'identity-submit:'.$member->id;
            if (RateLimiter::tooManyAttempts($limitKey, 5)) {
                throw ValidationException::withMessages(['identity' => 'Too many submission attempts. Please wait ten minutes before trying again.']);
            }
            RateLimiter::hit($limitKey, 600);
            $fingerprint = IdentitySubmission::profileHash($member);
            foreach (['front' => $front, 'back' => $back, 'selfie' => $selfie] as $kind => $file) {
                if ($file) {
                    $path = $file->store('identity-documents/'.$member->id, 'local');
                    if (! $path || ! Storage::disk('local')->exists($path) || Storage::disk('local')->size($path) === 0) {
                        throw ValidationException::withMessages(['identity' => 'Your photos could not be saved. Please try again.']);
                    }
                    $paths[$kind.'_path'] = $path;
                }
            }
            $score = app(FaceComparison::class)->compare(Storage::disk('local')->path($paths['front_path']), Storage::disk('local')->path($paths['selfie_path']));
            if (! is_finite($score) || $score < -1 || $score > 1) {
                throw ValidationException::withMessages(['identity' => 'Face comparison could not be completed. Please try again.']);
            }
            if (DB::transactionLevel() > 0) {
                DB::afterRollBack(fn () => Storage::disk('local')->delete($paths));
            }

            return DB::transaction(function () use ($member, $fingerprint, $paths, $score) {
                $fresh = User::whereKey($member->id)->lockForUpdate()->first();
                $this->eligible($fresh);
                if (! hash_equals($fingerprint, IdentitySubmission::profileHash($fresh)) || $fresh->hasVerifiedIdentity()
                    || IdentitySubmission::where('user_id', $fresh->id)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['identity' => 'Your account or review status changed. Refresh the page and try again.']);
                }

                return IdentitySubmission::create([
                    'user_id' => $fresh->id, 'status' => 'pending', ...$paths, 'profile_hash' => $fingerprint,
                    'face_similarity' => $score, 'consented_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    public function review(User $admin, int $submissionId, string $decision, string $notes, bool $qrChecked = false, bool $detailsMatch = false, bool $faceReviewed = false): IdentitySubmission
    {
        $freshAdmin = User::find($admin->id);
        abort_unless($freshAdmin && $freshAdmin->isAdmin() && ! $freshAdmin->isSuspended() && $freshAdmin->hasVerifiedEmail(), 403);
        Validator::make(['decision' => $decision, 'notes' => trim($notes), 'qr_checked' => $qrChecked,
            'details_match' => $detailsMatch, 'face_reviewed' => $faceReviewed], [
                'decision' => ['required', 'in:approved,rejected'],
                'notes' => [$decision === 'rejected' ? 'required' : 'nullable', 'string', 'max:1000'],
                'qr_checked' => $decision === 'approved' ? ['accepted'] : ['boolean'],
                'details_match' => $decision === 'approved' ? ['accepted'] : ['boolean'],
                'face_reviewed' => $decision === 'approved' ? ['accepted'] : ['boolean'],
            ])->validate();

        return DB::transaction(function () use ($admin, $submissionId, $decision, $notes) {
            $snapshot = IdentitySubmission::findOrFail($submissionId);
            // Lock participants in a stable order, before the submission.
            $users = User::whereIn('id', [$admin->id, $snapshot->user_id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $reviewer = $users->get($admin->id);
            abort_unless($reviewer && $reviewer->isAdmin() && ! $reviewer->isSuspended() && $reviewer->hasVerifiedEmail()
                && $reviewer->id !== $snapshot->user_id, 403);
            $member = $users->get($snapshot->user_id);
            $this->eligible($member);
            $submission = IdentitySubmission::whereKey($submissionId)->lockForUpdate()->firstOrFail();
            if ($submission->status !== 'pending' || $submission->created_at->lte(now()->subDays(30))
                || ! hash_equals($submission->profile_hash, IdentitySubmission::profileHash($member))) {
                throw ValidationException::withMessages(['identity' => 'This submission is no longer pending or its account details changed.']);
            }
            if ($decision === 'approved') {
                if ($submission->face_similarity === null || ! is_finite($submission->face_similarity)
                    || $submission->face_similarity < -1 || $submission->face_similarity > 1) {
                    throw ValidationException::withMessages(['identity' => 'A completed face comparison is required before approval.']);
                }
                foreach (array_filter([$submission->front_path, $submission->back_path, $submission->selfie_path]) as $path) {
                    if (! Storage::disk('local')->exists($path)) {
                        throw ValidationException::withMessages(['identity' => 'Review photos are unavailable. Ask the member to resubmit.']);
                    }
                }
                if (! $submission->front_path || ! $submission->selfie_path) {
                    throw ValidationException::withMessages(['identity' => 'The required review photos are missing.']);
                }
                $member->forceFill(['identity_verified_at' => now(), 'identity_verification_environment' => 'manual',
                    'identity_reference' => 'manual-review:'.$submission->id])->save();
            }
            $submission->update(['status' => $decision, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => trim($notes) ?: null]);
            $submission->purgeDocuments();

            return $submission->refresh();
        });
    }

    private function eligible(?User $member): void
    {
        if (! $member || $member->isAdmin() || $member->isSuspended() || ! $member->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['identity' => 'An active member account with a confirmed email is required.']);
        }
    }
}
