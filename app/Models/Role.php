<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'permissions',
        'is_system', 'level', 'church_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
        'level'       => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
