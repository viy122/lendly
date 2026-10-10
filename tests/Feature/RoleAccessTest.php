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

    public function test_any_member_can_access_the_unified_dashboard_and_both_owner_and_renter_areas(): void
    {
        $member = User::factory()->renter()->create();

        $this->actingAs($member)->get('/dashboard')->assertOk();
        $this->actingAs($member)->get('/owner/listings')->assertOk();
        $this->actingAs($member)->get('/renter/rental-requests')->assertOk();
        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
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
