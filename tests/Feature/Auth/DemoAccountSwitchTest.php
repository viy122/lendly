<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DemoAccountSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_members_cannot_use_demo_switch_to_become_admin(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        User::factory()->admin()->create(['email' => 'admin@tala.test']);
        Volt::test('layout.sidebar')->call('switchDemoUser', 'admin')->assertForbidden();
        $this->assertGuest();

        $member = User::factory()->create();
        Volt::actingAs($member)->test('layout.sidebar')->call('switchDemoUser', 'admin')->assertForbidden();
        $this->assertAuthenticatedAs($member);
    }

    public function test_even_admin_demo_switch_cannot_enter_a_locked_or_suspended_account(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'owner@tala.test']);
        try {
            for ($attempt = 0; $attempt < 3; $attempt++) {
                AccountLockout::recordFailure($target->email, 'account');
            }
        } catch (ValidationException) {
        }

        Volt::actingAs($admin)->test('layout.sidebar')->call('switchDemoUser', 'owner')->assertHasErrors('account');
        $this->assertAuthenticatedAs($admin);
        $this->travel(15)->minutes();
        $target->update(['status' => 'suspended']);
        Volt::actingAs($admin)->test('layout.sidebar')->call('switchDemoUser', 'owner')->assertForbidden();
        $this->assertAuthenticatedAs($admin);
    }
}
