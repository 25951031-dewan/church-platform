<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Common\Auth\Models\Role;
use Common\Notifications\Notifications\NewMemberNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_user_can_list_notifications(): void
    {
        $user = $this->memberUser();
        $user->notify(new NewMemberNotification(User::factory()->create()));

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = $this->memberUser();
        $user->notify(new NewMemberNotification(User::factory()->create()));
        $notificationId = $user->fresh()->unreadNotifications()->first()->id;

        $response = $this->actingAs($user)->postJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertOk();
        $this->assertNotNull($user->fresh()->notifications()->where('id', $notificationId)->value('read_at'));
    }

    public function test_user_can_get_unread_count(): void
    {
        $user = $this->memberUser();
        $user->notify(new NewMemberNotification(User::factory()->create()));
        $user->notify(new NewMemberNotification(User::factory()->create()));

        $response = $this->actingAs($user)->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
        $response->assertJsonPath('count', 2);
    }
}
