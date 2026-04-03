<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\ArticleCategory;
use Illuminate\Support\Collection;

class PaginateArticleCategories
{
    public function execute(array $params): Collection
    {
        $query = ArticleCategory::query()->withCount('articles');

        if (!filter_var($params['include_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }
}
