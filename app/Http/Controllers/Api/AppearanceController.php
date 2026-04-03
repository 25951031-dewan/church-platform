<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CssTheme;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppearanceController extends Controller
{
    /**
     * Get all appearance settings including themes.
     */
    public function index(): JsonResponse
    {
        $setting = Setting::first();
        $themes = CssTheme::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'default_theme' => $setting->default_theme ?? 'light',
                'themes' => $themes,
                'custom_css' => $setting->custom_css ?? '',
                'custom_js' => $setting->custom_js ?? '',
                'primary_color' => $setting->primary_color ?? '#4F46E5',
                'secondary_color' => $setting->secondary_color ?? '#7C3AED',
            ],
        ]);
    }

    /**
     * Update appearance settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_theme' => 'sometimes|string|in:light,dark,custom',
            'custom_css' => 'nullable|string|max:50000',
            'custom_js' => 'nullable|string|max:50000',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
        ]);

        $setting = Setting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = Setting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appearance settings updated.',
            'data' => $setting->fresh(),
        ]);
    }

    /**
     * List CSS themes.
     */
    public function themes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CssTheme::orderBy('name')->get(),
        ]);
    }

    /**
     * Create or update a CSS theme.
     */
    public function saveTheme(Request $request, ?CssTheme $theme = null): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_dark' => 'boolean',
            'is_default' => 'boolean',
            'colors' => 'required|array',
            'colors.primary' => 'required|string',
            'colors.primary_light' => 'nullable|string',
            'colors.on_primary' => 'nullable|string',
            'colors.accent' => 'nullable|string',
            'colors.background' => 'required|string',
            'colors.background_alt' => 'nullable|string',
            'colors.text_primary' => 'required|string',
            'colors.text_secondary' => 'nullable|string',
            'colors.border' => 'nullable|string',
            'colors.header_bg' => 'nullable|string',
            'colors.header_text' => 'nullable|string',
            'colors.footer_bg' => 'nullable|string',
            'colors.footer_text' => 'nullable|string',
            'custom_css' => 'nullable|string|max:50000',
        ]);

        if ($request->input('is_default')) {
            CssTheme::where('is_default', true)->update(['is_default' => false]);
        }

        $validated['created_by'] = auth()->id();

        if ($theme && $theme->exists) {
            $theme->update($validated);
        } else {
            $theme = CssTheme::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Theme saved successfully.',
            'data' => $theme->fresh(),
        ]);
    }

    /**
     * Delete a CSS theme.
     */
    public function deleteTheme(CssTheme $theme): JsonResponse
    {
        $theme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Theme deleted.',
        ]);
    }

    /**
     * Generate favicon from uploaded image.
     */
    public function generateFavicon(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120',
        ]);

        $file = $request->file('image');
        $path = $file->store('settings/favicons', 'public');

        $setting = Setting::first();
        if ($setting) {
            if ($setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $setting->update(['favicon' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Favicon generated.',
            'data' => ['path' => Storage::url($path)],
        ]);
    }
}
