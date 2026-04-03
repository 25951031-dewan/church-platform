<?php

namespace Tests\Feature\LiveMeetings;

use App\Plugins\LiveMeeting\Models\Meeting;
use App\Models\User;
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

    private function member(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        $user->clearPermissionCache();
        return $user;
    }

    public function test_member_can_register_and_unregister_for_meeting(): void
    {
        $member = $this->member();
        $meeting = Meeting::factory()->create(['requires_registration' => true]);

        $register = $this->actingAs($member)->postJson("/api/v1/meetings/{$meeting->id}/register");
        $register->assertCreated();

        $this->assertDatabaseHas('meeting_registrations', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
        ]);

        $unregister = $this->actingAs($member)->deleteJson("/api/v1/meetings/{$meeting->id}/register");
        $unregister->assertNoContent();

        $this->assertDatabaseMissing('meeting_registrations', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
        ]);
    }
}
