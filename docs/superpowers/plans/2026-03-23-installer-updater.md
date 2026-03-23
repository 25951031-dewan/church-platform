# Installer & Update System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a zero-manual-work 3-step installer (`/install`) and admin update dashboard (`/update`) as a single Laravel plugin, with matching artisan commands sharing all business logic.

**Architecture:** A single `InstallerPlugin` registers `web`-middleware Blade routes conditionally for the installer (guarded by `storage/installed.lock`) and always for the updater (guarded by `auth`+`role:admin`). All side-effect logic lives in `InstallerService` and `UpdaterService`. Controllers are thin — validate input, call service, redirect or stream.

**Tech Stack:** Laravel 11, Blade + Tailwind CDN (no build step), Spatie laravel-permission (roles), `Process` facade (artisan subprocesses), `PHP_BINARY` constant, SSE (`StreamedResponse`) for update progress.

**Spec:** `docs/superpowers/specs/2026-03-23-installer-design.md`

**Run tests with:** `"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest`
**Run single file:** `"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php`
**Format PHP:** `vendor/bin/pint`

---

## File Map

| File | Create/Modify | Responsibility |
|------|--------------|----------------|
| `plugins/Installer/plugin.json` | Create | Plugin metadata |
| `plugins/Installer/InstallerServiceProvider.php` | Create | Register web routes conditionally; load views |
| `plugins/Installer/routes/installer.php` | Create | `/install` route definitions |
| `plugins/Installer/routes/updater.php` | Create | `/update` route definitions |
| `plugins/Installer/Services/InstallerService.php` | Create | File ops, env writing, DB testing, migrations, admin creation |
| `plugins/Installer/Services/UpdaterService.php` | Create | Version check, git pull, composer install, SSE step runner |
| `plugins/Installer/Controllers/InstallerController.php` | Create | Thin HTTP handlers for steps 1-3 and complete |
| `plugins/Installer/Controllers/UpdaterController.php` | Create | GET /update dashboard + GET /update/run SSE stream |
| `plugins/Installer/Commands/InstallCommand.php` | Create | `php artisan church:install` interactive CLI |
| `plugins/Installer/Commands/UpdateCommand.php` | Create | `php artisan church:update` interactive CLI |
| `plugins/Installer/resources/views/installer/layout.blade.php` | Create | Tailwind CDN shell, step progress indicator |
| `plugins/Installer/resources/views/installer/step1.blade.php` | Create | Requirements checklist view |
| `plugins/Installer/resources/views/installer/step2.blade.php` | Create | DB credentials form |
| `plugins/Installer/resources/views/installer/step3.blade.php` | Create | Admin account form |
| `plugins/Installer/resources/views/installer/complete.blade.php` | Create | Success screen |
| `plugins/Installer/resources/views/installer/update.blade.php` | Create | Update dashboard + SSE log |
| `config/version.php` | Create | `APP_VERSION` constant |
| `bootstrap/providers.php` | Modify | Register `InstallerServiceProvider` |
| `tests/Feature/InstallerTest.php` | Create | HTTP + service tests for install flow |
| `tests/Feature/UpdaterTest.php` | Create | HTTP + service tests for update flow |

---

## Task 1: Plugin Scaffold

**Files:**
- Create: `plugins/Installer/plugin.json`
- Create: `plugins/Installer/InstallerServiceProvider.php`
- Create: `plugins/Installer/routes/installer.php`
- Create: `plugins/Installer/routes/updater.php`
- Create: `config/version.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Create `plugin.json`**

```json
{
  "name": "Installer",
  "slug": "installer",
  "version": "1.0.0",
  "description": "3-step web installer and admin update dashboard for the Church Platform.",
  "author": "Church Platform",
  "icon": "download",
  "category": "Core",
  "requires": [],
  "settings_page": false,
  "can_disable": false,
  "can_remove": false,
  "enabled_by_default": true
}
```

- [ ] **Step 2: Create `InstallerServiceProvider.php`**

```php
<?php

namespace Plugins\Installer;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class InstallerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'installer');

        $router = $this->app->make(Router::class);

        // Installer: web middleware, only when not yet installed
        if (! file_exists(storage_path('installed.lock'))) {
            $router->middleware('web')
                   ->prefix('install')
                   ->group(base_path('plugins/Installer/routes/installer.php'));
        }

        // Updater: always registered, guarded by auth + admin role
        $router->middleware(['web', 'auth', 'role:admin'])
               ->prefix('update')
               ->group(base_path('plugins/Installer/routes/updater.php'));
    }

    public function register(): void
    {
        $this->commands([
            Commands\InstallCommand::class,
            Commands\UpdateCommand::class,
        ]);
    }
}
```

- [ ] **Step 3: Create `routes/installer.php`**

```php
<?php

use Illuminate\Support\Facades\Route;
use Plugins\Installer\Controllers\InstallerController;

Route::get('/',         fn () => redirect('/install/step1'));
Route::get('/step1',    [InstallerController::class, 'step1']);
Route::post('/step1',   [InstallerController::class, 'postStep1']);
Route::get('/step2',    [InstallerController::class, 'step2']);
Route::post('/step2',   [InstallerController::class, 'postStep2']);
Route::get('/step3',    [InstallerController::class, 'step3']);
Route::post('/step3',   [InstallerController::class, 'postStep3']);
Route::get('/complete', [InstallerController::class, 'complete']);
```

- [ ] **Step 4: Create `routes/updater.php`**

```php
<?php

use Illuminate\Support\Facades\Route;
use Plugins\Installer\Controllers\UpdaterController;

