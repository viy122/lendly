<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Auth\Events\Validated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lock_triggered_during_validation_prevents_confirmation(): void
    {
        $user = User::factory()->create();
        AccountLockout::recordFailure($user->email, 'password');
        AccountLockout::recordFailure($user->email, 'password');
        Event::listen(Validated::class, function () use ($user) {
            try {
                AccountLockout::recordFailure($user->email, 'password');
            } catch (ValidationException) {
            }
        });

        Volt::actingAs($user)->test('pages.auth.confirm-password')->set('password', 'password')
            ->call('confirmPassword')->assertHasErrors('password')->assertNoRedirect();
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response
            ->assertSeeVolt('pages.auth.confirm-password')
            ->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'password');

        $component->call('confirmPassword');

        $component
            ->assertRedirect('/dashboard')
            ->assertHasNoErrors();
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password');

        $component->call('confirmPassword');

        $component
            ->assertNoRedirect()
            ->assertHasErrors('password');
    }

    public function test_three_wrong_confirmations_lock_confirmation_and_login(): void
    {
        $user = User::factory()->create();
        $this->freezeTime();
        $this->actingAs($user);

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password');

        $component->call('confirmPassword');
        $component->call('confirmPassword');
        $component->call('confirmPassword')->assertHasErrors('password')->assertSee('900 seconds');
        $component->set('password', 'password')
            ->call('confirmPassword')->assertHasErrors('password')->assertNoRedirect();
        $this->assertNull(session('auth.password_confirmed_at'));

        auth()->logout();
        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_login_and_confirmation_share_the_wrong_password_counter(): void
    {
        $user = User::factory()->create();
        $this->freezeTime();
        $login = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');
        $login->call('login');
        $login->call('login');

        $this->actingAs($user);
        Volt::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password')
            ->call('confirmPassword')->assertHasErrors('password')->assertSee('900 seconds');
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_successful_confirmation_clears_prior_wrong_passwords(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Volt::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password');
        $component->call('confirmPassword');
        $component->call('confirmPassword');
        $component->set('password', 'password')->call('confirmPassword')->assertHasNoErrors();

        auth()->logout();
        $login = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');
        $login->call('login');
        $login->call('login');
        $login->set('form.password', 'password')->call('login')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }
}
