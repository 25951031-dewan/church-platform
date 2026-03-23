# Church Platform — Installer Design Spec
**Date:** 2026-03-23
**Status:** Approved

---

## Overview

A self-contained 3-step installer for the Church Platform that requires zero manual shell work. It ships as a Laravel plugin (`InstallerPlugin`) with Blade+Tailwind web UI and a matching `php artisan church:install` CLI command. Both paths share a single `InstallerService` so logic is never duplicated.

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
  resources/views/
    layout.blade.php                 ← shared Tailwind CDN shell
    step1-requirements.blade.php
    step2-database.blade.php
    step3-admin.blade.php
    complete.blade.php
```

### Route Registration Guard

`InstallerServiceProvider::boot()` checks for `storage/installed.lock` before registering routes:

```php
if (!file_exists(storage_path('installed.lock'))) {
    $router->prefix('install')->group(...);
}
```

Once locked, visiting `/install` returns 404 — no re-installation possible on a live site.

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
| POST | `/install/step3` | Create admin + church, finalise .env, lock installer |
| GET | `/install/complete` | Success screen |

No `auth:sanctum` middleware — installer runs before any users exist.

---

## Step 1 — Requirements Check

### Auto-setup (runs on page load, before user sees checklist)

`InstallerService::prepareEnvironment()` performs all of these silently:

1. Create `bootstrap/cache/` if missing → `chmod 0775`
2. `chmod 0775` on `storage/` and all subdirectories
3. Write root `/.htaccess` if missing (redirects all traffic to `public/`)
4. Write `public/.htaccess` if missing (Laravel standard front-controller rewrite)
5. Copy `.env.example` → `.env` if `.env` does not exist
6. Write Step 1 `.env` slice:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL` — auto-detected from `$_SERVER['HTTP_HOST']`
   - `APP_KEY` — generated via `Artisan::call('key:generate')`

### Checklist displayed to user

| Check | Pass Condition |
|-------|---------------|
| PHP ≥ 8.2 | `PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 2` |
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
| APP_KEY set | `config('app.key') !== ''` |

"Next →" button is enabled only when all checks pass. If any check fails (e.g. vendor/ missing), a red error message explains the required manual step.

### .env written at end of Step 1

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://detected-host.com
APP_KEY=base64:...generated...
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
2. Test DB connection via raw PDO — return inline error without page reload if it fails
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
4. Run `php artisan config:clear`
5. Run `php artisan migrate --force` via `Process::run()`
6. Redirect to Step 3

---

## Step 3 — Admin Account

### Form fields

| Field | Validation |
|-------|------------|
| Admin Name | required, string, max:100 |
| Admin Email | required, email, unique:users |
| Admin Password | required, string, min:8 — **single field, no confirm** |

### On POST /install/step3

1. Validate form
2. Create `User` record
3. Assign `admin` role via Spatie permissions (`$user->assignRole('admin')`)
4. Create default `Church` record: `name = APP_NAME`, `slug` auto-generated
5. Write Step 3 `.env` slice:
   ```
   APP_INSTALLED=true
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   ```
6. Run `php artisan config:cache && php artisan route:cache`
7. Create `storage/installed.lock` (contains install timestamp)
8. Redirect to `/install/complete`

---

## InstallerService — Key Methods

```php
class InstallerService
{
    // Step 1 auto-setup
    public function prepareEnvironment(): array          // returns ['check' => bool, ...]
    public function checkRequirements(): array           // returns pass/fail per item

    // .env management
    public function updateEnv(array $values): void       // safe key=value writer
    public function generateAppKey(): void               // calls Artisan::call('key:generate')

    // .htaccess
    public function writeRootHtaccess(): void
    public function writePublicHtaccess(): void

    // Directory setup
    public function prepareDirectories(): void           // create + chmod bootstrap/cache, storage

    // Database
    public function testConnection(array $config): bool  // raw PDO connection test
    public function runMigrations(): void                // Process::run artisan migrate

    // Finalise
    public function createAdmin(array $data): User
    public function createDefaultChurch(string $name): Church
    public function lockInstaller(): void               // writes storage/installed.lock
    public function warmCaches(): void                  // config:cache + route:cache
}
```

### `updateEnv()` implementation strategy

Reads `.env` line by line. For each `KEY=value` line, replaces the value if the key is in the update array. Appends any keys not already present. Never corrupts previously written keys.

---

## Artisan Command — `php artisan church:install`

`InstallCommand` calls the same `InstallerService` methods interactively:

```
Step 1: prepareEnvironment() → print checklist results
Step 2: $this->ask() for DB fields → testConnection() → updateEnv() → runMigrations()
Step 3: $this->ask() for admin fields → createAdmin() → createDefaultChurch()
        → updateEnv() → lockInstaller() → warmCaches()
```

Password prompts use `$this->secret()` (hidden input). No confirm prompt for passwords.

If `storage/installed.lock` already exists, the command exits early with:
```
Already installed. Delete storage/installed.lock to re-run.
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
Standard Laravel front-controller rewrite (identical to Laravel skeleton).

---

## UI Design (Blade + Tailwind CDN)

- Clean white card, centred on a light grey background
- Progress indicator at top: `① Requirements → ② Database → ③ Admin Account`
- Active step highlighted in blue
- Completed steps show ✅ checkmark
- Error messages render inline in red below the relevant field
- No JavaScript framework required — standard form POST, minimal vanilla JS for the DB connection test (one `fetch()` call)
- Tailwind loaded via CDN `<script>` tag — zero build step required

---

## Security

| Concern | Mitigation |
|---------|-----------|
| Installer re-run on live site | Routes only registered when `installed.lock` absent |
| Exposed credentials in URL | All sensitive fields POST only |
| DB password in logs | `InstallerService::testConnection()` never logs credentials |
| Admin password in logs | `$this->secret()` on CLI; `type="password"` + no logging on web |
| .env readable by web | `public/.htaccess` blocks direct access to `.env` |

---

## Out of Scope

- `npm install` / `npm run build` — Vite assets assumed pre-built in `public/build/`
- Multi-database support (Postgres, SQLite) — MySQL only for installer; can be changed in `.env` post-install
- Email/SMTP configuration — set in `.env` post-install
- Re-installer / upgrade wizard — separate concern
