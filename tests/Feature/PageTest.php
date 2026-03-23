<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('page slug is generated from name on creation', function () {
    $page = new ChurchEntity(['name' => 'Youth Ministry', 'type' => 'page']);
    expect($page->generateSlug())->toBe('youth-ministry');
});

test('GET /pages returns list of pages', function () {
    ChurchEntity::factory()->count(3)->create(['type' => 'page']);
    $this->getJson('/api/v1/pages')->assertStatus(200)->assertJsonCount(3, 'data');
});

test('authenticated user can create a page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/pages', [
        'name'        => 'Youth Ministry',
        'description' => 'For all youth',
    ])->assertStatus(201)->assertJsonFragment(['name' => 'Youth Ministry']);
});

test('creating a page makes the creator an admin member', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson('/api/v1/pages', ['name' => 'Worship Team']);
    $id = $response->json('id');
    $this->assertDatabaseHas('entity_members', [
        'entity_id' => $id, 'user_id' => $user->id, 'role' => 'admin',
    ]);
});

test('non-owner cannot update page', function () {
    $page  = ChurchEntity::factory()->create(['type' => 'page']);
    $other = User::factory()->create();
    $this->actingAs($other)->putJson("/api/v1/pages/{$page->id}", ['name' => 'Hacked'])
         ->assertStatus(403);
});

test('owner can update own page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $user->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'admin', 'status' => 'approved']);
    $this->actingAs($user)->putJson("/api/v1/pages/{$page->id}", ['name' => 'Updated'])
         ->assertStatus(200)->assertJsonFragment(['name' => 'Updated']);
});

test('GET /pages/{slug} returns page by slug', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'slug' => 'worship-team-1234']);
    $this->getJson('/api/v1/pages/worship-team-1234')->assertStatus(200)->assertJsonFragment(['slug' => 'worship-team-1234']);
});

test('authenticated user can follow a page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    $this->actingAs($user)->postJson("/api/v1/pages/{$page->id}/follow")
         ->assertStatus(201);
    $this->assertDatabaseHas('entity_members', [
        'entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved',
    ]);
});

test('following a page increments members_count', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'members_count' => 0]);
    $this->actingAs($user)->postJson("/api/v1/pages/{$page->id}/follow")->assertStatus(201);
    expect($page->fresh()->members_count)->toBe(1);
});

test('user can unfollow a page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page', 'members_count' => 1]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved']);
    $this->actingAs($user)->deleteJson("/api/v1/pages/{$page->id}/follow")->assertStatus(200);
    $this->assertDatabaseMissing('entity_members', ['entity_id' => $page->id, 'user_id' => $user->id]);
    expect($page->fresh()->members_count)->toBe(0);
});

test('admin can promote member to moderator', function () {
    $admin  = User::factory()->create();
    $member = User::factory()->create();
    $page   = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id,  'role' => 'admin',  'status' => 'approved']);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);
    $this->actingAs($admin)
         ->putJson("/api/v1/pages/{$page->id}/members/{$member->id}/role", ['role' => 'moderator'])
         ->assertStatus(200)->assertJsonFragment(['role' => 'moderator']);
});
