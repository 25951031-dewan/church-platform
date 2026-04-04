<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Traits\BelongsToChurch;
use App\Models\User;
use App\Plugins\Timeline\Models\Post;

class FeedChannel extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'name',
        'slug',
        'description',
        'type',
        'config',
        'is_public',
        'is_active',
        'allow_posts',
        'moderate_posts',
        'allowed_content_types',
        'max_file_size',
        'permissions',
        'created_by',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'json',
        'allowed_content_types' => 'json',
        'permissions' => 'json',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'allow_posts' => 'boolean',
        'moderate_posts' => 'boolean',
    ];

    /**
     * Get the user who created this channel
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get posts in this channel (BeMusic pattern)
     */
    public function posts(): MorphToMany
    {
        return $this->morphToMany(
            Post::class,
            'channelable',
            'channel_items',
            'channel_id',
            'channelable_id'
        )->withPivot(['position', 'is_featured', 'metadata'])
         ->withTimestamps()
         ->orderBy('pivot_position')
         ->orderBy('created_at', 'desc');
    }

    /**
     * Check if user can view this channel
     */
    public function canView(?User $user = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Check permissions
        $permissions = $this->permissions ?? [];
        
        // If no specific permissions, allow church members
        if (empty($permissions['view'])) {
            return $user->church_id === $this->church_id;
        }

        // Check user roles/permissions
        return $this->userHasPermission($user, 'view');
    }

    /**
     * Check if user can post to this channel
     */
    public function canPost(?User $user = null): bool
    {
        if (!$this->allow_posts || !$this->is_active) {
            return false;
        }

        if (!$user) {
            return false;
        }

        // Church members can post by default
        if ($user->church_id === $this->church_id) {
            return !$this->userHasPermission($user, 'blocked');
        }

        return $this->userHasPermission($user, 'post');
    }

    /**
     * Check if user can moderate this channel
     */
    public function canModerate(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $this->userHasPermission($user, 'moderate') ||
               $user->id === $this->created_by ||
               $user->hasRole('admin');
    }

    /**
     * Check if content type is allowed
     */
    public function allowsContentType(string $type): bool
    {
        $allowed = $this->allowed_content_types ?? ['text', 'image', 'video', 'link'];
        return in_array($type, $allowed);
    }

    /**
     * Check if file size is within limits
     */
    public function isFileSizeAllowed(int $sizeInKb): bool
    {
        if (!$this->max_file_size) {
            return true; // No limit
        }

        return $sizeInKb <= $this->max_file_size;
    }

    /**
     * Get channel configuration
     */
    public function getConfig(string $key = null, $default = null)
    {
        $config = $this->config ?? [];
        
        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * Check user permissions
     */
    protected function userHasPermission(User $user, string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        $allowed = $permissions[$permission] ?? [];

        // Check user ID
        if (in_array($user->id, $allowed['users'] ?? [])) {
            return true;
        }

        // Check user roles
        $userRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = $allowed['roles'] ?? [];
        
        return !empty(array_intersect($userRoles, $allowedRoles));
    }

    /**
     * Scope to get active channels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get public channels
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get channels by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get channels for a church
     */
    public function scopeForChurch($query, $churchId = null)
    {
        return $query->where('church_id', $churchId);
    }

    /**
     * Get available channel types
     */
    public static function getTypes(): array
    {
        return [
            'timeline' => 'General Timeline',
            'announcement' => 'Announcements',
            'prayer' => 'Prayer Requests',
            'event' => 'Events',
            'sermon' => 'Sermons',
            'bible_study' => 'Bible Study',
            'youth' => 'Youth Ministry',
            'worship' => 'Worship & Music',
            'outreach' => 'Outreach & Missions',
            'custom' => 'Custom Channel',
        ];
    }

    /**
     * Create default channels for a church
     */
    public static function createDefaultChannels($churchId, User $creator): void
    {
        $defaultChannels = [
            [
                'name' => 'General Timeline',
                'slug' => 'general-' . $churchId,
                'description' => 'Main church timeline for general updates and discussions',
                'type' => 'timeline',
                'is_public' => true,
                'allow_posts' => true,
                'allowed_content_types' => ['text', 'image', 'video', 'link'],
            ],
            [
                'name' => 'Announcements',
                'slug' => 'announcements-' . $churchId,
                'description' => 'Official church announcements and news',
                'type' => 'announcement',
                'is_public' => true,
                'allow_posts' => false,
                'moderate_posts' => true,
                'allowed_content_types' => ['text', 'image', 'link'],
            ],
            [
                'name' => 'Prayer Requests',
                'slug' => 'prayer-' . $churchId,
                'description' => 'Community prayer requests and testimonies',
                'type' => 'prayer',
                'is_public' => false,
                'allow_posts' => true,
                'allowed_content_types' => ['text'],
            ],
        ];

        foreach ($defaultChannels as $channelData) {
            static::create(array_merge($channelData, [
                'church_id' => $churchId,
                'created_by' => $creator->id,
                'is_active' => true,
            ]));
        }
    }
}