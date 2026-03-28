<?php

namespace Tests\Feature\Sermons;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\SermonSeries;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SermonPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Sermons\Database\Seeders\SermonPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_member_can_view_sermon(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($user)->getJson("/api/v1/sermons/{$sermon->id}")
            ->assertOk();
    }

    public function test_member_cannot_update_sermon(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/sermons/{$sermon->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_member_cannot_manage_series(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/sermon-series', [
            'name' => 'Test Series',
        ])->assertForbidden();
    }

    public function test_admin_can_manage_series_and_speakers(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/sermon-series', [
            'name' => 'Gospel of John',
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/v1/speakers', [
            'name' => 'Pastor Sarah',
            'bio' => 'Lead pastor and teacher',
        ])->assertCreated();
    }
}
