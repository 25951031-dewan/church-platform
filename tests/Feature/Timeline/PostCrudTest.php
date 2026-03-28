<?php

namespace Tests\Feature\Timeline;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
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

    public function test_member_can_create_post(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/posts', [
            'content' => 'God is good!',
            'type' => 'text',
            'visibility' => 'public',
        ])
            ->assertStatus(201)
            ->assertJsonPath('post.content', 'God is good!')
            ->assertJsonStructure(['post' => ['id', 'content', 'type', 'user', 'reaction_counts']]);
    }

    public function test_member_can_update_own_post(): void
    {
        $user = $this->memberUser();

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Original',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Updated content',
        ])
            ->assertOk()
            ->assertJsonPath('post.content', 'Updated content');
    }

    public function test_member_cannot_update_others_post(): void
    {
        $author = $this->memberUser();
        $other = $this->memberUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'My post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($other)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Hacked!',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_any_post(): void
    {
        $author = $this->memberUser();
        $admin = $this->adminUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Member post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($admin)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Admin edited',
        ])->assertOk();
    }

    public function test_member_can_delete_own_post(): void
    {
        $user = $this->memberUser();

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Delete me',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/posts/{$post->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_unauthenticated_cannot_create_post(): void
    {
        $this->postJson('/api/v1/posts', [
            'content' => 'Anonymous post',
        ])->assertUnauthorized();
    }
}
