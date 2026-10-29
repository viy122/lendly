<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GoogleSignInUiTest extends TestCase
{
    use RefreshDatabase;

    private function configureGoogle(): void
    {
        config(['services.google' => [
            'client_id' => 'synthetic-google-client', 'client_secret' => 'synthetic-google-secret',
            'redirect' => 'https://lendly.example.test/auth/google/callback',
        ]]);
    }

    public function test_google_is_first_but_disabled_when_configuration_is_missing_or_incomplete(): void
    {
        foreach ([null, 'client_id', 'client_secret', 'redirect'] as $missing) {
            $this->configureGoogle();
            if ($missing === null) {
                config(['services.google' => []]);
            } else {
                config(['services.google.'.$missing => '']);
            }
            foreach (['pages.auth.login', 'pages.auth.register'] as $page) {
                $component = Volt::test($page)->assertSeeInOrder(['Continue with Google', 'or continue with email', 'name="email"'], false)
                    ->assertSee('Google sign-in will be available soon. You can continue with email.');
                $document = new \DOMDocument;
                $document->loadHTML($component->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
                $controls = (new \DOMXPath($document))->query('//*[self::a or self::button][contains(normalize-space(.), "Continue with Google")]');
                $this->assertCount(1, $controls);
                $this->assertSame('button', $controls->item(0)->nodeName);
                $this->assertTrue($controls->item(0)->hasAttribute('disabled'), 'Unavailable Google sign-in must be natively disabled.');
                $this->assertFalse($controls->item(0)->hasAttribute('href'));
                if ($page === 'pages.auth.login') {
                    $component->assertSet('showInterfacePicker', false)
                        ->call('startGoogleSignIn')->assertHasErrors('google')->assertNoRedirect()
                        ->assertSet('showInterfacePicker', false);
                }
            }
        }
    }

    public function test_google_precedes_email_and_is_available_when_configuration_is_complete(): void
    {
        $this->configureGoogle();
        foreach (['pages.auth.login', 'pages.auth.register'] as $page) {
            $component = Volt::test($page)->assertSeeInOrder(['Continue with Google', 'or continue with email', 'name="email"'], false)
                ->assertDontSee('National ID and live selfie')->assertDontSee('Google sign-in will be available soon.')
                ->assertDontSee('synthetic-google-client')->assertDontSee('synthetic-google-secret');
            $document = new \DOMDocument;
            $document->loadHTML($component->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $controls = (new \DOMXPath($document))->query('//*[self::a or self::button][contains(normalize-space(.), "Continue with Google")]');
            $this->assertCount(1, $controls);
            $this->assertFalse($controls->item(0)->hasAttribute('disabled'));
        }
    }

    public function test_google_login_redirects_before_any_interface_selection_or_password_validation(): void
    {
        $this->configureGoogle();
        $component = Volt::test('pages.auth.login')->call('startGoogleSignIn')
            ->assertHasNoErrors()->assertSet('showInterfacePicker', false)
            ->assertDontSee('How would you like to use Lendly?')
            ->assertRedirect(route('auth.google.redirect'));
        $this->assertArrayNotHasKey('redirectUsingNavigate', $component->effects);
        $this->assertGuest();
    }

    public function test_missing_google_configuration_prevents_redirecting(): void
    {
        config(['services.google' => []]);
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->assertHasErrors('google')
            ->assertSet('showInterfacePicker', false)->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_google_cannot_open_the_password_interface_picker(): void
    {
        $this->configureGoogle();
        Volt::test('pages.auth.login')->call('chooseInterface', 'renter')->assertForbidden();
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->call('chooseInterface', 'owner')->assertForbidden();
        $this->assertGuest();
    }

    public function test_email_password_login_retains_its_existing_interface_picker(): void
    {
        $this->configureGoogle();
        $user = User::factory()->create();
        Volt::test('pages.auth.login')->set('form.email', $user->email)->set('form.password', 'password')->call('login')
            ->assertHasNoErrors()->assertSet('showInterfacePicker', true)
            ->call('chooseInterface', 'owner')->assertHasNoErrors()->assertRedirect('/owner/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('owner', session('active_interface'));
    }

    public function test_invalid_password_login_does_not_open_the_interface_picker(): void
    {
        $this->configureGoogle();
        Volt::test('pages.auth.login')->call('login')
            ->assertHasErrors(['form.email', 'form.password'])
            ->assertSet('showInterfacePicker', false)->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_registration_requests_google_permissions_without_preselecting_an_interface(): void
    {
        $this->configureGoogle();
        $response = $this->get(route('register'))->assertOk();
        $response->assertSee('href="'.route('auth.google.redirect', ['prompt' => 'consent']).'"', false)
            ->assertDontSee('interface=renter')->assertDontSee('interface=owner');
        $this->assertGuest();
    }

    public function test_oauth_errors_are_visible_on_login_and_registration_even_if_google_is_disabled(): void
    {
        config(['services.google' => []]);
        foreach (['login', 'register'] as $route) {
            $this->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag([
                'google' => 'Synthetic Google sign-in retry message.',
            ]))])
                ->get(route($route))->assertOk()->assertSee('Synthetic Google sign-in retry message.');
        }
    }
}
