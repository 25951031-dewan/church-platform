# Developer Onboarding

## Prerequisites

| Tool | Version | Install |
|---|---|---|
| PHP | 8.3+ | [Laravel Herd](https://herd.laravel.com) (recommended) |
| Node.js | 24.x | `nvm install 24` |
| Composer | 2.x | `brew install composer` |
| MySQL | 8.0+ | Herd Pro or `brew install mysql` |
| Git | any | `brew install git` |
| GitHub CLI | any | `brew install gh` |

## First-time Setup

```bash
# 1. Clone
git clone https://github.com/25951031-dewan/church-platform.git
cd church-platform

# 2. PHP dependencies
composer install

# 3. Node dependencies (use nvm)
PATH="/Users/YOUR_USER/.nvm/versions/node/v24.14.0/bin:$PATH" npm install

# 4. Environment
cp .env.example .env
php artisan key:generate

# 5. Database
# Create a MySQL DB named 'church_platform', then:
php artisan migrate

# 6. Frontend build
PATH="/Users/YOUR_USER/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build
# or for hot-reload dev:
PATH="/Users/YOUR_USER/.nvm/versions/node/v24.14.0/bin:$PATH" npm run dev
```

> **macOS Herd users**: Replace `php` with `"/Users/YOUR_USER/Library/Application Support/Herd/bin/php"` everywhere.

## Running Tests

Tests use SQLite in-memory — no database setup needed.

```bash
# All tests
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest

# Single plugin
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/EventTest.php

# Named test
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest --filter="can create event"
```

Expected: some pre-existing failures in `CommentTest`, `FeedTest`, `ReactionTest` (7 total) — these exist on `main` and are not caused by your work.

## Code Style

PHP formatting is handled by Laravel Pint:
```bash
vendor/bin/pint              # format all PHP
vendor/bin/pint app/         # format a directory
vendor/bin/pint plugins/Event/  # format a plugin
```

## Creating a New Plugin

Use the `/new-plugin` Claude skill, or do it manually:

### Manual steps

1. Create directory structure:
```
plugins/PluginName/
  PluginNameServiceProvider.php
  plugin.json
  routes/api.php
  Controllers/
  Models/
  database/migrations/
```

2. ServiceProvider (copy this exact pattern):
```php
<?php
namespace Plugins\PluginName;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class PluginNameServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')
               ->group(base_path('plugins/PluginName/routes/api.php'));
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

3. Register in `bootstrap/providers.php`:
```php
Plugins\PluginName\PluginNameServiceProvider::class,
```

4. Create migration with correct timestamp format:
```
plugins/PluginName/database/migrations/2026_MM_DD_000001_create_things_table.php
```

5. If plugin needs a factory, put it in `database/factories/ThingFactory.php` and add `newFactory()` to the model:
```php
protected static function newFactory()
{
    return \Database\Factories\ThingFactory::new();
}
```

## Git Workflow

Sprints use isolated git worktrees:
```bash
# Start a new sprint
git worktree add .worktrees/sprint-6 -b sprint/6-feature-name
cd .worktrees/sprint-6
ln -s ../../vendor vendor
cp ../../.env .env
```

The worktree's `tests/bootstrap.php` handles PSR-4 isolation automatically.

## Environment Variables (key ones)

```
APP_ENV=local
APP_KEY=base64:...
DB_CONNECTION=mysql
DB_DATABASE=church_platform
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
SCOUT_DRIVER=database
GOOGLE_2FA_...
SOCIALITE_GOOGLE_...
```

## Useful Commands

```bash
# Generate API docs (Scribe)
php artisan scribe:generate

# Clear bootstrap caches
php artisan config:clear && php artisan cache:clear

# Tinker (REPL)
php artisan tinker

# Queue worker (for Jobs)
php artisan queue:work

# Run scheduler locally
php artisan schedule:work
```
