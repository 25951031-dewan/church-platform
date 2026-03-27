<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->banned()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }
}
