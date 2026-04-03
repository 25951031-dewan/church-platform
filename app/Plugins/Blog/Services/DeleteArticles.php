<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class DeleteArticles
{
    public function execute(array $ids): void
    {
        $articles = Article::whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            $article->reactions()->delete();
            $article->tags()->detach();
            $article->delete();
        }
    }
}
