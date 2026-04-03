<?php

namespace Tests\Feature\LiveMeeting;

use App\Models\User;
use App\Plugins\LiveMeeting\Models\Meeting;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\LiveMeeting\Database\Seeders\LiveMeetingPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        $user->clearPermissionCache();
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        $user->clearPermissionCache();
        return $user;
    }

    private function moderatorUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'moderator')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_member_can_list_meetings(): void
    {
        Meeting::factory()->count(3)->create();

        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/meetings');

        $response->assertOk();
    }

    public function test_member_can_view_live_meetings(): void
    {
        Meeting::factory()->live()->count(2)->create();
        Meeting::factory()->past()->count(2)->create(); // should not appear

        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/meetings/live');

        $response->assertOk();
        $response->assertJsonCount(2, 'meetings');
    }

    public function test_member_can_view_meeting_detail(): void
    {
        $meeting = Meeting::factory()->create();

        $response = $this->actingAs($this->memberUser())->getJson("/api/v1/meetings/{$meeting->id}");

        $response->assertOk();
        $response->assertJsonPath('meeting.id', $meeting->id);
    }

    public function test_moderator_can_create_meeting(): void
    {
        $moderator = $this->moderatorUser();

        $response = $this->actingAs($moderator)->postJson('/api/v1/meetings', [
            'title' => 'Sunday Service',
            'meeting_url' => 'https://zoom.us/j/1234567890',
            'platform' => 'zoom',
            'starts_at' => now()->addHour()->toDateTimeString(),
            'ends_at' => now()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('meetings', ['title' => 'Sunday Service', 'host_id' => $moderator->id]);
    }

    public function test_host_can_update_own_meeting(): void
    {
        $host = $this->memberUser();
        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        $response = $this->actingAs($host)->putJson("/api/v1/meetings/{$meeting->id}", [
            'title' => 'Updated Meeting Title',
        ]);

        $response->assertOk();
        $response->assertJsonPath('meeting.title', 'Updated Meeting Title');
    }

    public function test_member_cannot_update_others_meeting(): void
    {
        $otherUser = User::factory()->create();
        $meeting = Meeting::factory()->create(['host_id' => $otherUser->id]);

        $response = $this->actingAs($this->memberUser())->putJson("/api/v1/meetings/{$meeting->id}", [
            'title' => 'Hijacked Title',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_meeting(): void
    {
        $meeting = Meeting::factory()->create();

        $response = $this->actingAs($this->adminUser())->deleteJson("/api/v1/meetings/{$meeting->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
    }
}
