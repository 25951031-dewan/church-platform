<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\TimelineSetting;
use App\Plugins\Timeline\Models\DailyVerse;
use Carbon\Carbon;

class TimelineSettingsService
{
    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature, ?int $churchId = null): bool
    {
        return (bool) TimelineSetting::getValue($feature, true, $churchId);
    }

    /**
     * Check if posts are enabled
     */
    public function arePostsEnabled(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('posts_enabled', $churchId);
    }

    /**
     * Check if photo posts are enabled
     */
    public function arePhotoPostsEnabled(?int $churchId = null): bool
    {
        return $this->arePostsEnabled($churchId) && 
               $this->isFeatureEnabled('photo_posts_enabled', $churchId);
    }

    /**
     * Check if video posts are enabled
     */
    public function areVideoPostsEnabled(?int $churchId = null): bool
    {
        return $this->arePostsEnabled($churchId) && 
               $this->isFeatureEnabled('video_posts_enabled', $churchId);
    }

    /**
     * Check if comments are enabled
     */
    public function areCommentsEnabled(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('comments_enabled', $churchId);
    }

    /**
     * Check if reactions are enabled
     */
    public function areReactionsEnabled(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('reactions_enabled', $churchId);
    }

    /**
     * Check if public posting is allowed
     */
    public function isPublicPostingAllowed(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('public_posting', $churchId);
    }

    /**
     * Check if post approval is required
     */
    public function isPostApprovalRequired(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('post_approval_required', $churchId);
    }

    /**
     * Get max photo size in bytes
     */
    public function getMaxPhotoSize(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('max_photo_size', 5242880, $churchId); // 5MB default
    }

    /**
     * Get max video size in bytes
     */
    public function getMaxVideoSize(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('max_video_size', 52428800, $churchId); // 50MB default
    }

    /**
     * Get allowed photo types
     */
    public function getAllowedPhotoTypes(?int $churchId = null): array
    {
        $types = TimelineSetting::getValue('allowed_photo_types', 'jpg,jpeg,png,webp', $churchId);
        return explode(',', strtolower($types));
    }

    /**
     * Get allowed video types
     */
    public function getAllowedVideoTypes(?int $churchId = null): array
    {
        $types = TimelineSetting::getValue('allowed_video_types', 'mp4,webm,mov', $churchId);
        return explode(',', strtolower($types));
    }

    /**
     * Get daily post limit
     */
    public function getDailyPostLimit(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('daily_post_limit', 10, $churchId);
    }

    /**
     * Get post character limit
     */
    public function getPostCharacterLimit(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('post_character_limit', 5000, $churchId);
    }

    /**
     * Get comment character limit
     */
    public function getCommentCharacterLimit(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('comment_character_limit', 1000, $churchId);
    }

    /**
     * Get minimum user age in days to post
     */
    public function getMinUserAgeToPost(?int $churchId = null): int
    {
        return (int) TimelineSetting::getValue('min_user_age_to_post', 0, $churchId);
    }

    /**
     * Check if daily verses are enabled
     */
    public function areDailyVersesEnabled(?int $churchId = null): bool
    {
        return $this->isFeatureEnabled('daily_verse_enabled', $churchId);
    }

    /**
     * Check if verses should be shown on feed
     */
    public function shouldShowVerseOnFeed(?int $churchId = null): bool
    {
        return $this->areDailyVersesEnabled($churchId) && 
               $this->isFeatureEnabled('show_verse_on_feed', $churchId);
    }

    /**
     * Get today's verse if verses are enabled
     */
    public function getTodaysVerse(?int $churchId = null): ?DailyVerse
    {
        if (!$this->areDailyVersesEnabled($churchId)) {
            return null;
        }

        return DailyVerse::getTodaysVerse($churchId);
    }

    /**
     * Validate file upload against settings
     */
    public function validateFileUpload(string $file, string $type, ?int $churchId = null): array
    {
        $errors = [];

        if ($type === 'photo') {
            if (!$this->arePhotoPostsEnabled($churchId)) {
                $errors[] = 'Photo uploads are disabled';
                return $errors;
            }

            $maxSize = $this->getMaxPhotoSize($churchId);
            $allowedTypes = $this->getAllowedPhotoTypes($churchId);
        } elseif ($type === 'video') {
            if (!$this->areVideoPostsEnabled($churchId)) {
                $errors[] = 'Video uploads are disabled';
                return $errors;
            }

            $maxSize = $this->getMaxVideoSize($churchId);
            $allowedTypes = $this->getAllowedVideoTypes($churchId);
        } else {
            $errors[] = 'Invalid file type';
            return $errors;
        }

        // Check file size
        if (filesize($file) > $maxSize) {
            $errors[] = sprintf('File size exceeds maximum of %d MB', $maxSize / 1024 / 1024);
        }

        // Check file extension
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = sprintf('File type %s is not allowed. Allowed types: %s', 
                $extension, implode(', ', $allowedTypes));
        }

        return $errors;
    }

    /**
     * Check user posting limits
     */
    public function canUserPost(int $userId, ?int $churchId = null): array
    {
        $errors = [];

        if (!$this->arePostsEnabled($churchId)) {
            $errors[] = 'Posts are currently disabled';
            return $errors;
        }

        // Check user age requirement
        $minAge = $this->getMinUserAgeToPost($churchId);
        if ($minAge > 0) {
            $user = \App\Models\User::find($userId);
            if ($user && $user->created_at->diffInDays(now()) < $minAge) {
                $errors[] = sprintf('You must wait %d days after registration before posting', $minAge);
            }
        }

        // Check daily post limit
        $dailyLimit = $this->getDailyPostLimit($churchId);
        if ($dailyLimit > 0) {
            $todaysPosts = \App\Plugins\Timeline\Models\Post::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->count();

            if ($todaysPosts >= $dailyLimit) {
                $errors[] = sprintf('Daily post limit of %d reached', $dailyLimit);
            }
        }

        return $errors;
    }

    /**
     * Validate post content
     */
    public function validatePostContent(string $content, ?int $churchId = null): array
    {
        $errors = [];

        $maxLength = $this->getPostCharacterLimit($churchId);
        if (strlen($content) > $maxLength) {
            $errors[] = sprintf('Post content exceeds maximum of %d characters', $maxLength);
        }

        return $errors;
    }

    /**
     * Validate comment content
     */
    public function validateCommentContent(string $content, ?int $churchId = null): array
    {
        $errors = [];

        if (!$this->areCommentsEnabled($churchId)) {
            $errors[] = 'Comments are currently disabled';
            return $errors;
        }

        $maxLength = $this->getCommentCharacterLimit($churchId);
        if (strlen($content) > $maxLength) {
            $errors[] = sprintf('Comment content exceeds maximum of %d characters', $maxLength);
        }

        return $errors;
    }
}