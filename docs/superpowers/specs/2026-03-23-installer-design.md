# Church Platform — Installer Design Spec
**Date:** 2026-03-23
**Status:** Approved (rev 2 — spec-reviewer fixes applied)

---

## Overview

A self-contained 3-step installer for the Church Platform that requires zero manual shell work. It ships as a Laravel plugin (`InstallerPlugin`) with Blade+Tailwind CDN web UI and a matching `php artisan church:install` CLI command. Both paths share a single `InstallerService` so logic is never duplicated.

The installer is permanently locked after completion — `storage/installed.lock` is created on success and the `/install` routes return 404 from that point forward.

---

## Architecture

```
plugins/Installer/
  InstallerServiceProvider.php       ← registers routes only when not installed
  Controllers/
    InstallerController.php          ← GET/POST handlers for each step
  Services/
    InstallerService.php             ← all side-effect logic (env, dirs, migrations)
  Commands/
    InstallCommand.php               ← php artisan church:install
  resources/views/installer/
    layout.blade.php                 ← shared Tailwind CDN shell (no build needed)
    step1-requirements.blade.php
    step2-database.blade.php
    step3-admin.blade.php
    complete.blade.php
```

### Route Registration Guard

`InstallerServiceProvider::boot()` registers routes using **`web` middleware** (installer needs sessions for CSRF + step-to-step state) only when `storage/installed.lock` is absent:

```php
if (!file_exists(storage_path('installed.lock'))) {
    $router->middleware('web')->prefix('install')
           ->group(__DIR__ . '/../routes/installer.php');
}
```

> **Important:** Once `installed.lock` exists AND `route:cache` has been run (which happens as the very last action in Step 3 — after locking), the route cache will NOT contain the installer routes because they were unregistered before caching. This is the correct ordering that guarantees the security lock. See Step 3 finalisation order below.

---

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/install` | Redirect to `/install/step1` |
| GET | `/install/step1` | Show requirements checklist |
| POST | `/install/step1` | Mark step 1 complete, redirect to step 2 |
| GET | `/install/step2` | Show DB credentials form |
| POST | `/install/step2` | Validate + test DB, write .env slice, run migrations |
| GET | `/install/step3` | Show admin account form |
| POST | `/install/step3` | Create roles + admin + church, finalise .env, lock installer, warm caches |
| GET | `/install/complete` | Success screen |

No `auth:sanctum` middleware — installer runs before any users exist.
All POST routes are protected by Laravel's standard `VerifyCsrfToken` middleware (included in the `web` stack). All Blade forms must include `@csrf`.

---

## Step 1 — Requirements Check

### Auto-setup (runs on `GET /install/step1` page load, before user sees checklist)

`InstallerService::prepareEnvironment()` performs all of these silently:

1. Create `bootstrap/cache/` if missing → `chmod(0775)`
2. `chmod(0775)` on `storage/`, `storage/app/`, `storage/framework/`, `storage/logs/`
3. Write root `/.htaccess` if missing (redirects all traffic to `public/`)
4. Write `public/.htaccess` if missing (Laravel standard front-controller rewrite)
5. Copy `.env.example` → `.env` if `.env` does not exist

Then on `POST /install/step1`, `InstallerService::writeStep1Env()` writes the Step 1 slice and calls `Artisan::call('key:generate')`. The POST handler then **redirects to `GET /install/step2`** — this causes a fresh request lifecycle so the newly generated `APP_KEY` is loaded from disk by `Dotenv` before the next view renders. The checklist success is stored in the session.

> **Why redirect after step1 POST rather than checking on the GET?**
> `Artisan::call('key:generate')` writes the key to disk but the current request has already bootstrapped with an empty key in memory. `config('app.key')` remains empty for the rest of that request. The redirect forces a new bootstrap cycle so `config('app.key')` is correctly populated for all subsequent requests.

### Checklist displayed to user (all auto-fixed before display)

| Check | Pass Condition |
|-------|---------------|
| PHP ≥ 8.2 | `PHP_VERSION_ID >= 80200` |
| pdo_mysql | `extension_loaded('pdo_mysql')` |
| mbstring | `extension_loaded('mbstring')` |
| openssl | `extension_loaded('openssl')` |
| tokenizer | `extension_loaded('tokenizer')` |
| xml | `extension_loaded('xml')` |
| ctype | `extension_loaded('ctype')` |
| json | `extension_loaded('json')` |
| bcmath | `extension_loaded('bcmath')` |
| `storage/` writable | `is_writable(storage_path())` |
| `bootstrap/cache/` writable | `is_writable(base_path('bootstrap/cache'))` |
| Root `.htaccess` | `file_exists(base_path('.htaccess'))` |
| Public `.htaccess` | `file_exists(public_path('.htaccess'))` |
| `vendor/` present | `is_dir(base_path('vendor'))` |

"Next →" button is enabled only when all checks pass. If `vendor/` is missing, a red message explains `composer install` must be run manually first — this is the only item that cannot be auto-fixed.

### .env written on POST /install/step1

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://detected-host.com   ← auto-detected from request()->getSchemeAndHttpHost()
APP_KEY=base64:...generated...      ← written by key:generate
```

