<?php

namespace Tests\Feature;

use App\Livewire\Identity\Verify;
use App\Models\IdentityVerificationAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class IdentityVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_email_verified_members_can_open_identity_verification(): void
    {
        $this->get('/identity/verify')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get('/identity/verify')
            ->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->create())->get('/identity/verify')->assertForbidden();
    }

    public function test_both_member_interfaces_have_the_same_honest_unavailable_flow(): void
    {
        config(['services.identity.client_id' => null]);
        $member = User::factory()->create();

        foreach (['owner', 'renter'] as $interface) {
            $this->actingAs($member)->withSession(['active_interface' => $interface])->get('/identity/verify')
                ->assertOk()->assertSee('Verify your identity')
                ->assertSee('Automatic identity verification is not available yet')
                ->assertDontSee('Identity verified');
        }

        $this->assertDatabaseCount('identity_verification_attempts', 0);
        $this->assertFalse($member->fresh()->hasVerifiedIdentity());
    }

    public function test_consent_is_required_before_a_verification_attempt_is_created(): void
    {
        $member = User::factory()->create();

        Livewire::actingAs($member)->test(Verify::class)->call('startVerification')
            ->assertHasErrors('consent');

        $this->assertDatabaseCount('identity_verification_attempts', 0);
    }

    public function test_name_changes_clear_identity_verification_and_cancel_pending_attempts(): void
    {
        $member = User::factory()->create();
        $member->forceFill([
            'identity_verified_at' => now(), 'identity_reference' => 'PRIVATE-REFERENCE',
            'identity_verification_environment' => 'production',
        ])->save();
        $attempt = IdentityVerificationAttempt::create([
            'user_id' => $member->id, 'status' => 'pending', 'input_hash' => str_repeat('a', 64),
            'expires_at' => now()->addMinutes(10),
        ]);

        $member->update(['name' => 'A different name']);

        $this->assertNull($member->fresh()->identity_verified_at);
        $this->assertNull($member->fresh()->identity_reference);
        $this->assertSame('cancelled', $attempt->fresh()->status);
    }

    public function test_private_identity_reference_is_encrypted_and_not_serialized(): void
    {
        $member = User::factory()->create();
        $member->forceFill(['identity_reference' => 'PRIVATE-IDENTITY-REFERENCE'])->save();

        $this->assertSame('PRIVATE-IDENTITY-REFERENCE', $member->fresh()->identity_reference);
        $this->assertNotSame('PRIVATE-IDENTITY-REFERENCE', $member->fresh()->getRawOriginal('identity_reference'));
        $this->assertArrayNotHasKey('identity_reference', $member->fresh()->toArray());
        $this->assertArrayNotHasKey('identity_verified_at', $member->fresh()->toArray());
    }

    public function test_a_stale_profile_edit_clears_identity_verified_by_another_request(): void
    {
        $member = User::factory()->create();
        $staleProfile = $member->fresh();
        $member->forceFill([
            'identity_verified_at' => now(), 'identity_reference' => 'PRIVATE-REFERENCE',
            'identity_verification_environment' => 'production',
        ])->save();

        $staleProfile->update(['name' => 'Updated account name']);

        $this->assertNull($member->fresh()->identity_verified_at);
        $this->assertNull($member->fresh()->identity_reference);
        $this->assertNull($member->fresh()->identity_verification_environment);
    }

    public function test_public_profile_only_shows_production_verification_without_private_reference(): void
    {
        $member = User::factory()->create();
        $member->forceFill([
            'identity_verified_at' => now(), 'identity_reference' => 'SECRET-REFERENCE',
            'identity_verification_environment' => 'sandbox',
        ])->save();
        $this->get(route('users.show', $member))->assertOk()->assertDontSee('Identity verified');

        $member->forceFill(['identity_verification_environment' => 'production'])->save();
        $this->get(route('users.show', $member))->assertOk()->assertSee('Identity verified')
            ->assertDontSee('SECRET-REFERENCE');
    }

    public function test_livewire_completion_uses_the_server_result_and_clears_submitted_details(): void
    {
        $this->configureProvider();
        Http::preventStrayRequests();
        Http::fake([
            '*/api/auth' => Http::response(['data' => ['access_token' => 'test-token']]),
            '*/api/query/qr' => Http::response([
                'data' => ['code' => 'AAA001', 'reference' => 'test-reference', 'first_name' => 'Maria',
                    'middle_name' => '', 'last_name' => 'Reyes', 'suffix' => '', 'birth_date' => '1990-01-02'],
                'meta' => ['result_grade' => 1],
            ]),
        ]);
        $member = User::factory()->create(['name' => 'Maria Reyes']);
        $component = Livewire::actingAs($member)->test(Verify::class)
            ->assertDontSee('test-secret')->assertDontSee('test-client')
            ->set('first_name', 'Maria')->set('last_name', 'Reyes')->set('birth_date', '1990-01-02')
            ->set('qr_value', 'synthetic-qr')->set('consent', true)->call('startVerification')
            ->assertHasNoErrors();
        $attemptId = $component->get('attemptId');

        $component->call('completeVerification', $attemptId, (string) Str::uuid())
            ->assertHasNoErrors()->assertDispatched('identity-verified')->assertSee('Identity verified')
            ->assertSet('qr_value', '')->assertSet('birth_date', '')->assertSet('first_name', '')
            ->assertSet('attemptId', null)->assertSet('consent', false);

        $this->assertTrue($member->fresh()->hasVerifiedIdentity());
        Http::assertSentCount(2);
    }

    public function test_an_unstarted_completion_cannot_grant_verification(): void
    {
        Http::preventStrayRequests();
        $member = User::factory()->create();

        Livewire::actingAs($member)->test(Verify::class)->set('consent', true)
            ->call('completeVerification', (string) Str::uuid(), (string) Str::uuid())->assertForbidden();

        $this->assertFalse($member->fresh()->hasVerifiedIdentity());
        Http::assertNothingSent();
    }

    public function test_a_sandbox_verified_member_can_open_the_production_verification_form(): void
    {
        $this->configureProvider();
        $member = User::factory()->create();
        $member->forceFill(['identity_verified_at' => now(), 'identity_verification_environment' => 'sandbox'])->save();

        $this->actingAs($member)->get('/identity/verify')->assertOk()
            ->assertSee('Continue to live selfie')->assertDontSee('Your test identity check is complete.');
    }

    public function test_cancelling_an_old_attempt_does_not_cancel_a_newer_attempt(): void
    {
        $this->configureProvider();
        $member = User::factory()->create(['name' => 'Maria Reyes']);
        $component = Livewire::actingAs($member)->test(Verify::class)
            ->set('first_name', 'Maria')->set('last_name', 'Reyes')->set('birth_date', '1990-01-02')
            ->set('qr_value', 'synthetic-qr')->set('consent', true)->call('startVerification');
        $oldId = $component->get('attemptId');
        $component->call('startVerification');
        $newId = $component->get('attemptId');

        $component->call('cancelVerification', $oldId)->assertHasNoErrors()->assertSet('attemptId', $newId);
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $newId, 'status' => 'pending']);

        $component->call('cancelVerification', $newId)->assertSet('attemptId', null);
        $this->assertDatabaseHas('identity_verification_attempts', ['id' => $newId, 'status' => 'cancelled']);
    }

    public function test_the_client_cannot_replace_the_server_bound_attempt_id(): void
    {
        $member = User::factory()->create();
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($member)->test(Verify::class)->set('attemptId', (string) Str::uuid());
    }

    public function test_a_member_cannot_cancel_another_members_attempt(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $attempt = IdentityVerificationAttempt::create([
            'user_id' => $other->id, 'status' => 'pending', 'input_hash' => str_repeat('a', 64),
            'expires_at' => now()->addMinutes(10),
        ]);

        Livewire::actingAs($member)->test(Verify::class)->call('cancelVerification', $attempt->id)
            ->assertHasNoErrors();

        $this->assertSame('pending', $attempt->fresh()->status);
    }

    private function configureProvider(): void
    {
        config(['services.identity' => [
            'base_url' => 'https://identity.example.test', 'client_id' => 'test-client',
            'client_secret' => 'test-secret', 'public_key' => 'test-public',
            'sdk_url' => 'https://sdk.example.test/liveness.js', 'environment' => 'production',
        ]]);
    }
}
