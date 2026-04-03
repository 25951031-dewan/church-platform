<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleStudy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BibleStudyController extends Controller
{
    /**
     * Display a paginated listing of bible studies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BibleStudy::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('scripture_reference', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN));
        }

        $studies = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $studies,
        ]);
    }

    /**
     * Display featured bible studies.
     */
    public function featured(Request $request): JsonResponse
    {
        $studies = BibleStudy::where('is_featured', true)
            ->where('is_published', true)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $studies,
        ]);
    }

    /**
     * Display the specified bible study.
     */
    public function show(BibleStudy $bibleStudy): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $bibleStudy,
        ]);
    }

    /**
     * Store a newly created bible study.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:10000',
            'content'             => 'required|string',
            'scripture_reference' => 'nullable|string|max:500',
            'category'            => ['required', Rule::in(['old-testament', 'new-testament', 'topical', 'devotional'])],
            'difficulty'          => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_minutes'    => 'nullable|integer|min:1',
            'author'              => 'nullable|string|max:255',
            'cover_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'attachment'          => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'is_featured'         => 'nullable|boolean',
            'is_published'        => 'nullable|boolean',
            'tags'                => 'nullable|string|max:1000',
        ]);

        // Auto-generate slug
        $slug = Str::slug($validated['title']);
        $original = $slug;
        $counter = 1;
        while (BibleStudy::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('bible-studies/covers', 'public');
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')
                ->store('bible-studies/attachments', 'public');
        }

        $study = BibleStudy::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bible study created successfully.',
            'data'    => $study,
        ], 201);
    }

    /**
     * Update the specified bible study.
     */
    public function update(Request $request, BibleStudy $bibleStudy): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'sometimes|required|string|max:255',
            'description'         => 'nullable|string|max:10000',
            'content'             => 'sometimes|required|string',
            'scripture_reference' => 'nullable|string|max:500',
            'category'            => ['sometimes', 'required', Rule::in(['old-testament', 'new-testament', 'topical', 'devotional'])],
            'difficulty'          => ['sometimes', 'required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_minutes'    => 'nullable|integer|min:1',
            'author'              => 'nullable|string|max:255',
            'cover_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'attachment'          => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'is_featured'         => 'nullable|boolean',
            'is_published'        => 'nullable|boolean',
            'tags'                => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($bibleStudy->cover_image) {
                Storage::disk('public')->delete($bibleStudy->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')
                ->store('bible-studies/covers', 'public');
        }

        if ($request->hasFile('attachment')) {
            if ($bibleStudy->attachment) {
                Storage::disk('public')->delete($bibleStudy->attachment);
            }
            $validated['attachment'] = $request->file('attachment')
                ->store('bible-studies/attachments', 'public');
        }

        $bibleStudy->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bible study updated successfully.',
            'data'    => $bibleStudy->fresh(),
        ]);
    }

    /**
     * Remove the specified bible study.
     */
    public function destroy(BibleStudy $bibleStudy): JsonResponse
    {
        if ($bibleStudy->cover_image) {
            Storage::disk('public')->delete($bibleStudy->cover_image);
        }

        if ($bibleStudy->attachment) {
            Storage::disk('public')->delete($bibleStudy->attachment);
        }

        $bibleStudy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bible study deleted successfully.',
        ]);
    }
}
