<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccountLockout;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private MockHandler $google;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google' => ['client_id' => 'synthetic-client', 'client_secret' => 'synthetic-secret', 'redirect' => 'http://localhost/auth/google/callback']]);
        $this->google = new MockHandler;
        Socialite::extend('google', function ($app) {
            return (new GoogleProvider($app['request'], 'synthetic-client', 'synthetic-secret', 'http://localhost/auth/google/callback'))
                ->setHttpClient(new Client(['handler' => HandlerStack::create($this->google)]));
        });
    }

    public function test_redirect_stores_interface_rotates_oauth_state_and_uses_google_account_picker(): void
    {
        $this->withSession(['state' => 'prior-state', 'google_oauth_interface' => 'renter']);
        $response = $this->get(route('auth.google.redirect', ['interface' => 'owner']));
        $response->assertRedirect()->assertSessionHas('google_oauth_interface', 'owner');
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertNotSame('prior-state', $query['state']);
        $this->assertSame(session('state'), $query['state']);
        $this->assertSame('select_account', $query['prompt']);
        foreach (['openid', 'profile', 'email'] as $scope) {
            $this->assertContains($scope, explode(' ', $query['scope']));
        }
        $this->assertGuest();
    }

    public function test_invalid_interface_is_rejected_and_default_interface_is_renter(): void
    {
        $this->get(route('auth.google.redirect', ['interface' => 'admin']))->assertForbidden();
        $this->get(route('auth.google.redirect'))->assertRedirect()->assertSessionHas('google_oauth_interface', 'renter');
        $this->assertGuest();
    }

    public function test_new_verified_gmail_member_is_created_and_sent_to_identity_after_selected_interface(): void
    {
        $state = $this->begin('owner');
        $session = session()->getId();
        $this->withSession(['url.intended' => '/admin/dashboard']);
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('identity.verify'))
            ->assertSessionHas('active_interface', 'owner')->assertSessionMissing('url.intended')
            ->assertSessionMissing('google_oauth_interface')->assertSessionMissing('state');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('testmember@gmail.com', $user->email);
        $this->assertSame('Google Member', $user->name);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('member', $user->role->value);
        $this->assertStringStartsWith('$2y$', $user->getRawOriginal('password'));
        $this->assertNotSame($session, session()->getId());
        $this->assertFalse($user->hasVerifiedIdentity());
        $this->assertNull($user->identity_reference);
        $this->assertArrayNotHasKey('google_id', $user->toArray());
        $this->assertStringNotContainsString('synthetic-google-token', json_encode($user->getAttributes()));
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_verified_existing_gmail_alias_links_without_changing_profile_password_or_identity(): void
    {
        $user = User::factory()->create(['email' => 'Test.Member+legacy@GMAIL.COM', 'name' => 'Existing Member']);
        $user->forceFill(['identity_verified_at' => now(), 'identity_reference' => 'PRIVATE-REFERENCE', 'identity_verification_environment' => 'production'])->save();
        $before = $user->getAttributes();
        $state = $this->begin('owner');
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        foreach (['name', 'email', 'password', 'identity_verified_at', 'identity_reference', 'identity_verification_environment'] as $field) {
            $this->assertSame($before[$field], $user->fresh()->getAttributes()[$field]);
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_returning_google_member_uses_selected_interface_and_keeps_saved_profile(): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com', 'name' => 'Saved Name']);
        $user->forceFill(['google_id' => 'google-123', 'identity_verified_at' => now(), 'identity_verification_environment' => 'production'])->save();
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('renter.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Saved Name', $user->fresh()->name);
    }

    #[DataProvider('invalidProfiles')]
    public function test_unverified_non_gmail_and_malformed_remote_profiles_cannot_authenticate(array $changes): void
    {
        $state = $this->begin();
        $this->profile($changes);

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google')
            ->assertSessionMissing('state')->assertSessionMissing('google_oauth_interface');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidProfiles(): array
    {
        return [
            [['email_verified' => false]], [['email_verified' => 'true']], [['email_verified' => 1]],
            [['email_verified' => null]], [['email' => 'member@example.com']], [['email' => 'bad@gmail.com.evil.test']],
            [['email' => 'invalid @gmail.com']], [['email' => null]], [['email' => ['testmember@gmail.com']]],
            [['sub' => '']], [['sub' => str_repeat('g', 256)]], [['sub' => ['google-123']]],
            [['name' => str_repeat('n', 256)]], [['name' => ['Google Member']]],
        ];
    }

    #[DataProvider('blockedAccounts')]
    public function test_unverified_suspended_admin_and_conflicting_existing_accounts_cannot_be_merged(string $kind): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com']);
        match ($kind) {
            'unverified' => $user->forceFill(['email_verified_at' => null])->save(),
            'suspended' => $user->update(['status' => 'suspended']),
            'admin' => $user->update(['role' => 'admin']),
            'different Google identity' => $user->forceFill(['google_id' => 'other-google-id'])->save(),
        };
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        if ($kind === 'unverified') {
            $this->assertNull($user->fresh()->email_verified_at);
            $this->assertNull($user->fresh()->google_id);
        }
        $this->assertDatabaseCount('users', 1);
    }

    public static function blockedAccounts(): array
    {
        return array_map(fn ($kind) => [$kind], ['unverified', 'suspended', 'admin', 'different Google identity']);
    }

    public function test_google_identity_cannot_authenticate_an_account_whose_email_changed(): void
    {
        $user = User::factory()->create(['email' => 'othermember@gmail.com']);
        $user->forceFill(['google_id' => 'google-123'])->save();
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame('othermember@gmail.com', $user->fresh()->email);
    }

    public function test_closed_google_account_creates_a_fresh_member_without_restoring_the_old_account(): void
    {
        $closed = User::factory()->create(['email' => 'testmember@gmail.com']);
        $closed->forceFill(['google_id' => 'google-123'])->save();
        $closed->delete();
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('identity.verify'));

        $this->assertAuthenticated();
        $this->assertNotSame($closed->id, auth()->id());
        $this->assertSoftDeleted($closed);
        $this->assertNull(User::withTrashed()->findOrFail($closed->id)->google_id);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_unique_google_identity_race_fails_closed_without_partial_link(): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com']);
        $other = User::factory()->create(['email' => 'othermember@gmail.com']);
        $event = 'eloquent.updating: '.User::class;
        Event::listen($event, function (User $candidate) use ($user, $other) {
            if ($candidate->id === $user->id && $candidate->isDirty('google_id')) {
                User::whereKey($other->id)->update(['google_id' => 'google-123']);
            }
        });
        $state = $this->begin();
        $this->profile();

        try {
            $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');
        } finally {
            Event::forget($event);
        }
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertNull($other->fresh()->google_id);
    }

    public function test_ambiguous_canonical_gmail_accounts_fail_closed(): void
    {
        User::factory()->create(['email' => 'testmember@gmail.com']);
        User::factory()->create(['email' => 'Test.Member+legacy@gmail.com']);
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame(0, User::whereNotNull('google_id')->count());
    }

    public function test_existing_account_lockout_cannot_be_bypassed_with_google(): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com']);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                AccountLockout::recordFailure($user->email, 'email');
            } catch (ValidationException) {
            }
        }
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_suspension_after_remote_profile_retrieval_is_rechecked(): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com']);
        $state = $this->begin();
        $this->profile();
        Event::listen('eloquent.retrieved: '.User::class, function (User $candidate) use ($user) {
            if ($candidate->id === $user->id) {
                User::whereKey($user->id)->update(['status' => 'suspended']);
            }
        });

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_lockout_triggered_during_google_login_prevents_an_authenticated_session(): void
    {
        $user = User::factory()->create(['email' => 'testmember@gmail.com']);
        AccountLockout::recordFailure($user->email, 'google');
        AccountLockout::recordFailure($user->email, 'google');
        Event::listen(Login::class, function () use ($user) {
            try {
                AccountLockout::recordFailure($user->email, 'google');
            } catch (ValidationException) {
            }
        });
        $state = $this->begin();
        $this->profile();

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_callback_requires_the_saved_interface_even_when_google_state_is_valid(): void
    {
        $state = $this->begin();
        session()->forget('google_oauth_interface');

        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google')
            ->assertSessionMissing('state');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cancel_invalid_state_and_replay_leave_no_authenticated_session(): void
    {
        $state = $this->begin();
        $this->get(route('auth.google.callback', ['error' => 'access_denied', 'state' => $state]))
            ->assertRedirect(route('login'))->assertSessionHasErrors('google')->assertSessionMissing('state');
        $state = $this->begin();
        $this->finishOAuth('wrong-state')->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $state = $this->begin();
        $this->profile();
        $this->finishOAuth($state)->assertRedirect(route('identity.verify'));
        auth()->logout();
        $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_token_failure_has_a_generic_error_without_provider_body(): void
    {
        $state = $this->begin();
        $this->google->append(new Response(400, [], '{"error":"sensitive-token-payload"}'));

        $response = $this->finishOAuth($state)->assertRedirect(route('login'))->assertSessionHasErrors('google');

        $this->assertStringNotContainsString('sensitive-token-payload', json_encode(session('errors')->all()));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_missing_configuration_fails_gracefully_and_clears_prior_oauth_state(): void
    {
        config(['services.google.client_secret' => null]);

        $this->withSession(['state' => 'old-state', 'google_oauth_interface' => 'owner'])
            ->get(route('auth.google.redirect'))->assertRedirect(route('login'))->assertSessionHasErrors('google')
            ->assertSessionMissing('state')->assertSessionMissing('google_oauth_interface');

        $this->assertGuest();
    }

    private function begin(string $interface = 'renter'): string
    {
        Socialite::forgetDrivers();
        $response = $this->get(route('auth.google.redirect', ['interface' => $interface]))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        return $query['state'];
    }

    private function finishOAuth(string $state)
    {
        Socialite::forgetDrivers();

        return $this->get(route('auth.google.callback', ['state' => $state, 'code' => 'synthetic-code']));
    }

    private function profile(array $changes = []): void
    {
        $this->google->append(
            new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"synthetic-google-token","token_type":"Bearer","expires_in":3600}'),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(array_replace([
                'sub' => 'google-123', 'name' => 'Google Member', 'email' => 'Test.Member+oauth@gmail.com',
                'email_verified' => true, 'picture' => 'https://example.test/avatar.png',
            ], $changes))),
        );
    }
}
