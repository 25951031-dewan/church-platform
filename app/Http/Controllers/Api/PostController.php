<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a paginated listing of all posts (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('meta_keywords', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->paginate($request->get('per_page', 15));

        return response()->json($posts);
    }

    /**
     * Display a paginated listing of published posts (public).
     */
    public function published(Request $request): JsonResponse
    {
        $query = Post::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->latest('published_at');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $posts = $query->paginate($request->get('per_page', 15));

        return response()->json($posts);
    }

    /**
     * Display featured published posts.
     */
    public function featured(Request $request): JsonResponse
    {
        $posts = Post::where('status', 'published')
            ->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->latest('published_at')
            ->paginate($request->get('per_page', 10));

        return response()->json($posts);
    }

    /**
     * Display the specified post by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $post,
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'excerpt'          => 'nullable|string|max:500',
            'category'         => 'nullable|string|max:255',
            'featured_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'status'           => 'nullable|string|in:draft,published,scheduled',
            'is_featured'      => 'nullable|boolean',
            'published_at'     => 'nullable|date',
            'author_name'      => 'nullable|string|max:255',
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords'    => 'nullable|string|max:500',
            'og_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tags'             => 'nullable|string|max:1000',
        ]);

        // Auto-generate slug from title
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        // Default status to draft
        $validated['status'] = $validated['status'] ?? 'draft';

        // Auto-fill SEO fields if not provided
        if (empty($validated['meta_title'])) {
            $validated['meta_title'] = Str::limit($validated['title'], 70);
        }

        if (empty($validated['meta_description']) && !empty($validated['excerpt'])) {
            $validated['meta_description'] = Str::limit($validated['excerpt'], 160);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('posts/images', 'public');
        }

        if ($request->hasFile('og_image')) {
            $validated['og_image'] = $request->file('og_image')
                ->store('posts/og-images', 'public');
        }

        $post = Post::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'data'    => $post,
        ], 201);
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title'            => 'sometimes|required|string|max:255',
            'content'          => 'sometimes|required|string',
            'excerpt'          => 'nullable|string|max:500',
            'category'         => 'nullable|string|max:255',
            'featured_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'status'           => 'nullable|string|in:draft,published,scheduled',
            'is_featured'      => 'nullable|boolean',
            'published_at'     => 'nullable|date',
            'author_name'      => 'nullable|string|max:255',
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords'    => 'nullable|string|max:500',
            'og_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tags'             => 'nullable|string|max:1000',
        ]);

        // Re-generate slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $post->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')
                ->store('posts/images', 'public');
        }

        if ($request->hasFile('og_image')) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }
            $validated['og_image'] = $request->file('og_image')
                ->store('posts/og-images', 'public');
        }

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data'    => $post->fresh(),
        ]);
    }

    /**
     * Increment the view count for a post by slug.
     */
    public function incrementView(string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->firstOrFail();
        $post->increment('view_count');

        return response()->json([
            'success' => true,
            'view_count' => $post->view_count,
        ]);
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post): JsonResponse
    {
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        if ($post->og_image) {
            Storage::disk('public')->delete($post->og_image);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