Route::get('/',    [UpdaterController::class, 'dashboard'])->name('update.dashboard');
// GET (not POST) — EventSource browser API only supports GET requests for SSE streams.
// The 'signed' middleware verifies a Laravel signed URL, providing CSRF-equivalent protection.
Route::get('/run', [UpdaterController::class, 'run'])->name('update.run')->middleware('signed');
```

- [ ] **Step 5: Create `config/version.php`**

```php
<?php

return [
    'current'      => env('APP_VERSION', '1.0.0'),
    'releases_api' => env(
        'RELEASES_API_URL',
        'https://api.github.com/repos/YOUR_ORG/church-platform/releases/latest'
    ),
];
```

- [ ] **Step 6: Register in `bootstrap/providers.php`** — add before closing `]`:

```php
Plugins\Installer\InstallerServiceProvider::class,
```

- [ ] **Step 7: Commit scaffold**

```bash
git add plugins/Installer/ config/version.php bootstrap/providers.php
git commit -m "feat(installer): scaffold InstallerPlugin — ServiceProvider, routes, version config"
```

---

## Task 2: InstallerService — File Utilities

**Files:**
- Create: `plugins/Installer/Services/InstallerService.php`
- Test: `tests/Feature/InstallerTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/InstallerTest.php

use Plugins\Installer\Services\InstallerService;

test('updateEnv writes new key-value pairs atomically', function () {
    $envPath = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
    file_put_contents($envPath, "APP_NAME=\"Old Name\"\nAPP_DEBUG=true\n");

    $service = new InstallerService($envPath);
    $service->updateEnv(['APP_NAME' => 'New Church', 'APP_KEY' => 'base64:abc123']);

    $contents = file_get_contents($envPath);
    expect($contents)->toContain('APP_NAME="New Church"');
    expect($contents)->toContain('APP_KEY=base64:abc123');
    expect($contents)->toContain('APP_DEBUG=true');

    unlink($envPath);
});

test('updateEnv handles multi-word values with quotes', function () {
    $envPath = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
    file_put_contents($envPath, "APP_NAME=\"Church Platform\"\n");

    $service = new InstallerService($envPath);
    $service->updateEnv(['APP_NAME' => 'My Great Church']);

    expect(file_get_contents($envPath))->toContain('APP_NAME="My Great Church"');
    unlink($envPath);
});

