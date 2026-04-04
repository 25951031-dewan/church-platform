<?php

namespace App\Listeners;

use App\Events\Group\UserJoinedGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyGroupAdmins implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserJoinedGroup $event): void
    {
        $group = $event->group;
        $user = $event->user;

        \Log::info('User joined group', [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        // TODO: Notify group admins of new member
    }
}
