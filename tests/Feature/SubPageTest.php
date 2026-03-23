<?php

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('can list sub-pages of a parent page', function () {
    $parent = ChurchEntity::factory()->create(['type' => 'page']);
    ChurchEntity::factory()->count(3)->create([
        'type' => 'page',
        'parent_entity_id' => $parent->id,
    ]);

    $this->getJson("/api/v1/pages/{$parent->id}/sub-pages")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('creating a sub-page requires admin of parent', function () {
    $owner = User::factory()->create();
    $parent = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $owner->id]);
    EntityMember::create([
        'entity_id' => $parent->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $nonAdmin = User::factory()->create();

    $this->actingAs($nonAdmin)
        ->postJson("/api/v1/pages/{$parent->id}/sub-pages", ['name' => 'Youth Choir'])
        ->assertForbidden();
});

test('admin can create a sub-page under their parent page', function () {
    $owner = User::factory()->create();
    $parent = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $owner->id]);
    EntityMember::create([
        'entity_id' => $parent->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$parent->id}/sub-pages", [
            'name'        => 'Youth Ministry',
            'description' => 'For young adults',
        ])
        ->assertCreated()
        ->assertJsonPath('parent_entity_id', $parent->id)
        ->assertJsonPath('type', 'page');
});

test('listing sub-pages of unknown parent returns 404', function () {
    $this->getJson('/api/v1/pages/9999/sub-pages')
        ->assertNotFound();
});
