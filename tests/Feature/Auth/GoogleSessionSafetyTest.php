<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleSessionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/session-safety/login', function (Request $request) {
            abort_unless(Auth::guard('web')->attempt($request->only('email', 'password')), 422);

            return response()->noContent();
        });
    }

    public function test_password_login_stamps_a_credential_hmac_before_google_linking(): void
    {
        $user = User::factory()->create();

        $this->post('/session-safety/login', ['email' => $user->email, 'password' => 'password'])
            ->assertNoContent()
            ->assertSessionHas('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()));

        $this->assertNotSame($user->getAuthPassword(), session('password_hash_web'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', session('password_hash_web'));
    }

    public function test_non_web_login_does_not_stamp_the_web_session(): void
    {
        $user = User::factory()->create();
        Route::middleware('web')->post('/session-safety/other-login', function () use ($user) {
            Event::dispatch(new Login('other', $user, false));

            return response()->noContent();
        });

        $this->post('/session-safety/other-login')->assertNoContent()->assertSessionMissing('password_hash_web');
    }

    public function test_login_events_without_a_request_session_are_ignored(): void
    {
        Event::dispatch(new Login('web', User::factory()->create(), false));

        $this->assertFalse(request()->hasSession());
        $this->assertNull(session('password_hash_web'));
    }

    public function test_google_linked_session_with_the_current_credential_snapshot_can_continue(): void
    {
        $user = User::factory()->create(['google_id' => 'verified-google-user']);

        $this->withSession([
            Auth::guard('web')->getName() => $user->id,
            'password_hash_web' => Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()),
        ])->get(route('profile'))->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('invalidSnapshots')]
    public function test_google_linked_session_without_a_current_snapshot_is_rejected(mixed $snapshot): void
    {
        $user = User::factory()->create(['google_id' => 'verified-google-user']);
        $rememberToken = $user->remember_token;

        $this->withSession([
            Auth::guard('web')->getName() => $user->id,
            'password_hash_web' => $snapshot,
            '_token' => 'old-csrf-token',
            'old-session-data' => 'discard',
        ])->get(route('profile'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('old-session-data')
            ->assertSessionMissing('password_hash_web');

        $this->assertGuest();
        $this->assertNotSame('old-csrf-token', session()->token());
        $this->assertSame($rememberToken, $user->fresh()->remember_token);
    }

    public static function invalidSnapshots(): array
    {
        return [
            'missing' => [null],
            'outdated' => [str_repeat('0', 64)],
            'malformed' => [['not-a-string']],
        ];
    }

    #[DataProvider('sessionDrivers')]
    public function test_existing_password_session_cannot_survive_google_confirmation(string $driver): void
    {
        config(['session.driver' => $driver]);
        $user = User::factory()->unverified()->create();
        $login = $this->post('/session-safety/login', ['email' => $user->email, 'password' => 'password'])
            ->assertNoContent();
        $user->forceFill([
            'google_id' => 'verified-google-user',
            'email_verified_at' => now(),
            'password' => 'new-secret-after-confirmation',
        ])->save();

        $this->resumeSession($login);

        $this->get(route('profile'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();

        if ($driver === 'file') {
            session()->getHandler()->destroy(session()->getId());
        }
    }

    public static function sessionDrivers(): array
    {
        return [['array'], ['file'], ['database'], ['cookie']];
    }

    public function test_in_flight_old_password_login_cannot_stamp_the_new_credentials(): void
    {
        $user = User::factory()->unverified()->create();
        $staleUser = $user->fresh();
        Route::middleware('web')->post('/session-safety/in-flight-login', function () use ($staleUser) {
            Auth::guard('web')->login($staleUser);

            return response()->noContent();
        });
        $user->forceFill([
            'google_id' => 'verified-google-user',
            'email_verified_at' => now(),
            'password' => 'new-secret-after-confirmation',
        ])->save();

        $login = $this->post('/session-safety/in-flight-login')->assertNoContent()
            ->assertSessionHas('password_hash_web', Auth::guard('web')->hashPasswordForCookie($staleUser->getAuthPassword()));
        $this->resumeSession($login);

        $this->get(route('profile'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_livewire_update_rejects_the_session_after_google_confirmation(): void
    {
        $user = User::factory()->create();
        $this->post('/session-safety/login', ['email' => $user->email, 'password' => 'password'])->assertNoContent();
        $page = $this->get(route('profile'))->assertOk();
        preg_match_all('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);
        $snapshot = collect($matches[1])->map(fn ($value) => html_entity_decode($value, ENT_QUOTES))
            ->first(fn ($value) => json_decode($value, true)['memo']['name'] === 'profile.update-profile-information-form');
        $this->assertNotNull($snapshot);
        $user->forceFill(['google_id' => 'verified-google-user', 'password' => 'new-secret-after-confirmation'])->save();
        $this->resumeSession($page);

        $this->withCredentials()->postJson('/livewire/update', ['components' => [[
            'snapshot' => $snapshot,
            'updates' => ['name' => 'Attacker Changed This'],
            'calls' => [['path' => '', 'method' => 'updateProfileInformation', 'params' => []]],
        ]]], ['X-Livewire' => ''])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_legacy_non_google_session_does_not_require_a_credential_snapshot(): void
    {
        $user = User::factory()->create();

        $this->withSession([Auth::guard('web')->getName() => $user->id])
            ->get(route('profile'))->assertOk()->assertSessionMissing('password_hash_web');

        $this->assertAuthenticatedAs($user);
    }

    private function resumeSession(TestResponse $response): void
    {
        $this->withUnencryptedCookies(collect($response->headers->getCookies())
            ->mapWithKeys(fn ($cookie) => [$cookie->getName() => $cookie->getValue()])->all());
        session()->flush();
        Auth::forgetGuards();
    }
}
