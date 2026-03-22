<?php

namespace App\Http\Controllers\Api\Admin;

use App\Core\SettingsManager;
use App\Http\Controllers\Controller;
use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsManager $settings,
        private readonly PlatformModeService $platform,
    ) {}

    /**
     * Get platform settings.
     *
     * @group Admin / Settings
     *
     * @response 200 {
     *   "platform_mode": "single",
     *   "show_church_directory": false,
     *   "default_church_id": null,
     *   "feature_toggles": { "events": true, "sermons": true }
     * }
     */
    public function show(): JsonResponse
    {
        $row = DB::table('settings')->where('key', 'platform')->first();

        return response()->json([
            'platform_mode'         => $row?->platform_mode ?? 'single',
            'show_church_directory' => (bool) ($row?->show_church_directory ?? false),
            'default_church_id'     => $row?->default_church_id,
            'feature_toggles'       => json_decode($row?->feature_toggles ?? '{}', true) ?? [],
        ]);
    }

    /**
     * Update platform settings.
     *
     * @group Admin / Settings
     *
     * @bodyParam platform_mode string optional Enum: single, multi. Example: multi
     * @bodyParam show_church_directory boolean optional Show public church directory. Example: true
     * @bodyParam default_church_id integer optional ID of the default church. Example: 1
     * @bodyParam feature_toggles object optional Map of feature slug => enabled bool. Example: {"events":true,"sermons":false}
     *
     * @response 200 scenario="Updated" {"platform_mode":"multi","show_church_directory":true,"default_church_id":null,"feature_toggles":{"events":true}}
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_mode'         => ['sometimes', Rule::in(['single', 'multi'])],
            'show_church_directory' => ['sometimes', 'boolean'],
            'default_church_id'     => ['sometimes', 'nullable', 'integer', 'exists:churches,id'],
            'feature_toggles'       => ['sometimes', 'array'],
            'feature_toggles.*'     => ['boolean'],
        ]);

        $current = DB::table('settings')->where('key', 'platform')->first();

        $updates = [];

        if (array_key_exists('platform_mode', $validated)) {
            $updates['platform_mode'] = $validated['platform_mode'];
        }

        if (array_key_exists('show_church_directory', $validated)) {
            $updates['show_church_directory'] = $validated['show_church_directory'];
        }

        if (array_key_exists('default_church_id', $validated)) {
            $updates['default_church_id'] = $validated['default_church_id'];
        }

        if (array_key_exists('feature_toggles', $validated)) {
            $existing = json_decode($current?->feature_toggles ?? '{}', true) ?? [];
            $updates['feature_toggles'] = json_encode(
                array_merge($existing, $validated['feature_toggles'])
            );
        }

        if (! empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('settings')->where('key', 'platform')->update($updates);
            $this->settings->flush();
        }

        return $this->show();
    }
}