---

## Step 2 — Database Credentials

### Form fields

| Field | Default | Validation |
|-------|---------|------------|
| App Name | `Church Platform` | required, string, max:100 |
| DB Host | `127.0.0.1` | required, string |
| DB Port | `3306` | required, integer, between:1,65535 |
| DB Name | `church_platform` | required, string |
| DB Username | `root` | required, string |
| DB Password | *(empty)* | nullable, string — **single field, no confirm** |

### On POST /install/step2

1. Validate form
2. Test DB connection via raw PDO — return inline validation error if it fails (no page reload needed; a standard `back()->withErrors()` is sufficient)
3. Write Step 2 `.env` slice via `InstallerService::updateEnv()`:
   ```
   APP_NAME="Church Platform"
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=church_platform
   DB_USERNAME=root
   DB_PASSWORD=secret
   ```
4. Run `Artisan::call('config:clear')`
5. Run `php artisan migrate --force` via `Process::run()` (spawns fresh PHP process that reads the newly-written `.env`)
6. Redirect to `GET /install/step3`

---

## Step 3 — Admin Account

### Form fields

| Field | Validation |
|-------|------------|
| Admin Name | required, string, max:100 |
| Admin Email | required, email, unique:users |
| Admin Password | required, string, min:8 — **single field, no confirm** |

### On POST /install/step3 — finalisation order (order is critical)

1. Validate form
2. `InstallerService::seedRoles()` — create `admin`, `church_leader`, `member` roles via Spatie (`Role::firstOrCreate(['name' => 'admin'])` etc.)
3. `InstallerService::createAdmin(array $data): User` — create `User`, call `$user->assignRole('admin')`
4. `InstallerService::createDefaultChurch(string $name, int $createdBy): Church` — create default `Church` record with `name = APP_NAME` and `created_by = $admin->id`
5. `InstallerService::createStorageLink()` — calls `Artisan::call('storage:link')` so Spatie MediaLibrary uploads resolve correctly
6. Write Step 3 `.env` slice:
   ```
   APP_INSTALLED=true
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   ```
7. **`InstallerService::lockInstaller()`** — write `storage/installed.lock` with install timestamp
   *(lock MUST be written before route cache — this ensures installer routes are absent when cache is built)*
