<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSettings;
use App\Models\CustomCss;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AppearanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get current theme and appearance settings
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $themeSettings = ThemeSettings::forChurch($churchId)->first() ?? new ThemeSettings();
        $customCss = CustomCss::forChurch($churchId)->first();

        return response()->json([
            'theme_settings' => [
                'theme' => $themeSettings->theme ?? 'default',
                'color_scheme' => $themeSettings->color_scheme ?? 'dark',
                'primary_color' => $themeSettings->primary_color ?? '#3B82F6',
                'secondary_color' => $themeSettings->secondary_color ?? '#10B981',
                'accent_color' => $themeSettings->accent_color ?? '#F59E0B',
                'background_color' => $themeSettings->background_color ?? '#0C0E12',
                'surface_color' => $themeSettings->surface_color ?? '#161920',
                'text_color' => $themeSettings->text_color ?? '#FFFFFF',
                'muted_text_color' => $themeSettings->muted_text_color ?? '#9CA3AF',
                'border_color' => $themeSettings->border_color ?? '#374151',
                'logo_url' => $themeSettings->logo_url,
                'favicon_url' => $themeSettings->favicon_url,
                'font_family' => $themeSettings->font_family ?? 'Inter',
                'font_size_scale' => $themeSettings->font_size_scale ?? '1.0',
                'border_radius' => $themeSettings->border_radius ?? '0.5rem',
                'shadow_style' => $themeSettings->shadow_style ?? 'soft',
                'animation_speed' => $themeSettings->animation_speed ?? 'normal',
                'custom_properties' => $themeSettings->custom_properties ?? [],
            ],
            'custom_css' => [
                'global_css' => $customCss?->global_css,
                'admin_css' => $customCss?->admin_css,
                'mobile_css' => $customCss?->mobile_css,
                'print_css' => $customCss?->print_css,
            ],
        ]);
    }

    /**
     * Update theme settings
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'nullable|string|in:default,modern,classic,minimal',
            'color_scheme' => 'nullable|string|in:light,dark,auto',
            'primary_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'surface_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'text_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'muted_text_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'border_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'font_family' => 'nullable|string|in:Inter,Roboto,Open Sans,Source Sans Pro,Lato,Montserrat,Nunito',
            'font_size_scale' => 'nullable|string|in:0.875,1.0,1.125,1.25',
            'border_radius' => 'nullable|string|in:0,0.25rem,0.5rem,0.75rem,1rem',
            'shadow_style' => 'nullable|string|in:none,soft,medium,strong',
            'animation_speed' => 'nullable|string|in:none,slow,normal,fast',
            'custom_properties' => 'nullable|array',
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

        $themeSettings = ThemeSettings::updateOrCreate(
            ['church_id' => $churchId],
            $data
        );

        return response()->json([
            'success' => true,
            'theme_settings' => $themeSettings,
            'message' => 'Theme settings updated successfully.',
        ]);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
            'type' => 'nullable|string|in:logo,favicon',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $type = $request->input('type', 'logo');

        try {
            $file = $request->file('logo');
            $filename = time() . '_' . $type . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("themes/church-{$churchId}/assets", $filename, 'public');
            $url = Storage::url($path);

            // Update theme settings
            $themeSettings = ThemeSettings::firstOrCreate(['church_id' => $churchId]);
            if ($type === 'logo') {
                $themeSettings->logo_url = $url;
            } else {
                $themeSettings->favicon_url = $url;
            }
            $themeSettings->save();

            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => ucfirst($type) . ' uploaded successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update custom CSS
     */
    public function updateCustomCss(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_css' => 'nullable|string|max:100000',
            'admin_css' => 'nullable|string|max:50000',
            'mobile_css' => 'nullable|string|max:50000',
            'print_css' => 'nullable|string|max:25000',
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

        $customCss = CustomCss::updateOrCreate(
            ['church_id' => $churchId],
            $data
        );

        return response()->json([
            'success' => true,
            'custom_css' => $customCss,
            'message' => 'Custom CSS updated successfully.',
        ]);
    }

    /**
     * Get available themes
     */
    public function themes(): JsonResponse
    {
        $themes = [
            'default' => [
                'name' => 'Default Theme',
                'description' => 'Clean and modern church platform design',
                'preview' => '/images/themes/default-preview.jpg',
                'color_schemes' => ['light', 'dark'],
            ],
            'modern' => [
                'name' => 'Modern Theme',
                'description' => 'Contemporary design with bold typography',
                'preview' => '/images/themes/modern-preview.jpg',
                'color_schemes' => ['light', 'dark'],
            ],
            'classic' => [
                'name' => 'Classic Theme',
                'description' => 'Traditional church design with elegant details',
                'preview' => '/images/themes/classic-preview.jpg',
                'color_schemes' => ['light', 'dark'],
            ],
            'minimal' => [
                'name' => 'Minimal Theme',
                'description' => 'Clean and minimal design focusing on content',
                'preview' => '/images/themes/minimal-preview.jpg',
                'color_schemes' => ['light', 'dark'],
            ],
        ];

        return response()->json([
            'themes' => $themes,
        ]);
    }

    /**
     * Get color palettes
     */
    public function colorPalettes(): JsonResponse
    {
        $palettes = [
            'blue' => [
                'name' => 'Ocean Blue',
                'primary' => '#3B82F6',
                'secondary' => '#10B981',
                'accent' => '#F59E0B',
            ],
            'purple' => [
                'name' => 'Royal Purple',
                'primary' => '#8B5CF6',
                'secondary' => '#06B6D4',
                'accent' => '#EF4444',
            ],
            'green' => [
                'name' => 'Nature Green',
                'primary' => '#10B981',
                'secondary' => '#3B82F6',
                'accent' => '#F59E0B',
            ],
            'red' => [
                'name' => 'Warm Red',
                'primary' => '#EF4444',
                'secondary' => '#8B5CF6',
                'accent' => '#10B981',
            ],
            'orange' => [
                'name' => 'Sunset Orange',
                'primary' => '#F59E0B',
                'secondary' => '#EF4444',
                'accent' => '#10B981',
            ],
        ];

        return response()->json([
            'palettes' => $palettes,
        ]);
    }

    /**
     * Preview theme changes
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme_settings' => 'required|array',
            'custom_css' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // In a real implementation, this would generate a preview token
        // and create a temporary preview environment
        $previewToken = bin2hex(random_bytes(16));

        return response()->json([
            'success' => true,
            'preview_token' => $previewToken,
            'preview_url' => route('theme.preview', ['token' => $previewToken]),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Reset theme to default
     */
    public function reset(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        // Reset theme settings to default
        $defaults = [
            'theme' => 'default',
            'color_scheme' => 'dark',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
            'accent_color' => '#F59E0B',
            'background_color' => '#0C0E12',
            'surface_color' => '#161920',
            'text_color' => '#FFFFFF',
            'muted_text_color' => '#9CA3AF',
            'border_color' => '#374151',
            'font_family' => 'Inter',
            'font_size_scale' => '1.0',
            'border_radius' => '0.5rem',
            'shadow_style' => 'soft',
            'animation_speed' => 'normal',
            'custom_properties' => [],
        ];

        ThemeSettings::updateOrCreate(
            ['church_id' => $churchId],
            array_merge($defaults, ['church_id' => $churchId])
        );

        // Clear custom CSS
        CustomCss::where('church_id', $churchId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Theme settings reset to default successfully.',
        ]);
    }

    /**
     * Export theme settings
     */
    public function export(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $themeSettings = ThemeSettings::forChurch($churchId)->first();
        $customCss = CustomCss::forChurch($churchId)->first();

        $export = [
            'version' => '1.0',
            'exported_at' => now()->toISOString(),
            'theme_settings' => $themeSettings?->toArray(),
            'custom_css' => $customCss?->toArray(),
        ];

        return response()->json([
            'export' => $export,
            'filename' => "church-theme-{$churchId}-" . now()->format('Y-m-d') . '.json',
        ]);
    }

    /**
     * Import theme settings
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme_file' => 'required|file|mimes:json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $content = file_get_contents($request->file('theme_file')->getPathname());
            $data = json_decode($content, true);

            if (!$data || !isset($data['version'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid theme file format.',
                ], 422);
            }

            $churchId = $request->user()->church_id;

            // Import theme settings
            if (isset($data['theme_settings'])) {
                $themeData = $data['theme_settings'];
                unset($themeData['id'], $themeData['church_id'], $themeData['created_at'], $themeData['updated_at']);
                $themeData['church_id'] = $churchId;

                ThemeSettings::updateOrCreate(
                    ['church_id' => $churchId],
                    $themeData
                );
            }

            // Import custom CSS
            if (isset($data['custom_css'])) {
                $cssData = $data['custom_css'];
                unset($cssData['id'], $cssData['church_id'], $cssData['created_at'], $cssData['updated_at']);
                $cssData['church_id'] = $churchId;

                CustomCss::updateOrCreate(
                    ['church_id' => $churchId],
                    $cssData
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Theme settings imported successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import theme: ' . $e->getMessage(),
            ], 500);
        }
    }
}