test('prepareDirectories creates bootstrap/cache if missing', function () {
    $tempBase = sys_get_temp_dir() . '/church_test_' . uniqid();
    mkdir($tempBase . '/storage/app', 0755, true);
    mkdir($tempBase . '/storage/framework/sessions', 0755, true);
    mkdir($tempBase . '/storage/logs', 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->prepareDirectories();

    expect(is_dir($tempBase . '/bootstrap/cache'))->toBeTrue();
    expect(is_writable($tempBase . '/bootstrap/cache'))->toBeTrue();

    exec("rm -rf {$tempBase}");
});

test('writeRootHtaccess creates root .htaccess if missing', function () {
    $tempBase = sys_get_temp_dir() . '/church_test_' . uniqid();
    mkdir($tempBase, 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->writeRootHtaccess();

    expect(file_exists($tempBase . '/.htaccess'))->toBeTrue();
    expect(file_get_contents($tempBase . '/.htaccess'))->toContain('RewriteRule');

    exec("rm -rf {$tempBase}");
});
```

- [ ] **Step 2: Run tests — confirm FAIL**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php
```

- [ ] **Step 3: Create `InstallerService.php`**

```php
<?php

namespace Plugins\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class InstallerService
{
    private string $envPath;
    private string $basePath;
    private string $publicPath;
    private string $storagePath;

    public function __construct(
        ?string $envPath  = null,
        ?string $basePath = null,
    ) {
        $this->basePath    = $basePath   ?? base_path();
        $this->envPath     = $envPath    ?? $this->basePath . '/.env';
        $this->publicPath  = $this->basePath . '/public';
        $this->storagePath = $this->basePath . '/storage';
    }

    // ── .env management ───────────────────────────────────────────────────────

    /**
     * Safely update key=value pairs in .env.
     * Uses atomic write (temp file + rename) to prevent half-written reads by
     * concurrent child artisan processes. Multi-word values are always quoted.
     */
    public function updateEnv(array $values): void
    {
        $lines   = file_exists($this->envPath) ? file($this->envPath, FILE_IGNORE_NEW_LINES) : [];
        $updated = [];

        foreach ($lines as $line) {
            $matched = false;
            foreach ($values as $key => $value) {
                if (preg_match('/^' . preg_quote($key, '/') . '=/', $line)) {
                    $updated[] = $this->formatEnvLine($key, $value);
                    unset($values[$key]);
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                $updated[] = $line;
            }
        }

        foreach ($values as $key => $value) {
            $updated[] = $this->formatEnvLine($key, $value);
        }

        $tmp = $this->envPath . '.tmp.' . uniqid();
        file_put_contents($tmp, implode("\n", $updated) . "\n");
        rename($tmp, $this->envPath);
    }

    private function formatEnvLine(string $key, mixed $value): string
    {
        $value = (string) $value;
        if (str_contains($value, ' ') || str_contains($value, '#') || $value === '') {
            return $key . '="' . addslashes($value) . '"';
        }
        return $key . '=' . $value;
    }

    // ── Directories + permissions ─────────────────────────────────────────────

    public function prepareDirectories(): void
    {
        $bootstrapCache = $this->basePath . '/bootstrap/cache';
        if (! is_dir($bootstrapCache)) {
            mkdir($bootstrapCache, 0775, true);
        }
        chmod($bootstrapCache, 0775);

        foreach ($this->storageDirs() as $dir) {
            if (! is_dir($dir)) mkdir($dir, 0775, true);
            chmod($dir, 0775);
        }
    }

    private function storageDirs(): array
    {
        return [
            $this->storagePath,
            $this->storagePath . '/app',
            $this->storagePath . '/app/public',
            $this->storagePath . '/framework',
            $this->storagePath . '/framework/cache',
            $this->storagePath . '/framework/sessions',
            $this->storagePath . '/framework/views',
            $this->storagePath . '/logs',
        ];
    }

    // ── .htaccess ─────────────────────────────────────────────────────────────

    public function writeRootHtaccess(): void
    {
        $path = $this->basePath . '/.htaccess';
        if (file_exists($path)) return;

        file_put_contents($path, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>
HTACCESS);
    }

    public function writePublicHtaccess(): void
    {
        $path = $this->publicPath . '/.htaccess';
        if (file_exists($path)) return;

        file_put_contents($path, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS);
    }

    // ── Step 1 env write ──────────────────────────────────────────────────────

    public function prepareEnvironment(): void
    {
        $this->prepareDirectories();
        $this->writeRootHtaccess();
        $this->writePublicHtaccess();

        if (! file_exists($this->envPath) && file_exists($this->basePath . '/.env.example')) {
            copy($this->basePath . '/.env.example', $this->envPath);
        }
    }

    public function checkRequirements(): array
    {
        return [
            'php'             => PHP_VERSION_ID >= 80200,
            'pdo_mysql'       => extension_loaded('pdo_mysql'),
            'mbstring'        => extension_loaded('mbstring'),
            'openssl'         => extension_loaded('openssl'),
            'tokenizer'       => extension_loaded('tokenizer'),
            'xml'             => extension_loaded('xml'),
            'ctype'           => extension_loaded('ctype'),
            'json'            => extension_loaded('json'),
            'bcmath'          => extension_loaded('bcmath'),
            'storage'         => is_writable($this->storagePath),
            'bootstrap_cache' => is_writable($this->basePath . '/bootstrap/cache'),
            'root_htaccess'   => file_exists($this->basePath . '/.htaccess'),
            'public_htaccess' => file_exists($this->publicPath . '/.htaccess'),
            'vendor'          => is_dir($this->basePath . '/vendor'),
        ];
    }

    public function writeStep1Env(string $appUrl): void
    {
        $this->updateEnv([
            'APP_ENV'   => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL'   => $appUrl,
        ]);
        Artisan::call('key:generate', ['--force' => true]);
        // key:generate writes APP_KEY to disk. The caller MUST redirect after this
        // so the next request re-bootstraps and loads the key from disk.
    }

    // ── Database (Step 2) ─────────────────────────────────────────────────────

    public function testConnection(array $config): bool
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            new \PDO($dsn, $config['username'], $config['password']);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function runMigrations(): void
    {
        // PHP_BINARY ensures the same PHP version runs migrations as serves the web request.
        Process::run([PHP_BINARY, $this->basePath . '/artisan', 'migrate', '--force'])->throw();
    }

    // ── Finalise (Step 3) ─────────────────────────────────────────────────────

    public function seedRoles(): void
    {
        foreach (['admin', 'church_leader', 'member'] as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    public function createAdmin(array $data): \App\Models\User
    {
        $user = \App\Models\User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->assignRole('admin');
        return $user;
    }

    public function createDefaultChurch(string $name, int $createdBy): \App\Models\Church
    {
        return \App\Models\Church::create([
            'name'       => $name,
            'slug'       => \Illuminate\Support\Str::slug($name),
            'status'     => 'active',
            'created_by' => $createdBy,
        ]);
    }

    public function createStorageLink(): void
    {
        Artisan::call('storage:link', ['--force' => true]);
    }

    public function lockInstaller(): void
    {
        // MUST be called before warmCaches() so installer routes are absent from route cache.
        file_put_contents(storage_path('installed.lock'), now()->toIso8601String());
    }

    public function warmCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
    }
}
```

- [ ] **Step 4: Run tests — all 4 pass**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php
```

- [ ] **Step 5: Pint format**

```bash
vendor/bin/pint plugins/Installer/Services/InstallerService.php
```

- [ ] **Step 6: Commit**

```bash
git add plugins/Installer/Services/InstallerService.php tests/Feature/InstallerTest.php
git commit -m "feat(installer): InstallerService — atomic updateEnv, directories, htaccess, requirements"
```

---

## Task 3: InstallerController + All Views

**Files:**
- Create: `plugins/Installer/Controllers/InstallerController.php`
- Create: all `resources/views/installer/*.blade.php`

- [ ] **Step 1: Write failing HTTP tests**

Append to `tests/Feature/InstallerTest.php`:

```php
test('GET /install/step1 shows requirements checklist', function () {
    if (file_exists(storage_path('installed.lock'))) unlink(storage_path('installed.lock'));
    $this->get('/install/step1')->assertStatus(200)->assertSee('Requirements');
});

test('POST /install/step1 redirects to step2', function () {
    if (file_exists(storage_path('installed.lock'))) unlink(storage_path('installed.lock'));
    // withoutMiddleware bypasses VerifyCsrfToken — installer web routes require CSRF
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
         ->post('/install/step1')
         ->assertRedirect('/install/step2');
});

test('POST /install/step2 validates required db fields', function () {
    if (file_exists(storage_path('installed.lock'))) unlink(storage_path('installed.lock'));
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
         ->post('/install/step2', [])
         ->assertSessionHasErrors(['db_host', 'db_database', 'db_username']);
});

test('GET /install/step3 shows admin form', function () {
    if (file_exists(storage_path('installed.lock'))) unlink(storage_path('installed.lock'));
    $this->get('/install/step3')->assertStatus(200)->assertSee('Admin');
});

test('POST /install/step3 validates admin fields', function () {
    if (file_exists(storage_path('installed.lock'))) unlink(storage_path('installed.lock'));
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
         ->post('/install/step3', [])
         ->assertSessionHasErrors(['admin_name', 'admin_email', 'admin_password']);
});
```

- [ ] **Step 2: Run to confirm fail**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php --filter="step"
```

- [ ] **Step 3: Create `InstallerController.php`**

```php
<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Plugins\Installer\Services\InstallerService;

class InstallerController extends Controller
{
    public function __construct(private InstallerService $service) {}

    public function step1(): View
    {
        $this->service->prepareEnvironment();
        $requirements = $this->service->checkRequirements();
        $allPassed    = ! in_array(false, $requirements, true);
        return view('installer::installer.step1', compact('requirements', 'allPassed'));
    }

    public function postStep1(Request $request): RedirectResponse
    {
        $this->service->writeStep1Env($request->getSchemeAndHttpHost());
        // Redirect so next request re-bootstraps and loads APP_KEY from disk
        return redirect('/install/step2');
    }

    public function step2(): View
    {
        return view('installer::installer.step2');
    }

    public function postStep2(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name'    => ['required', 'string', 'max:100'],
            'db_host'     => ['required', 'string'],
            'db_port'     => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        if (! $this->service->testConnection([
            'host'     => $data['db_host'],
            'port'     => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
        ])) {
            return back()->withErrors(['db_host' => 'Could not connect to database. Check credentials.'])->withInput();
        }

        $this->service->updateEnv([
            'APP_NAME'      => $data['app_name'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $data['db_host'],
            'DB_PORT'       => (string) $data['db_port'],
            'DB_DATABASE'   => $data['db_database'],
            'DB_USERNAME'   => $data['db_username'],
            'DB_PASSWORD'   => $data['db_password'] ?? '',
        ]);

        // Store app_name in session — config('app.name') is stale (bootstrapped before .env write).
        // Artisan::call('config:clear') removes the cache but does NOT reload in-memory config.
        session(['installer_app_name' => $data['app_name']]);

        Artisan::call('config:clear');
        $this->service->runMigrations();

        return redirect('/install/step3');
    }

    public function step3(): View
    {
        return view('installer::installer.step3');
    }

    public function postStep3(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_name'     => ['required', 'string', 'max:100'],
            'admin_email'    => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $this->service->seedRoles();
        $admin = $this->service->createAdmin([
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $data['admin_password'],
        ]);
        // Read app_name from session — in-memory config() is stale from before step 2's .env write
        $this->service->createDefaultChurch(session('installer_app_name', 'Church Platform'), $admin->id);
        $this->service->createStorageLink();

        $this->service->updateEnv([
            'APP_INSTALLED'    => 'true',
            'SESSION_DRIVER'   => 'database',
            'CACHE_STORE'      => 'database',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        // CRITICAL: lock BEFORE warmCaches so route:cache excludes installer routes
        $this->service->lockInstaller();
        $this->service->warmCaches();

        return redirect('/install/complete');
    }

    public function complete(): View
    {
        return view('installer::installer.complete');
    }
}
```

- [ ] **Step 4: Create views directory**

```bash
mkdir -p plugins/Installer/resources/views/installer
```

- [ ] **Step 5: Create `layout.blade.php`**

```html
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Church Platform Installer' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4">
<div class="w-full max-w-lg">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">&#9962; Church Platform</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $subtitle ?? 'Installation Wizard' }}</p>
    </div>
    @isset($step)
    <div class="flex items-center justify-center gap-2 mb-6">
        @foreach(['Requirements', 'Database', 'Admin Account'] as $i => $label)
            @php $num = $i + 1; $active = $num === $step; $done = $num < $step; @endphp
            <div class="flex items-center gap-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $done ? 'bg-green-500 text-white' : ($active ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                    {{ $done ? '&#10003;' : $num }}
                </div>
                <span class="text-xs {{ $active ? 'text-blue-700 font-semibold' : 'text-gray-400' }}">{{ $label }}</span>
            </div>
            @if($i < 2)<div class="w-8 h-px bg-gray-300"></div>@endif
        @endforeach
    </div>
    @endisset
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @yield('content')
    </div>
</div>
</body>
</html>
```

- [ ] **Step 6: Create `step1.blade.php`**

```html
@extends('installer::installer.layout')
@php $step = 1; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">System Requirements</h2>
@php
$labels = [
    'php'             => 'PHP >= 8.2',
    'pdo_mysql'       => 'Extension: pdo_mysql',
    'mbstring'        => 'Extension: mbstring',
    'openssl'         => 'Extension: openssl',
    'tokenizer'       => 'Extension: tokenizer',
    'xml'             => 'Extension: xml',
    'ctype'           => 'Extension: ctype',
    'json'            => 'Extension: json',
    'bcmath'          => 'Extension: bcmath',
    'storage'         => 'storage/ writable',
    'bootstrap_cache' => 'bootstrap/cache/ writable',
    'root_htaccess'   => 'Root .htaccess',
    'public_htaccess' => 'public/.htaccess',
    'vendor'          => 'Composer dependencies (vendor/)',
];
@endphp
<div class="space-y-2 mb-6">
@foreach($requirements as $key => $pass)
<div class="flex items-center justify-between py-1.5 border-b border-gray-50">
    <span class="text-sm text-gray-700">{{ $labels[$key] ?? $key }}</span>
    <span class="{{ $pass ? 'text-green-600' : 'text-red-500' }} text-sm font-semibold">
        {{ $pass ? 'OK' : 'Failed' }}
    </span>
</div>
@endforeach
</div>
@if(! $allPassed)
    <p class="text-sm text-red-600 mb-4">Fix the items above. Directories and .htaccess are auto-fixed — refresh to re-check.</p>
@endif
<form method="POST" action="/install/step1">
    @csrf
    <button type="submit" {{ ! $allPassed ? 'disabled' : '' }}
        class="w-full py-2.5 rounded-xl font-semibold text-sm transition-colors
               {{ $allPassed ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">
        Next: Database Setup
    </button>
</form>
@endsection
```

- [ ] **Step 7: Create `step2.blade.php`**

```html
@extends('installer::installer.layout')
@php $step = 2; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">Database & App Settings</h2>
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">{{ $errors->first() }}</div>
@endif
<form method="POST" action="/install/step2" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">App Name</label>
        <input type="text" name="app_name" value="{{ old('app_name', 'Church Platform') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
    </div>
    <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Host</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
            <input type="number" name="db_port" value="{{ old('db_port', '3306') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Database Name</label>
        <input type="text" name="db_database" value="{{ old('db_database', 'church_platform') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Username</label>
            <input type="text" name="db_username" value="{{ old('db_username', 'root') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Password</label>
            <input type="password" name="db_password"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
    </div>
    <button type="submit"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors mt-2">
        Next: Admin Account
    </button>
</form>
@endsection
```

- [ ] **Step 8: Create `step3.blade.php`**

```html
@extends('installer::installer.layout')
@php $step = 3; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">Admin Account</h2>
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">{{ $errors->first() }}</div>
@endif
<form method="POST" action="/install/step3" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Your Name</label>
        <input type="text" name="admin_name" value="{{ old('admin_name') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Admin Email</label>
        <input type="email" name="admin_email" value="{{ old('admin_email') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
        <input type="password" name="admin_password"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <button type="submit"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-green-600 text-white hover:bg-green-700 transition-colors mt-2">
        Complete Installation
    </button>
</form>
@endsection
```

- [ ] **Step 9: Create `complete.blade.php`**

```html
@extends('installer::installer.layout')
@section('content')
<div class="text-center py-4">
    <div class="text-5xl mb-4">&#127881;</div>
    <h2 class="text-xl font-bold text-gray-900 mb-2">Installation Complete!</h2>
    <p class="text-gray-500 text-sm mb-6">Your Church Platform is ready. Log in with your admin account.</p>
    <a href="/" class="inline-block px-6 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        Go to App
    </a>
</div>
@endsection
```

- [ ] **Step 10: Run tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php
```

- [ ] **Step 11: Commit**

```bash
git add plugins/Installer/Controllers/InstallerController.php \
        plugins/Installer/resources/views/
git commit -m "feat(installer): InstallerController + all 4 Blade views — steps 1-3, complete screen"
```

---

## Task 4: Artisan Install Command

**Files:**
- Create: `plugins/Installer/Commands/InstallCommand.php`

- [ ] **Step 1: Write failing test**

Append to `tests/Feature/InstallerTest.php`:

```php
test('church:install exits early when already installed', function () {
    file_put_contents(storage_path('installed.lock'), now()->toIso8601String());

    $this->artisan('church:install')
         ->expectsOutputToContain('Already installed')
         ->assertExitCode(1);

    unlink(storage_path('installed.lock'));
});
```

- [ ] **Step 2: Run to confirm fail**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php --filter="church:install"
```

- [ ] **Step 3: Create `InstallCommand.php`**

```php
<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Plugins\Installer\Services\InstallerService;

class InstallCommand extends Command
{
    protected $signature   = 'church:install';
    protected $description = 'Run the Church Platform 3-step installer interactively.';

    public function handle(InstallerService $service): int
    {
        if (file_exists(storage_path('installed.lock'))) {
            $this->error('Already installed. Delete storage/installed.lock to re-run (then clear route cache).');
            return self::FAILURE;
        }

        $this->info('');
        $this->line('  Church Platform Installer');
        $this->info('');

        // Step 1: Requirements
        $this->line('  Step 1 - Requirements');
        $service->prepareEnvironment();
        $checks = $service->checkRequirements();
        foreach ($checks as $key => $pass) {
            $this->line('  ' . ($pass ? '[OK]' : '[FAIL]') . '  ' . $key);
        }
        if (in_array(false, $checks, true)) {
            $this->error('Fix failed requirements before running the installer.');
            return self::FAILURE;
        }
        $appUrl = $this->ask('App URL', 'http://localhost');
        $service->writeStep1Env($appUrl);
        $this->line('  APP_KEY generated');
        $this->info('');

        // Step 2: Database
        $this->line('  Step 2 - Database');
        $appName = $this->ask('App Name', 'Church Platform');
        $dbHost  = $this->ask('DB Host', '127.0.0.1');
        $dbPort  = $this->ask('DB Port', '3306');
        $dbName  = $this->ask('DB Name', 'church_platform');
        $dbUser  = $this->ask('DB Username', 'root');
        $dbPass  = $this->secret('DB Password');

        if (! $service->testConnection(['host' => $dbHost, 'port' => $dbPort, 'database' => $dbName, 'username' => $dbUser, 'password' => $dbPass ?? ''])) {
            $this->error('Could not connect to database. Check credentials and try again.');
            return self::FAILURE;
        }

        $service->updateEnv([
            'APP_NAME' => $appName, 'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $dbHost,   'DB_PORT' => $dbPort,
            'DB_DATABASE' => $dbName, 'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => $dbPass ?? '',
        ]);
        Artisan::call('config:clear');
        $this->line('  Running migrations...');
        $service->runMigrations();
        $this->line('  Migrations complete');
        $this->info('');

        // Step 3: Admin Account
        $this->line('  Step 3 - Admin Account');
        $adminName  = $this->ask('Admin Name');
        $adminEmail = $this->ask('Admin Email');
        $adminPass  = $this->secret('Admin Password');

        $service->seedRoles();
        $admin = $service->createAdmin(['name' => $adminName, 'email' => $adminEmail, 'password' => $adminPass]);
        $service->createDefaultChurch($appName, $admin->id);
        $service->createStorageLink();

        $service->updateEnv(['APP_INSTALLED' => 'true', 'SESSION_DRIVER' => 'database', 'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'sync']);

        $service->lockInstaller();   // MUST be before warmCaches
        $service->warmCaches();

        $this->info('');
        $this->line("  Installation complete! Visit: {$appUrl}");
        $this->info('');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/InstallerTest.php
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Installer/Commands/InstallCommand.php
git commit -m "feat(installer): php artisan church:install — interactive 3-step CLI"
```

---

## Task 5: UpdaterService

**Files:**
- Create: `plugins/Installer/Services/UpdaterService.php`
- Test: `tests/Feature/UpdaterTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/UpdaterTest.php

use Plugins\Installer\Services\UpdaterService;

test('checkConcurrency throws when updating.lock exists', function () {
    file_put_contents(storage_path('updating.lock'), now()->toIso8601String());

    expect(fn () => (new UpdaterService())->checkConcurrency())
        ->toThrow(\RuntimeException::class, 'already in progress');

    unlink(storage_path('updating.lock'));
});

test('writeLock and releaseLock manage updating.lock', function () {
    $service = new UpdaterService();
    $service->writeLock();
    expect(file_exists(storage_path('updating.lock')))->toBeTrue();
    $service->releaseLock();
    expect(file_exists(storage_path('updating.lock')))->toBeFalse();
});

test('checkForUpdate returns version comparison array', function () {
    // Mock HTTP so tests do not hit GitHub API
    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response([
            'tag_name' => 'v2.0.0',
            'html_url' => 'https://github.com/example/release',
            'body'     => 'Release notes here',
        ], 200),
    ]);
    \Illuminate\Support\Facades\Cache::forget('church_platform_latest_release');

    $info = (new UpdaterService())->checkForUpdate();
    expect($info['latest'])->toBe('2.0.0');
    expect($info)->toHaveKey('update_available');
    expect($info)->toHaveKey('current');
});
```

- [ ] **Step 2: Run to confirm fail**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/UpdaterTest.php
```

- [ ] **Step 3: Create `UpdaterService.php`**

```php
<?php

namespace Plugins\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class UpdaterService
{
    private InstallerService $installer;

    public function __construct()
    {
        $this->installer = new InstallerService();
    }

    public function checkForUpdate(): array
    {
        $current = config('version.current', '1.0.0');

        $release = Cache::remember('church_platform_latest_release', 3600, function () {
            $response = Http::withHeaders(['User-Agent' => 'ChurchPlatform-Updater'])
                            ->get(config('version.releases_api'));
            return $response->ok() ? $response->json() : null;
        });

        $latest = ltrim($release['tag_name'] ?? $current, 'v');

        return [
            'current'          => $current,
            'latest'           => $latest,
            'update_available' => version_compare($latest, $current, '>'),
            'release_url'      => $release['html_url'] ?? '#',
            'release_notes'    => $release['body'] ?? '',
        ];
    }

    public function checkConcurrency(): void
    {
        if (file_exists(storage_path('updating.lock'))) {
            throw new \RuntimeException('Update already in progress. If stuck, delete storage/updating.lock.');
        }
    }

    public function writeLock(): void
    {
        file_put_contents(storage_path('updating.lock'), now()->toIso8601String());
    }

    public function releaseLock(): void
    {
        @unlink(storage_path('updating.lock'));
    }

    public function maintenanceOn(): void
    {
        Artisan::call('down');
    }

    public function maintenanceOff(): void
    {
        Artisan::call('up');
    }

    /** Returns 'git' or 'zip' indicating which method was used. */
    public function pullLatestCode(): string
    {
        if (is_dir(base_path('.git'))) {
            // Use absolute git path — web server PATH is minimal
            Process::run(['/usr/bin/git', 'pull', 'origin', 'main', '--ff-only'],
                ['cwd' => base_path()])->throw();
            return 'git';
        }

        $this->pullViaZip();
        return 'zip';
    }

    private function pullViaZip(): void
    {
        $release = Cache::get('church_platform_latest_release');
        $zipUrl  = $release['zipball_url'] ?? null;
        if (! $zipUrl) throw new \RuntimeException('No ZIP URL available in release data.');

        $tmpFile = sys_get_temp_dir() . '/church_update_' . uniqid() . '.zip';
        $tmpDir  = sys_get_temp_dir() . '/church_update_' . uniqid();

        file_put_contents($tmpFile,
            Http::withHeaders(['User-Agent' => 'ChurchPlatform-Updater'])->get($zipUrl)->body()
        );

        $zip = new \ZipArchive();
        $zip->open($tmpFile);
        $zip->extractTo($tmpDir);
        $zip->close();

        $extracted = glob($tmpDir . '/*', GLOB_ONLYDIR)[0] ?? $tmpDir;
        $this->copyDirectory($extracted, base_path(), ['.env', 'storage', 'public/build', 'public/storage']);

        unlink($tmpFile);
        exec('rm -rf ' . escapeshellarg($tmpDir));
    }

    private function copyDirectory(string $src, string $dest, array $skip = []): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $rel = str_replace($src . DIRECTORY_SEPARATOR, '', $item->getPathname());
            foreach ($skip as $s) { if (str_starts_with($rel, $s)) continue 2; }
            $target = $dest . DIRECTORY_SEPARATOR . $rel;
            $item->isDir() ? @mkdir($target, 0755, true) : copy($item->getPathname(), $target);
        }
    }

    public function composerInstall(): void
    {
        // PHP_BINARY — same PHP version as the web app.
        // '../composer.phar' is relative to cwd (base_path()) — composer.phar sits one level
        // above the project root, which is a common deployment pattern to keep it out of webroot.
        // If composer.phar is at base_path() instead, change to base_path('composer.phar').
        Process::run([PHP_BINARY, '../composer.phar', 'install',
            '--no-dev', '--optimize-autoloader', '--no-interaction',
        ], ['cwd' => base_path()])->throw();
    }

    public function runMigrations(): void
    {
        Process::run([PHP_BINARY, base_path('artisan'), 'migrate', '--force'])->throw();
    }

    public function warmCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
    }

    public function writeNewVersion(string $version): void
    {
        $this->installer->updateEnv(['APP_VERSION' => $version]);
    }

    /**
     * Run full update sequence, emitting SSE-compatible events via $emit callable.
     * $emit signature: fn(string $step, string $status, string $message)
     * On any failure: site is brought back online and lock is released before re-throwing.
     */
    public function runUpdate(callable $emit): void
    {
        $this->checkConcurrency();
        $this->writeLock();

        try {
            $info = $this->checkForUpdate();

            $emit('maintenanceOn', 'running', 'Enabling maintenance mode...');
            $this->maintenanceOn();
            $emit('maintenanceOn', 'done', 'Maintenance mode ON');

            $emit('pullCode', 'running', 'Pulling latest code...');
            $method = $this->pullLatestCode();
            $emit('pullCode', 'done', "Code updated via {$method}");

            $emit('composer', 'running', 'Installing dependencies...');
            $this->composerInstall();
            $emit('composer', 'done', 'Dependencies installed');

            $emit('migrate', 'running', 'Running database migrations...');
            $this->runMigrations();
            $emit('migrate', 'done', 'Migrations complete');

            $emit('cache', 'running', 'Warming caches...');
            $this->warmCaches();
            $emit('cache', 'done', 'Caches warmed');

            $emit('maintenanceOff', 'running', 'Taking site back online...');
            $this->maintenanceOff();
            $emit('maintenanceOff', 'done', 'Site is online');

            $this->writeNewVersion($info['latest']);
            $emit('complete', 'done', "Update complete - v{$info['latest']}");

        } catch (\Throwable $e) {
            try { $this->maintenanceOff(); } catch (\Throwable) {}
            $emit('error', 'error', 'Error: ' . $e->getMessage());
            throw $e;
        } finally {
            $this->releaseLock();
        }
    }
}
```

- [ ] **Step 4: Run tests — all pass**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/UpdaterTest.php
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Installer/Services/UpdaterService.php tests/Feature/UpdaterTest.php
git commit -m "feat(installer): UpdaterService — version check, git/zip pull, SSE runner, concurrency lock"
```

