<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\BookCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaginateBookCategories
{
    public function execute(Request $request): Collection
    {
        $query = BookCategory::query()
            ->withCount(['books' => fn ($q) => $q->where('is_active', true)]);

        if (!$request->boolean('include_inactive')) {
            $query->active();
        }

        return $query->orderBy('parent_id')->orderBy('sort_order')->get();
    }
}
