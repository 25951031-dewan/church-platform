<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'title', 'slug', 'description', 'content', 'image', 'location', 'location_url',
        'start_date', 'end_date', 'is_recurring', 'recurrence_pattern', 'is_featured',
        'is_active', 'max_attendees', 'registration_required', 'registration_link',
        'meta_title', 'meta_description', 'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime', 'end_date' => 'datetime',
        'is_recurring' => 'boolean', 'is_featured' => 'boolean',
        'is_active' => 'boolean', 'registration_required' => 'boolean'
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function registrations() { return $this->hasMany(EventRegistration::class); }

    public function scopeUpcoming($query) { return $query->where('start_date', '>=', now())->where('is_active', true)->orderBy('start_date'); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function getRegistrationCountAttribute() { return $this->registrations()->count(); }
    public function getIsFull() { return $this->max_attendees && $this->registration_count >= $this->max_attendees; }
}
