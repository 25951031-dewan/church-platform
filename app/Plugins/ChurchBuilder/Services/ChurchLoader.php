<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;

class ChurchLoader
{
    public function load(Church $church): Church
    {
        return $church->load([
            'admin:id,name,avatar',
        ])->loadCount(['approvedMembers', 'publishedPages', 'reactions']);
    }

    public function loadForDetail(Church $church): array
    {
        $this->load($church);
        $church->load(['publishedPages:id,church_id,title,slug,sort_order']);

        $data = $church->toArray();

        $userId = auth()->id();
        if ($userId) {
            $membership = $church->getMembership($userId);
            $data['current_user_membership'] = $membership ? [
                'role' => $membership->role,
                'status' => $membership->status,
                'joined_at' => $membership->joined_at,
            ] : null;
            $data['is_church_admin'] = $church->isChurchAdmin($userId);
        }

        return $data;
    }
}
