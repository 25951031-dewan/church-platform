<?php
use App\Models\User;
use Plugins\Post\Models\Post;

test('user can react to a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/reactions', [
        'reactable_type' => 'post', 'reactable_id' => $post->id, 'emoji' => '👍',
    ])->assertStatus(201)->assertJson(['reacted' => true]);

    expect($post->fresh()->reactions_count)->toBe(1);
});

test('reacting twice removes the reaction (toggle)', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/reactions', ['reactable_type' => 'post', 'reactable_id' => $post->id]);
    $this->actingAs($user)->postJson('/api/v1/reactions', ['reactable_type' => 'post', 'reactable_id' => $post->id])
        ->assertJson(['reacted' => false]);

    expect($post->fresh()->reactions_count)->toBe(0);
});
