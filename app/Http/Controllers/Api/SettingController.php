<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display the church settings.
     */
    public function show(): JsonResponse
    {
        $setting = Setting::first();

        if (!$setting) {
            return response()->json([
                'success' => true,
                'message' => 'No settings configured yet.',
                'data'    => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    /**
     * Update the church settings (create if not exists).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'church_name'       => 'sometimes|required|string|max:255',
            'tagline'           => 'nullable|string|max:500',
            'description'       => 'nullable|string|max:5000',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string|max:500',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'zip_code'          => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:255',
            'website_url'       => 'nullable|url|max:500',
            'facebook_url'      => 'nullable|url|max:500',
            'twitter_url'       => 'nullable|url|max:500',
            'instagram_url'     => 'nullable|url|max:500',
            'youtube_url'       => 'nullable|url|max:500',
            'tiktok_url'        => 'nullable|url|max:500',
            'service_times'     => 'nullable|string|max:2000',
            'pastor_name'       => 'nullable|string|max:255',
            'pastor_title'      => 'nullable|string|max:255',
            'about_text'        => 'nullable|string|max:10000',
            'mission_statement' => 'nullable|string|max:2000',
            'vision_statement'  => 'nullable|string|max:2000',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'banner'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'favicon'           => 'nullable|image|mimes:ico,png|max:1024',
            'primary_color'     => 'nullable|string|max:20',
            'secondary_color'   => 'nullable|string|max:20',
            'footer_text'       => 'nullable|string|max:1000',
            'google_maps_embed' => 'nullable|string|max:2000',
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'meta_keywords'     => 'nullable|string|max:500',
            'theme_config'      => 'nullable|json|max:10000',
            'widget_config'     => 'nullable|json|max:10000',
            // Auth providers
            'auth_google_enabled'         => 'nullable|boolean',
            'auth_google_client_id'       => 'nullable|string|max:500',
            'auth_google_client_secret'   => 'nullable|string|max:500',
            'auth_facebook_enabled'       => 'nullable|boolean',
            'auth_facebook_client_id'     => 'nullable|string|max:500',
            'auth_facebook_client_secret' => 'nullable|string|max:500',
            // Storage
            'storage_driver'       => 'nullable|string|in:local,s3',
            'storage_s3_key'       => 'nullable|string|max:500',
            'storage_s3_secret'    => 'nullable|string|max:500',
            'storage_s3_region'    => 'nullable|string|max:50',
            'storage_s3_bucket'    => 'nullable|string|max:255',
            'max_upload_size'      => 'nullable|integer|min:1|max:1024',
            'allowed_file_types'   => 'nullable|string|max:500',
            // Cache
            'cache_driver'         => 'nullable|string|in:file,database,redis,memcached',
            'cache_ttl'            => 'nullable|integer|min:60|max:86400',
            'enable_page_cache'    => 'nullable|boolean',
            'enable_minification'  => 'nullable|boolean',
            'cdn_url'              => 'nullable|string|max:500',
            // Logging
            'log_channel'          => 'nullable|string|in:daily,single,stack,syslog,errorlog',
            'queue_driver'         => 'nullable|string|in:sync,database,redis',
        ]);

        // Don't overwrite secrets if masked placeholder sent
        $secrets = ['auth_google_client_secret', 'auth_facebook_client_secret', 'storage_s3_secret'];
        foreach ($secrets as $secret) {
            if (isset($validated[$secret]) && ($validated[$secret] === '********' || empty($validated[$secret]))) {
                unset($validated[$secret]);
            }
        }

        // Decode JSON strings to arrays for proper storage
        if (isset($validated['theme_config']) && is_string($validated['theme_config'])) {
            $validated['theme_config'] = json_decode($validated['theme_config'], true);
        }
        if (isset($validated['widget_config']) && is_string($validated['widget_config'])) {
            $validated['widget_config'] = json_decode($validated['widget_config'], true);
        }

        $setting = Setting::first();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($setting && $setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')
                ->store('settings', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            if ($setting && $setting->banner) {
                Storage::disk('public')->delete($setting->banner);
            }
            $validated['banner'] = $request->file('banner')
                ->store('settings', 'public');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if ($setting && $setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $validated['favicon'] = $request->file('favicon')
                ->store('settings', 'public');
        }

        if ($setting) {
            $setting->update($validated);
            $message = 'Settings updated successfully.';
        } else {
            $setting = Setting::create($validated);
            $message = 'Settings created successfully.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $setting->fresh(),
        ]);
    }

    /**
     * Get email settings (admin only, returns masked secrets).
     */
    public function emailSettings(): JsonResponse
    {
        $setting = Setting::first();

        return response()->json([
            'success' => true,
            'data'    => $setting ? $setting->getEmailSettings() : [
                'mail_provider' => 'smtp',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'email_contact_notification' => true,
                'email_newsletter_enabled' => true,
                'email_welcome_enabled' => false,
            ],
        ]);
    }

    /**
     * Update email settings.
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_provider'              => 'required|string|in:smtp,mailchimp,sendgrid,mailgun',
            'smtp_host'                  => 'nullable|string|max:255',
            'smtp_port'                  => 'nullable|integer|min:1|max:65535',
            'smtp_username'              => 'nullable|string|max:255',
            'smtp_password'              => 'nullable|string|max:500',
            'smtp_encryption'            => 'nullable|string|in:tls,ssl,none',
            'mail_from_address'          => 'nullable|email|max:255',
            'mail_from_name'             => 'nullable|string|max:255',
            'mailchimp_api_key'          => 'nullable|string|max:500',
            'mailchimp_list_id'          => 'nullable|string|max:255',
            'mailchimp_server_prefix'    => 'nullable|string|max:20',
            'sendgrid_api_key'           => 'nullable|string|max:500',
            'mailgun_domain'             => 'nullable|string|max:255',
            'mailgun_secret'             => 'nullable|string|max:500',
            'email_contact_notification' => 'boolean',
            'email_contact_recipient'    => 'nullable|email|max:255',
            'email_newsletter_enabled'   => 'boolean',
            'email_welcome_enabled'      => 'boolean',
            'email_welcome_template'     => 'nullable|string|max:10000',
            'email_signature'            => 'nullable|string|max:2000',
        ]);

        $setting = Setting::first();

        // Don't overwrite secrets if placeholder values sent
        if (isset($validated['smtp_password']) && empty($validated['smtp_password'])) {
            unset($validated['smtp_password']);
        }
        if (isset($validated['mailchimp_api_key']) && empty($validated['mailchimp_api_key'])) {
            unset($validated['mailchimp_api_key']);
        }
        if (isset($validated['sendgrid_api_key']) && empty($validated['sendgrid_api_key'])) {
            unset($validated['sendgrid_api_key']);
        }
        if (isset($validated['mailgun_secret']) && empty($validated['mailgun_secret'])) {
            unset($validated['mailgun_secret']);
        }

        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = Setting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email settings updated successfully.',
            'data'    => $setting->fresh()->getEmailSettings(),
        ]);
    }

    /**
     * Send a test email to verify email configuration.
     */
    public function testEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_email' => 'required|email|max:255',
        ]);

        try {
            $emailService = new EmailService();
            $emailService->sendTestEmail($validated['to_email']);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $validated['to_email'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get homepage widget configuration.
     */
    public function widgetConfig(): JsonResponse
    {
        $setting = Setting::first();

        $defaultWidgets = [
            ['id' => 'announcements', 'label' => 'Announcements Ticker', 'icon' => 'fa-bullhorn', 'enabled' => true, 'settings' => []],
            ['id' => 'verse', 'label' => 'Verse of the Day', 'icon' => 'fa-book-bible', 'enabled' => true, 'settings' => []],
            ['id' => 'blessing', 'label' => "Today's Blessing", 'icon' => 'fa-hand-holding-heart', 'enabled' => true, 'settings' => []],
            ['id' => 'posts', 'label' => 'Latest Posts / News', 'icon' => 'fa-newspaper', 'enabled' => true, 'settings' => ['count' => 3]],
            ['id' => 'prayers', 'label' => 'Prayer Requests', 'icon' => 'fa-praying-hands', 'enabled' => true, 'settings' => ['count' => 3]],
            ['id' => 'events', 'label' => 'Upcoming Events', 'icon' => 'fa-calendar-alt', 'enabled' => true, 'settings' => ['count' => 3]],
            ['id' => 'sermon', 'label' => 'Latest Sermon', 'icon' => 'fa-microphone-alt', 'enabled' => true, 'settings' => []],
            ['id' => 'testimonies', 'label' => 'Testimonies', 'icon' => 'fa-cross', 'enabled' => false, 'settings' => ['count' => 3]],
            ['id' => 'reviews', 'label' => 'Reviews', 'icon' => 'fa-star', 'enabled' => false, 'settings' => ['count' => 3]],
            ['id' => 'ministries', 'label' => 'Ministries', 'icon' => 'fa-hands-helping', 'enabled' => false, 'settings' => []],
            ['id' => 'galleries', 'label' => 'Photo Gallery', 'icon' => 'fa-images', 'enabled' => false, 'settings' => ['count' => 6]],
            ['id' => 'newsletter', 'label' => 'Newsletter Signup', 'icon' => 'fa-mail-bulk', 'enabled' => false, 'settings' => []],
            ['id' => 'contact', 'label' => 'Quick Contact', 'icon' => 'fa-envelope', 'enabled' => false, 'settings' => []],
        ];

        $config = $setting && $setting->widget_config ? $setting->widget_config : $defaultWidgets;

        return response()->json([
            'success' => true,
            'data'    => [
                'widgets' => $config,
                'available_widgets' => $defaultWidgets,
            ],
        ]);
    }

    /**
     * Save homepage widget configuration.
     */
    /**
     * Get custom profile fields configuration.
     */
    public function profileFields(): JsonResponse
    {
        $setting = Setting::first();
        $fields = $setting->custom_profile_fields ?? [];

        return response()->json([
            'success' => true,
            'data' => $fields,
        ]);
    }

    /**
     * Update custom profile fields configuration.
     */
    public function updateProfileFields(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.key' => 'required|string|max:50',
            'fields.*.label' => 'required|string|max:100',
            'fields.*.type' => 'required|in:text,textarea,select,email,tel,url',
            'fields.*.required' => 'required|boolean',
            'fields.*.enabled' => 'required|boolean',
            'fields.*.options' => 'nullable|string',
            'fields.*.placeholder' => 'nullable|string|max:200',
        ]);

        $setting = Setting::firstOrCreate([], ['church_name' => config('app.name')]);
        $setting->update(['custom_profile_fields' => $validated['fields']]);

        return response()->json([
            'success' => true,
            'message' => 'Profile fields configuration saved.',
            'data' => $setting->fresh()->custom_profile_fields,
        ]);
    }

    public function updateWidgetConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets'            => 'required|array',
            'widgets.*.id'       => 'required|string',
            'widgets.*.label'    => 'required|string',
            'widgets.*.icon'     => 'required|string',
            'widgets.*.enabled'  => 'required|boolean',
            'widgets.*.settings' => 'nullable|array',
        ]);

        $setting = Setting::first();

        if ($setting) {
            $setting->update(['widget_config' => $validated['widgets']]);
        } else {
            $setting = Setting::create(['widget_config' => $validated['widgets']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Homepage layout saved successfully.',
            'data'    => $setting->fresh()->widget_config,
        ]);
    }
}
