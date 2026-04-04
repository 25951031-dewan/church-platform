<?php

namespace App\Plugins\Marketplace\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Marketplace\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->topLevel()
            ->with(['children' => function ($q) {
                $q->active()->ordered();
            }])
            ->ordered()
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load(['children' => function ($q) {
            $q->active()->ordered();
        }]);

        $category->loadCount(['listings' => function ($q) {
            $q->active();
        }]);

        return response()->json([
            'category' => $category,
        ]);
    }
}
