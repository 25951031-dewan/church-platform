<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $tags = Tag::query()
            ->withCount('articles')
            ->orderBy('name')
            ->get();

        return response()->json(['tags' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manageTags', Article::class);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        $tag = Tag::create($data);

        return response()->json(['tag' => $tag], 201);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('manageTags', Article::class);

        $tag->articles()->detach();
        $tag->delete();

        return response()->json(null, 204);
    }
}
