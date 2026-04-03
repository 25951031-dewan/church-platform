<?php

namespace Tests\Feature\Chat;

use Common\Auth\Models\User;
use Common\Chat\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Grant chat permissions
        // Note: You may need to adjust this based on your permission system
    }

    public function test_user_can_list_their_conversations(): void
    {
        Sanctum::actingAs($this->user);

        // Create a conversation
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $conversation->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->getJson('/api/v1/chat/conversations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'display_name', 'users', 'updated_at'],
                ],
            ]);
    }

    public function test_user_can_create_direct_conversation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/chat/conversations', [
            'user_ids' => [$this->otherUser->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'direct');

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('conversation_user', [
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('conversation_user', [
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_user_can_create_group_conversation(): void
    {
        Sanctum::actingAs($this->user);

        $thirdUser = User::factory()->create();

        $response = $this->postJson('/api/v1/chat/conversations', [
            'user_ids' => [$this->otherUser->id, $thirdUser->id],
            'name' => 'Test Group',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'group')
            ->assertJsonPath('data.name', 'Test Group');
    }

    public function test_duplicate_direct_conversation_returns_existing(): void
    {
        Sanctum::actingAs($this->user);

        // Create first conversation
        $response1 = $this->postJson('/api/v1/chat/conversations', [
            'user_ids' => [$this->otherUser->id],
        ]);
        $conversationId = $response1->json('data.id');

        // Try to create duplicate
        $response2 = $this->postJson('/api/v1/chat/conversations', [
            'user_ids' => [$this->otherUser->id],
        ]);

        // Should return existing conversation
        $response2->assertOk()
            ->assertJsonPath('data.id', $conversationId);
    }

    public function test_user_can_view_their_conversation(): void
    {
        Sanctum::actingAs($this->user);

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $conversation->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->getJson("/api/v1/chat/conversations/{$conversation->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $conversation->id);
    }

    public function test_user_cannot_view_other_users_conversation(): void
    {
        $thirdUser = User::factory()->create();
        Sanctum::actingAs($thirdUser);

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $conversation->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->getJson("/api/v1/chat/conversations/{$conversation->id}");

        $response->assertForbidden();
    }

    public function test_user_can_leave_conversation(): void
    {
        Sanctum::actingAs($this->user);

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $conversation->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->deleteJson("/api/v1/chat/conversations/{$conversation->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_mark_conversation_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $conversation->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->postJson("/api/v1/chat/conversations/{$conversation->id}/read");

        $response->assertOk();

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->user->id,
        ]);
    }
}
