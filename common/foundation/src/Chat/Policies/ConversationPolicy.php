<?php

namespace Common\Chat\Policies;

use Common\Auth\Models\User;
use Common\Chat\Models\Conversation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the list of their conversations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('chat.send');
    }

    /**
     * Determine if the user can view a specific conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // User can view if they're a participant or have moderate permission
        return $conversation->hasUser($user) || $user->hasPermission('chat.moderate');
    }

    /**
     * Determine if the user can create conversations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('chat.send');
    }

    /**
     * Determine if the user can create group conversations.
     */
    public function createGroup(User $user): bool
    {
        return $user->hasPermission('chat.create_group');
    }

    /**
     * Determine if the user can send messages to a conversation.
     */
    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->hasUser($user) && $user->hasPermission('chat.send');
    }

    /**
     * Determine if the user can attach files to messages.
     */
    public function attachFiles(User $user): bool
    {
        return $user->hasPermission('chat.attach_files');
    }

    /**
     * Determine if the user can moderate any chat (admin).
     */
    public function moderate(User $user): bool
    {
        return $user->hasPermission('chat.moderate');
    }

    /**
     * Determine if the user can delete/leave a conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        // Creator can delete OR user can leave their own conversation OR admin can moderate
        return $conversation->created_by === $user->id
            || $conversation->hasUser($user)
            || $user->hasPermission('chat.moderate');
    }
}
