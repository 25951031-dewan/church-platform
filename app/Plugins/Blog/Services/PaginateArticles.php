<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PaginateArticles
{
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Article::query()->with(['author', 'category', 'tags']);

        // Default to published-only unless status filter is provided
        if (isset($params['status'])) {
            $statuses = explode(',', $params['status']);
            $query->whereIn('status', $statuses);
        } else {
            // Include published + scheduled articles past their publish date
            $query->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'scheduled')
                         ->where('published_at', '<=', Carbon::now());
                  });
            });
        }

        if ($categoryId = ($params['category_id'] ?? null)) {
            $query->where('category_id', $categoryId);
        }

        if ($tag = ($params['tag'] ?? null)) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $tag));
        }

        if ($search = ($params['search'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if (filter_var($params['featured'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }

        $query->where('is_active', true);

        $orderBy = $params['order_by'] ?? 'published_at';
        $orderDir = $params['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        $perPage = min($params['per_page'] ?? 15, 50);
        return $query->paginate($perPage);
    }
}
