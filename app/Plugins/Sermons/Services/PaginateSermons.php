<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateSermons
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Sermon::query()
            ->with(['author:id,name,avatar', 'sermonSeries:id,name,slug', 'speakerProfile:id,name,slug,image'])
            ->withCount(['comments', 'reactions']);

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        } elseif ($request->boolean('published')) {
            $query->published();
        } else {
            $query->active();
        }

        if ($request->has('speaker_id')) {
            $query->where('speaker_id', $request->input('speaker_id'));
        }

        if ($request->has('series_id')) {
            $query->where('series_id', $request->input('series_id'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('speaker', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('scripture_reference', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('sermon_date');

        return $query->paginate(min((int) $request->input('per_page', 15), 50));
    }
}
