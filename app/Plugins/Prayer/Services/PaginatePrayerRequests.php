<?php

namespace App\Plugins\Prayer\Services;

use App\Plugins\Prayer\Models\PrayerRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginatePrayerRequests
{
    public function __construct(
        private PrayerRequestLoader $loader,
    ) {}

    public function execute(Request $request): LengthAwarePaginator
    {
        $query = PrayerRequest::query()
            ->with(['user:id,name,avatar'])
            ->withCount('reactions');

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        // Wall mode: public + approved only
        if ($request->boolean('wall')) {
            $query->publicWall();
        } elseif ($request->boolean('flagged')) {
            // Pastoral dashboard: flagged prayers
            $query->flagged()->latest();
        } else {
            $query->latest();
        }

        if ($request->has('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('request', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(min((int) $request->input('per_page', 15), 50));

        // Sanitize anonymous prayers in the paginated results
        $paginator->getCollection()->transform(function ($prayer) {
            $data = $prayer->toArray();
            return $this->loader->sanitizeAnonymous($data);
        });

        return $paginator;
    }
}
