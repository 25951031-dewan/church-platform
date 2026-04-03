<?php
namespace App\Models;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'name', 'slug', 'type', 'parent_id', 'description',
        'image', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cat) {
            if (!$cat->slug) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function parent() { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByType($query, $type) { return $query->where('type', $type); }
    public function scopeRoots($query) { return $query->whereNull('parent_id'); }
}
