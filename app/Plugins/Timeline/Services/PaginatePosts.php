<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginatePosts
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user:id,name,avatar', 'media', 'reactions'])
            ->withCount(['comments', 'reactions']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->input('feed', false)) {
            $query->feed();
        } else {
            $query->published()->latest();
        }

        return $query->paginate($request->input('per_page', 15));
    }
}
