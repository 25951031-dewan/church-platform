<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Post\Models\Post;

test('page admin can post as page', function () {
    $admin = User::factory()->create();
    $page  = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'approved']);

    $this->actingAs($admin)->postJson('/api/v1/posts', [
        'body'            => 'Sunday service starts at 9am',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ])->assertStatus(201)->assertJsonFragment(['posted_as' => 'entity']);
});

test('non-admin cannot post as page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);

    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body'            => 'Trying to hijack',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ])->assertStatus(403);
});

test('post as page sets entity_id and actor_entity_id on the record', function () {
    $admin = User::factory()->create();
    $page  = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'approved']);

    $response = $this->actingAs($admin)->postJson('/api/v1/posts', [
        'body'            => 'Entity post',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ]);

    $this->assertDatabaseHas('social_posts', [
        'id'              => $response->json('id'),
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ]);
});

test('regular post still works without entity fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body' => 'Normal post',
    ])->assertStatus(201)->assertJsonFragment(['posted_as' => 'user']);
});
