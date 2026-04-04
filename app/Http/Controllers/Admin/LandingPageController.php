<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPageSection;
use App\Models\LandingPageSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingPageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get all landing page sections and settings
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $sections = LandingPageSection::forChurch($churchId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'title' => $section->title,
                    'content' => $section->content,
                    'settings' => $section->settings,
                    'is_active' => $section->is_active,
                    'sort_order' => $section->sort_order,
                    'background_image' => $section->background_image,
                    'background_color' => $section->background_color,
                    'text_color' => $section->text_color,
                ];
            });

        $settings = LandingPageSettings::forChurch($churchId)->first() ?? new LandingPageSettings();

        return response()->json([
            'sections' => $sections,
            'settings' => [
                'enabled' => $settings->enabled ?? true,
                'layout' => $settings->layout ?? 'default',
                'header_transparent' => $settings->header_transparent ?? false,
                'footer_style' => $settings->footer_style ?? 'default',
                'seo_title' => $settings->seo_title,
                'seo_description' => $settings->seo_description,
                'custom_css' => $settings->custom_css,
                'custom_js' => $settings->custom_js,
            ],
        ]);
    }

    /**
     * Create a new landing page section
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:hero,about,services,testimonials,gallery,contact,custom',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'background_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $data = $request->validated();
        $data['church_id'] = $churchId;

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            $imagePath = $request->file('background_image')->store(
                "landing-pages/church-{$churchId}/backgrounds", 
                'public'
            );
            $data['background_image'] = $imagePath;
        }

        // Set sort order if not provided
        if (!isset($data['sort_order'])) {
            $maxOrder = LandingPageSection::forChurch($churchId)->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        }

        $section = LandingPageSection::create($data);

        return response()->json([
            'success' => true,
            'section' => $section,
            'message' => 'Landing page section created successfully.',
        ], 201);
    }

    /**
     * Update a landing page section
     */
    public function update(Request $request, LandingPageSection $section): JsonResponse
    {
        $this->authorize('update', $section);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|in:hero,about,services,testimonials,gallery,contact,custom',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'background_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->validated();

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            // Delete old image if exists
            if ($section->background_image) {
                Storage::disk('public')->delete($section->background_image);
            }

            $imagePath = $request->file('background_image')->store(
                "landing-pages/church-{$section->church_id}/backgrounds", 
                'public'
            );
            $data['background_image'] = $imagePath;
        }

        $section->update($data);

        return response()->json([
            'success' => true,
            'section' => $section->fresh(),
            'message' => 'Landing page section updated successfully.',
        ]);
    }

    /**
     * Delete a landing page section
     */
    public function destroy(LandingPageSection $section): JsonResponse
    {
        $this->authorize('delete', $section);

        // Delete associated image if exists
        if ($section->background_image) {
            Storage::disk('public')->delete($section->background_image);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Landing page section deleted successfully.',
        ]);
    }

    /**
     * Reorder landing page sections
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sections' => 'required|array',
            'sections.*.id' => 'required|integer|exists:landing_page_sections,id',
            'sections.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;

        foreach ($request->sections as $sectionData) {
            LandingPageSection::where('id', $sectionData['id'])
                ->where('church_id', $churchId)
                ->update(['sort_order' => $sectionData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Landing page sections reordered successfully.',
        ]);
    }

    /**
     * Update landing page settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'nullable|boolean',
            'layout' => 'nullable|string|in:default,modern,classic',
            'header_transparent' => 'nullable|boolean',
            'footer_style' => 'nullable|string|in:default,minimal,extended',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'custom_css' => 'nullable|string|max:50000',
            'custom_js' => 'nullable|string|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $data = $request->validated();
        $data['church_id'] = $churchId;

        $settings = LandingPageSettings::updateOrCreate(
            ['church_id' => $churchId],
            $data
        );

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'message' => 'Landing page settings updated successfully.',
        ]);
    }

    /**
     * Get available section templates
     */
    public function templates(): JsonResponse
    {
        $templates = [
            'hero' => [
                'name' => 'Hero Section',
                'description' => 'Large banner with title, subtitle, and call-to-action',
                'fields' => ['title', 'subtitle', 'description', 'button_text', 'button_link', 'background_image'],
                'preview' => '/images/templates/hero-preview.jpg',
            ],
            'about' => [
                'name' => 'About Section',
                'description' => 'About our church with image and description',
                'fields' => ['title', 'description', 'image', 'mission_text'],
                'preview' => '/images/templates/about-preview.jpg',
            ],
            'services' => [
                'name' => 'Services Grid',
                'description' => 'Grid of church services and ministries',
                'fields' => ['title', 'services_list', 'description'],
                'preview' => '/images/templates/services-preview.jpg',
            ],
            'testimonials' => [
                'name' => 'Testimonials',
                'description' => 'Member testimonials and stories',
                'fields' => ['title', 'testimonials_list'],
                'preview' => '/images/templates/testimonials-preview.jpg',
            ],
            'gallery' => [
                'name' => 'Photo Gallery',
                'description' => 'Church photos and event highlights',
                'fields' => ['title', 'gallery_images', 'description'],
                'preview' => '/images/templates/gallery-preview.jpg',
            ],
            'contact' => [
                'name' => 'Contact Information',
                'description' => 'Church location, hours, and contact details',
                'fields' => ['title', 'address', 'phone', 'email', 'service_times', 'map_embed'],
                'preview' => '/images/templates/contact-preview.jpg',
            ],
        ];

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Preview landing page
     */
    public function preview(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $sections = LandingPageSection::forChurch($churchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = LandingPageSettings::forChurch($churchId)->first();

        // Generate preview URL (in production this would render the actual page)
        $previewUrl = route('landing.preview', ['church' => $churchId, 'token' => csrf_token()]);

        return response()->json([
            'preview_url' => $previewUrl,
            'sections_count' => $sections->count(),
            'settings' => $settings,
        ]);
    }
}