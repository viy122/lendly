<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GoogleInterfaceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_is_only_available_to_the_member_who_just_signed_in_with_google(): void
    {
        $this->get('/auth/google/interface')->assertRedirect(route('login'));
        $user = User::factory()->create();
        $this->actingAs($user)->get('/auth/google/interface')->assertForbidden();
        $this->withSession(['google_oauth_pending_user' => $user->id + 1])->get('/auth/google/interface')->assertForbidden();
        $this->withSession(['google_oauth_pending_user' => $user->id])->get('/auth/google/interface')->assertOk()
            ->assertSee('How would you like to use Lendly?')->assertSee($user->email);
    }

    public function test_each_interface_opens_its_dashboard_for_an_identity_verified_google_member(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['google_id' => 'google-member', 'identity_verified_at' => now(), 'identity_verification_environment' => 'production'])->save();
        foreach (['renter', 'owner'] as $interface) {
            $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id, 'url.intended' => '/admin/dashboard']);
            Volt::test('pages.auth.google-interface')->call('chooseInterface', $interface)->assertHasNoErrors()
                ->assertRedirect(route($interface.'.dashboard'))->assertSessionHas('active_interface', $interface)
                ->assertSessionMissing('google_oauth_pending_user')->assertSessionMissing('url.intended');
            $this->assertAuthenticatedAs($user);
        }
    }

    public function test_identity_pending_member_continues_to_id_and_selfie_after_choosing(): void
    {
        config(['services.identity' => [
            'base_url' => 'https://identity.example.test', 'client_id' => 'test-client',
            'client_secret' => 'test-secret', 'public_key' => 'test-public',
            'sdk_url' => 'https://sdk.example.test/liveness.js', 'environment' => 'sandbox',
        ]]);
        $user = User::factory()->create();
        foreach (['renter', 'owner'] as $interface) {
            $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
            Volt::test('pages.auth.google-interface')->call('chooseInterface', $interface)
                ->assertRedirect(route('identity.verify'))->assertSessionHas('active_interface', $interface);
        }
        $this->assertFalse($user->fresh()->hasVerifiedIdentity());
    }

    public function test_member_continues_to_manual_review_without_an_api_provider(): void
    {
        config(['services.identity.client_id' => null]);
        $user = User::factory()->create();
        foreach (['renter', 'owner'] as $interface) {
            $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
            Volt::test('pages.auth.google-interface')->call('chooseInterface', $interface)
                ->assertRedirect(route('identity.verify'))->assertSessionHas('active_interface', $interface);
        }
        $this->assertFalse($user->fresh()->hasVerifiedIdentity());
    }

    public function test_unsupported_interface_cannot_select_admin_or_consume_the_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
        Volt::test('pages.auth.google-interface')->call('chooseInterface', 'admin')->assertForbidden();
        $this->assertSame($user->id, session('google_oauth_pending_user'));
        $this->assertNotSame('admin', session('active_interface'));
    }

    public function test_a_selection_cannot_be_replayed_after_it_was_consumed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
        $picker = Volt::test('pages.auth.google-interface')->call('chooseInterface', 'renter')->assertRedirect();
        $picker->call('chooseInterface', 'owner')->assertForbidden();
        $this->assertSame('renter', session('active_interface'));
    }

    public function test_cancel_signs_out_the_google_member_without_selecting_an_interface(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
        Volt::test('pages.auth.google-interface')->call('cancelInterfaceSelection')->assertRedirect(route('login'))
            ->assertSessionMissing('google_oauth_pending_user')->assertSessionMissing('active_interface');
        $this->assertGuest();
    }

    public function test_fresh_account_status_and_google_context_are_checked_on_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
        $picker = Volt::test('pages.auth.google-interface');
        $user->fresh()->update(['status' => 'suspended']);
        $picker->call('chooseInterface', 'renter')->assertForbidden();
        $this->assertNull(session('active_interface'));
    }

    public function test_a_lockout_after_google_login_still_blocks_interface_selection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['google_oauth_pending_user' => $user->id]);
        $picker = Volt::test('pages.auth.google-interface');
        for ($i = 0; $i < 3; $i++) {
            try {
                AccountLockout::recordFailure($user->email, 'interface');
            } catch (ValidationException) {
            }
        }
        $picker->call('chooseInterface', 'owner')->assertHasErrors('interface')->assertNoRedirect();
        $this->assertNull(session('active_interface'));
    }
}
