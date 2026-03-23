<?php

use App\Models\User;
use Plugins\Community\Models\Community;

test('creating a community accepts a community_type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/communities', [
            'name'           => 'Sunday Bible Study',
            'community_type' => 'bible_study',
        ])
        ->assertCreated()
        ->assertJsonPath('community_type', 'bible_study');
});

test('community_type appears in the community list', function () {
    Community::factory()->create([
        'community_type' => 'prayer_circle',
        'status'         => 'active',
        'is_counsel_group' => false,
    ]);

    $this->getJson('/api/v1/communities')
        ->assertOk()
        ->assertJsonFragment(['community_type' => 'prayer_circle']);
});

test('community_type defaults to null when not provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/communities', ['name' => 'General Group'])
        ->assertCreated();

    expect($response->json('community_type'))->toBeNull();
});
