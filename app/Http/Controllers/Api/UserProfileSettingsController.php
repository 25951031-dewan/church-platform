<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Plugins\Timeline\Models\TimelineSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserProfileSettingsController extends Controller
{
    /**
     * Get user's timeline-related preferences
     */
    public function getTimelinePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get user's timeline preferences (stored in user settings)
        $preferences = [
            'feed_layout_preference' => $user->getSetting('feed_layout_preference', 'default'),
            'daily_verse_enabled' => $user->getSetting('daily_verse_enabled', true),
            'notifications_timeline' => $user->getSetting('notifications_timeline', true),
            'auto_play_videos' => $user->getSetting('auto_play_videos', false),
            'show_comments_by_default' => $user->getSetting('show_comments_by_default', true),
            'timeline_posts_per_page' => $user->getSetting('timeline_posts_per_page', 20),
            'preferred_translation' => $user->getSetting('preferred_translation', 'NIV'),
            'dark_mode' => $user->getSetting('dark_mode', true),
            'compact_view' => $user->getSetting('compact_view', false),
        ];

        return response()->json([
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update user's timeline preferences
     */
    public function updateTimelinePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.feed_layout_preference' => 'nullable|string|in:default,compact,expanded',
            'preferences.daily_verse_enabled' => 'nullable|boolean',
            'preferences.notifications_timeline' => 'nullable|boolean',
            'preferences.auto_play_videos' => 'nullable|boolean',
            'preferences.show_comments_by_default' => 'nullable|boolean',
            'preferences.timeline_posts_per_page' => 'nullable|integer|min:5|max:50',
            'preferences.preferred_translation' => 'nullable|string|max:10',
            'preferences.dark_mode' => 'nullable|boolean',
            'preferences.compact_view' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $preferences = $request->input('preferences');

        // Save each preference
        foreach ($preferences as $key => $value) {
            $user->setSetting($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Timeline preferences updated successfully.',
        ]);
    }

    /**
     * Get user's privacy settings related to Timeline
     */
    public function getPrivacySettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $privacySettings = [
            'profile_visibility' => $user->getSetting('profile_visibility', 'church_members'),
            'posts_visibility' => $user->getSetting('posts_visibility', 'church_members'),
            'allow_comments_from' => $user->getSetting('allow_comments_from', 'church_members'),
            'allow_prayer_requests' => $user->getSetting('allow_prayer_requests', true),
            'show_online_status' => $user->getSetting('show_online_status', true),
            'email_notifications_posts' => $user->getSetting('email_notifications_posts', false),
            'email_notifications_comments' => $user->getSetting('email_notifications_comments', true),
            'email_daily_verse' => $user->getSetting('email_daily_verse', false),
        ];

        return response()->json([
            'privacy_settings' => $privacySettings,
        ]);
    }

    /**
     * Update user's privacy settings
     */
    public function updatePrivacySettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privacy_settings' => 'required|array',
            'privacy_settings.profile_visibility' => 'nullable|string|in:public,church_members,private',
            'privacy_settings.posts_visibility' => 'nullable|string|in:public,church_members,private',
            'privacy_settings.allow_comments_from' => 'nullable|string|in:everyone,church_members,none',
            'privacy_settings.allow_prayer_requests' => 'nullable|boolean',
            'privacy_settings.show_online_status' => 'nullable|boolean',
            'privacy_settings.email_notifications_posts' => 'nullable|boolean',
            'privacy_settings.email_notifications_comments' => 'nullable|boolean',
            'privacy_settings.email_daily_verse' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $settings = $request->input('privacy_settings');

        // Save each setting
        foreach ($settings as $key => $value) {
            $user->setSetting($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Privacy settings updated successfully.',
        ]);
    }

    /**
     * Get user's blocked users and content filters
     */
    public function getContentFilters(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get blocked users
        $blockedUsers = $user->blockedUsers()->select('id', 'name', 'email')->get();
        
        // Get content filter preferences
        $filters = [
            'hide_sensitive_content' => $user->getSetting('hide_sensitive_content', false),
            'filter_profanity' => $user->getSetting('filter_profanity', true),
            'blocked_keywords' => $user->getSetting('blocked_keywords', []),
            'hide_announcement_posts' => $user->getSetting('hide_announcement_posts', false),
            'hide_prayer_posts' => $user->getSetting('hide_prayer_posts', false),
            'hide_event_posts' => $user->getSetting('hide_event_posts', false),
        ];

        return response()->json([
            'blocked_users' => $blockedUsers,
            'content_filters' => $filters,
        ]);
    }

    /**
     * Update content filters
     */
    public function updateContentFilters(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content_filters' => 'required|array',
            'content_filters.hide_sensitive_content' => 'nullable|boolean',
            'content_filters.filter_profanity' => 'nullable|boolean',
            'content_filters.blocked_keywords' => 'nullable|array',
            'content_filters.blocked_keywords.*' => 'string|max:50',
            'content_filters.hide_announcement_posts' => 'nullable|boolean',
            'content_filters.hide_prayer_posts' => 'nullable|boolean',
            'content_filters.hide_event_posts' => 'nullable|boolean',
            'block_user_id' => 'nullable|integer|exists:users,id',
            'unblock_user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Update content filters
        if ($request->has('content_filters')) {
            $filters = $request->input('content_filters');
            foreach ($filters as $key => $value) {
                $user->setSetting($key, $value);
            }
        }

        // Block user
        if ($request->has('block_user_id')) {
            $blockUserId = $request->input('block_user_id');
            if ($blockUserId !== $user->id) {
                $user->blockedUsers()->syncWithoutDetaching([$blockUserId]);
            }
        }

        // Unblock user
        if ($request->has('unblock_user_id')) {
            $unblockUserId = $request->input('unblock_user_id');
            $user->blockedUsers()->detach($unblockUserId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content filters updated successfully.',
        ]);
    }

    /**
     * Reset user's timeline settings to defaults
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        $user = $request->user();

        // Timeline preference defaults
        $timelineDefaults = [
            'feed_layout_preference' => 'default',
            'daily_verse_enabled' => true,
            'notifications_timeline' => true,
            'auto_play_videos' => false,
            'show_comments_by_default' => true,
            'timeline_posts_per_page' => 20,
            'preferred_translation' => 'NIV',
            'dark_mode' => true,
            'compact_view' => false,
        ];

        // Privacy setting defaults
        $privacyDefaults = [
            'profile_visibility' => 'church_members',
            'posts_visibility' => 'church_members',
            'allow_comments_from' => 'church_members',
            'allow_prayer_requests' => true,
            'show_online_status' => true,
            'email_notifications_posts' => false,
            'email_notifications_comments' => true,
            'email_daily_verse' => false,
        ];

        // Content filter defaults
        $filterDefaults = [
            'hide_sensitive_content' => false,
            'filter_profanity' => true,
            'blocked_keywords' => [],
            'hide_announcement_posts' => false,
            'hide_prayer_posts' => false,
            'hide_event_posts' => false,
        ];

        $allDefaults = array_merge($timelineDefaults, $privacyDefaults, $filterDefaults);

        // Reset all settings
        foreach ($allDefaults as $key => $value) {
            $user->setSetting($key, $value);
        }

        // Remove all blocked users
        $user->blockedUsers()->detach();

        return response()->json([
            'success' => true,
            'message' => 'Timeline settings reset to defaults successfully.',
        ]);
    }
}