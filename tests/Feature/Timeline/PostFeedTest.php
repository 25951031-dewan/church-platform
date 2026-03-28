<?php

namespace Tests\Feature\Timeline;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
    }

    public function test_feed_returns_paginated_posts(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        Post::factory(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)->getJson('/api/v1/posts?feed=1')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }

    public function test_pinned_posts_appear_first(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        $regular = Post::create([
            'user_id' => $user->id,
            'content' => 'Regular post',
            'type' => 'text',
            'visibility' => 'public',
            'created_at' => now(),
        ]);

        $pinned = Post::create([
            'user_id' => $user->id,
            'content' => 'Pinned post',
            'type' => 'text',
            'visibility' => 'public',
            'is_pinned' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/posts?feed=1');
        $data = $response->json('data');

        $this->assertEquals($pinned->id, $data[0]['id']);
    }

    public function test_scheduled_posts_hidden_from_feed(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        Post::create([
            'user_id' => $user->id,
            'content' => 'Visible post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Scheduled post',
            'type' => 'text',
            'visibility' => 'public',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/posts?feed=1');
        $this->assertCount(1, $response->json('data'));
    }
}
