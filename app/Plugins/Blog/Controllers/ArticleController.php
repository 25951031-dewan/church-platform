<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Requests\ModifyArticle;
use App\Plugins\Blog\Services\ArticleLoader;
use App\Plugins\Blog\Services\CrupdateArticle;
use App\Plugins\Blog\Services\DeleteArticles;
use App\Plugins\Blog\Services\PaginateArticles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController
{
    public function __construct(
        private ArticleLoader $loader,
        private PaginateArticles $paginator,
        private CrupdateArticle $crupdater,
        private DeleteArticles $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $results = $this->paginator->execute($request->all());

        return response()->json(['pagination' => $results]);
    }

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        $article->incrementView();
        $article->refresh();

        return response()->json($this->loader->loadForDetail($article));
    }

    public function store(ModifyArticle $request): JsonResponse
    {
        Gate::authorize('create', Article::class);

        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        // Check publish permission if setting status to published/scheduled
        if (in_array($data['status'] ?? 'draft', ['published', 'scheduled'])) {
            Gate::authorize('publish', Article::class);
        }

        $article = $this->crupdater->execute(new Article(), $data);

        return response()->json(['article' => $article], 201);
    }

    public function update(ModifyArticle $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        $data = $request->validated();

        // Check publish permission if changing status to published/scheduled
        if (in_array($data['status'] ?? $article->status, ['published', 'scheduled'])
            && ($data['status'] ?? null) !== null
            && $data['status'] !== $article->status) {
            Gate::authorize('publish', Article::class);
        }

        $article = $this->crupdater->execute($article, $data);

        return response()->json(['article' => $article]);
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('delete', $article);

        $this->deleter->execute([$article->id]);

        return response()->json(null, 204);
    }
}
