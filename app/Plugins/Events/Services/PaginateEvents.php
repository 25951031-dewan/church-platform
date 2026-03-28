<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateEvents
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Event::query()
            ->with(['creator:id,name,avatar'])
            ->withCount(['attendingRsvps', 'registrations']);

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        } elseif ($request->boolean('featured')) {
            $query->featured();
        } else {
            $query->active()->orderByDesc('start_date');
        }

        if ($request->has('from')) {
            $query->where('start_date', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->where('start_date', '<=', $request->input('to'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(min((int) $request->input('per_page', 15), 50));
    }
}
