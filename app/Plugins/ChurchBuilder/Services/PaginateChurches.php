<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateChurches
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Church::query()
            ->approved()
            ->with(['admin:id,name,avatar'])
            ->withCount('approvedMembers');

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->boolean('verified')) {
            $query->verified();
        }

        // Geo-search: lat, lng, radius (km)
        if ($request->has('lat') && $request->has('lng')) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');
            $radius = (float) $request->input('radius', 50);
            $query->nearby($lat, $lng, $radius);
        } else {
            $query->latest();
        }

        if ($request->has('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->has('denomination')) {
            $query->where('denomination', $request->input('denomination'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('denomination', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(min((int) $request->input('per_page', 12), 50));
    }
}
