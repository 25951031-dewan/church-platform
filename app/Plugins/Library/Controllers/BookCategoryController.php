<?php

namespace App\Plugins\Library\Controllers;

use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use App\Plugins\Library\Requests\ModifyBookCategory;
use App\Plugins\Library\Services\CrupdateBookCategory;
use App\Plugins\Library\Services\PaginateBookCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class BookCategoryController extends Controller
{
    public function __construct(
        private PaginateBookCategories $paginator,
        private CrupdateBookCategory $crupdate,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Book::class);

        // Only allow include_inactive for users who can manage categories
        if ($request->boolean('include_inactive') && !Gate::allows('manageCategories', Book::class)) {
            $request->merge(['include_inactive' => false]);
        }

        $categories = $this->paginator->execute($request);
        return response()->json(['categories' => $categories]);
    }

    public function store(ModifyBookCategory $request): JsonResponse
    {
        Gate::authorize('manageCategories', Book::class);

        $category = $this->crupdate->execute($request->validated());

        return response()->json(['category' => $category], 201);
    }

    public function update(ModifyBookCategory $request, BookCategory $bookCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Book::class);

        $category = $this->crupdate->execute($request->validated(), $bookCategory);

        return response()->json(['category' => $category]);
    }

    public function destroy(BookCategory $bookCategory): Response
    {
        Gate::authorize('manageCategories', Book::class);

        Book::where('category_id', $bookCategory->id)->update(['category_id' => null]);
        $bookCategory->delete();

        return response()->noContent();
    }
}
