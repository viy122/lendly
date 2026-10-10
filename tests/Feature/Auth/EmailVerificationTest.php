<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response
            ->assertSeeVolt('pages.auth.verify-email')
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_is_redirected_away_from_the_dashboard(): void
    {
        $user = User::factory()->renter()->unverified()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/verify-email');
    }

    public function test_verification_email_can_be_resent_but_is_rate_limited_across_both_forms(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'testuser@gmail.com']);

        Volt::actingAs($user)->test('pages.auth.verify-email')
            ->call('sendVerification')
            ->assertHasNoErrors();

        Volt::actingAs($user)->test('profile.update-profile-information-form')
            ->call('sendVerification')
            ->assertHasErrors('verification');

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);

        $this->travel(61)->seconds();

        Volt::actingAs($user)->test('pages.auth.verify-email')
            ->call('sendVerification')
            ->assertHasNoErrors();

        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_resend_failure_keeps_the_user_signed_in_and_shows_retry_feedback(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)->test('pages.auth.verify-email')
            ->call('sendVerification')
            ->assertHasErrors('verification')
            ->assertNoRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_profile_resend_failure_shows_retry_feedback(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)->test('profile.update-profile-information-form')
            ->call('sendVerification')
            ->assertHasErrors('verification')
            ->assertNoRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
