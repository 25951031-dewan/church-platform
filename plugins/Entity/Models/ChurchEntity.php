<?php
namespace Plugins\Entity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChurchEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'church_entities';

    protected $fillable = [
        'type', 'owner_id', 'name', 'slug', 'description', 'category_id',
        'cover_image', 'profile_image', 'website', 'address', 'phone',
        'social_links', 'action_button', 'is_verified', 'privacy',
        'allow_posts', 'require_approval', 'parent_entity_id',
        'members_count', 'posts_count', 'meta', 'is_active',
    ];

    protected $casts = [
        'social_links'     => 'array',
        'action_button'    => 'array',
        'meta'             => 'array',
        'is_verified'      => 'boolean',
        'allow_posts'      => 'boolean',
        'require_approval' => 'boolean',
        'is_active'        => 'boolean',
        'members_count'    => 'integer',
        'posts_count'      => 'integer',
    ];

    // Scopes
    public function scopePages($query)       { return $query->where('type', 'page'); }
    public function scopeCommunities($query) { return $query->where('type', 'community'); }
    public function scopeActive($query)      { return $query->where('is_active', true); }

    // Relations
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(EntityMember::class, 'entity_id');
    }

    public function approvedMembers()
    {
        return $this->hasMany(EntityMember::class, 'entity_id')->where('status', 'approved');
    }

    public function admins()
    {
        return $this->hasMany(EntityMember::class, 'entity_id')
                    ->whereIn('role', ['admin', 'moderator'])
                    ->where('status', 'approved');
    }

    // Helpers
    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }

    public function isMember(int $userId): bool
    {
        return $this->members()
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()
                    ->where('user_id', $userId)
                    ->whereIn('role', ['admin', 'moderator'])
                    ->where('status', 'approved')
                    ->exists();
    }

    protected static function newFactory()
    {
        return \Database\Factories\ChurchEntityFactory::new();
    }
}
