<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SessionTokenLogoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_web_logout_revokes_sanctum_token_created_at_login(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
        ]);

        // Login via halaman web -> membuat session + token Sanctum (auth_token)
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(1, $user->tokens()->count(), 'Login web harus membuat 1 token Sanctum.');

        // Logout -> token Sanctum ikut di-revoke (reset token session)
        $this->post('/logout');

        $this->assertGuest();
        $this->assertSame(0, $user->tokens()->count(), 'Logout harus me-revoke token Sanctum milik sesi web.');
    }

    public function test_logout_still_works_when_no_web_token_was_created(): void
    {
        $user = User::factory()->create([
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
