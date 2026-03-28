<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchMember;

class ChurchMemberService
{
    public function join(Church $church, int $userId): ChurchMember
    {
        return ChurchMember::firstOrCreate(
            ['church_id' => $church->id, 'user_id' => $userId],
            ['role' => 'member', 'status' => 'approved', 'joined_at' => now()]
        );
    }

    public function leave(Church $church, int $userId): void
    {
        ChurchMember::where('church_id', $church->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function removeMember(Church $church, int $userId): void
    {
        $this->leave($church, $userId);
    }

    public function updateRole(Church $church, int $userId, string $role): ?ChurchMember
    {
        $member = ChurchMember::where('church_id', $church->id)
            ->where('user_id', $userId)
            ->first();

        if ($member) {
            $member->update(['role' => $role]);
        }

        return $member;
    }
}
