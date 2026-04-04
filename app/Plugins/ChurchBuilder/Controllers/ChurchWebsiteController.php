<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Requests\UpdateChurchWebsiteRequest;
use App\Plugins\ChurchBuilder\Services\ChurchLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Website Builder Controller for Church Admins
 * Provides tabbed interface for managing church website
 */
class ChurchWebsiteController extends Controller
{
    public function __construct(
        private ChurchLoader $loader
    ) {}

    /**
     * GET /api/churches/{church}/website
     * Get church data for website builder dashboard
     */
    public function show(Church $church): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        return response()->json([
            'church' => $this->loader->loadForWebsiteBuilder($church)
        ]);
    }

    /**
     * PUT /api/churches/{church}/website/general
     * Update general settings (Tab 1)
     */
    public function updateGeneral(Church $church, UpdateChurchWebsiteRequest $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $church->update($request->validated());

        return response()->json([
            'church' => $this->loader->loadForWebsiteBuilder($church),
            'message' => 'General settings updated successfully'
        ]);
    }

    /**
     * PUT /api/churches/{church}/website/about
     * Update about & history (Tab 2)
     */
    public function updateAbout(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'history' => 'nullable|string|max:10000',
            'mission_statement' => 'nullable|string|max:1000',
            'vision_statement' => 'nullable|string|max:1000',
            'year_founded' => 'nullable|integer|min:1000|max:' . date('Y'),
        ]);

        $church->update($validated);

        return response()->json([
            'church' => $this->loader->loadForWebsiteBuilder($church),
            'message' => 'About section updated successfully'
        ]);
    }

    /**
     * PUT /api/churches/{church}/website/appearance
     * Update appearance settings (Tab 3)
     */
    public function updateAppearance(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $church->update($validated);

        return response()->json([
            'church' => $this->loader->loadForWebsiteBuilder($church),
            'message' => 'Appearance updated successfully'
        ]);
    }

    /**
     * POST /api/churches/{church}/website/logo
     * Upload logo (Tab 3)
     */
    public function uploadLogo(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048'
        ]);

        // Delete old logo
        if ($church->logo) {
            Storage::disk('public')->delete($church->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store('churches/logos', 'public');
        $church->update(['logo' => $path]);

        return response()->json([
            'logo_url' => $church->logo_url,
            'message' => 'Logo uploaded successfully'
        ]);
    }

    /**
     * POST /api/churches/{church}/website/cover
     * Upload cover photo (Tab 3)
     */
    public function uploadCoverPhoto(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $request->validate([
            'cover_photo' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120'
        ]);

        // Delete old cover photo
        if ($church->cover_photo) {
            Storage::disk('public')->delete($church->cover_photo);
        }

        // Store new cover photo
        $path = $request->file('cover_photo')->store('churches/covers', 'public');
        $church->update(['cover_photo' => $path]);

        return response()->json([
            'cover_photo_url' => $church->cover_photo_url,
            'message' => 'Cover photo uploaded successfully'
        ]);
    }

    /**
     * PUT /api/churches/{church}/website/seo
     * Update SEO & social media settings (Tab 4)
     */
    public function updateSeo(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
        ]);

        $church->update($validated);

        return response()->json([
            'church' => $this->loader->loadForWebsiteBuilder($church),
            'message' => 'SEO & social media settings updated successfully'
        ]);
    }

    /**
     * POST /api/churches/{church}/website/documents
     * Upload document (Tab 2)
     */
    public function uploadDocument(Church $church, Request $request): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB
            'title' => 'required|string|max:100',
        ]);

        $file = $request->file('document');
        $path = $file->store('churches/documents', 'public');

        // Add to documents array
        $documents = $church->documents ?? [];
        $documents[] = [
            'title' => $request->input('title'),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toISOString(),
        ];

        $church->update(['documents' => $documents]);

        return response()->json([
            'documents' => $documents,
            'message' => 'Document uploaded successfully'
        ]);
    }

    /**
     * DELETE /api/churches/{church}/website/documents/{index}
     * Remove document (Tab 2)
     */
    public function deleteDocument(Church $church, int $index): JsonResponse
    {
        Gate::authorize('manageWebsite', $church);

        $documents = $church->documents ?? [];

        if (!isset($documents[$index])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        // Delete file
        Storage::disk('public')->delete($documents[$index]['path']);

        // Remove from array
        unset($documents[$index]);
        $documents = array_values($documents); // Re-index

        $church->update(['documents' => $documents]);

        return response()->json([
            'documents' => $documents,
            'message' => 'Document deleted successfully'
        ]);
    }
}