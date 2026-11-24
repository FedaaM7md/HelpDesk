<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_a_team_member_can_sign_in_and_sign_out(): void
    {
        $user = $this->staff('admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret12345!',
        ]);

        $response->assertRedirect(route('app.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame($user->locale, session('locale'));

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $user = $this->staff('agent', [
            'is_active' => false,
            'password' => 'Secret12345!',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret12345!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sign_in_attempts_are_throttled(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'nobody@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('محاولات', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_guest_cannot_post_logout_without_a_session(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_remember_me_sets_the_remember_cookie(): void
    {
        $user = $this->staff('agent');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret12345!',
            'remember' => '1',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(User::find($user->id)->remember_token);
    }
}
