<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChurchSettings;
use App\Models\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get all church settings grouped by category
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $settings = ChurchSettings::forChurch($churchId)
            ->get()
            ->groupBy('category');

        $categories = [
            'general' => $settings->get('general', collect())->pluck('value', 'key'),
            'contact' => $settings->get('contact', collect())->pluck('value', 'key'),
            'social' => $settings->get('social', collect())->pluck('value', 'key'),
            'worship' => $settings->get('worship', collect())->pluck('value', 'key'),
            'events' => $settings->get('events', collect())->pluck('value', 'key'),
            'content' => $settings->get('content', collect())->pluck('value', 'key'),
            'notifications' => $settings->get('notifications', collect())->pluck('value', 'key'),
            'security' => $settings->get('security', collect())->pluck('value', 'key'),
            'integrations' => $settings->get('integrations', collect())->pluck('value', 'key'),
            'advanced' => $settings->get('advanced', collect())->pluck('value', 'key'),
        ];

        return response()->json([
            'settings' => $categories,
        ]);
    }

    /**
     * Update general church settings
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'church_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'denomination' => 'nullable|string|max:100',
            'pastor_name' => 'nullable|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'timezone' => 'required|string|max:50',
            'language' => 'required|string|max:5',
            'currency' => 'required|string|max:3',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|in:12,24',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('general', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'General settings updated successfully.',
        ]);
    }

    /**
     * Update contact information
     */
    public function updateContact(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'office_hours' => 'nullable|array',
            'office_hours.*' => 'nullable|array',
            'office_hours.*.open' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'office_hours.*.close' => 'nullable|string|regex:/^\d{2}:\d{2}$/',
            'office_hours.*.closed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('contact', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Contact information updated successfully.',
        ]);
    }

    /**
     * Update social media links
     */
    public function updateSocial(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'podcast_url' => 'nullable|url|max:255',
            'spotify_url' => 'nullable|url|max:255',
            'apple_podcasts_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('social', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Social media settings updated successfully.',
        ]);
    }

    /**
     * Update worship service settings
     */
    public function updateWorship(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_times' => 'required|array',
            'service_times.*.name' => 'required|string|max:100',
            'service_times.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'service_times.*.time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'service_times.*.description' => 'nullable|string|max:500',
            'live_streaming_enabled' => 'nullable|boolean',
            'live_streaming_url' => 'nullable|url|max:255',
            'online_giving_enabled' => 'nullable|boolean',
            'online_giving_url' => 'nullable|url|max:255',
            'prayer_request_enabled' => 'nullable|boolean',
            'prayer_request_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('worship', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Worship settings updated successfully.',
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_notifications_enabled' => 'nullable|boolean',
            'sms_notifications_enabled' => 'nullable|boolean',
            'push_notifications_enabled' => 'nullable|boolean',
            'notification_from_email' => 'nullable|email|max:255',
            'notification_from_name' => 'nullable|string|max:255',
            'weekly_digest_enabled' => 'nullable|boolean',
            'event_reminders_enabled' => 'nullable|boolean',
            'prayer_request_notifications' => 'nullable|boolean',
            'new_member_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('notifications', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully.',
        ]);
    }

    /**
     * Update security settings
     */
    public function updateSecurity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'registration_enabled' => 'nullable|boolean',
            'approval_required' => 'nullable|boolean',
            'email_verification_required' => 'nullable|boolean',
            'password_min_length' => 'nullable|integer|min:6|max:50',
            'password_require_uppercase' => 'nullable|boolean',
            'password_require_lowercase' => 'nullable|boolean',
            'password_require_numbers' => 'nullable|boolean',
            'password_require_symbols' => 'nullable|boolean',
            'session_timeout' => 'nullable|integer|min:15|max:1440',
            'max_login_attempts' => 'nullable|integer|min:3|max:20',
            'lockout_duration' => 'nullable|integer|min:5|max:60',
            'two_factor_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('security', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Security settings updated successfully.',
        ]);
    }

    /**
     * Update integration settings
     */
    public function updateIntegrations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'mailchimp_api_key' => 'nullable|string|max:255',
            'mailchimp_list_id' => 'nullable|string|max:50',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_client_secret' => 'nullable|string|max:255',
            'google_maps_api_key' => 'nullable|string|max:255',
            'youtube_api_key' => 'nullable|string|max:255',
            'zoom_api_key' => 'nullable|string|max:255',
            'zoom_api_secret' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->updateSettings('integrations', $request->validated(), $request->user()->church_id);

        return response()->json([
            'success' => true,
            'message' => 'Integration settings updated successfully.',
        ]);
    }

    /**
     * Get system-wide settings (super admin only)
     */
    public function getSystemSettings(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $settings = SystemSettings::all()->pluck('value', 'key');

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Update system-wide settings (super admin only)
     */
    public function updateSystemSettings(Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('settings') as $key => $value) {
            SystemSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Clear settings cache
        Cache::tags(['settings'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully.',
        ]);
    }

    /**
     * Export all church settings
     */
    public function export(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        $settings = ChurchSettings::forChurch($churchId)->get();
        $grouped = $settings->groupBy('category')->map(function ($items) {
            return $items->pluck('value', 'key');
        });

        $export = [
            'version' => '1.0',
            'exported_at' => now()->toISOString(),
            'church_id' => $churchId,
            'settings' => $grouped,
        ];

        return response()->json([
            'export' => $export,
            'filename' => "church-settings-{$churchId}-" . now()->format('Y-m-d') . '.json',
        ]);
    }

    /**
     * Import church settings
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings_file' => 'required|file|mimes:json',
            'overwrite_existing' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $content = file_get_contents($request->file('settings_file')->getPathname());
            $data = json_decode($content, true);

            if (!$data || !isset($data['version']) || !isset($data['settings'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid settings file format.',
                ], 422);
            }

            $churchId = $request->user()->church_id;
            $overwrite = $request->input('overwrite_existing', false);

            foreach ($data['settings'] as $category => $settings) {
                foreach ($settings as $key => $value) {
                    if ($overwrite) {
                        ChurchSettings::updateOrCreate(
                            ['church_id' => $churchId, 'category' => $category, 'key' => $key],
                            ['value' => $value]
                        );
                    } else {
                        ChurchSettings::firstOrCreate(
                            ['church_id' => $churchId, 'category' => $category, 'key' => $key],
                            ['value' => $value]
                        );
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*' => 'string|in:general,contact,social,worship,events,content,notifications,security,integrations,advanced',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $categories = $request->input('categories');

        ChurchSettings::where('church_id', $churchId)
            ->whereIn('category', $categories)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Settings reset to defaults successfully.',
        ]);
    }

    /**
     * Helper method to update settings by category
     */
    private function updateSettings(string $category, array $settings, int $churchId): void
    {
        foreach ($settings as $key => $value) {
            ChurchSettings::updateOrCreate(
                ['church_id' => $churchId, 'category' => $category, 'key' => $key],
                ['value' => $value]
            );
        }
    }
}