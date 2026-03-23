<?php
use App\Models\User;
use Plugins\Post\Models\Post;

test('create prayer post returns 201 with type=prayer', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'prayer',
        'body' => 'Please pray for my family.',
    ])->assertStatus(201)->assertJsonPath('type', 'prayer');
});

test('create anonymous prayer post has null author in response', function () {
    $user = User::factory()->create();
    $res = $this->actingAs($user)->postJson('/api/v1/posts', [
        'type'         => 'prayer',
        'body'         => 'Anonymous prayer.',
        'is_anonymous' => true,
    ])->assertStatus(201);
    // author should be null when is_anonymous=true
    expect($res->json('author'))->toBeNull();
});

test('create blessing with scripture in meta returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'blessing',
        'body' => 'God provided a new job!',
        'meta' => ['scripture' => 'Jeremiah 29:11'],
    ])->assertStatus(201)->assertJsonPath('meta.scripture', 'Jeremiah 29:11');
});

test('create poll with 1 option returns 422', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'poll',
        'meta' => ['question' => 'A question?', 'options' => [['text' => 'Only one']]],
    ])->assertStatus(422);
});

test('create poll with 11 options returns 422', function () {
    $user = User::factory()->create();
    $options = array_fill(0, 11, ['text' => 'Option']);
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'poll',
        'meta' => ['question' => 'Too many?', 'options' => $options],
    ])->assertStatus(422);
});

test('create bible study with scripture and passage returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'bible_study',
        'body' => 'Reflection on grace.',
        'meta' => ['scripture' => 'Romans 8:28', 'passage' => 'And we know that in all things God works for the good…'],
    ])->assertStatus(201);
});

test('reshare poll returns 422', function () {
    $user = User::factory()->create();
    $poll = Post::factory()->create([
        'type'    => 'poll',
        'status'  => 'published',
        'user_id' => $user->id,
        'meta'    => [
            'question'       => 'Q',
            'options'        => [['id' => 'opt_a', 'text' => 'A', 'votes_count' => 0], ['id' => 'opt_b', 'text' => 'B', 'votes_count' => 0]],
            'ends_at'        => null,
            'allow_multiple' => false,
        ],
    ]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/cross-post", [
        'targets' => [['community_id' => null, 'church_id' => null]],
    ])->assertStatus(422);
});

test('reshare blessing copies meta.scripture and body', function () {
    $owner   = User::factory()->create();
    $other   = User::factory()->create();
    $blessing = Post::factory()->create([
        'type'    => 'blessing',
        'status'  => 'published',
        'user_id' => $owner->id,
        'body'    => 'God is good!',
        'meta'    => ['scripture' => 'Psalm 23:1'],
    ]);

    // Cross-post to a new community (or null targets) — check if crossPost accepts null community
    // The existing crossPost may require valid targets; let's use store() with cross_post_targets
    $this->actingAs($other)->postJson("/api/v1/posts/{$blessing->id}/cross-post", [
        'targets' => [['community_id' => null, 'church_id' => null]],
    ])->assertStatus(200);

    $reshare = Post::where('parent_id', $blessing->id)->first();
    expect($reshare)->not->toBeNull();
    expect($reshare->meta['scripture'])->toBe('Psalm 23:1');
});

test('author can mark prayer answered', function () {
    $user   = User::factory()->create();
    $prayer = Post::factory()->create([
        'user_id' => $user->id,
        'type'    => 'prayer',
        'status'  => 'published',
        'meta'    => ['answered' => false, 'answered_at' => null],
    ]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$prayer->id}/answer-prayer")
        ->assertStatus(200)->assertJsonPath('answered', true);
});

test('non-author cannot mark prayer answered', function () {
    $owner  = User::factory()->create();
    $other  = User::factory()->create();
    $prayer = Post::factory()->create([
        'user_id' => $owner->id,
        'type'    => 'prayer',
        'status'  => 'published',
        'meta'    => ['answered' => false, 'answered_at' => null],
    ]);

    $this->actingAs($other)->postJson("/api/v1/posts/{$prayer->id}/answer-prayer")
        ->assertStatus(403);
});

test('GET /feed?type=prayer returns only prayer posts', function () {
    Post::factory()->create(['type' => 'post', 'status' => 'published']);
    Post::factory()->create([
        'type'   => 'prayer',
        'status' => 'published',
        'meta'   => ['answered' => false, 'answered_at' => null],
    ]);

    $this->getJson('/api/v1/feed?type=prayer')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'prayer');
});
