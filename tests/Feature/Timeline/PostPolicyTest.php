<?php

namespace Tests\Feature\Timeline;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
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

    public function test_public_post_visible_to_authenticated_user(): void
    {
        $author = $this->memberUser();
        $viewer = $this->memberUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Public post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($viewer)->getJson("/api/v1/posts/{$post->id}")
            ->assertOk();
    }

    public function test_members_only_post_requires_posts_view_permission(): void
    {
        $author = $this->memberUser();
        $viewer = $this->memberUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Members only',
            'type' => 'text',
            'visibility' => 'members',
        ]);

        // Member has posts.view from TimelinePermissionSeeder
        $this->actingAs($viewer)->getJson("/api/v1/posts/{$post->id}")
            ->assertOk();
    }

    public function test_only_admin_can_pin_post(): void
    {
        $member = $this->memberUser();
        $admin = $this->adminUser();

        $post = Post::create([
            'user_id' => $member->id,
            'content' => 'Pin me',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        // Member cannot pin
        $this->actingAs($member)->patchJson("/api/v1/posts/{$post->id}/pin")
            ->assertStatus(403);

        // Admin can pin (has posts.pin via super-admin)
        $this->actingAs($admin)->patchJson("/api/v1/posts/{$post->id}/pin")
            ->assertOk();
    }

    public function test_only_admin_can_create_announcement(): void
    {
        $member = $this->memberUser();
        $admin = $this->adminUser();

        // Member cannot create announcement
        $this->actingAs($member)->postJson('/api/v1/posts', [
            'content' => 'Church announcement',
            'type' => 'announcement',
        ])->assertStatus(403);

        // Admin can create announcement
        $this->actingAs($admin)->postJson('/api/v1/posts', [
            'content' => 'Church announcement',
            'type' => 'announcement',
        ])->assertStatus(201);
    }
}
