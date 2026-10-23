<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
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
                    $component->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', false)
                        ->call('startGoogleSignIn')->assertHasErrors('google')->assertNoRedirect()
                        ->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', false);
                }
            }
        }
    }

    public function test_google_precedes_email_and_is_available_when_configuration_is_complete(): void
    {
        $this->configureGoogle();
        foreach (['pages.auth.login', 'pages.auth.register'] as $page) {
            $component = Volt::test($page)->assertSeeInOrder(['Continue with Google', 'or continue with email', 'name="email"'], false)
                ->assertSee('National ID and live selfie')->assertDontSee('Google sign-in will be available soon.')
                ->assertDontSee('synthetic-google-client')->assertDontSee('synthetic-google-secret');
            $document = new \DOMDocument;
            $document->loadHTML($component->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $controls = (new \DOMXPath($document))->query('//*[self::a or self::button][contains(normalize-space(.), "Continue with Google")]');
            $this->assertCount(1, $controls);
            $this->assertFalse($controls->item(0)->hasAttribute('disabled'));
        }
    }

    public function test_google_login_opens_the_interface_picker_before_redirecting_without_password_validation(): void
    {
        $this->configureGoogle();
        foreach (['renter', 'owner'] as $interface) {
            $component = Volt::test('pages.auth.login')->call('startGoogleSignIn')
                ->assertHasNoErrors()->assertSet('googleSignIn', true)->assertSet('showInterfacePicker', true)
                ->assertNoRedirect();
            $component->call('chooseInterface', $interface)->assertHasNoErrors()
                ->assertRedirect(route('auth.google.redirect', ['interface' => $interface]))
                ->assertSet('showInterfacePicker', false)->assertSet('googleSignIn', false);
            $this->assertArrayNotHasKey('redirectUsingNavigate', $component->effects);
            $this->assertGuest();
        }
    }

    public function test_google_configuration_is_checked_before_start_and_again_after_interface_selection(): void
    {
        config(['services.google' => []]);
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->assertHasErrors('google')
            ->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', false)->assertNoRedirect();
        $this->configureGoogle();
        $component = Volt::test('pages.auth.login')->call('startGoogleSignIn');
        config(['services.google.client_secret' => null]);
        $component->call('chooseInterface', 'renter')->assertHasErrors('google')->assertNoRedirect()
            ->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', false);
        $this->assertGuest();
    }

    public function test_google_interface_selection_rejects_unsupported_values_and_unopened_picker(): void
    {
        $this->configureGoogle();
        Volt::test('pages.auth.login')->call('chooseInterface', 'renter')->assertForbidden();
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->call('chooseInterface', 'admin')->assertForbidden();
        $this->assertGuest();
    }

    public function test_cancelling_google_selection_does_not_change_the_existing_password_login_path(): void
    {
        $this->configureGoogle();
        $user = User::factory()->create();
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->call('cancelInterfaceSelection')
            ->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', false)
            ->set('form.email', $user->email)->set('form.password', 'password')->call('login')
            ->assertHasNoErrors()->assertSet('googleSignIn', false)->assertSet('showInterfacePicker', true)
            ->call('chooseInterface', 'owner')->assertHasNoErrors()->assertRedirect('/owner/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('owner', session('active_interface'));
    }

    public function test_starting_a_password_login_clears_google_mode_even_when_credentials_are_invalid(): void
    {
        $this->configureGoogle();
        Volt::test('pages.auth.login')->call('startGoogleSignIn')->call('login')
            ->assertHasErrors(['form.email', 'form.password'])->assertSet('googleSignIn', false)
            ->assertSet('showInterfacePicker', false)->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_google_mode_cannot_be_enabled_directly_from_the_browser(): void
    {
        $this->configureGoogle();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Volt::test('pages.auth.login')->set('googleSignIn', true);
    }

    public function test_registration_links_to_google_using_the_default_renter_interface(): void
    {
        $this->configureGoogle();
        $response = $this->get(route('register'))->assertOk();
        $response->assertSee('href="'.route('auth.google.redirect', ['interface' => 'renter']).'"', false);
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
