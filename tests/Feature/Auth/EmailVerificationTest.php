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

    public function test_member_can_verify_later_and_access_account_pages_without_being_marked_verified(): void
    {
        $user = User::factory()->renter()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')
            ->assertOk()
            ->assertSee('Verify later')
            ->assertSee('href="'.route('dashboard').'"', false);

        foreach (['/dashboard', '/profile', '/owner/listings', '/owner/listings/create', '/owner/rental-requests', '/owner/rentals', '/renter/rental-requests', '/renter/rentals', '/messages', '/notifications'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/dashboard')->assertSee('Your email is still unverified.');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_member_can_save_profile_data(): void
    {
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)->test('profile.update-profile-information-form')
            ->set('phone', '09171234567')
            ->set('address', '123 Rizal St, Manila')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '09171234567',
            'address' => '123 Rizal St, Manila',
            'email_verified_at' => null,
        ]);
    }

    public function test_member_can_resend_verification_after_continuing_to_their_account(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        Volt::test('pages.auth.verify-email')
            ->call('sendVerification')
            ->assertHasNoErrors()
            ->assertSee('A new verification link has been sent');

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_member_does_not_see_verification_reminder(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()->assertDontSee('Your email is still unverified.');
    }

    public function test_admin_still_needs_email_verification(): void
    {
        $user = User::factory()->admin()->unverified()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertRedirect('/verify-email');
        $this->get('/verify-email')->assertDontSee('Verify later');
    }

    public function test_guest_cannot_access_account_pages(): void
    {
        foreach (['/dashboard', '/owner/listings', '/renter/rental-requests', '/messages', '/notifications', '/verify-email'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }
}
