<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_role_dashboards(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_members_only_see_and_access_their_selected_interface(): void
    {
        $member = User::factory()->renter()->create();

        $this->actingAs($member)->withSession(['active_interface' => 'renter']);
        $this->get('/renter/dashboard')->assertOk()
            ->assertSee('Renter dashboard')
            ->assertSee('href="'.route('renter.rental-requests.index').'"', false)
            ->assertDontSee('href="'.route('owner.listings.index').'"', false)
            ->assertDontSee('Total earnings')
            ->assertDontSee('New listing');
        $this->get('/renter/rental-requests')->assertOk();
        $this->get('/owner/dashboard')->assertForbidden();
        $this->get('/owner/listings')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();

        $this->withSession(['active_interface' => 'owner']);
        $this->get('/owner/dashboard')->assertOk()
            ->assertSee('Owner dashboard')
            ->assertSee('Total earnings')
            ->assertSee('href="'.route('owner.listings.index').'"', false)
            ->assertDontSee('href="'.route('renter.rental-requests.index').'"', false)
            ->assertDontSee('href="'.route('listings.index').'"', false)
            ->assertDontSee('Recent rental requests');
        $this->get('/owner/listings')->assertOk();
        $this->get('/renter/dashboard')->assertForbidden();
        $this->get('/renter/rental-requests')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertSee('Owner dashboard');
    }

    public function test_interface_access_checks_persist_on_livewire_updates(): void
    {
        $member = User::factory()->create();
        $response = $this->actingAs($member)->withSession(['active_interface' => 'owner'])
            ->get('/owner/listings/create')->assertOk();

        preg_match_all('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        $snapshot = collect($matches[1])->map(fn ($value) => html_entity_decode($value, ENT_QUOTES))
            ->first(fn ($value) => json_decode($value, true)['memo']['name'] === 'owner.listings.form');

        $this->assertNotNull($snapshot);

        $this->withSession(['active_interface' => 'renter'])->postJson('/livewire/update', [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]],
            ]],
        ], ['X-Livewire' => ''])->assertForbidden();
    }

    public function test_admin_can_access_own_dashboard_but_not_member_areas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/owner/listings')->assertForbidden();
        $this->actingAs($admin)->get('/renter/rental-requests')->assertForbidden();
    }

    public function test_suspended_user_is_logged_out_and_blocked_on_next_request(): void
    {
        $renter = User::factory()->renter()->suspended()->create();

        $this->actingAs($renter)
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $renter = User::factory()->renter()->suspended()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $renter->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component->assertHasErrors();
        $this->assertGuest();
    }

    public function test_admin_can_suspend_and_reactivate_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $renter = User::factory()->renter()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('toggleSuspension', $renter->id);

        $this->assertTrue($renter->fresh()->isSuspended());

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('toggleSuspension', $renter->id);

        $this->assertFalse($renter->fresh()->isSuspended());
    }

    public function test_admin_cannot_suspend_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('toggleSuspension', $otherAdmin->id)
            ->assertForbidden();
    }

    public function test_registered_member_must_verify_email_before_entering_the_dashboard(): void
    {
        Notification::fake();

        $component = Volt::test('pages.auth.register')
            ->set('name', 'New Person')
            ->set('email', 'newsignup@gmail.com')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'NewPassword1!');

        $component->call('register');

        $component->assertHasNoErrors()->assertRedirect(route('verification.notice', absolute: false));

        $this->assertSame('member', auth()->user()->role->value);
        $this->get('/dashboard')->assertRedirect('/verify-email');
    }
}
