<?php

namespace Tests\Feature\Blog;

use App\Models\User;
use App\Plugins\Blog\Models\Tag;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Blog\Database\Seeders\BlogPermissionSeeder::class);
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

    public function test_member_can_list_tags(): void
    {
        Tag::create(['name' => 'faith', 'slug' => 'faith']);
        Tag::create(['name' => 'prayer', 'slug' => 'prayer']);

        $response = $this->actingAs($this->memberUser())->getJson('/api/v1/tags');

        $response->assertOk();
        $response->assertJsonCount(2, 'tags');
    }

    public function test_admin_can_create_tag(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson('/api/v1/tags', [
            'name' => 'worship',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('tags', ['name' => 'worship']);
    }

    public function test_admin_can_delete_tag(): void
    {
        $tag = Tag::create(['name' => 'to-delete', 'slug' => 'to-delete']);

        $response = $this->actingAs($this->adminUser())->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
