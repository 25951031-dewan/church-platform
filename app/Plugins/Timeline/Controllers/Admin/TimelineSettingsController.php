<?php

namespace App\Plugins\Timeline\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Timeline\Models\TimelineSetting;
use App\Plugins\Timeline\Models\DailyVerse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TimelineSettingsController extends Controller
{
    /**
     * Return success response
     */
    protected function success(array $data = [], string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Return error response
     */
    protected function error(string $message = 'Error', array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    /**
     * Get community settings
     */
    public function getCommunitySettings(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;
        $settings = TimelineSetting::getAllSettings($churchId);
        $defaults = TimelineSetting::getDefaultSettings();
        
        // Merge with defaults
        $communitySettings = array_merge($defaults, $settings);
        
        return $this->success([
            'settings' => $communitySettings,
            'defaults' => $defaults
        ]);
    }

    /**
     * Update community settings
     */
    public function updateCommunitySettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.posts_enabled' => 'boolean',
            'settings.photo_posts_enabled' => 'boolean',
            'settings.video_posts_enabled' => 'boolean',
            'settings.announcement_posts_enabled' => 'boolean',
            'settings.comments_enabled' => 'boolean',
            'settings.reactions_enabled' => 'boolean',
            'settings.public_posting' => 'boolean',
            'settings.post_approval_required' => 'boolean',
            'settings.daily_post_limit' => 'integer|min:1|max:100',
            'settings.comment_character_limit' => 'integer|min:10|max:10000',
            'settings.post_character_limit' => 'integer|min:10|max:50000',
            'settings.min_user_age_to_post' => 'integer|min:0|max:365',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $churchId = $request->user()->church_id;
        $settings = $request->input('settings');

        // Save each setting
        foreach ($settings as $key => $value) {
            TimelineSetting::setValue($key, $value, $churchId);
        }

        return $this->success(['message' => 'Community settings updated successfully']);
    }

    /**
     * Get media settings
     */
    public function getMediaSettings(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;
        $settings = TimelineSetting::getAllSettings($churchId);
        $defaults = TimelineSetting::getDefaultSettings();
        
        $mediaSettings = array_merge($defaults, $settings);
        
        return $this->success([
            'settings' => $mediaSettings,
            'defaults' => $defaults
        ]);
    }

    /**
     * Update media settings
     */
    public function updateMediaSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.max_photo_size' => 'integer|min:1048576|max:52428800', // 1MB to 50MB
            'settings.max_video_size' => 'integer|min:1048576|max:524288000', // 1MB to 500MB
            'settings.allowed_photo_types' => 'string',
            'settings.allowed_video_types' => 'string',
            'settings.max_photos_per_post' => 'integer|min:1|max:20',
            'settings.max_videos_per_post' => 'integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $churchId = $request->user()->church_id;
        $settings = $request->input('settings');

        // Save each setting
        foreach ($settings as $key => $value) {
            TimelineSetting::setValue($key, $value, $churchId);
        }

        return $this->success(['message' => 'Media settings updated successfully']);
    }

    /**
     * Get daily verse settings and verses
     */
    public function getDailyVerseSettings(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;
        $settings = TimelineSetting::getAllSettings($churchId);
        $defaults = TimelineSetting::getDefaultSettings();
        
        $verseSettings = array_merge($defaults, $settings);
        
        // Get recent verses (last 30 days)
        $recentVerses = DailyVerse::where('church_id', $churchId)
            ->where('verse_date', '>=', now()->subDays(30))
            ->orderBy('verse_date', 'desc')
            ->get();
        
        // Get today's verse
        $todaysVerse = DailyVerse::getTodaysVerse($churchId);
        
        return $this->success([
            'settings' => $verseSettings,
            'recent_verses' => $recentVerses,
            'todays_verse' => $todaysVerse,
            'stats' => [
                'total_verses' => DailyVerse::where('church_id', $churchId)->count(),
                'active_verses' => DailyVerse::where('church_id', $churchId)->where('is_active', true)->count(),
                'future_verses' => DailyVerse::where('church_id', $churchId)
                    ->where('verse_date', '>', now())
                    ->count()
            ]
        ]);
    }

    /**
     * Update daily verse settings
     */
    public function updateDailyVerseSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.daily_verse_enabled' => 'boolean',
            'settings.show_verse_on_feed' => 'boolean',
            'settings.verse_translation' => 'string|max:10',
            'settings.verse_reflection_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $churchId = $request->user()->church_id;
        $settings = $request->input('settings');

        // Save each setting
        foreach ($settings as $key => $value) {
            TimelineSetting::setValue($key, $value, $churchId);
        }

        return $this->success(['message' => 'Daily verse settings updated successfully']);
    }

    /**
     * Import daily verses from CSV
     */
    public function importDailyVerses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $churchId = $request->user()->church_id;
        $file = $request->file('file');
        
        try {
            // Read CSV file
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            $headers = array_shift($csvData); // Remove header row
            
            // Expected headers
            $expectedHeaders = ['verse_date', 'reference', 'text', 'translation', 'reflection', 'is_active'];
            
            // Validate headers
            if (array_diff($expectedHeaders, $headers)) {
                return $this->error('Invalid CSV format. Expected headers: ' . implode(', ', $expectedHeaders), [], 422);
            }
            
            // Process CSV data
            $data = [];
            foreach ($csvData as $row) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                }
            }
            
            if (empty($data)) {
                return $this->error('No valid data found in CSV file', [], 422);
            }
            
            // Import verses
            $result = DailyVerse::createFromCsvData($data, $churchId);
            
            return $this->success([
                'message' => 'Daily verses imported successfully',
                'created_count' => count($result['created']),
                'error_count' => count($result['errors']),
                'errors' => $result['errors']
            ]);
            
        } catch (\Exception $e) {
            return $this->error('Failed to import CSV: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Export daily verses to CSV
     */
    public function exportDailyVerses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors(), 422);
        }

        $churchId = $request->user()->church_id;
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        
        try {
            $verses = DailyVerse::exportToCsv($churchId, $startDate, $endDate);
            
            // Create CSV content
            $csvContent = "verse_date,reference,text,translation,reflection,is_active\n";
            
            foreach ($verses as $verse) {
                $csvContent .= sprintf(
                    "%s,\"%s\",\"%s\",\"%s\",\"%s\",%s\n",
                    $verse['verse_date'],
                    str_replace('"', '""', $verse['reference']),
                    str_replace('"', '""', $verse['text']),
                    str_replace('"', '""', $verse['translation']),
                    str_replace('"', '""', $verse['reflection'] ?? ''),
                    $verse['is_active']
                );
            }
            
            $filename = 'daily_verses_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
        } catch (\Exception $e) {
            return $this->error('Failed to export verses: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Download sample CSV template
     */
    public function downloadSampleCsv(): \Illuminate\Http\Response
    {
        $sampleData = DailyVerse::getSampleCsvData();
        
        $csvContent = "verse_date,reference,text,translation,reflection,is_active\n";
        
        foreach ($sampleData as $verse) {
            $csvContent .= sprintf(
                "%s,\"%s\",\"%s\",\"%s\",\"%s\",%s\n",
                $verse['verse_date'],
                str_replace('"', '""', $verse['reference']),
                str_replace('"', '""', $verse['text']),
                str_replace('"', '""', $verse['translation']),
                str_replace('"', '""', $verse['reflection']),
                $verse['is_active']
            );
        }
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="daily_verses_sample.csv"');
    }
}