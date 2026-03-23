<?php

use App\Models\User;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

test('joining an open community auto-approves', function () {
    $user      = User::factory()->create();
    $community = Community::factory()->create(['privacy' => 'public', 'privacy_closed' => '0']);

    $this->actingAs($user)->postJson("/api/v1/communities/{$community->id}/join")
         ->assertStatus(201)->assertJson(['status' => 'approved']);

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $user->id])->value('status'))
        ->toBe('approved');
});

test('joining a closed community creates pending request', function () {
    $user      = User::factory()->create();
    $community = Community::factory()->closed()->create();

    $this->actingAs($user)->postJson("/api/v1/communities/{$community->id}/join")
         ->assertStatus(201)->assertJson(['status' => 'pending']);

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $user->id])->value('status'))
        ->toBe('pending');
});

test('community admin can approve a pending member', function () {
    $admin     = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->closed()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,     'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($admin)
         ->postJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $requester->id])->value('status'))
        ->toBe('approved');
});

test('community admin can reject a pending member', function () {
    $admin     = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,     'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($admin)
         ->deleteJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $requester->id])->exists())
        ->toBeFalse();
});

test('non-admin cannot approve members', function () {
    $user      = User::factory()->create();
    $requester = User::factory()->create();
    $community = Community::factory()->create();
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $user->id,      'role' => 'member', 'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $requester->id, 'role' => 'member', 'status' => 'pending']);

    $this->actingAs($user)
         ->postJson("/api/v1/communities/{$community->id}/members/{$requester->id}/approve")
         ->assertStatus(403);
});

test('admin can promote member to moderator', function () {
    $admin  = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,  'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);

    $this->actingAs($admin)
         ->patchJson("/api/v1/communities/{$community->id}/members/{$member->id}", ['role' => 'moderator'])
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $member->id])->value('role'))
        ->toBe('moderator');
});

test('admin can ban a member', function () {
    $admin  = User::factory()->create();
    $member = User::factory()->create();
    $community = Community::factory()->create(['created_by' => $admin->id]);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $admin->id,  'role' => 'admin',  'status' => 'approved']);
    CommunityMember::create(['community_id' => $community->id, 'user_id' => $member->id, 'role' => 'member', 'status' => 'approved']);

    $this->actingAs($admin)
         ->postJson("/api/v1/communities/{$community->id}/members/{$member->id}/ban")
         ->assertOk();

    expect(CommunityMember::where(['community_id' => $community->id, 'user_id' => $member->id])->value('status'))
        ->toBe('banned');
});
