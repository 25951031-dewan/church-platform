<?php
use App\Models\User;
use Plugins\Post\Models\Post;

test('authenticated user sees home feed', function () {
    $user = User::factory()->create();
    Post::factory(5)->create(['status' => 'published']);

    $this->actingAs($user)->getJson('/api/v1/feed')
        ->assertOk()->assertJsonStructure(['data', 'meta']);
});

test('community feed returns only that communitys posts', function () {
    Post::factory(3)->create(['status' => 'published', 'community_id' => 1]);
    Post::factory(2)->create(['status' => 'published', 'community_id' => 2]);

    $this->getJson('/api/v1/feed/community/1')
        ->assertOk()->assertJsonCount(3, 'data');
});
