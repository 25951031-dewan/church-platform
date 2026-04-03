<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'event_id', 'user_id', 'name', 'email', 'phone', 'guests', 'notes', 'status'];

    public function event() { return $this->belongsTo(Event::class); }
    public function user() { return $this->belongsTo(User::class); }
}
