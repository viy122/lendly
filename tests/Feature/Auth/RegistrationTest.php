<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AuthEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', ' Test.User+rent@GMAIL.COM ')
            ->set('password', 'LendlyPass123!')
            ->set('password_confirmation', 'LendlyPass123!');

        $component->call('register');

        $component->assertHasNoErrors()->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame('member', auth()->user()->role->value);
        $this->assertSame('testuser@gmail.com', auth()->user()->email);
        $this->assertFalse(auth()->user()->hasVerifiedEmail());
        Notification::assertSentTo(auth()->user(), VerifyEmail::class);
    }

    #[DataProvider('invalidEmailAddresses')]
    public function test_signup_rejects_invalid_or_non_gmail_addresses(string $email): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', $email)
            ->set('password', 'LendlyPass123!')
            ->set('password_confirmation', 'LendlyPass123!')
            ->call('register')
            ->assertHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public static function invalidEmailAddresses(): array
    {
        return [
            ['testuser@example.com'],
            ['testuser@googlemail.com'],
            ['testuser@gmail.com.example.com'],
            ['testuser@@gmail.com'],
            ['.testuser@gmail.com'],
            ['testuser.@gmail.com'],
            ['test..user@gmail.com'],
            ['test_user@gmail.com'],
            ['test user@gmail.com'],
        ];
    }

    #[DataProvider('existingGmailAddresses')]
    public function test_signup_rejects_another_alias_of_an_existing_gmail_account(string $existing): void
    {
        User::factory()->create(['email' => $existing]);

        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test.user+another@gmail.com')
            ->set('password', 'LendlyPass123!')
            ->set('password_confirmation', 'LendlyPass123!')
            ->call('register')
            ->assertHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public static function existingGmailAddresses(): array
    {
        return [
            ['testuser@gmail.com'],
            ['Test.User+legacy@GMAIL.COM'],
            ['Test.User+legacy@GOOGLEMAIL.COM'],
        ];
    }

    public function test_account_lookup_preserves_distinct_legacy_gmail_alias_accounts(): void
    {
        $canonical = User::factory()->create(['email' => 'testuser@gmail.com']);
        $legacy = User::factory()->create(['email' => 'Test.User+legacy@GMAIL.COM']);

        $this->assertSame($legacy->id, AuthEmail::findUser(' Test.User+legacy@GMAIL.COM ')?->id);
        $this->assertSame($canonical->id, AuthEmail::findUser('testuser@gmail.com')?->id);
        $this->assertTrue(AuthEmail::isTaken('test.user+new@gmail.com', $canonical->id));
    }

    public function test_registration_survives_a_verification_email_transport_failure(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));

        Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'testuser@gmail.com')
            ->set('password', 'LendlyPass123!')
            ->set('password_confirmation', 'LendlyPass123!')
            ->call('register')
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
        $this->assertFalse(auth()->user()->hasVerifiedEmail());
        $this->assertSame('verification-mail-failed', session('status'));
    }

    public function test_registration_attempts_are_rate_limited(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'invalid@example.com')
            ->set('password', 'LendlyPass123!')
            ->set('password_confirmation', 'LendlyPass123!');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('register')->assertHasErrors('email');
        }

        Notification::fake();

        $component->set('email', 'testuser@gmail.com')->call('register')->assertHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
