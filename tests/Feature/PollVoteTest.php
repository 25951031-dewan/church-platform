<?php
use App\Models\User;
use Plugins\Post\Models\Post;
use Plugins\Post\Models\PollVote;

function makePoll(array $overrides = []): Post {
    return Post::factory()->create(array_merge([
        'type'   => 'poll',
        'status' => 'published',
        'meta'   => [
            'question'       => 'Favourite hymn?',
            'options'        => [
                ['id' => 'opt_aaa', 'text' => 'Amazing Grace', 'votes_count' => 0],
                ['id' => 'opt_bbb', 'text' => 'Holy Holy Holy', 'votes_count' => 0],
            ],
            'ends_at'        => null,
            'allow_multiple' => false,
        ],
    ], $overrides));
}

test('vote on valid option returns 201 and GET /votes shows count 1', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])
        ->assertStatus(201);

    // Assert via GET /votes (reads from poll_votes table — authoritative on SQLite)
    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->assertStatus(200)->json('counts');
    expect((int)($counts['opt_aaa'] ?? 0))->toBe(1);
});

test('voting on same option twice returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa']);
    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])
        ->assertStatus(422);
});

test('change vote decrements old and increments new (allow_multiple=false)', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])->assertStatus(201);
    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_bbb'])->assertStatus(201);

    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->json('counts');
    expect((int)($counts['opt_aaa'] ?? 0))->toBe(0);
    expect((int)($counts['opt_bbb'] ?? 0))->toBe(1);
});

test('vote on expired poll returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll(['meta' => [
        'question'       => 'Old poll',
        'options'        => [['id' => 'opt_x', 'text' => 'A', 'votes_count' => 0], ['id' => 'opt_y', 'text' => 'B', 'votes_count' => 0]],
        'ends_at'        => now()->subDay()->toIso8601String(),
        'allow_multiple' => false,
    ]]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_x'])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Poll has ended']);
});

test('vote with invalid option_id returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_INVALID'])
        ->assertStatus(422);
});

test('GET /posts/{id}/votes unauthenticated returns 200 with user_vote null', function () {
    $poll = makePoll();
    $this->getJson("/api/v1/posts/{$poll->id}/votes")
        ->assertStatus(200)->assertJsonPath('user_vote', null);
});

test('DELETE /posts/{id}/vote removes vote and count drops to 0', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa']);
    $this->actingAs($user)->deleteJson("/api/v1/posts/{$poll->id}/vote")->assertStatus(200);

    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->json('counts');
    expect((int)($counts['opt_aaa'] ?? 0))->toBe(0);
});

test('DELETE /posts/{id}/vote when no vote exists returns 404', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->deleteJson("/api/v1/posts/{$poll->id}/vote")->assertStatus(404);
});
