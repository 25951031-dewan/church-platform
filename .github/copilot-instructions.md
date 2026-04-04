# Church Platform — Copilot Instructions

This is a white-label church community platform built on a **BeMusic Foundation Fork**.
Read this entire file before making any suggestions. Every section is load-bearing.

---

## Architecture

- **Backend:** Laravel 12, PHP, `App\` namespace for app code, `Common\` namespace for shared foundation (`common/foundation/src/`)
- **Frontend:** React 19 + TypeScript SPA. Single entry point: `resources/client/main.tsx`. React Router v7, TanStack Query v5, Zustand for bootstrap store, Tailwind CSS v4.
- **Plugin system:** All features are plugins in `app/Plugins/{Name}/`. Enabled via `config/plugins.json`. Routes loaded by `Common\Core\PluginManager` from `app/Plugins/{Name}/Routes/api.php`.
- **Build output:** `public/build/` is committed to git (shared hosting has no Node.js). Always rebuild locally with `npm run build` and commit the result.

---

## Authentication — CRITICAL

**This app uses Sanctum Bearer token auth, NOT Laravel session auth.**

Rules that must never be broken:
- Never add `GET /login → view('auth.login')` or any Blade auth route. The React SPA owns `/login`.
- Never use `Auth::attempt()`, `auth()->login()`, or session cookies for the primary auth flow.
- Never call `auth()->user()` in code that runs on page load (it uses session guard, always returns null for token users).
- The `POST /api/v1/login` route returns `{ token, user }`. The React client stores the token in `localStorage` as `auth_token`.
- The `BootstrapDataProvider` in `resources/client/common/core/bootstrap-data.tsx` rehydrates the user on mount by calling `GET /api/v1/me` with the Bearer token. This is intentional — do not remove the `ready` state gate.
- The `routes/web.php` named route `login` must exist but must redirect to `/` (required by Laravel's auth middleware for redirects). It must NOT render a Blade view.

---

## Dark Theme — CRITICAL

**Tailwind v4 dark mode is activated by `class="dark"` on the `<html>` element** (`resources/views/app.blade.php`). All `dark:` variants are inactive without it.

Color palette for all UI (never use light-mode defaults):
```
Page background:    bg-[#0C0E12]
Card/panel:         bg-[#161920]
Borders:            border-white/5  or  border-white/10
Primary text:       text-white
Secondary text:     text-gray-400
Accent/links:       text-indigo-400
Active tab border:  border-indigo-500
Inputs:             bg-[#161920] border border-white/10 text-white placeholder:text-gray-500
```

Never use `bg-white`, `bg-gray-50`, `bg-gray-100`, `text-gray-900`, or `text-black` in any component.

---

## Plugin Structure

Every plugin follows this layout:
```
app/Plugins/{Name}/
  Database/
    Migrations/
    Seeders/
  Http/
    Controllers/
    Requests/
  Models/
  Policies/        # Must extend Common\Core\BasePolicy (NOT HandlesAuthorization trait)
  Routes/
    api.php        # Loaded automatically by PluginManager under /api/v1/ prefix
  Providers/
    {Name}ServiceProvider.php
```

Registration in `config/plugins.json`:
```json
"{name}": { "enabled": true, "name": "Plugin Name" }
```

Do NOT add `permissions` arrays to `plugins.json`. Permissions go in a `{Name}PermissionSeeder.php`.

---

## API Routes

- All API routes are under `/api/v1/` prefix
- Plugin routes are in `app/Plugins/{Name}/Routes/api.php` — they are auto-prefixed
- Legacy routes in `routes/api.php` use `App\Http\Controllers\Api\*` — prefer plugin routes for new code
- Public routes: no middleware. Authenticated routes: `middleware(['auth:sanctum'])`

---

## Models & Policies

- All Policies must extend `Common\Core\BasePolicy`, not use `HandlesAuthorization`
- Permissions follow the `own` vs `any` pattern from BeMusic foundation
- Settings are stored as key-value in the `settings` table — never add migrations for new settings
  ```php
  Setting::where('key', 'church_name')->value('value');
  Setting::pluck('value', 'key'); // get all
  ```

---

## Frontend Patterns

- API calls: use `apiClient` from `resources/client/common/http/api-client.ts` — it auto-attaches the Bearer token
- Data fetching: TanStack Query (`useQuery`, `useMutation`). Query keys follow `['resource', params]` pattern.
- Auth state: `useAuth()` from `resources/client/common/auth/use-auth.ts`. Check `isAuthenticated` for guards.
- Bootstrap data: `useBootstrapStore()` for user/settings/plugins from `window.__BOOTSTRAP_DATA__`
- Route protection: `<RequireAuth>` wrapper component
- Admin routes: under `/admin/*`, wrapped with admin permission check

---

## Common Mistakes to Avoid

| Wrong | Right |
|-------|-------|
| `Auth::attempt()` in web routes | `POST /api/v1/login` returns Bearer token |
| `view('auth.login')` for `/login` | React SPA handles login at `/login` |
| `auth()->user()` in `BootstrapDataService` | Returns null for token users — use `auth('sanctum')->user()` or rely on frontend rehydration |
| `extends HandlesAuthorization` in Policies | `extends Common\Core\BasePolicy` |
| `Common\Auth\Models\User` in tests | `App\Models\User` |
| Adding `permissions` to `plugins.json` | Create a `{Name}PermissionSeeder.php` |
| Enabling plugins that don't have a directory | Only enable plugins with actual code in `app/Plugins/` |
| `bg-white` or `text-gray-900` in components | Use dark palette above |
| `public/build` in `.gitignore` | Build is committed — shared hosting has no Node |
| Calling `loadRoutes()` in `AppServiceProvider::booted()` | `auto_discover` is disabled — `routes/api.php` is authoritative |
| Writing plugin enable/disable to `plugin_status` DB and expecting it to affect routes | `routes/api.php` reads `config/plugins.json` — both must agree |

---

## Post-Copilot Session Audit

Run this after any Copilot session before testing:

```bash
# Policies: must extend BasePolicy
grep -rn "HandlesAuthorization" app/Plugins/ --include="*.php"

# Tests: must use App\Models\User
grep -rn "Common\\Auth\\Models\\User" tests/ --include="*.php"

# No double route loading
grep -n "loadRoutes\|auto_discover" app/Providers/AppServiceProvider.php

# No Blade auth routes
grep -n "view.*auth.login\|Auth::attempt" routes/web.php

# No light-mode classes in plugin components
grep -rn "bg-white\|bg-gray-50\|text-gray-900" resources/client/plugins/ --include="*.tsx"
```

---

## Known Architecture — Two PluginManagers (DO NOT CONFUSE)

| | `Common\Core\PluginManager` | `App\Services\PluginManager` |
|-|-----------------------------|------------------------------|
| Source | `common/foundation/src/Core/` | `app/Services/` (Copilot-added) |
| Reads from | `config/plugins.json` | `plugin_status` DB table |
| Used by | `routes/api.php` route loading | Admin plugin toggle UI only |
| Status | **Active and authoritative** | `auto_discover` disabled |

Never call `app(App\Services\PluginManager::class)->loadRoutes()` — it double-loads all plugin routes.

---

## Seeder Pattern

Demo/permission seeders must be idempotent:
```php
// Early exit guard
if (DB::table('sermons')->count() > 0) return;

// Upsert pattern for foreign key dependencies
$speakerId = DB::table('sermon_speakers')->where('slug', 'john')->value('id')
    ?? DB::table('sermon_speakers')->insertGetId([...]);
```

Run order: `PermissionSeeder → RoleSeeder → DemoSeeder`

---

## Deployment

Shared hosting deploy (no Node.js on server):
```bash
# Local: build and commit
npm run build
git add public/build/
git commit -m "build: rebuild assets"
git push

# Server:
git pull
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

Installer lock file: `storage/installed` — must exist for app to run (checked by `CheckInstalled` middleware).
