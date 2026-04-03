<?php

namespace App\Modules\Counseling\Policies;

use App\Models\User;
use App\Modules\Counseling\Models\CounselingThread;

class CounselingPolicy
{
    public function view(User $user, CounselingThread $thread): bool
    {
        if ($user->is_admin) return true;
        return $thread->isParticipant($user->id);
    }

    public function respond(User $user, CounselingThread $thread): bool
    {
        if ($user->is_admin) return true;
        return $thread->isParticipant($user->id);
    }

    public function assign(User $user, CounselingThread $thread): bool
    {
        if ($user->is_admin) return true;
        return $user->hasRole('church_admin');
    }

    public function close(User $user, CounselingThread $thread): bool
    {
        if ($user->is_admin) return true;
        return $thread->isParticipant($user->id);
    }
}
