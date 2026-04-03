<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class ArticleLoader
{
    public function load(Article $article): Article
    {
        return $article->load(['author', 'category', 'tags'])
            ->loadCount('reactions');
    }

    public function loadForDetail(Article $article): array
    {
        $this->load($article);

        return [
            'article' => $article,
        ];
    }
}
