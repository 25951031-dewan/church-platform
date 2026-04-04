---
applyTo: "app/**/*.php,routes/**/*.php"
---

# PHP / Laravel Rules — Church Platform

## Policies
- ALWAYS `extends Common\Core\BasePolicy`
- NEVER use `HandlesAuthorization` trait
- `before()` in BasePolicy already handles super-admin bypass

## Auth
- NEVER `Auth::attempt()` — Bearer token only
- NEVER `auth()->user()` — returns null for token users
- USE `auth('sanctum')->user()` in API controllers

## Plugins
- New features go in `app/Plugins/{Name}/`
- Routes in `app/Plugins/{Name}/Routes/api.php` — auto-loaded under `/api/v1/`
- Register in `config/plugins.json` as `{ "enabled": true, "version": "1.0.0" }`
- Permissions go in `{Name}PermissionSeeder.php` — NEVER in `plugins.json`

## Settings (no migrations needed)
```php
Setting::where('key', 'x')->value('value');
Setting::updateOrCreate(['key' => 'x'], ['value' => $v]);
```

## Seeders must be idempotent
```php
if (DB::table('table')->count() > 0) return;
$id = DB::table('t')->where('slug','x')->value('id') ?? DB::table('t')->insertGetId([...]);
```
