<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    protected function demoUser(string $role, bool $isDemo = true): User
    {
        return User::factory()->create([
            'email' => config('helpdesk.demo_accounts.'.$role),
            'role' => $role,
            'is_active' => true,
            'is_demo' => $isDemo,
            'password' => 'Demo1234!',
        ]);
    }

    public function test_demo_login_signs_a_visitor_in_as_the_chosen_role(): void
    {
        config(['helpdesk.demo' => true]);
        $admin = $this->demoUser('admin', false);

        $this->post(route('demo.login'), ['role' => 'admin'])
            ->assertRedirect(route('app.dashboard'));

        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_demo_login_returns_404_when_demo_mode_is_disabled(): void
    {
        config(['helpdesk.demo' => false]);

        $this->post(route('demo.login'), ['role' => 'admin'])->assertNotFound();
        $this->assertGuest();
    }

    public function test_demo_login_rejects_an_unknown_role(): void
    {
        config(['helpdesk.demo' => true]);

        $this->post(route('demo.login'), ['role' => 'owner'])
            ->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    public function test_demo_account_identity_cannot_be_changed(): void
    {
        config(['helpdesk.demo' => true]);
        $admin = $this->demoUser('admin', false);
        $target = $this->demoUser('agent');

        $this->actingAs($admin)->patch(route('app.users.update', $target), [
            'name' => 'Renamed',
            'email' => 'changed@example.test',
            'role' => 'agent',
            'locale' => 'ar',
            'is_active' => 1,
        ])->assertSessionHasErrors('email');

        $this->assertSame(config('helpdesk.demo_accounts.agent'), $target->fresh()->email);
    }

    public function test_demo_account_cannot_be_deleted(): void
    {
        $admin = $this->staff('admin');
        $target = $this->demoUser('agent');

        $this->actingAs($admin)->delete(route('app.users.destroy', $target))
            ->assertSessionHasErrors('user');

        $this->assertNotNull($target->fresh());
    }

    public function test_demo_user_cannot_create_another_admin(): void
    {
        config(['helpdesk.demo' => true]);
        $admin = $this->demoUser('admin');

        $this->actingAs($admin)->post(route('app.users.store'), [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.test',
            'role' => 'admin',
            'locale' => 'ar',
            'password' => 'Str0ngPass!',
            'password_confirmation' => 'Str0ngPass!',
            'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'second-admin@example.test']);
    }

    public function test_demo_reset_command_is_a_no_op_when_demo_mode_is_disabled(): void
    {
        config(['helpdesk.demo' => false]);
        $admin = $this->staff('admin');

        $this->artisan('demo:reset --force')->assertSuccessful();

        // A no-op must not wipe existing records.
        $this->assertNotNull($admin->fresh());
    }
}
