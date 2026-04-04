<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToChurch;

class TimelineSetting extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'setting_key', 
        'setting_value'
    ];

    protected $casts = [
        'setting_value' => 'json'
    ];

    /**
     * Get setting value with fallback to default
     */
    public static function getValue(string $key, mixed $default = null, ?int $churchId = null): mixed
    {
        $setting = static::where('church_id', $churchId)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set setting value
     */
    public static function setValue(string $key, mixed $value, ?int $churchId = null): void
    {
        static::updateOrCreate(
            ['church_id' => $churchId, 'setting_key' => $key],
            ['setting_value' => $value]
        );
    }

    /**
     * Get all settings for a church
     */
    public static function getAllSettings(?int $churchId = null): array
    {
        $settings = static::where('church_id', $churchId)->get();
        
        return $settings->pluck('setting_value', 'setting_key')->toArray();
    }

    /**
     * Default timeline settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Community Controls
            'posts_enabled' => true,
            'photo_posts_enabled' => true,
            'video_posts_enabled' => true,
            'announcement_posts_enabled' => true,
            'comments_enabled' => true,
            'reactions_enabled' => true,
            'public_posting' => false,
            'post_approval_required' => false,
            
            // Media Limits
            'max_photo_size' => 5242880, // 5MB
            'max_video_size' => 52428800, // 50MB
            'allowed_photo_types' => 'jpg,jpeg,png,webp',
            'allowed_video_types' => 'mp4,webm,mov',
            'max_photos_per_post' => 10,
            'max_videos_per_post' => 1,
            
            // Posting Controls
            'daily_post_limit' => 10,
            'comment_character_limit' => 1000,
            'post_character_limit' => 5000,
            'min_user_age_to_post' => 0, // Days since registration
            
            // Daily Verse Settings
            'daily_verse_enabled' => true,
            'show_verse_on_feed' => true,
            'verse_translation' => 'NIV',
            'verse_reflection_enabled' => true,
        ];
    }
}