<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Models\ArticleCategory;
use App\Plugins\Blog\Requests\ModifyArticleCategory;
use App\Plugins\Blog\Services\CrupdateArticleCategory;
use App\Plugins\Blog\Services\PaginateArticleCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleCategoryController
{
    public function __construct(
        private PaginateArticleCategories $paginator,
        private CrupdateArticleCategory $crupdater,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $params = $request->all();
        if (!Gate::allows('manageCategories', Article::class)) {
            $params['include_inactive'] = false;
        }

        $categories = $this->paginator->execute($params);

        return response()->json(['categories' => $categories]);
    }

    public function store(ModifyArticleCategory $request): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        $category = $this->crupdater->execute(new ArticleCategory(), $request->validated());

        return response()->json(['category' => $category], 201);
    }

    public function update(ModifyArticleCategory $request, ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        $category = $this->crupdater->execute($articleCategory, $request->validated());

        return response()->json(['category' => $category]);
    }

    public function destroy(ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        // Unlink articles from this category
        Article::where('category_id', $articleCategory->id)->update(['category_id' => null]);
        $articleCategory->delete();

        return response()->json(null, 204);
    }
}
