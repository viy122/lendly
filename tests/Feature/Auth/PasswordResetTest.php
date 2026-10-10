<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_recovery_screens_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSeeVolt('pages.auth.forgot-password');
        $this->get('/reset-password')->assertOk()->assertSeeVolt('pages.auth.reset-password');
    }

    public function test_code_is_emailed_and_only_its_hash_is_stored(): void
    {
        $user = User::factory()->create(['email' => 'resetuser@gmail.com']);
        $code = $this->requestCode($user);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
        $token = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotSame($code, $token);
        $this->assertTrue(Hash::check($code, $token));
    }

    public function test_unknown_email_receives_the_same_response_without_a_notification(): void
    {
        Notification::fake();
        Volt::test('pages.auth.forgot-password')
            ->set('email', 'unknown@gmail.com')->call('sendPasswordResetCode')
            ->assertHasNoErrors()
            ->assertRedirect(route('password.reset', ['email' => 'unknown@gmail.com'], absolute: false));
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_requests_are_throttled_even_for_unknown_emails(): void
    {
        Notification::fake();
        $component = Volt::test('pages.auth.forgot-password')->set('email', 'unknown@gmail.com');
        $component->call('sendPasswordResetCode')->assertHasNoErrors();
        $component->call('sendPasswordResetCode')->assertHasErrors('email');
    }

    public function test_a_valid_code_changes_the_password_and_is_consumed(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $oldRememberToken = $user->remember_token;
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasNoErrors()->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasErrors('code');
    }

    public function test_a_code_expires_at_ten_minutes(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->travel(10)->minutes();
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_incorrect_code_does_not_change_password(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->resetComponent($user, $code === '000000' ? '111111' : '000000')
            ->call('resetPassword')->assertHasErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_three_wrong_codes_lock_login_and_reset_for_fifteen_minutes(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->resetComponent($user, $wrong)->call('resetPassword')->assertHasErrors('code');
        }
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasErrors('code');
        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email');
        $this->assertGuest();
        $this->travel(15)->minutes();
        $newCode = $this->requestCode($user);
        $this->resetComponent($user, $newCode)->call('resetPassword')->assertHasNoErrors();
    }

    public function test_resending_invalidates_the_previous_code_without_clearing_failures(): void
    {
        $user = User::factory()->create();
        $oldCode = $this->requestCode($user);
        $this->travel(61)->seconds();
        $newCode = $this->requestCode($user);
        $this->resetComponent($user, $oldCode)->call('resetPassword')->assertHasErrors('code');
        $this->resetComponent($user, $oldCode)->call('resetPassword')->assertHasErrors('code');
        $this->travel(61)->seconds();
        $latestCode = $this->requestCode($user);
        $this->resetComponent($user, $newCode)->call('resetPassword')->assertHasErrors('code');
        $this->resetComponent($user, $latestCode)->call('resetPassword')->assertHasErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_failures_lock_with_database_cache_after_transactions_commit(): void
    {
        Cache::setDefaultDriver('database');
        $this->app->forgetInstance(RateLimiter::class);
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->resetComponent($user, $wrong)->call('resetPassword')->assertHasErrors('code');
        }

        $this->resetComponent($user, $code)->call('resetPassword')->assertHasErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'password')
            ->call('login')->assertHasErrors('form.email');
        $this->assertGuest();
    }

    public function test_weak_or_unconfirmed_new_password_does_not_consume_code(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);
        $this->resetComponent($user, $code)->set('password', 'weakpass')->set('password_confirmation', 'weakpass')
            ->call('resetPassword')->assertHasErrors('password');
        $this->resetComponent($user, $code)->set('password_confirmation', 'Different1!')
            ->call('resetPassword')->assertHasErrors('password');
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasNoErrors();
    }

    public function test_gmail_alias_can_recover_the_same_account(): void
    {
        $user = User::factory()->create(['email' => 'resetuser@gmail.com']);
        Notification::fake();
        Volt::test('pages.auth.forgot-password')->set('email', ' Reset.User+tag@GMAIL.COM ')
            ->call('sendPasswordResetCode')->assertHasNoErrors();
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;
        $this->resetComponent($user, $code)->set('email', 'RESET.USER+test@gmail.com')
            ->call('resetPassword')->assertHasNoErrors();
    }

    public function test_mail_transport_failure_is_reported_and_does_not_leave_a_new_code(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        Volt::test('pages.auth.forgot-password')->set('email', $user->email)
            ->call('sendPasswordResetCode')->assertHasErrors('email');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_suspended_account_remains_suspended_after_password_recovery(): void
    {
        $user = User::factory()->suspended()->create();
        $code = $this->requestCode($user);
        $this->resetComponent($user, $code)->call('resetPassword')->assertHasNoErrors();
        $this->assertTrue($user->fresh()->isSuspended());
        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'NewPassword1!')
            ->call('login')->assertHasErrors('form.email');
        $this->assertGuest();
    }

    private function requestCode(User $user): string
    {
        Notification::fake();
        Volt::test('pages.auth.forgot-password')->set('email', $user->email)
            ->call('sendPasswordResetCode')->assertHasNoErrors();
        Notification::assertSentTo($user, PasswordResetCode::class);

        return Notification::sent($user, PasswordResetCode::class)->first()->code;
    }

    private function resetComponent(User $user, string $code)
    {
        return Volt::test('pages.auth.reset-password')
            ->set('email', $user->email)->set('code', $code)
            ->set('password', 'NewPassword1!')->set('password_confirmation', 'NewPassword1!');
    }
}
