<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertSoftDeleted($user);
        $this->assertSame('Deleted account', $user->fresh()->name);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }

    // FR-38: phone and address, not just name/email, actually persist.
    public function test_phone_and_address_can_be_updated(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('phone', '09171234567')
            ->set('address', '123 Rizal St, Manila')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('09171234567', $user->phone);
        $this->assertSame('123 Rizal St, Manila', $user->address);
    }

    // FR-38: profile picture upload and removal.
    public function test_avatar_can_be_uploaded_and_removed(): void
    {
        $user = User::factory()->create();

        Volt::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->set('avatar', UploadedFile::fake()->create('avatar.jpg', 50, 'image/jpeg'))
            ->call('updateAvatar')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertNotNull($user->avatarUrl());

        Volt::actingAs($user)
            ->test('profile.update-profile-information-form')
            ->call('removeAvatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_is_visible_on_the_profile_page(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/test.jpg']);

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($user->avatarUrl(), false);
    }
}
