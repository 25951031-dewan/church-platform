<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sermon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SermonController extends Controller
{
    /**
     * Display a paginated listing of all sermons.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sermon::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('speaker', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('scripture_reference', 'like', "%{$search}%");
            });
        }

        if ($request->has('speaker')) {
            $query->where('speaker', $request->speaker);
        }

        if ($request->has('series')) {
            $query->where('series', $request->series);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $sermons = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $sermons,
        ]);
    }

    /**
     * Display featured sermons.
     */
    public function featured(Request $request): JsonResponse
    {
        $sermons = Sermon::where('is_featured', true)
            ->where('is_published', true)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $sermons,
        ]);
    }

    /**
     * Display the specified sermon by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $sermon = Sermon::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $sermon,
        ]);
    }

    /**
     * Store a newly created sermon.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:10000',
            'content'             => 'nullable|string',
            'speaker'             => 'required|string|max:255',
            'scripture_reference' => 'nullable|string|max:500',
            'series'              => 'nullable|string|max:255',
            'category'            => 'nullable|string|max:255',
            'sermon_date'         => 'nullable|date',
            'duration'            => 'nullable|string|max:20',
            'video_url'           => 'nullable|url|max:500',
            'audio_url'           => 'nullable|url|max:500',
            'thumbnail'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'pdf_notes'           => 'nullable|file|mimes:pdf|max:20480',
            'is_featured'         => 'nullable|boolean',
            'is_published'        => 'nullable|boolean',
            'tags'                => 'nullable|string|max:1000',
        ]);

        // Auto-generate slug from title
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Sermon::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('sermons/thumbnails', 'public');
        }

        if ($request->hasFile('pdf_notes')) {
            $validated['pdf_notes'] = $request->file('pdf_notes')
                ->store('sermons/notes', 'public');
        }

        $sermon = Sermon::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sermon created successfully.',
            'data'    => $sermon,
        ], 201);
    }

    /**
     * Update the specified sermon.
     */
    public function update(Request $request, Sermon $sermon): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'sometimes|required|string|max:255',
            'description'         => 'nullable|string|max:10000',
            'content'             => 'nullable|string',
            'speaker'             => 'sometimes|required|string|max:255',
            'scripture_reference' => 'nullable|string|max:500',
            'series'              => 'nullable|string|max:255',
            'category'            => 'nullable|string|max:255',
            'sermon_date'         => 'nullable|date',
            'duration'            => 'nullable|string|max:20',
            'video_url'           => 'nullable|url|max:500',
            'audio_url'           => 'nullable|url|max:500',
            'thumbnail'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'pdf_notes'           => 'nullable|file|mimes:pdf|max:20480',
            'is_featured'         => 'nullable|boolean',
            'is_published'        => 'nullable|boolean',
            'tags'                => 'nullable|string|max:1000',
        ]);

        // Re-generate slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $sermon->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Sermon::where('slug', $slug)->where('id', '!=', $sermon->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('thumbnail')) {
            if ($sermon->thumbnail) {
                Storage::disk('public')->delete($sermon->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('sermons/thumbnails', 'public');
        }

        if ($request->hasFile('pdf_notes')) {
            if ($sermon->pdf_notes) {
                Storage::disk('public')->delete($sermon->pdf_notes);
            }
            $validated['pdf_notes'] = $request->file('pdf_notes')
                ->store('sermons/notes', 'public');
        }

        $sermon->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sermon updated successfully.',
            'data'    => $sermon->fresh(),
        ]);
    }

    /**
     * Remove the specified sermon.
     */
    public function destroy(Sermon $sermon): JsonResponse
    {
        if ($sermon->thumbnail) {
            Storage::disk('public')->delete($sermon->thumbnail);
        }

        if ($sermon->pdf_notes) {
            Storage::disk('public')->delete($sermon->pdf_notes);
        }

        $sermon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sermon deleted successfully.',
        ]);
    }
}
