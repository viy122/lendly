<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('accountSwitchAttempts')]
    public function test_passwordless_account_switching_is_rejected_in_every_environment(string $environment, bool $administrator): void
    {
        $this->app['env'] = $environment;
        User::factory()->admin()->create(['email' => 'admin@tala.test']);
        User::factory()->create(['email' => 'owner@tala.test']);
        $currentUser = $administrator ? User::factory()->admin()->create() : User::factory()->create();
        $this->actingAs($currentUser)->withSession(['active_interface' => 'renter']);

        Volt::test('layout.sidebar')
            ->assertDontSee('Demo: switch user')
            ->assertDontSee('wire:click="switchDemoUser', false);

        foreach (['admin', 'owner', 'renter', 'unknown'] as $target) {
            Volt::test('layout.sidebar')->call('switchDemoUser', $target)->assertForbidden()->assertNoRedirect();
            $this->assertAuthenticatedAs($currentUser);
            $this->assertSame('renter', session('active_interface'));
        }
    }

    public static function accountSwitchAttempts(): array
    {
        return [
            'local member' => ['local', false],
            'local admin' => ['local', true],
            'testing member' => ['testing', false],
            'testing admin' => ['testing', true],
            'production member' => ['production', false],
            'production admin' => ['production', true],
        ];
    }

    public function test_forged_livewire_switch_call_cannot_promote_a_member_to_seeded_admin(): void
    {
        $this->app['env'] = 'local';
        $member = User::factory()->create();
        User::factory()->admin()->create(['email' => 'admin@tala.test']);
        $response = $this->actingAs($member)->withSession(['active_interface' => 'owner'])
            ->get(route('owner.dashboard'))->assertOk();
        $snapshot = $this->snapshotFrom($response->getContent(), 'layout.sidebar');

        $this->postJson('/livewire/update', $this->callPayload($snapshot, 'switchDemoUser', ['admin']), [
            'X-Livewire' => '', 'X-CSRF-TOKEN' => session()->token(),
        ])
            ->assertForbidden();

        $this->assertAuthenticatedAs($member);
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_login_requires_the_correct_password_and_rotates_the_session(): void
    {
        $admin = User::factory()->admin()->create();
        $this->withSession(['pre-login-marker' => true]);
        $sessionId = session()->getId();

        $login = Volt::test('pages.auth.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'incorrect-password')
            ->call('login')
            ->assertHasErrors('form.email')
            ->assertSet('showInterfacePicker', false)
            ->assertNoRedirect();
        $this->assertGuest();

        $login->set('form.password', 'password')->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard', absolute: false))
            ->assertSet('form.password', '')
            ->assertSet('showInterfacePicker', false);

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($sessionId, session()->getId());
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_login_is_rate_limited_after_repeated_invalid_credentials(): void
    {
        $admin = User::factory()->admin()->create();
        $login = Volt::test('pages.auth.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'incorrect-password');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $login->call('login')->assertHasErrors('form.email')->assertNoRedirect();
        }

        $login->set('form.password', 'password')->call('login')
            ->assertHasErrors('form.email')
            ->assertSee('Too many login attempts')
            ->assertNoRedirect();
        $this->assertGuest();

        RateLimiter::clear(strtolower($admin->email).'|127.0.0.1');
        $login->call('login')->assertHasNoErrors()->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_logout_clears_private_session_data_and_revokes_remembered_login(): void
    {
        $admin = User::factory()->admin()->create();
        Volt::test('pages.auth.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password')
            ->set('form.remember', true)
            ->call('login')->assertHasNoErrors();
        $this->assertAuthenticatedAs($admin);
        session()->put('private-admin-marker', 'private data');
        session()->put('active_interface', 'owner');
        $sessionId = session()->getId();
        $csrfToken = session()->token();
        $rememberToken = $admin->fresh()->remember_token;

        Volt::test('layout.sidebar')->call('logout')
            ->assertHasNoErrors()->assertRedirect('/')
            ->assertSessionMissing('private-admin-marker')
            ->assertSessionMissing('active_interface');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrfToken, session()->token());
        $this->assertNotSame($rememberToken, $admin->fresh()->remember_token);
        foreach ($this->adminCollectionRoutes() as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_valid_member_credentials_never_grant_admin_route_access(): void
    {
        $member = User::factory()->create();
        Volt::test('pages.auth.login')
            ->set('form.email', $member->email)->set('form.password', 'password')
            ->call('login')->call('chooseInterface', 'owner')->assertHasNoErrors();

        foreach ($this->adminCollectionRoutes() as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->assertAuthenticatedAs($member);
    }

    public function test_admin_livewire_updates_require_admin_authentication_after_logout_or_account_change(): void
    {
        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $snapshot = $this->snapshotFrom($response->getContent(), 'admin.dashboard');
        auth()->logout();

        $this->postJson('/livewire/update', $this->callPayload($snapshot, '$refresh'), ['X-Livewire' => ''])
            ->assertUnauthorized();

        $member = User::factory()->create();
        $this->actingAs($member);
        $this->postJson('/livewire/update', $this->callPayload($snapshot, '$refresh'), ['X-Livewire' => ''])
            ->assertForbidden();
        $this->assertAuthenticatedAs($member);
    }

    private function adminCollectionRoutes(): array
    {
        return ['admin.dashboard', 'admin.listings.index', 'admin.rentals.index', 'admin.damage-reports.index', 'admin.disputes.index'];
    }

    private function snapshotFrom(string $html, string $component): string
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = collect($matches[1])
            ->map(fn ($value) => html_entity_decode($value, ENT_QUOTES))
            ->first(fn ($value) => json_decode($value, true)['memo']['name'] === $component);
        $this->assertNotNull($snapshot);

        return $snapshot;
    }

    private function callPayload(string $snapshot, string $method, array $parameters = []): array
    {
        return ['components' => [[
            'snapshot' => $snapshot, 'updates' => [],
            'calls' => [['path' => '', 'method' => $method, 'params' => $parameters]],
        ]]];
    }
}
