<?php

namespace App\Plugins\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $slug = Str::slug($tag->name);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $tag->slug = $slug;
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
