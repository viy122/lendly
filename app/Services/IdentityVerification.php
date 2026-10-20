<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\IdentityVerificationAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IdentityVerification
{
    public function available(): bool
    {
        foreach (['base_url', 'client_id', 'client_secret', 'public_key', 'sdk_url'] as $key) {
            if (! is_string(config("services.identity.$key")) || trim(config("services.identity.$key")) === '') {
                return false;
            }
        }

        $environment = config('services.identity.environment');
        if (! in_array($environment, ['sandbox', 'production'], true)) {
            return false;
        }

        foreach (['base_url', 'sdk_url'] as $key) {
            $url = config("services.identity.$key");
            if (! filter_var($url, FILTER_VALIDATE_URL)
                || ! in_array(parse_url($url, PHP_URL_SCHEME), app()->runningUnitTests() ? ['http', 'https'] : ['https'], true)
                || parse_url($url, PHP_URL_USER) !== null
                || parse_url($url, PHP_URL_PASS) !== null
                || parse_url($url, PHP_URL_FRAGMENT) !== null
                || ($key === 'base_url' && parse_url($url, PHP_URL_QUERY) !== null)) {
                return false;
            }
            if ($environment === 'production' && preg_match('/hackathon|staging|sandbox/i', parse_url($url, PHP_URL_HOST))) {
                return false;
            }
        }

        return true;
    }

    public function begin(User $user, array $details): string
    {
        $details = $this->validatedDetails($details);
        if (! $this->available()) {
            throw $this->failure();
        }

        return DB::transaction(function () use ($user, $details) {
            $member = User::withTrashed()->whereKey($user->id)->lockForUpdate()->first();
            if (! $this->eligible($member) || ! $this->matchesAccount($member, $details) || $this->alreadyVerified($member)) {
                throw $this->failure();
            }

            $limit = 'identity-verification:'.$member->id;
            if (RateLimiter::tooManyAttempts($limit, 5)) {
                throw $this->failure();
            }
            RateLimiter::hit($limit, 600);

            // The user lock serializes starts and claims; only the newest attempt remains eligible.
            IdentityVerificationAttempt::where('user_id', $member->id)
                ->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);
            $attempt = new IdentityVerificationAttempt;
            $attempt->forceFill([
                'id' => (string) Str::uuid(),
                'user_id' => $member->id,
                'status' => 'pending',
                'input_hash' => $this->inputHash($member, $details),
                'expires_at' => now()->addMinutes(10),
            ])->save();

            return $attempt->id;
        });
    }

    public function finish(User $user, string $attemptId, array $details, string $sessionId): void
    {
        $details = $this->validatedDetails($details);
        if (! $this->available() || ! Str::isUuid($attemptId) || ! Str::isUuid($sessionId)) {
            throw $this->failure();
        }
        $configuration = config('services.identity');
        $sessionId = strtolower($sessionId);
        $sessionHash = hash('sha256', $sessionId);

        try {
            DB::transaction(function () use ($user, $attemptId, $details, $sessionHash) {
                $member = User::withTrashed()->whereKey($user->id)->lockForUpdate()->first();
                $attempt = IdentityVerificationAttempt::where('user_id', $user->id)->whereKey($attemptId)->lockForUpdate()->first();
                if (! $this->canFinish($member, $attempt, $details, 'pending')
                    || IdentityVerificationAttempt::where('liveness_session_hash', $sessionHash)->exists()) {
                    throw $this->failure();
                }
                $attempt->forceFill(['status' => 'processing', 'liveness_session_hash' => $sessionHash])->save();
            });
        } catch (QueryException) {
            // The unique session hash also closes the race between different members.
            throw $this->failure();
        }

        $reference = null;
        try {
            $http = Http::acceptJson()->asJson()->withoutRedirecting()->connectTimeout(5)->timeout(20);
            $auth = $http->post(rtrim($configuration['base_url'], '/').'/api/auth', [
                'client_id' => $configuration['client_id'],
                'client_secret' => $configuration['client_secret'],
            ]);
            $token = $auth->json('data.access_token');
            if ($auth->successful() && is_string($token) && $token !== ''
                && strlen($token) <= 8192 && ! preg_match('/[\x00-\x20\x7f]/', $token)) {
                $response = $http->withToken($token)->post(rtrim($configuration['base_url'], '/').'/api/query/qr', [
                    'value' => $details['qr_value'],
                    'face_liveness_session_id' => $sessionId,
                ]);
                $body = $response->json();
                if ($response->successful() && is_array($body) && $this->matchesResult($body, $details)) {
                    $reference = $body['data']['reference'];
                }
            }
        } catch (\Throwable) {
            // Never include provider exceptions or response bodies in application errors.
        }

        $verified = DB::transaction(function () use ($user, $attemptId, $details, $configuration, $sessionHash, $reference) {
            $member = User::withTrashed()->whereKey($user->id)->lockForUpdate()->first();
            $attempt = IdentityVerificationAttempt::where('user_id', $user->id)->whereKey($attemptId)->lockForUpdate()->first();
            if ($reference === null || ! $this->available()
                || config('services.identity.environment') !== $configuration['environment']
                || ! $this->canFinish($member, $attempt, $details, 'processing')
                || $attempt->liveness_session_hash !== $sessionHash) {
                if ($attempt?->status === 'processing') {
                    $attempt->forceFill(['status' => 'failed'])->save();
                }

                return false;
            }

            $member->forceFill([
                'identity_verified_at' => now(),
                'identity_reference' => $reference,
                'identity_verification_environment' => $configuration['environment'],
            ])->save();
            $attempt->forceFill(['status' => 'verified'])->save();

            return true;
        });

        if (! $verified) {
            throw $this->failure();
        }
    }

    private function validatedDetails(array $details): array
    {
        $validator = Validator::make($details, [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['present', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['present', 'string', 'max:30'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'qr_value' => ['required', 'string', 'max:20000'],
        ]);
        if ($validator->fails()) {
            throw $this->failure();
        }

        $validated = $validator->validated();
        foreach ($validated as $value) {
            if (! mb_check_encoding($value, 'UTF-8')) {
                throw $this->failure();
            }
        }

        return $validated;
    }

    private function eligible(?User $user): bool
    {
        return $user && ! $user->trashed() && $user->role === UserRole::Member
            && $user->status === UserStatus::Active && $user->hasVerifiedEmail();
    }

    private function alreadyVerified(User $user): bool
    {
        return $user->identity_verified_at !== null
            && ($user->identity_verification_environment === 'production'
                || $user->identity_verification_environment === config('services.identity.environment'));
    }

    private function canFinish(?User $user, ?IdentityVerificationAttempt $attempt, array $details, string $status): bool
    {
        return $this->eligible($user) && ! $this->alreadyVerified($user)
            && $attempt && $attempt->status === $status && $attempt->expires_at->isFuture()
            && $this->matchesAccount($user, $details)
            && hash_equals($attempt->input_hash, $this->inputHash($user, $details));
    }

    private function matchesAccount(User $user, array $details): bool
    {
        return $this->normalize($user->name) === $this->normalize(implode(' ', [
            $details['first_name'], $details['middle_name'], $details['last_name'], $details['suffix'],
        ]));
    }

    private function matchesResult(array $body, array $details): bool
    {
        $data = $body['data'] ?? null;
        if (! is_array($data) || ($data['code'] ?? null) !== 'AAA001' || ($body['meta']['result_grade'] ?? null) !== 1
            || (array_key_exists('verified', $data) && $data['verified'] !== true)
            || ! is_string($data['reference'] ?? null) || trim($data['reference']) === '' || mb_strlen($data['reference']) > 255) {
            return false;
        }
        foreach (['first_name', 'middle_name', 'last_name', 'suffix', 'birth_date'] as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
            $value = $data[$key];
            if ($value === null && in_array($key, ['middle_name', 'suffix'], true)) {
                $value = '';
            }
            if (! is_string($value) || $this->normalize($value) !== $this->normalize($details[$key])) {
                return false;
            }
        }

        return true;
    }

    private function inputHash(User $user, array $details): string
    {
        return hash_hmac('sha256', json_encode([
            $user->id, $user->name, $user->email, $details, config('services.identity.environment'),
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    private function normalize(string $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }

    private function failure(): ValidationException
    {
        return ValidationException::withMessages(['identity' => 'Identity verification could not be completed. Please start again or try later.']);
    }
}
