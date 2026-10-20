<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\IdentityVerificationAttempt;
use App\Models\User;
use App\Services\IdentityVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.identity' => [
            'base_url' => 'https://identity.example.test',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'public_key' => 'test-public-key',
            'sdk_url' => 'https://sdk.example.test/liveness.js',
            'environment' => 'production',
        ]]);
        Http::preventStrayRequests();
    }

    public function test_documented_server_result_verifies_matching_member_and_stores_only_private_evidence(): void
    {
        $user = $this->member();
        $details = $this->details();
        $session = (string) Str::uuid();
        $this->provider();

        $id = app(IdentityVerification::class)->begin($user, $details);
        app(IdentityVerification::class)->finish($user, $id, $details, $session);

        $user->refresh();
        $this->assertTrue($user->hasVerifiedIdentity());
        $this->assertFalse($user->hasTestedIdentity());
        $this->assertSame('reference-123', $user->identity_reference);
        $this->assertSame('production', $user->identity_verification_environment);
        $this->assertNotSame('reference-123', DB::table('users')->where('id', $user->id)->value('identity_reference'));
        $this->assertArrayNotHasKey('identity_reference', $user->toArray());
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $id, 'status' => 'verified']);
        $stored = json_encode(DB::table('identity_verification_attempts')->where('id', $id)->first());
        foreach (['synthetic-qr', '1990-01-02', $session, 'MARIA', 'reference-123'] as $private) {
            $this->assertStringNotContainsString($private, $stored);
        }
        Http::assertSent(fn (Request $request) => $request->url() === 'https://identity.example.test/api/auth'
            && $request->data() === ['client_id' => 'test-client', 'client_secret' => 'test-secret']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://identity.example.test/api/query/qr'
            && $request->hasHeader('Authorization', 'Bearer server-token')
            && $request->data() === ['value' => $details['qr_value'], 'face_liveness_session_id' => $session]);
        Http::assertSentCount(2);
    }

    public function test_sandbox_success_is_only_test_verification(): void
    {
        config(['services.identity.environment' => 'sandbox']);
        $user = $this->member();
        $this->provider();

        $this->finish($user);

        $this->assertFalse($user->fresh()->hasVerifiedIdentity());
        $this->assertTrue($user->fresh()->hasTestedIdentity());
        $this->assertSame('sandbox', $user->fresh()->identity_verification_environment);
    }

    #[DataProvider('invalidProviderResults')]
    public function test_partial_malformed_mismatched_and_unsuccessful_provider_results_fail_closed(array $response, int $status = 200): void
    {
        $user = $this->member();
        $this->provider($response, $status);
        $id = app(IdentityVerification::class)->begin($user, $this->details());

        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));

        $this->assertNull($user->fresh()->identity_verified_at);
        $this->assertNull($user->fresh()->identity_reference);
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $id, 'status' => 'failed']);
    }

    public static function invalidProviderResults(): array
    {
        $success = self::success();

        return [
            'documented face mismatch' => [['data' => ['verified' => false], 'meta' => ['result_grade' => 'FAILED_FACE']]],
            'browser verified flag' => [['data' => ['verified' => true]]],
            'missing grade' => [array_replace($success, ['meta' => []])],
            'string grade' => [array_replace($success, ['meta' => ['result_grade' => '1']])],
            'boolean grade' => [array_replace($success, ['meta' => ['result_grade' => true]])],
            'missing reference' => [array_replace_recursive($success, ['data' => ['reference' => '']])],
            'oversized reference' => [array_replace_recursive($success, ['data' => ['reference' => str_repeat('r', 256)]])],
            'contradictory verified flag' => [array_replace_recursive($success, ['data' => ['verified' => false]])],
            'wrong code' => [array_replace_recursive($success, ['data' => ['code' => 'AAA002']])],
            'different first name' => [array_replace_recursive($success, ['data' => ['first_name' => 'Ana']])],
            'different middle name' => [array_replace_recursive($success, ['data' => ['middle_name' => 'Dela Cruz']])],
            'different surname' => [array_replace_recursive($success, ['data' => ['last_name' => 'Santos']])],
            'different suffix' => [array_replace_recursive($success, ['data' => ['suffix' => 'Jr.']])],
            'different birth date' => [array_replace_recursive($success, ['data' => ['birth_date' => '1990-01-03']])],
            'missing demographic' => [array_replace_recursive($success, ['data' => ['last_name' => null]])],
            'missing optional demographic' => [['data' => ['code' => 'AAA001', 'reference' => 'reference-123', 'first_name' => 'Maria', 'last_name' => 'Reyes', 'birth_date' => '1990-01-02'], 'meta' => ['result_grade' => 1]]],
            'array demographic' => [array_replace_recursive($success, ['data' => ['first_name' => ['Maria']]])],
            'http failure' => [$success, 500],
            'redirect' => [$success, 302],
            'empty body' => [[]],
        ];
    }

    public function test_names_accept_only_case_and_whitespace_normalization(): void
    {
        $user = $this->member(['name' => '  maria   clara REYES ']);
        $this->provider(array_replace_recursive(self::success(), ['data' => ['first_name' => '  Maria ', 'middle_name' => 'clara', 'last_name' => 'reyes']]));

        $this->finish($user);

        $this->assertTrue($user->fresh()->hasVerifiedIdentity());
    }

    public function test_provider_null_optional_names_match_only_empty_submitted_values(): void
    {
        $user = $this->member(['name' => 'Maria Reyes']);
        $details = array_replace($this->details(), ['middle_name' => '']);
        $this->provider(array_replace_recursive(self::success(), ['data' => ['middle_name' => null, 'suffix' => null]]));

        $id = app(IdentityVerification::class)->begin($user, $details);
        app(IdentityVerification::class)->finish($user, $id, $details, (string) Str::uuid());

        $this->assertTrue($user->fresh()->hasVerifiedIdentity());
    }

    public function test_full_sized_qr_payload_is_supported_without_being_stored(): void
    {
        $details = array_replace($this->details(), ['qr_value' => str_repeat('q', 20000)]);
        $user = $this->member();
        $this->provider();

        $id = app(IdentityVerification::class)->begin($user, $details);
        app(IdentityVerification::class)->finish($user, $id, $details, (string) Str::uuid());

        $this->assertTrue($user->fresh()->hasVerifiedIdentity());
        $this->rejects(fn () => app(IdentityVerification::class)->begin($this->member(), array_replace($details, ['qr_value' => str_repeat('q', 20001)])));
    }

    public function test_entered_identity_must_match_current_account_name_without_fuzzy_matching(): void
    {
        $user = $this->member(['name' => 'María Clara Reyes']);

        $this->rejects(fn () => app(IdentityVerification::class)->begin($user, $this->details()));

        $this->assertDatabaseCount('identity_verification_attempts', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('invalidConfigurations')]
    public function test_missing_or_invalid_configuration_never_starts_verification(string $key, mixed $value): void
    {
        config(["services.identity.$key" => $value]);

        $this->assertFalse(app(IdentityVerification::class)->available());
        $this->rejects(fn () => app(IdentityVerification::class)->begin($this->member(), $this->details()));
        Http::assertNothingSent();
    }

    public static function invalidConfigurations(): array
    {
        return [
            ['client_id', ''], ['client_secret', ''], ['public_key', ''], ['base_url', ''], ['sdk_url', ''],
            ['environment', 'test'], ['sdk_url', 'https://staging-sdk.everify.gov.ph/face-liveness.js'],
            ['sdk_url', 'https://hackathon-everify-face-liveness.e.gov.ph/js/everify-liveness-sdk.min.js'],
            ['sdk_url', 'https://sandbox.example.test/sdk.js'],
            ['base_url', 'https://hackathon-everify.e.gov.ph'],
            ['base_url', 'https://staging.example.test'],
            ['base_url', 'https://sandbox.example.test'],
        ];
    }

    public function test_invalid_details_and_sessions_do_not_call_provider(): void
    {
        $user = $this->member();
        foreach ([['birth_date' => '1990-02-30'], ['qr_value' => ''], ['first_name' => []]] as $invalid) {
            $this->rejects(fn () => app(IdentityVerification::class)->begin($user, array_replace($this->details(), $invalid)));
        }
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), 'browser-verified'));
        Http::assertNothingSent();
    }

    public function test_invalid_text_encoding_is_rejected_with_a_generic_error(): void
    {
        $this->rejects(fn () => app(IdentityVerification::class)->begin($this->member(), array_replace($this->details(), ['qr_value' => "invalid-\xFF"])));

        $this->assertDatabaseCount('identity_verification_attempts', 0);
        Http::assertNothingSent();
    }

    public function test_insecure_urls_are_unavailable_outside_automated_testing(): void
    {
        $this->app->instance('env', 'production');
        config(['services.identity.base_url' => 'http://identity.example.test']);
        $this->assertFalse(app(IdentityVerification::class)->available());
        config(['services.identity.base_url' => 'https://identity.example.test', 'services.identity.sdk_url' => 'http://sdk.example.test/liveness.js']);
        $this->assertFalse(app(IdentityVerification::class)->available());
    }

    public function test_only_active_email_verified_members_can_start(): void
    {
        foreach ([['email_verified_at' => null], ['status' => UserStatus::Suspended], ['role' => 'admin']] as $invalid) {
            $this->rejects(fn () => app(IdentityVerification::class)->begin($this->member($invalid), $this->details()));
        }
        Http::assertNothingSent();
    }

    public function test_begin_is_rate_limited_per_account(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 5; $i++) {
            app(IdentityVerification::class)->begin($user, $this->details());
        }
        $this->rejects(fn () => app(IdentityVerification::class)->begin($user, $this->details()));
        $this->assertNotEmpty(app(IdentityVerification::class)->begin($this->member(), $this->details()));
        Http::assertNothingSent();
    }

    public function test_new_attempt_cancels_earlier_attempt_and_old_response_cannot_finish(): void
    {
        $user = $this->member();
        $old = app(IdentityVerification::class)->begin($user, $this->details());
        app(IdentityVerification::class)->begin($user, $this->details());

        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $old, $this->details(), (string) Str::uuid()));

        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $old, 'status' => 'cancelled']);
        Http::assertNothingSent();
    }

    public function test_attempt_rejects_different_account_changed_details_and_expiry_before_remote_calls(): void
    {
        $user = $this->member();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        $this->rejects(fn () => app(IdentityVerification::class)->finish($this->member(), $id, $this->details(), (string) Str::uuid()));
        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, array_replace($this->details(), ['qr_value' => 'another-qr']), (string) Str::uuid()));
        $this->travel(11)->minutes();
        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));
        Http::assertNothingSent();
    }

    public function test_verified_attempt_and_liveness_session_cannot_be_replayed_even_on_another_account(): void
    {
        $user = $this->member();
        $session = (string) Str::uuid();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        $this->provider();
        app(IdentityVerification::class)->finish($user, $id, $this->details(), $session);
        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), $session));
        $other = $this->member();
        $otherId = app(IdentityVerification::class)->begin($other, $this->details());
        $this->rejects(fn () => app(IdentityVerification::class)->finish($other, $otherId, $this->details(), strtoupper($session)));
        $this->assertNull($other->fresh()->identity_verified_at);
        Http::assertSentCount(2);
    }

    public function test_hard_deleted_account_keeps_its_consumed_liveness_session_unusable(): void
    {
        $user = $this->member();
        $session = (string) Str::uuid();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        $this->provider();
        app(IdentityVerification::class)->finish($user, $id, $this->details(), $session);
        $hash = IdentityVerificationAttempt::findOrFail($id)->liveness_session_hash;

        $user->forceDelete();

        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $id, 'user_id' => null, 'liveness_session_hash' => $hash]);
        $other = $this->member();
        $otherId = app(IdentityVerification::class)->begin($other, $this->details());
        $this->rejects(fn () => app(IdentityVerification::class)->finish($other, $otherId, $this->details(), $session));
        $this->assertNull($other->fresh()->identity_verified_at);
        Http::assertSentCount(2);
    }

    #[DataProvider('accountChanges')]
    public function test_delayed_success_cannot_verify_an_account_changed_during_provider_call(string $change): void
    {
        $user = $this->member();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        Http::fake(function (Request $request) use ($user, $change) {
            if (str_ends_with($request->url(), '/api/auth')) {
                return Http::response(['data' => ['access_token' => 'server-token']]);
            }
            match ($change) {
                'name' => $user->forceFill(['name' => 'Changed Member'])->save(),
                'deleted' => $user->delete(),
                'suspended' => $user->forceFill(['status' => UserStatus::Suspended])->save(),
                'email' => $user->forceFill(['email_verified_at' => null])->save(),
                'environment' => config(['services.identity.environment' => 'sandbox']),
                'expiry' => $this->travel(11)->minutes(),
                'superseded' => app(IdentityVerification::class)->begin($user, $this->details()),
            };

            return Http::response(self::success());
        });

        $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));

        $this->assertNull(User::withTrashed()->findOrFail($user->id)->identity_verified_at);
        $this->assertNotSame('verified', IdentityVerificationAttempt::findOrFail($id)->status);
    }

    public static function accountChanges(): array
    {
        return array_map(fn ($change) => [$change], ['name', 'deleted', 'suspended', 'email', 'environment', 'expiry', 'superseded']);
    }

    public function test_processing_attempt_cannot_be_submitted_again_during_remote_call(): void
    {
        $user = $this->member();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        Http::fake(function (Request $request) use ($user, $id) {
            if (str_ends_with($request->url(), '/api/auth')) {
                $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));

                return Http::response(['data' => ['access_token' => 'server-token']]);
            }

            return Http::response(self::success());
        });

        app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid());

        $this->assertTrue($user->fresh()->hasVerifiedIdentity());
        Http::assertSentCount(2);
    }

    public function test_timeout_is_generic_and_consumes_the_attempt_without_exposing_sensitive_body(): void
    {
        $user = $this->member();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        Http::fake(fn () => throw new ConnectionException('sensitive provider content'));

        $error = $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));

        $this->assertStringNotContainsString('sensitive provider content', $error->getMessage());
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $id, 'status' => 'failed']);
        $this->assertNull($user->fresh()->identity_verified_at);
    }

    public function test_unexpected_http_client_error_is_generic_and_fails_the_attempt(): void
    {
        $user = $this->member();
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        Http::fake(fn () => throw new \RuntimeException('sensitive provider response'));

        $error = $this->rejects(fn () => app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid()));

        $this->assertStringNotContainsString('sensitive provider response', $error->getMessage());
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $id, 'status' => 'failed']);
        $this->assertNull($user->fresh()->identity_verified_at);
    }

    public function test_bad_authentication_response_does_not_send_qr(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response(['data' => ['access_token' => '']])]);

        $this->rejects(fn () => $this->finish($user));

        $this->assertNull($user->fresh()->identity_verified_at);
        Http::assertSentCount(1);
    }

    #[DataProvider('invalidTokens')]
    public function test_malformed_authentication_tokens_are_rejected_without_sending_qr(string $token): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response(['data' => ['access_token' => $token]])]);

        $this->rejects(fn () => $this->finish($user));

        $this->assertNull($user->fresh()->identity_verified_at);
        Http::assertSentCount(1);
    }

    public static function invalidTokens(): array
    {
        return [[str_repeat('t', 8193)], ["server-token\nprivate"], ["server-token\x00private"]];
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_replace(['name' => 'Maria Clara Reyes'], $attributes));
    }

    private function details(): array
    {
        return ['first_name' => 'MARIA', 'middle_name' => 'CLARA', 'last_name' => 'REYES', 'suffix' => '', 'birth_date' => '1990-01-02', 'qr_value' => 'synthetic-qr'];
    }

    private static function success(): array
    {
        return ['data' => ['code' => 'AAA001', 'reference' => 'reference-123', 'first_name' => 'Maria', 'middle_name' => 'Clara', 'last_name' => 'Reyes', 'suffix' => '', 'birth_date' => '1990-01-02'], 'meta' => ['result_grade' => 1]];
    }

    private function provider(?array $result = null, int $status = 200): void
    {
        Http::fake([
            '*/api/auth' => Http::response(['data' => ['access_token' => 'server-token']]),
            '*/api/query/qr' => Http::response($result ?? self::success(), $status),
        ]);
    }

    private function finish(User $user): void
    {
        $id = app(IdentityVerification::class)->begin($user, $this->details());
        app(IdentityVerification::class)->finish($user, $id, $this->details(), (string) Str::uuid());
    }

    private function rejects(callable $operation): ValidationException
    {
        try {
            $operation();
            $this->fail('Expected identity verification to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('identity', $exception->errors());

            return $exception;
        }
    }
}
