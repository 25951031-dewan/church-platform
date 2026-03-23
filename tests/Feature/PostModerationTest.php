<?php

use App\Models\User;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;
use Plugins\Post\Models\Post;

test('community admin can pin a community post', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertOk()
        ->assertJsonPath('is_pinned', true);

    expect($post->fresh()->is_pinned)->toBeTrue();
});

test('pinning an already-pinned post toggles it off', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
        'is_pinned'    => true,
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertOk()
        ->assertJsonPath('is_pinned', false);

    expect($post->fresh()->is_pinned)->toBeFalse();
});

test('non-admin cannot pin a post', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertForbidden();
});

test('community admin can approve a post', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/approve")
        ->assertOk()
        ->assertJsonPath('is_approved', true);

    expect($post->fresh()->is_approved)->toBe(1);
    expect($post->fresh()->approved_by)->toBe($admin->id);
});

test('unauthenticated user cannot pin a post', function () {
    $community = Community::factory()->create(['status' => 'active']);
    $post = Post::factory()->create(['community_id' => $community->id, 'status' => 'published']);

    $this->postJson("/api/v1/posts/{$post->id}/pin")->assertUnauthorized();
});

test('admin of a different community cannot pin a post', function () {
    $admin = User::factory()->create();
    $ownCommunity = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $ownCommunity->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);

    $otherCommunity = Community::factory()->create(['status' => 'active']);
    $post = Post::factory()->create([
        'community_id' => $otherCommunity->id,
        'status'       => 'published',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertForbidden();
});
