<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_valid_credentials_show_the_picker_without_logging_in(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('showInterfacePicker', true)
            ->assertSee('How would you like to use Lendly?')
            ->assertSee('Renter interface')
            ->assertSee('Owner interface');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/owner/listings')->assertRedirect('/login');
    }

    public function test_the_same_credentials_can_log_in_to_either_interface(): void
    {
        $user = User::factory()->create();

        foreach (['renter' => 'renter.dashboard', 'owner' => 'owner.dashboard'] as $interface => $destination) {
            $component = Volt::test('pages.auth.login')
                ->set('form.email', $user->email)
                ->set('form.password', 'password')
                ->call('login')
                ->assertNoRedirect();

            $this->assertGuest();

            $component->call('chooseInterface', $interface)
                ->assertHasNoErrors()
                ->assertRedirect(route($destination, absolute: false))
                ->assertSet('form.password', '');

            $this->assertAuthenticatedAs($user);
            $this->assertSame($interface, session('active_interface'));
            $this->get(route($destination))->assertOk();
            $this->assertSame('member', $user->fresh()->role->value);

            auth()->logout();
        }
    }

    public function test_chosen_interface_takes_priority_over_an_intended_page(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => route('listings.index')]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->call('chooseInterface', 'owner')
            ->assertRedirect(route('owner.dashboard', absolute: false))
            ->assertSessionMissing('url.intended');
    }

    public function test_cancelling_the_picker_keeps_the_user_logged_out_and_allows_retry(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->call('cancelInterfaceSelection')
            ->assertSet('showInterfacePicker', false)
            ->assertDontSee('How would you like to use Lendly?')
            ->assertNoRedirect()
            ->call('login')
            ->assertSet('showInterfacePicker', true);

        $this->assertGuest();
    }

    public function test_interface_selection_requires_the_credentials_step(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('chooseInterface', 'owner')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_invalid_interface_does_not_log_in(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->call('chooseInterface', 'admin')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_credentials_are_checked_again_when_an_interface_is_selected(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->set('form.password', 'wrong-password')
            ->call('chooseInterface', 'owner')
            ->assertHasErrors('form.email')
            ->assertSet('showInterfacePicker', false)
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_admin_login_goes_directly_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard', absolute: false))
            ->assertSet('showInterfacePicker', false);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('How would you like to use Lendly?');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect()
            ->assertSet('showInterfacePicker', false);

        $this->assertGuest();
    }

    public function test_unverified_member_can_log_back_in_to_the_saved_account(): void
    {
        $user = User::factory()->unverified()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->call('chooseInterface', 'renter')
            ->assertRedirect(route('renter.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route($user->dashboardRouteName()));

        $response
            ->assertOk()
            ->assertSeeVolt('layout.sidebar');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.sidebar');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
