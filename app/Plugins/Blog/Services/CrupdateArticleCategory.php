<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\ArticleCategory;

class CrupdateArticleCategory
{
    public function execute(ArticleCategory $category, array $data): ArticleCategory
    {
        $attributes = [
            'name' => $data['name'] ?? $category->name,
            'description' => $data['description'] ?? $category->description,
            'image' => $data['image'] ?? $category->image,
            'sort_order' => $data['sort_order'] ?? $category->sort_order ?? 0,
            'is_active' => $data['is_active'] ?? $category->is_active ?? true,
        ];

        if (!$category->exists) {
            $category = ArticleCategory::create($attributes);
        } else {
            $category->update($attributes);
        }

        return $category;
    }
}
