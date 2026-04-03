<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginateMeetings
{
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Meeting::query()->with(['host', 'event'])->active();

        if ($filter = ($params['filter'] ?? null)) {
            if ($filter === 'live') {
                $query->live();
            } elseif ($filter === 'upcoming') {
                $query->upcoming();
            }
        }

        if ($search = ($params['search'] ?? null)) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderBy('starts_at', 'asc');

        $perPage = min($params['per_page'] ?? 15, 50);
        return $query->paginate($perPage);
    }
}
