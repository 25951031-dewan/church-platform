<?php
namespace Plugins\Comment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphMany, MorphTo};

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['commentable_type', 'commentable_id', 'user_id', 'parent_id', 'body'];

    protected $casts = [
        'replies_count'   => 'integer',
        'reactions_count' => 'integer',
    ];

    public function commentable(): MorphTo  { return $this->morphTo(); }
    public function author(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }
    public function parent(): BelongsTo    { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies(): HasMany     { return $this->hasMany(Comment::class, 'parent_id')->latest(); }
    public function reactions(): MorphMany
    {
        return $this->morphMany(\Plugins\Reaction\Models\Reaction::class, 'reactable');
    }

    public function isTopLevel(): bool { return is_null($this->parent_id); }
}
