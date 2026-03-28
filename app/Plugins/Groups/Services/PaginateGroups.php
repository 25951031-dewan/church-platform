<?php

namespace App\Plugins\Groups\Services;

use App\Plugins\Groups\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateGroups
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Group::query()
            ->with(['creator:id,name,avatar'])
            ->withCount('approvedMembers');

        // Filter: my groups (groups current user is an approved member of)
        if ($request->boolean('my_groups')) {
            $query->whereHas('approvedMembers', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        } else {
            // Discovery mode: only show publicly visible groups
            $query->publiclyVisible();
        }

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('member_count');

        return $query->paginate(min((int) $request->input('per_page', 15), 50));
    }
}