---

## Task 6: UpdaterController + Update View + Artisan Update Command

**Files:**
- Create: `plugins/Installer/Controllers/UpdaterController.php`
- Create: `plugins/Installer/resources/views/installer/update.blade.php`
- Create: `plugins/Installer/Commands/UpdateCommand.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/UpdaterTest.php`:

```php
test('GET /update redirects guests to login', function () {
    $this->get('/update')->assertRedirect('/login');
});

test('GET /update returns 403 for non-admin users', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)->get('/update')->assertStatus(403);
});

test('GET /update shows dashboard to admin', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response(['tag_name' => 'v1.0.0'], 200),
    ]);

    $this->actingAs($admin)->get('/update')->assertStatus(200)->assertSee('System Update');
});

test('church:update command exits early when updating.lock exists', function () {
    file_put_contents(storage_path('updating.lock'), now()->toIso8601String());

    $this->artisan('church:update')
         ->expectsOutputToContain('already in progress')
         ->assertExitCode(1);

    unlink(storage_path('updating.lock'));
});
```

- [ ] **Step 2: Run to confirm fail**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/UpdaterTest.php --filter="update|church:update"
```

- [ ] **Step 3: Create `UpdaterController.php`**

```php
<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Installer\Services\UpdaterService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdaterController extends Controller
{
    public function __construct(private UpdaterService $service) {}

    public function dashboard(): \Illuminate\View\View
    {
        $versionInfo = $this->service->checkForUpdate();
        return view('installer::installer.update', compact('versionInfo'));
    }

    public function run(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () {
            if (ob_get_level()) ob_end_clean();

            $this->service->runUpdate(function (string $step, string $status, string $message) {
                echo 'data: ' . json_encode(compact('step', 'status', 'message')) . "\n\n";
                ob_flush();
                flush();
            });

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

- [ ] **Step 4: Create `update.blade.php`**

```html
@extends('installer::installer.layout')
@php $subtitle = 'System Update'; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-4">System Update</h2>

<div class="flex items-center justify-between py-3 border-b border-gray-100 mb-1">
    <span class="text-sm text-gray-600">Current version</span>
    <span class="text-sm font-mono font-semibold text-gray-800">v{{ $versionInfo['current'] }}</span>
</div>
<div class="flex items-center justify-between py-3 border-b border-gray-100 mb-4">
    <span class="text-sm text-gray-600">Latest version</span>
    <span class="text-sm font-mono font-semibold {{ $versionInfo['update_available'] ? 'text-green-600' : 'text-gray-500' }}">
        v{{ $versionInfo['latest'] }}
        @if($versionInfo['update_available'])
            <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded ml-1">Update available</span>
        @endif
    </span>
</div>

@if(! $versionInfo['update_available'])
    <p class="text-sm text-gray-500 text-center py-2">You are on the latest version.</p>
@else
    <div class="mb-4 p-3 bg-amber-50 border border-amber-100 rounded-xl text-xs text-amber-700">
        This will put the site in maintenance mode for approximately 30-60 seconds.
    </div>
    {{--
        EventSource only supports GET. We generate a signed URL so the GET /update/run
        endpoint cannot be triggered by anyone who doesn't have a valid signed token
        (signed middleware verifies the Laravel URL signature).
    --}}
    {{-- temporarySignedRoute expires in 10 min — prevents replay of a destructive action --}}
    <button id="updateBtn" data-url="{{ URL::temporarySignedRoute('update.run', now()->addMinutes(10)) }}"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        Update Now to v{{ $versionInfo['latest'] }}
    </button>
@endif

<div id="log" class="mt-6 hidden">
    <p class="text-xs font-medium text-gray-500 mb-2">Update Log</p>
    <div id="logLines" class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-100 space-y-1 max-h-64 overflow-y-auto"></div>
</div>
<div id="reloadBtn" class="hidden mt-4">
    <a href="/" class="block w-full text-center py-2.5 rounded-xl font-semibold text-sm bg-green-600 text-white hover:bg-green-700">
        Reload App
    </a>
</div>

<script>
document.getElementById('updateBtn')?.addEventListener('click', function() {
    this.disabled = true;
    this.textContent = 'Updating...';
    document.getElementById('log').classList.remove('hidden');
    // EventSource only supports GET — the signed URL provides CSRF-equivalent protection
    const source = new EventSource(this.dataset.url);
    source.onmessage = function(e) {
        const data = JSON.parse(e.data);
        const p = document.createElement('p');
        p.textContent = data.message;
        if (data.status === 'error') p.style.color = '#f87171';
        document.getElementById('logLines').appendChild(p);
        document.getElementById('logLines').scrollTop = 99999;
        if (data.step === 'complete' || data.status === 'error') {
            source.close();
            if (data.step === 'complete') document.getElementById('reloadBtn').classList.remove('hidden');
        }
    };
});
</script>
@endsection
```

- [ ] **Step 5: Create `UpdateCommand.php`**

```php
<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;
use Plugins\Installer\Services\UpdaterService;

class UpdateCommand extends Command
{
    protected $signature   = 'church:update';
    protected $description = 'Update the Church Platform to the latest version.';

    public function handle(UpdaterService $service): int
    {
        try {
            $service->checkConcurrency();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $info = $service->checkForUpdate();
        $this->info('');
        $this->line("  Current version: {$info['current']}");
        $this->line("  Latest version:  {$info['latest']}");
        $this->info('');

        if (! $info['update_available']) {
            $this->line("  Already on latest version ({$info['latest']}). No update needed.");
            return self::SUCCESS;
        }

        if (! $this->confirm("Update to v{$info['latest']}?", true)) {
            return self::SUCCESS;
        }

        $service->runUpdate(function (string $step, string $status, string $message) {
            $status === 'error' ? $this->error("  {$message}") : $this->line("  {$message}");
        });

        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Run full test suite**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest --no-coverage 2>&1 | tail -5
```
Expected: same 84 pass / 7 pre-existing fail ratio — no regressions.

- [ ] **Step 7: Pint all new files**

```bash
vendor/bin/pint plugins/Installer/
```

- [ ] **Step 8: Final commit**

```bash
git add plugins/Installer/
git commit -m "feat(installer): UpdaterController, update view (SSE), UpdateCommand — full update system"
```

---

## Task 7: Push + PR

- [ ] **Step 1: Push and open PR**

```bash
git push origin main
gh pr create \
  --title "feat: 3-step installer + update system (/install, /update, artisan commands)" \
  --body "## Summary
- 3-step web installer (/install) — Blade+Tailwind CDN, no build step
- Auto-creates bootstrap/cache/, sets permissions, writes root+public .htaccess
- Progressive .env writing per step; atomic rename() prevents half-writes
- php artisan church:install — interactive CLI, same InstallerService
- Admin update dashboard (/update) — version comparison, GitHub releases API (cached 1hr)
- POST /update/run — real-time SSE progress stream (maintenance -> pull -> migrate -> cache)
- php artisan church:update — CLI progress with same UpdaterService
- installed.lock written before route:cache; installer routes excluded from cache permanently
- updating.lock prevents concurrent update runs
- No confirm/re-enter password on any form

## Tests
- InstallerTest: file utilities, HTTP steps 1-3, artisan install
- UpdaterTest: concurrency lock, auth guard, version check, artisan update
- 84 passing, 7 pre-existing failures unchanged (CommentTest, FeedTest, ReactionTest)

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
```

---

## Quick Reference

| URL | Auth | Purpose |
|-----|------|---------|
| `/install/step1` | None (pre-install only) | Requirements + auto-setup |
| `/install/step2` | None (pre-install only) | DB credentials |
| `/install/step3` | None (pre-install only) | Admin account |
| `/install/complete` | None (pre-install only) | Success |
| `/update` | Admin only | Version dashboard |
| `/update/run?signature=...` | Admin only (signed URL) | SSE update stream (GET) |

| Command | Purpose |
|---------|---------|
| `php artisan church:install` | Interactive CLI installer |
| `php artisan church:update` | Pull + migrate + cache update |
