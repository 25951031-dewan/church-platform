# Church Platform — Claude Code Instructions

This file is loaded automatically at the start of every Claude Code session.
It mirrors `.github/copilot-instructions.md` for consistent AI collaboration.

---

## Project Identity

White-label church community platform. **Foundation Fork** architecture — BeMusic's `common/foundation` is the core, church features are plugins. The goal is a deployable SaaS product for churches.

Current branch: `v5-foundation`. Main branch: `main`.

---

## Critical Rules

### 1. Auth: Bearer Token Only

- **Never** add Blade auth routes (`GET /login → view(...)`, `POST /login`, `POST /logout`)
- **Never** use `Auth::attempt()` or session cookies for auth
- `auth()->user()` always returns null for token-based logins — use `auth('sanctum')->user()` in API controllers
- `routes/web.php` named `login` route must be `redirect('/')` only (Laravel middleware requires it)
- `BootstrapDataProvider` rehydrates user from `GET /api/v1/me` on mount — do not remove `ready` gate

### 2. Dark Theme: Always

- `<html class="dark">` in `app.blade.php` activates all `dark:` Tailwind variants
- Palette: `#0C0E12` (page), `#161920` (cards), `white/5` (borders), `text-white`, `text-gray-400`, `text-indigo-400`
- Never use `bg-white`, `bg-gray-50`, `bg-gray-100`, `text-gray-900`

### 3. Plugin Architecture

- New features → `app/Plugins/{Name}/` directory
- Routes → `app/Plugins/{Name}/Routes/api.php` (auto-loaded under `/api/v1/`)
- Policies → must `extend Common\Core\BasePolicy`
- Permissions → `{Name}PermissionSeeder.php`, never in `plugins.json`
- Enable in `config/plugins.json` only when code exists

### 4. Build Artifacts Are Committed

`public/build/` is tracked in git (shared hosting has no Node.js). After any frontend change:
```bash
npm run build   # or: arch -x86_64 ~/.nvm/versions/node/v24.14.1/bin/node node_modules/.bin/vite build
git add public/build/
git commit -m "build: rebuild assets"
```

---

## Tech Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 12, PHP, Sanctum |
| Frontend | React 19, TypeScript, React Router v7 |
| State | TanStack Query v5, Zustand |
| Styling | Tailwind CSS v4 |
| API Client | `apiClient` (auto-attaches Bearer token from localStorage) |

---

## Key File Locations

| What | Where |
|------|-------|
| App entry (React) | `resources/client/main.tsx` |
| Auth hook | `resources/client/common/auth/use-auth.ts` |
| Bootstrap store | `resources/client/common/core/bootstrap-data.tsx` |
| API client | `resources/client/common/http/api-client.ts` |
| Plugin routes | `app/Plugins/{Name}/Routes/api.php` |
| Plugin toggle | `config/plugins.json` |
| Settings model | `Common\Settings\Models\Setting` |
| Base policy | `Common\Core\BasePolicy` |
| Bootstrap service | `Common\Core\BootstrapDataService` |
| Web routes | `routes/web.php` |
| API routes | `routes/api.php` + plugin routes |

---

## What Copilot Gets Wrong (Always Verify After Copilot Sessions)

1. **Policies:** Copilot uses `HandlesAuthorization` trait → must be `extends Common\Core\BasePolicy`
2. **Tests:** Copilot uses `Common\Auth\Models\User` → must be `App\Models\User`
3. **plugins.json:** Copilot adds `permissions` arrays → move to `{Name}PermissionSeeder.php`
4. **Phantom plugins:** Copilot enables plugins that don't exist → check `app/Plugins/` first
5. **Auth routes:** Copilot may add Blade login routes → remove them immediately
6. **Light theme:** Copilot uses default Tailwind colors → replace with dark palette
7. **Double route loading:** Copilot's `App\Services\PluginManager::loadRoutes()` in `AppServiceProvider::booted()` loads all plugin routes AFTER `routes/api.php` already loaded them — overwriting with `auth:sanctum` on everything. Keep `auto_discover` disabled.

## Post-Copilot Audit Checklist

```bash
grep -rn "HandlesAuthorization" app/Plugins/ --include="*.php"          # must be empty
grep -rn "Common\\Auth\\Models\\User" tests/ --include="*.php"           # must be empty
grep -n "loadRoutes\|auto_discover" app/Providers/AppServiceProvider.php # must be disabled
grep -n "view.*auth.login\|Auth::attempt" routes/web.php                 # must be empty
grep -rn "bg-white\|bg-gray-50\|text-gray-900" resources/client/plugins/ --include="*.tsx" # must be empty
```

---

## Seeder Idempotency Pattern

```php
public function run(): void
{
    if (DB::table('sermons')->count() > 0) return; // guard

    $id = DB::table('table')->where('slug', 'x')->value('id')
        ?? DB::table('table')->insertGetId([...]); // upsert
}
```

---

## Deployment Checklist (Shared Hosting)

```bash
# On server after git pull:
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
# First deploy only:
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=DemoSeeder
# Assign Super Admin to user id=1:
php artisan tinker --execute="DB::table('user_role')->insertOrIgnore(['user_id'=>1,'role_id'=>1]);"
```

Installer lock: `storage/installed` file must exist.
