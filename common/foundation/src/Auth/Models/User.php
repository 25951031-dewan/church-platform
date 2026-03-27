<?php

namespace Common\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'banned_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'custom_fields' => 'array',
        'password' => 'hashed',
    ];

    // ── Relationships ──

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function directPermissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withPivot('granted');
    }

    // ── Permission Resolution (BeMusic pattern) ──

    public function hasPermission(string $permissionName): bool
    {
        return $this->getResolvedPermissions()[$permissionName] ?? false;
    }

    public function getResolvedPermissions(): array
    {
        return Cache::remember(
            "user.{$this->id}.permissions",
            now()->addMinutes(5),
            function () {
                // Layer 1: Collect all role permissions
                $rolePerms = $this->roles
                    ->load('permissions')
                    ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
                    ->unique()
                    ->mapWithKeys(fn (string $name) => [$name => true])
                    ->toArray();

                // Layer 2: Apply direct user overrides (grant or deny)
                $directPerms = $this->directPermissions
                    ->mapWithKeys(fn (Permission $perm) => [
                        $perm->name => (bool) $perm->pivot->granted,
                    ])
                    ->toArray();

                // Direct overrides win (including explicit denies)
                return array_merge($rolePerms, $directPerms);
            }
        );
    }

    public function clearPermissionCache(): void
    {
        Cache::forget("user.{$this->id}.permissions");
    }

    public function getRoleLevel(): int
    {
        return $this->roles->max('level') ?? 0;
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function getBootstrapData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'permissions' => $this->getResolvedPermissions(),
            'role_level' => $this->getRoleLevel(),
            'roles' => $this->roles->pluck('slug')->toArray(),
            'theme' => $this->theme,
            'language' => $this->language,
        ];
    }
}
