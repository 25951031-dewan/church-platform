<?php
use App\Models\User;
use Plugins\Post\Models\Post;
use Plugins\Comment\Models\Comment;

test('user can comment on a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/comments', [
        'post_id' => $post->id,
        'body'    => 'Great post!',
    ])->assertStatus(201)->assertJsonFragment(['body' => 'Great post!']);

    expect(Comment::count())->toBe(1);
});

test('user can reply to a comment', function () {
    $user    = User::factory()->create();
    $post    = Post::factory()->create(['status' => 'published']);
    $comment = Comment::factory()->create(['commentable_id' => $post->id, 'commentable_type' => Post::class]);

    $this->actingAs($user)->postJson('/api/v1/comments', [
        'post_id'   => $post->id,
        'body'      => 'Nice reply!',
        'parent_id' => $comment->id,
    ])->assertStatus(201);

    expect($comment->fresh()->replies_count)->toBe(1);
});

test('user cannot delete another users comment', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertStatus(403);
});
