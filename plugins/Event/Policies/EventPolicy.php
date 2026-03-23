<?php
namespace Plugins\Event\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;

class EventPolicy
{
    public function view(?User $user, Event $event): bool
    {
        return $event->status === 'published';
    }

    public function create(User $user, ?int $communityId = null, ?int $churchId = null): bool
    {
        if ($communityId) {
            return DB::table('community_members')
                ->where('user_id', $user->id)
                ->where('community_id', $communityId)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'approved')
                ->exists();
        }
        if ($churchId) {
            return DB::table('church_members')
                ->where('user_id', $user->id)
                ->where('church_id', $churchId)
                ->where('role', 'admin')
                ->exists();
        }
        return true; // platform-wide: any authenticated user
    }

    public function update(User $user, Event $event): bool
    {
        if ($event->created_by === $user->id) return true;
        if ($user->is_admin) return true;

        if ($event->community_id) {
            $isCommunityAdmin = DB::table('community_members')
                ->where('user_id', $user->id)
                ->where('community_id', $event->community_id)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'approved')
                ->exists();
            if ($isCommunityAdmin) return true;
        }

        if ($event->church_id) {
            return DB::table('church_members')
                ->where('user_id', $user->id)
                ->where('church_id', $event->church_id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->created_by === $user->id || $user->is_admin;
    }

    public function post(User $user, Event $event): bool
    {
        return true;
    }
}