8. **`InstallerService::warmCaches()`** — `Artisan::call('config:cache')` then `Artisan::call('route:cache')`
   *(runs after lock — the ServiceProvider's guard fires during cache build and excludes installer routes)*
9. Redirect to `/install/complete`

> **Why lockInstaller() before warmCaches()?**
> `route:cache` bootstraps the application and serialises all currently-registered routes. If `installed.lock` exists at that moment, the ServiceProvider guard skips registering the installer routes, so they are absent from the cache. If the order were reversed, the installer routes would be permanently baked into the route cache.

---

## InstallerService — Complete Method List

```php
class InstallerService
{
    // Step 1 — environment prep (runs on GET /install/step1)
    public function prepareEnvironment(): void
        // creates bootstrap/cache/, chmods storage/, writes .htaccess files, copies .env.example

    public function checkRequirements(): array
        // returns ['php' => true, 'pdo_mysql' => true, 'vendor' => false, ...]

    public function writeStep1Env(): void
        // writes APP_ENV, APP_DEBUG, APP_URL, then calls Artisan::call('key:generate')

    // .htaccess
    public function writeRootHtaccess(): void
    public function writePublicHtaccess(): void

    // Directory setup
    public function prepareDirectories(): void
        // mkdir bootstrap/cache if missing, chmod storage tree

    // .env management
    public function updateEnv(array $values): void
        // see updateEnv() contract below

    // Step 2 — database
    public function testConnection(array $config): bool
        // raw PDO, never logs credentials

    public function runMigrations(): void
        // Process::run(['php', 'artisan', 'migrate', '--force'])

    // Step 3 — finalise
    public function seedRoles(): void
        // Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']) × 3

    public function createAdmin(array $data): User

    public function createDefaultChurch(string $name, int $createdBy): Church

    public function createStorageLink(): void
        // Artisan::call('storage:link')

    public function lockInstaller(): void
        // file_put_contents(storage_path('installed.lock'), now()->toIso8601String())

    public function warmCaches(): void
        // Artisan::call('config:cache') then Artisan::call('route:cache')
}
```

### `updateEnv()` contract

- Reads `.env` line by line
- For each `KEY=value` or `KEY="quoted value"` line: if the key exists in the update array, replace the whole line
- Appends any keys from the update array that were not found
- Multi-word values are always written with double-quotes: `APP_NAME="Church Platform"`
- Writes to a **temp file first**, then `rename()`s atomically — prevents a concurrent child `artisan` process from reading a half-written file
- Uses regex: `/^(KEY)=.*/` per key to match both quoted and unquoted existing values

---

## Artisan Command — `php artisan church:install`

`InstallCommand` calls the same `InstallerService` methods interactively. Password prompts use `$this->secret()` (hidden, no confirm). If `storage/installed.lock` already exists, exits immediately:

```
Already installed. Delete storage/installed.lock to re-run (then clear route cache).
```

**CLI flow:**

```
Step 1 — Requirements
  prepareEnvironment() → print checklist
  writeStep1Env()      → APP_KEY generated

Step 2 — Database  ($this->ask() for each field)
  testConnection()     → abort with error if fails
  updateEnv()          → write DB slice
  runMigrations()

Step 3 — Admin Account  ($this->ask() / $this->secret())
  seedRoles()
  createAdmin()
  createDefaultChurch($name, $admin->id)
  createStorageLink()
  updateEnv()          → write final slice
  lockInstaller()      ← BEFORE warmCaches
  warmCaches()

→ print "🎉 Installation complete! Visit: {APP_URL}"
```

---

## .htaccess File Contents

### Root `/.htaccess`
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>
```

### `public/.htaccess`
Standard Laravel front-controller rewrite (identical to Laravel 11 skeleton default).

---

## UI Design (Blade + Tailwind CDN)

- Clean white card, centred on a light grey background
- Progress indicator at top: `① Requirements → ② Database → ③ Admin Account`
- Active step highlighted in blue; completed steps show ✅
- Error messages render inline in red below the relevant field
- All forms include `@csrf` (required by `web` middleware CSRF protection)
- Tailwind loaded via CDN `<script>` tag in `layout.blade.php` — zero build step required
- No JavaScript framework; one `fetch()` call on step 2 for the DB connection test button

---

## Security

| Concern | Mitigation |
|---------|-----------|
| Installer re-run on live site | `installed.lock` created BEFORE `route:cache`; cached routes never include installer |
| Exposed credentials in URL | All sensitive fields POST only |
| DB password in logs | `testConnection()` never logs; PDO exception caught and message sanitised |
| Admin password in logs | `$this->secret()` on CLI; `type="password"` + no logging on web |
| `.env` readable by web | `public/.htaccess` blocks direct `.env` access |
| CSRF | All POST routes under `web` middleware; all Blade forms include `@csrf` |
| Atomic `.env` writes | `updateEnv()` writes temp file + `rename()` — safe against concurrent reads |

---

## Out of Scope

- `npm install` / `npm run build` — Vite assets assumed pre-built in `public/build/`
- Multi-database support (Postgres, SQLite) — MySQL only; changeable in `.env` post-install
- Email/SMTP configuration — set in `.env` post-install
- Re-installer / upgrade wizard — separate concern
