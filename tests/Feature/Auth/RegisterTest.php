<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20, 'is_default' => true]);

        $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.name', 'John Doe');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_registration_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Jane',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }
}
