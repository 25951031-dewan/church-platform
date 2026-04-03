<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileThemeController extends Controller
{
    /**
     * Get mobile theme configuration.
     */
    public function show(): JsonResponse
    {
        $setting = Setting::first();

        $defaultConfig = [
            'enabled' => true,
            'bottom_nav' => [
                ['id' => 'home', 'label' => 'Home', 'icon' => 'fa-home', 'route' => '/', 'enabled' => true],
                ['id' => 'sermons', 'label' => 'Sermons', 'icon' => 'fa-microphone-alt', 'route' => '/sermons', 'enabled' => true],
                ['id' => 'events', 'label' => 'Events', 'icon' => 'fa-calendar', 'route' => '/events', 'enabled' => true],
                ['id' => 'prayers', 'label' => 'Prayers', 'icon' => 'fa-praying-hands', 'route' => '/prayers', 'enabled' => true],
                ['id' => 'more', 'label' => 'More', 'icon' => 'fa-ellipsis-h', 'route' => '/menu', 'enabled' => true],
            ],
            'header_style' => 'compact',
            'card_style' => 'rounded',
            'font_size' => 'medium',
            'enable_pull_refresh' => true,
            'enable_swipe_nav' => true,
            'enable_haptics' => true,
            'splash_screen' => [
                'background_color' => '#4F46E5',
                'text_color' => '#ffffff',
                'show_logo' => true,
                'show_spinner' => true,
            ],
            'quick_actions' => [
                ['id' => 'give', 'label' => 'Give', 'icon' => 'fa-heart', 'action' => 'donate', 'enabled' => true],
                ['id' => 'pray', 'label' => 'Pray', 'icon' => 'fa-praying-hands', 'action' => 'prayer-request', 'enabled' => true],
                ['id' => 'connect', 'label' => 'Connect', 'icon' => 'fa-users', 'action' => 'contact', 'enabled' => true],
                ['id' => 'bible', 'label' => 'Bible', 'icon' => 'fa-book-bible', 'action' => 'bible-studies', 'enabled' => true],
            ],
            'gesture_settings' => [
                'swipe_back' => true,
                'pull_to_refresh' => true,
                'double_tap_top' => true,
            ],
        ];

        $config = $setting && $setting->mobile_theme_config
            ? $setting->mobile_theme_config
            : $defaultConfig;

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $setting->mobile_theme_enabled ?? true,
                'config' => $config,
            ],
        ]);
    }

    /**
     * Update mobile theme configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'config' => 'required|array',
            'config.bottom_nav' => 'array',
            'config.bottom_nav.*.id' => 'required|string',
            'config.bottom_nav.*.label' => 'required|string',
            'config.bottom_nav.*.icon' => 'required|string',
            'config.bottom_nav.*.route' => 'required|string',
            'config.bottom_nav.*.enabled' => 'required|boolean',
            'config.header_style' => 'string|in:compact,standard,large',
            'config.card_style' => 'string|in:rounded,flat,elevated',
            'config.font_size' => 'string|in:small,medium,large',
            'config.enable_pull_refresh' => 'boolean',
            'config.enable_swipe_nav' => 'boolean',
            'config.enable_haptics' => 'boolean',
            'config.splash_screen' => 'array',
            'config.quick_actions' => 'array',
            'config.gesture_settings' => 'array',
        ]);

        $setting = Setting::first();

        $updateData = [
            'mobile_theme_enabled' => $validated['enabled'] ?? true,
            'mobile_theme_config' => $validated['config'],
        ];

        if ($setting) {
            $setting->update($updateData);
        } else {
            $setting = Setting::create($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mobile theme settings saved.',
            'data' => $setting->fresh()->mobile_theme_config,
        ]);
    }

    /**
     * Get PWA manifest configuration.
     */
    public function pwaConfig(): JsonResponse
    {
        $setting = Setting::first();

        return response()->json([
            'success' => true,
            'data' => [
                'pwa_enabled' => $setting->pwa_enabled ?? true,
                'pwa_name' => $setting->pwa_name ?? $setting->church_name ?? 'Church App',
                'pwa_short_name' => $setting->pwa_short_name ?? 'Church',
                'pwa_description' => $setting->pwa_description ?? $setting->description ?? '',
                'pwa_theme_color' => $setting->pwa_theme_color ?? '#4F46E5',
                'pwa_background_color' => $setting->pwa_background_color ?? '#ffffff',
                'pwa_display' => $setting->pwa_display ?? 'standalone',
                'pwa_orientation' => $setting->pwa_orientation ?? 'any',
                'pwa_icon_192' => $setting->pwa_icon_192,
                'pwa_icon_512' => $setting->pwa_icon_512,
            ],
        ]);
    }

    /**
     * Update PWA configuration.
     */
    public function updatePwaConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pwa_enabled' => 'boolean',
            'pwa_name' => 'nullable|string|max:255',
            'pwa_short_name' => 'nullable|string|max:50',
            'pwa_description' => 'nullable|string|max:1000',
            'pwa_theme_color' => 'nullable|string|max:20',
            'pwa_background_color' => 'nullable|string|max:20',
            'pwa_display' => 'string|in:standalone,fullscreen,minimal-ui,browser',
            'pwa_orientation' => 'string|in:any,portrait,landscape',
            'pwa_icon_192' => 'nullable|image|mimes:png|max:2048',
            'pwa_icon_512' => 'nullable|image|mimes:png|max:5120',
        ]);

        $setting = Setting::first();

        if ($request->hasFile('pwa_icon_192')) {
            $validated['pwa_icon_192'] = $request->file('pwa_icon_192')->store('pwa', 'public');
        } else {
            unset($validated['pwa_icon_192']);
        }
        if ($request->hasFile('pwa_icon_512')) {
            $validated['pwa_icon_512'] = $request->file('pwa_icon_512')->store('pwa', 'public');
        } else {
            unset($validated['pwa_icon_512']);
        }

        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = Setting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'PWA settings saved.',
        ]);
    }
}
