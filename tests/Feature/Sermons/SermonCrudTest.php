<?php

namespace Tests\Feature\Sermons;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SermonCrudTest extends TestCase
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

    public function test_member_can_list_sermons(): void
    {
        $user = $this->memberUser();
        Sermon::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/sermons')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_sermon(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/sermons', [
            'title' => 'The Good Shepherd',
            'speaker' => 'Pastor John',
            'sermon_date' => '2026-03-28',
            'scripture_reference' => 'John 10:1-18',
            'audio_url' => 'https://example.com/sermon.mp3',
        ])->assertCreated()
            ->assertJsonPath('sermon.title', 'The Good Shepherd')
            ->assertJsonPath('sermon.speaker', 'Pastor John');
    }

    public function test_member_cannot_create_sermon(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/sermons', [
            'title' => 'Test',
            'speaker' => 'Test',
        ])->assertForbidden();
    }

    public function test_admin_can_update_sermon(): void
    {
        $admin = $this->adminUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/sermons/{$sermon->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('sermon.title', 'Updated Title');
    }

    public function test_admin_can_delete_sermon(): void
    {
        $admin = $this->adminUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/sermons/{$sermon->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sermons', ['id' => $sermon->id]);
    }

    public function test_show_sermon_increments_view_count(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create(['view_count' => 0]);

        $this->actingAs($user)->getJson("/api/v1/sermons/{$sermon->id}")
            ->assertOk();

        $this->assertEquals(1, $sermon->fresh()->view_count);
    }
}
