# Church Platform — Claude Code Guide

## Project Overview
Laravel 11 + React 18 + TypeScript church management platform. Plugin-based architecture where every feature lives in `plugins/PluginName/`. The frontend is a Vite SPA mounted at `resources/js/`.

## Executables (use these exact paths)
```bash
# PHP / Pest tests
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/FooTest.php

# Code style (run after editing PHP)
vendor/bin/pint

# Frontend build
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run dev

# Composer
composer require vendor/package
```

## Plugin Architecture

### Directory layout
```
plugins/
  PluginName/
    PluginNameServiceProvider.php   ← required
    plugin.json                     ← required
    routes/api.php
    Controllers/
    Models/
    Services/
    Policies/
    Jobs/
    Notifications/
    database/migrations/
```

### ServiceProvider — CRITICAL pattern
**Always** use `Router::class` directly. Never use `loadRoutesFrom()` — it bypasses the `api` middleware and `api/` URL prefix.

```php
use Illuminate\Routing\Router;

public function boot(): void
{
    $router = $this->app->make(Router::class);
    $router->middleware('api')->prefix('api')->group(base_path('plugins/Foo/routes/api.php'));
    $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
}
```

### Registering a new plugin
Add to `bootstrap/providers.php`:
```php
Plugins\PluginName\PluginNameServiceProvider::class,
```

### Routes
All plugin routes use `Route::prefix('v1')` inside the file. Auth routes wrap in `Route::middleware('auth:sanctum')`.

```php
Route::prefix('v1')->group(function () {
    Route::get('/things', [ThingController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/things', [ThingController::class, 'store']);
    });
});
```
Resulting URL: `POST /api/v1/things`

### Migrations
Timestamp format: `YYYY_MM_DD_000001_description.php` (increment the last 6-digit suffix per migration in same plugin).

### Factories
Plugin models live in `plugins/`, but factories go in `database/factories/`. Override `newFactory()` on the model:

```php
protected static function newFactory()
{
    return \Database\Factories\ThingFactory::new();
}
```

## Database / Testing

- **Tests**: SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- **Production**: MySQL
- **SQLite compat**: Never use `JSON_SET`, `JSON_EXTRACT`, `JSON_UNQUOTE`, `SHOW INDEX` in migrations or services. Wrap MySQL-only statements in:
  ```php
  if (DB::getDriverName() !== 'mysql') return;
  // or try/catch for service-layer JSON_SET calls
  ```
- **Race conditions**: Use `Model::lockForUpdate()->findOrFail($id)` inside `DB::transaction()` for counter increments.

## Test Patterns (PestPHP)

```php
// tests/Feature/ThingTest.php
use App\Models\User;
use Plugins\Thing\Models\Thing;

test('authenticated user can create thing', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->postJson('/api/v1/things', ['title' => 'Hello'])
        ->assertStatus(201);
});
```

Run all: `"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest`
Run one file: `... pest tests/Feature/ThingTest.php`
Run tagged: `... pest --filter="thing"`

## Frontend (React + TypeScript)

- Entry: `resources/js/app.js`
- Plugin UIs: `resources/js/plugins/pluginname/`
- API calls: `axios` with `/api/v1/` prefix
- State: TanStack Query (`@tanstack/react-query`)
- Styling: Tailwind CSS
- Charts: Recharts
- Page builder: GrapesJS (admin only)

## Key Dependencies
| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Roles (`admin`, `church_leader`, `member`) |
| `spatie/laravel-medialibrary` | File/image uploads |
| `laravel/sanctum` | SPA auth (token-based) |
| `laravel/scout` | Full-text search |
| `laravel/socialite` | OAuth (Google, Facebook) |
| `pragmarx/google2fa-laravel` | 2FA |
| `pusher/pusher-php-server` | Real-time broadcasting |
| `rlanvin/php-rrule` | Recurring event expansion |
| `knuckleswtf/scribe` | API doc generation |

## Worktree Pattern
New sprints use git worktrees at `.worktrees/sprint-N/` on branch `sprint/N-description`.
- Shared vendor via symlink: `vendor -> ../../vendor`
- Each worktree needs its own `.env` (copy from main)
- `tests/bootstrap.php` uses PSR-4 override + classmap scan to isolate plugin/app classes

## Known Pre-existing Test Failures
`CommentTest`, `FeedTest`, `ReactionTest` — 7 failures that exist on `main`. Not caused by feature work. Related to missing factories from Sprint 2 context.
