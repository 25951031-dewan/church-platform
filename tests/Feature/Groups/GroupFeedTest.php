<?php

namespace Tests\Feature\Groups;

use App\Models\User;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Groups\Database\Seeders\GroupPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    public function test_group_feed_returns_only_group_posts(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        // Create posts: 2 in group, 1 not in group
        Post::factory()->count(2)->create(['group_id' => $group->id, 'user_id' => $user->id]);
        Post::factory()->create(['user_id' => $user->id]); // no group_id

        $this->actingAs($user)
            ->getJson("/api/v1/posts?group_id={$group->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_member_can_post_in_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->postJson('/api/v1/posts', [
            'content' => 'Hello group!',
            'group_id' => $group->id,
        ])->assertCreated();

        $this->assertDatabaseHas('timeline_posts', [
            'content' => 'Hello group!',
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_main_feed_excludes_group_posts_by_default(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create();

        Post::factory()->create(['user_id' => $user->id, 'group_id' => $group->id]);
        Post::factory()->create(['user_id' => $user->id, 'group_id' => null]);

        $this->actingAs($user)
            ->getJson('/api/v1/posts?feed=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_private_group_feed_hidden_from_non_members(): void
    {
        $owner = $this->memberUser();
        $outsider = $this->memberUser();
        $group = Group::factory()->create(['type' => 'private', 'created_by' => $owner->id]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'admin',
            'status' => 'approved',
        ]);

        Post::factory()->create(['group_id' => $group->id, 'user_id' => $owner->id]);

        // Non-member cannot view private group
        $this->actingAs($outsider)
            ->getJson("/api/v1/groups/{$group->id}")
            ->assertForbidden();
    }
}
