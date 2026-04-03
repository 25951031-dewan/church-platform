<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_user_can_view_preferences(): void
    {
        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/notifications/preferences');

        $response->assertOk();
        $response->assertJsonStructure(['preferences']);
    }

    public function test_user_can_update_preferences(): void
    {
        $user = $this->memberUser();

        $response = $this->actingAs($user)->putJson('/api/v1/notifications/preferences', [
            'preferences' => [[
                'notification_type' => 'sermon',
                'push_enabled' => false,
                'email_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'notification_type' => 'sermon',
            'push_enabled' => false,
        ]);
    }

    public function test_user_can_register_and_unregister_push_subscription(): void
    {
        $user = $this->memberUser();

        $register = $this->actingAs($user)->postJson('/api/v1/notifications/push/register', [
            'player_id' => 'player-123',
            'device_type' => 'web',
            'device_name' => 'Chrome',
        ]);

        $register->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'player_id' => 'player-123',
        ]);

        $unregister = $this->actingAs($user)->postJson('/api/v1/notifications/push/unregister', [
            'player_id' => 'player-123',
        ]);

        $unregister->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $user->id,
            'player_id' => 'player-123',
        ]);
    }
}
