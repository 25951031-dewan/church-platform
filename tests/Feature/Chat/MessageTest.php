<?php

namespace Tests\Feature\Chat;

use Common\Auth\Models\User;
use Common\Chat\Models\Conversation;
use Common\Chat\Models\Message;
use Common\Chat\Events\MessageSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $this->user->id,
        ]);
        $this->conversation->users()->attach([$this->user->id, $this->otherUser->id]);
    }

    public function test_user_can_send_message(): void
    {
        Event::fake([MessageSent::class]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/chat/conversations/{$this->conversation->id}/messages", [
            'body' => 'Hello, world!',
            'type' => 'text',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Hello, world!')
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'body' => 'Hello, world!',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_user_can_get_messages(): void
    {
        Sanctum::actingAs($this->user);

        // Create some messages
        Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'body' => 'Message 1',
            'type' => 'text',
        ]);

        Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->otherUser->id,
            'body' => 'Message 2',
            'type' => 'text',
        ]);

        $response = $this->getJson("/api/v1/chat/conversations/{$this->conversation->id}/messages");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_delete_own_message(): void
    {
        Sanctum::actingAs($this->user);

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'body' => 'To be deleted',
            'type' => 'text',
        ]);

        $response = $this->deleteJson("/api/v1/chat/messages/{$message->id}");

        $response->assertOk();

        // Should be soft deleted
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_user_cannot_delete_others_message(): void
    {
        Sanctum::actingAs($this->user);

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->otherUser->id,
            'body' => 'Not your message',
            'type' => 'text',
        ]);

        $response = $this->deleteJson("/api/v1/chat/messages/{$message->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }

    public function test_user_cannot_send_message_to_non_member_conversation(): void
    {
        $thirdUser = User::factory()->create();
        Sanctum::actingAs($thirdUser);

        $response = $this->postJson("/api/v1/chat/conversations/{$this->conversation->id}/messages", [
            'body' => 'Should fail',
        ]);

        $response->assertForbidden();
    }

    public function test_message_requires_body_or_file(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/chat/conversations/{$this->conversation->id}/messages", [
            'type' => 'text',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_messages_are_paginated(): void
    {
        Sanctum::actingAs($this->user);

        // Create 60 messages
        for ($i = 0; $i < 60; $i++) {
            Message::create([
                'conversation_id' => $this->conversation->id,
                'user_id' => $this->user->id,
                'body' => "Message {$i}",
                'type' => 'text',
            ]);
        }

        $response = $this->getJson("/api/v1/chat/conversations/{$this->conversation->id}/messages");

        $response->assertOk()
            ->assertJsonPath('per_page', 50)
            ->assertJsonCount(50, 'data')
            ->assertJsonStructure(['current_page', 'last_page', 'total']);
    }
}
