<?php

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('page admin can view insights', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'      => 'page',
        'owner_id'  => $owner->id,
        'is_active' => true,
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->getJson("/api/v1/pages/{$page->id}/insights")
        ->assertOk()
        ->assertJsonStructure([
            'members_count', 'sub_pages_count', 'posts_count',
            'is_verified', 'verification_requested', 'created_at',
        ]);
});

test('non-admin cannot view insights', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'is_active' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/pages/{$page->id}/insights")
        ->assertForbidden();
});

test('page admin can request verification', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'        => 'page',
        'owner_id'    => $owner->id,
        'is_active'   => true,
        'is_verified' => false,
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertCreated()
        ->assertJsonPath('verification_requested', true);

    expect($page->fresh()->verification_requested_at)->not->toBeNull();
});

test('requesting verification twice returns 422', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'                      => 'page',
        'owner_id'                  => $owner->id,
        'is_active'                 => true,
        'is_verified'               => false,
        'verification_requested_at' => now(),
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertStatus(422);
});

test('non-admin cannot request verification', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'is_active' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertForbidden();
});
