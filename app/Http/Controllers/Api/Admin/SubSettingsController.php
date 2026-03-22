<?php

namespace App\Http\Controllers\Api\Admin;

use App\Core\SettingsManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Handles per-section admin settings (general, email, appearance, seo, cache, auth, storage, api).
 * Each section is stored as a JSON blob in the `settings` table under its own key.
 */
class SubSettingsController extends Controller
{
    private const ALLOWED_GROUPS = [
        'general', 'email', 'appearance', 'seo', 'cache', 'auth', 'storage', 'api',
    ];

    private const VALIDATION_RULES = [
        'general' => [
            'church_name'   => ['sometimes', 'string', 'max:255'],
            'tagline'       => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
        ],
        'email' => [
            'smtp_host'         => ['sometimes', 'string', 'max:255'],
            'smtp_port'         => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'smtp_username'     => ['sometimes', 'string', 'max:255'],
            'smtp_password'     => ['sometimes', 'string', 'max:255'],
            'smtp_encryption'   => ['sometimes', Rule::in(['tls', 'ssl', ''])],
            'mail_from_address' => ['sometimes', 'email', 'max:255'],
            'mail_from_name'    => ['sometimes', 'string', 'max:255'],
        ],
        'appearance' => [
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{3,6}$/'],
            'custom_css'    => ['sometimes', 'string'],
            'custom_js'     => ['sometimes', 'string'],
        ],
        'seo' => [
            'meta_title'       => ['sometimes', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'string', 'max:500'],
            'analytics_id'     => ['sometimes', 'string', 'max:50'],
            'sitemap_enabled'  => ['sometimes', 'boolean'],
        ],
        'cache' => [
            'cache_driver' => ['sometimes', Rule::in(['redis', 'file', 'database', 'array'])],
            'cache_ttl'    => ['sometimes', 'integer', 'min:60', 'max:86400'],
            'cdn_url'      => ['sometimes', 'nullable', 'url', 'max:255'],
        ],
        'auth' => [
            'social_login_providers'   => ['sometimes', 'array'],
            'social_login_providers.*' => ['string', Rule::in(['google', 'facebook', 'twitter', 'github'])],
            'captcha_enabled'          => ['sometimes', 'boolean'],
        ],
        'storage' => [
            'storage_driver' => ['sometimes', Rule::in(['local', 's3'])],
            's3_bucket'      => ['sometimes', 'string', 'max:255'],
            's3_region'      => ['sometimes', 'string', 'max:100'],
            's3_key'         => ['sometimes', 'string', 'max:255'],
            's3_secret'      => ['sometimes', 'string', 'max:255'],
            'max_upload_mb'  => ['sometimes', 'integer', 'min:1', 'max:200'],
        ],
        'api' => [
            'turnstile_site_key'   => ['sometimes', 'string', 'max:255'],
            'turnstile_secret_key' => ['sometimes', 'string', 'max:255'],
            'api_rate_limit'       => ['sometimes', 'integer', 'min:10', 'max:10000'],
        ],
    ];

    public function __construct(private readonly SettingsManager $settings) {}

    /**
     * Get settings for a specific group.
     *
     * @group Admin / Settings
     *
     * @urlParam group string required The settings group. Example: general
     */
    public function show(string $group): JsonResponse
    {
        if (! in_array($group, self::ALLOWED_GROUPS, true)) {
            return response()->json(['message' => 'Unknown settings group.'], 404);
        }

        $data = $this->load($group);

        // Never return sensitive values
        if ($group === 'email') {
            unset($data['smtp_password']);
        }
        if ($group === 'api') {
            unset($data['turnstile_secret_key']);
        }
        if ($group === 'storage') {
            unset($data['s3_secret']);
        }

        return response()->json($data);
    }

    /**
     * Update settings for a specific group.
     *
     * @group Admin / Settings
     *
     * @urlParam group string required The settings group. Example: general
     */
    public function update(Request $request, string $group): JsonResponse
    {
        if (! in_array($group, self::ALLOWED_GROUPS, true)) {
            return response()->json(['message' => 'Unknown settings group.'], 404);
        }

        $rules     = self::VALIDATION_RULES[$group] ?? [];
        $validated = $request->validate($rules);

        $current = $this->load($group);

        // For secret fields, only overwrite if a new value was explicitly provided
        foreach (['smtp_password', 'turnstile_secret_key', 's3_secret'] as $secret) {
            if (array_key_exists($secret, $validated) && $validated[$secret] === '') {
                unset($validated[$secret]);
            }
        }

        // Propagate captcha_enabled change to the captcha settings row as well
        if ($group === 'auth' && array_key_exists('captcha_enabled', $validated)) {
            DB::table('settings')
                ->where('key', 'captcha')
                ->update(['captcha_enabled' => $validated['captcha_enabled'], 'updated_at' => now()]);
        }

        // Propagate Turnstile keys to the captcha settings row
        if ($group === 'api') {
            $captchaUpdates = [];
            if (array_key_exists('turnstile_site_key', $validated)) {
                $captchaUpdates['turnstile_site_key'] = $validated['turnstile_site_key'];
            }
            if (array_key_exists('turnstile_secret_key', $validated)) {
                $captchaUpdates['turnstile_secret_key'] = $validated['turnstile_secret_key'];
            }
            if (! empty($captchaUpdates)) {
                $captchaUpdates['updated_at'] = now();
                DB::table('settings')->where('key', 'captcha')->update($captchaUpdates);
            }
        }

        $merged = array_merge($current, $validated);

        DB::table('settings')->updateOrInsert(
            ['key' => $group],
            ['value' => json_encode($merged), 'updated_at' => now()]
        );

        $this->settings->flush();

        return $this->show($group);
    }

    private function load(string $group): array
    {
        $row = DB::table('settings')->where('key', $group)->first();

        if (! $row || ! $row->value) {
            return [];
        }

        return json_decode($row->value, true) ?? [];
    }
}
