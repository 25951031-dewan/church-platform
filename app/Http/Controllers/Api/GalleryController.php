<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Display a paginated listing of galleries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Gallery::withCount('images')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $galleries = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $galleries,
        ]);
    }

    /**
     * Display the specified gallery with its images.
     */
    public function show(Gallery $gallery): JsonResponse
    {
        $gallery->load('images');

        return response()->json([
            'success' => true,
            'data'    => $gallery,
        ]);
    }

    /**
     * Store a newly created gallery.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category'    => 'nullable|string|max:255',
            'event_date'  => 'nullable|date',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_published' => 'nullable|boolean',
            'images'      => 'nullable|array|max:50',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('galleries/covers', 'public');
        }

        // Remove images from validated data before creating gallery
        $imageFiles = $request->file('images', []);
        unset($validated['images']);

        $gallery = Gallery::create($validated);

        // Upload and attach images
        if (!empty($imageFiles)) {
            foreach ($imageFiles as $index => $imageFile) {
                $path = $imageFile->store('galleries/' . $gallery->id, 'public');
                GalleryImage::create([
                    'gallery_id'    => $gallery->id,
                    'image_path'    => $path,
                    'original_name' => $imageFile->getClientOriginalName(),
                    'file_size'     => $imageFile->getSize(),
                    'sort_order'    => $index,
                ]);
            }
        }

        $gallery->load('images');

        return response()->json([
            'success' => true,
            'message' => 'Gallery created successfully.',
            'data'    => $gallery,
        ], 201);
    }

    /**
     * Add images to an existing gallery.
     */
    public function addImage(Request $request, Gallery $gallery): JsonResponse
    {
        $request->validate([
            'images'   => 'required|array|min:1|max:50',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'captions'   => 'nullable|array',
            'captions.*' => 'nullable|string|max:500',
        ]);

        $maxOrder = $gallery->images()->max('sort_order') ?? -1;
        $uploadedImages = [];

        foreach ($request->file('images') as $index => $imageFile) {
            $path = $imageFile->store('galleries/' . $gallery->id, 'public');

            $caption = null;
            if ($request->has('captions') && isset($request->captions[$index])) {
                $caption = $request->captions[$index];
            }

            $galleryImage = GalleryImage::create([
                'gallery_id'    => $gallery->id,
                'image_path'    => $path,
                'original_name' => $imageFile->getClientOriginalName(),
                'file_size'     => $imageFile->getSize(),
                'caption'       => $caption,
                'sort_order'    => $maxOrder + $index + 1,
            ]);

            $uploadedImages[] = $galleryImage;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' image(s) added to the gallery.',
            'data'    => $uploadedImages,
        ]);
    }

    /**
     * Remove a single image from a gallery.
     */
    public function removeImage(GalleryImage $galleryImage): JsonResponse
    {
        if ($galleryImage->image_path) {
            Storage::disk('public')->delete($galleryImage->image_path);
        }

        $galleryImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image removed from gallery successfully.',
        ]);
    }

    /**
     * Remove the specified gallery and all its images.
     */
    public function destroy(Gallery $gallery): JsonResponse
    {
        // Delete all associated image files
        foreach ($gallery->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        // Delete the cover image
        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }

        // Delete the gallery directory if it exists
        Storage::disk('public')->deleteDirectory('galleries/' . $gallery->id);

        // Delete the gallery (cascade will handle gallery_images if set up)
        $gallery->images()->delete();
        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gallery and all images deleted successfully.',
        ]);
    }
}
