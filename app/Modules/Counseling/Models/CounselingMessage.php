<?php

namespace App\Modules\Counseling\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CounselingMessage extends Model
{
    protected $fillable = ['thread_id', 'sender_id', 'body', 'attachments', 'read_at'];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CounselingThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Encrypt body on set
    public function setBodyAttribute($value): void
    {
        $this->attributes['body'] = Crypt::encryptString($value);
    }

    // Decrypt body on get
    public function getBodyAttribute($value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // fallback for unencrypted legacy data
        }
    }
}
