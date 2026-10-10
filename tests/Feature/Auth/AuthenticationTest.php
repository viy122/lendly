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

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route($user->dashboardRouteName(), absolute: false));

        $this->assertAuthenticated();
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
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_a_lock_triggered_during_password_validation_prevents_login(): void
    {
        $user = User::factory()->create();
        AccountLockout::recordFailure($user->email, 'form.email');
        AccountLockout::recordFailure($user->email, 'form.email');
        Event::listen(Validated::class, function () use ($user) {
            try {
                AccountLockout::recordFailure($user->email, 'form.email');
            } catch (ValidationException) {
            }
        });

        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email');
        $this->assertGuest();
        $this->expectException(ValidationException::class);
        AccountLockout::ensureNotLocked($user->email, 'form.email');
    }

    public function test_legacy_gmail_account_can_log_in_using_a_normalized_alias(): void
    {
        $user = User::factory()->create(['email' => 'Legacy.Person+old@gmail.com']);

        Volt::test('pages.auth.login')
            ->set('form.email', ' legacy.person+new@GMAIL.COM ')
            ->set('form.password', 'password')
            ->call('login')->assertHasNoErrors()->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_third_wrong_password_locks_login_for_fifteen_minutes_from_that_attempt(): void
    {
        $user = User::factory()->create();
        $this->freezeTime();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login')->assertHasErrors('form.email');
        $component->call('login')->assertHasErrors('form.email');
        $this->travel(10)->minutes();
        $component->call('login')
            ->assertHasErrors('form.email')
            ->assertSee('900 seconds');

        $component->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email')->assertNoRedirect();
        $this->assertGuest();

        $this->travel(899)->seconds();
        $component->call('login')->assertHasErrors('form.email')->assertNoRedirect();
        $this->travel(1)->seconds();
        $component->call('login')->assertHasNoErrors()->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_changing_ip_cannot_bypass_the_account_lock(): void
    {
        $user = User::factory()->create();
        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $component->call('login');
        }

        request()->server->set('REMOTE_ADDR', '203.0.113.25');
        $this->assertSame('203.0.113.25', request()->ip());
        $form = $component->instance()->form;
        $form->password = 'password';

        try {
            $form->authenticate();
            $this->fail('A locked account authenticated from another IP.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.email', $exception->errors());
        }

        $this->assertGuest();
    }

    public function test_gmail_aliases_share_the_account_lock(): void
    {
        $user = User::factory()->create(['email' => 'lockoutperson@gmail.com']);
        $component = Volt::test('pages.auth.login')
            ->set('form.password', 'wrong-password');

        foreach (['lockoutperson@gmail.com', 'Lockout.Person@gmail.com', 'lockoutperson+lendly@gmail.com'] as $email) {
            $component->set('form.email', $email)->call('login');
        }

        $component->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_successful_login_clears_prior_wrong_passwords(): void
    {
        $user = User::factory()->create();
        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');
        $component->call('login');
        $component->set('form.password', 'password')->call('login')->assertHasNoErrors();
        auth()->logout();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');
        $component->call('login');
        $component->call('login');
        $component->set('form.password', 'password')->call('login')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_locking_one_account_does_not_block_another_account(): void
    {
        $lockedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $component = Volt::test('pages.auth.login')
            ->set('form.email', $lockedUser->email)
            ->set('form.password', 'wrong-password');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $component->call('login');
        }

        Volt::test('pages.auth.login')
            ->set('form.email', $otherUser->email)
            ->set('form.password', 'password')
            ->call('login')->assertHasNoErrors();
        $this->assertAuthenticatedAs($otherUser);
    }

    public function test_unknown_email_has_the_same_lockout_response(): void
    {
        $this->freezeTime();
        $component = Volt::test('pages.auth.login')
            ->set('form.email', 'unknown@example.com')
            ->set('form.password', 'wrong-password');

        $component->call('login');
        $component->call('login');
        $component->call('login')->assertHasErrors('form.email')->assertSee('900 seconds');
        $this->assertGuest();
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
