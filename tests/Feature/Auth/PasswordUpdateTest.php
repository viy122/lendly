<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'New-password1!')
            ->set('password_confirmation', 'New-password1!')
            ->call('updatePassword');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertTrue(Hash::check('New-password1!', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'wrong-password')
            ->set('password', 'New-password1!')
            ->set('password_confirmation', 'New-password1!')
            ->call('updatePassword');

        $component
            ->assertHasErrors(['current_password'])
            ->assertNoRedirect();
    }

    #[DataProvider('invalidPasswords')]
    public function test_new_password_must_meet_every_requirement(string $password): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public static function invalidPasswords(): array
    {
        return [
            'too short' => ['Aa1!bbb'],
            'no uppercase letter' => ['password1!'],
            'no lowercase letter' => ['PASSWORD1!'],
            'no number' => ['Password!'],
            'no special character' => ['Password1'],
        ];
    }

    public function test_new_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)->test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'New-password1!')
            ->set('password_confirmation', 'Another-password1!')
            ->call('updatePassword')
            ->assertHasErrors(['password' => 'confirmed']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_new_password_shows_accessible_strength_requirements(): void
    {
        $component = Volt::actingAs(User::factory()->create())->test('profile.update-password-form')
            ->assertSee('Password strength')
            ->assertSee('At least 8 characters')
            ->assertSee('An uppercase letter')
            ->assertSee('A lowercase letter')
            ->assertSee('A number')
            ->assertSee('A special character')
            ->assertSee('role="progressbar"', false);

        $document = new \DOMDocument;
        @$document->loadHTML($component->html());
        $xpath = new \DOMXPath($document);

        foreach (['current_password', 'password', 'password_confirmation'] as $field) {
            $inputs = $xpath->query('//input[@id="update_password_'.$field.'"]');
            $this->assertCount(1, $inputs, 'The '.$field.' field must render a native input.');
            $this->assertSame('password', $inputs->item(0)->getAttribute('type'));
            $this->assertSame($field, $inputs->item(0)->getAttribute('wire:model'));
        }

        $this->assertSame('update_password_password_requirements',
            $xpath->query('//input[@id="update_password_password"]')->item(0)->getAttribute('aria-describedby'));
    }
}
