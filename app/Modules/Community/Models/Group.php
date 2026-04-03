<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'name', 'description', 'type', 'cover_image', 'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role', 'joined_at');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function scopePublicGroups($query)
    {
        return $query->where('type', 'public');
    }

    public function scopeForChurchMembers($query, int $churchId)
    {
        return $query->where(function ($q) use ($churchId) {
            $q->where('type', 'public')
              ->orWhere(function ($q2) use ($churchId) {
                  $q2->whereIn('type', ['church_only', 'private'])
                     ->where('church_id', $churchId);
              });
        });
    }
}
