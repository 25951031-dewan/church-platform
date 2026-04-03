<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class CrupdateArticle
{
    public function execute(Article $article, array $data): Article
    {
        $attributes = [
            'title' => $data['title'] ?? $article->title,
            'content' => $data['content'] ?? $article->content,
            'excerpt' => $data['excerpt'] ?? $article->excerpt,
            'cover_image' => $data['cover_image'] ?? $article->cover_image,
            'category_id' => $data['category_id'] ?? $article->category_id,
            'church_id' => $data['church_id'] ?? $article->church_id,
            'status' => $data['status'] ?? $article->status ?? 'draft',
            'published_at' => $data['published_at'] ?? $article->published_at,
            'is_featured' => $data['is_featured'] ?? $article->is_featured ?? false,
            'is_active' => $data['is_active'] ?? $article->is_active ?? true,
            'meta_title' => $data['meta_title'] ?? $article->meta_title,
            'meta_description' => $data['meta_description'] ?? $article->meta_description,
        ];

        if (!$article->exists) {
            $attributes['author_id'] = $data['author_id'];
            $article = Article::create($attributes);
        } else {
            $article->update($attributes);
        }

        if (isset($data['tag_ids'])) {
            $article->tags()->sync($data['tag_ids']);
        }

        return $article->load(['author', 'category', 'tags']);
    }
}
