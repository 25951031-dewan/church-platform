<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Post\Models\Post;

test('GET /feed/page/{id} returns posts scoped to that entity', function () {
    $page      = ChurchEntity::factory()->create(['type' => 'page']);
    $pagePost  = Post::factory()->create(['entity_id' => $page->id, 'status' => 'published', 'published_at' => now()]);
    $otherPost = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

    $response = $this->getJson("/api/v1/feed/page/{$page->id}")
         ->assertStatus(200);

    $postIds = collect($response->json('data'))->pluck('id')->toArray();
    expect($postIds)->toContain($pagePost->id)->not->toContain($otherPost->id);
});

test('GET /feed/page/{id} returns 404 for non-existent entity', function () {
    $this->getJson('/api/v1/feed/page/99999')->assertStatus(404);
});

test('home feed includes posts from followed pages', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved']);

    $pagePost  = Post::factory()->create(['entity_id' => $page->id, 'status' => 'published', 'published_at' => now()]);
    $otherPost = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/feed');
    $ids = collect($response->json('data'))->pluck('id')->toArray();

    expect($ids)->toContain($pagePost->id);
});

test('GET /feed/page/{id} supports type filter', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    Post::factory()->create(['entity_id' => $page->id, 'type' => 'prayer', 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['entity_id' => $page->id, 'type' => 'post',   'status' => 'published', 'published_at' => now()]);

    $this->getJson("/api/v1/feed/page/{$page->id}?type=prayer")
         ->assertStatus(200)
         ->assertJsonCount(1, 'data');
});
