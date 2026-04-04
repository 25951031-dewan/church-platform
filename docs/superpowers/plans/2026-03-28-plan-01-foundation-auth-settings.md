# Plan 1: Foundation + Auth + Settings — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the bootable platform core — Laravel 12 scaffold with BeMusic-pattern auth (roles, permissions DB tables, policies), settings engine (key-value store), plugin system, React 19 + TypeScript admin shell, Docker environment, and CI pipeline. No church features yet — just the foundation everything else builds on.

**Architecture:** Fresh Laravel 12 project with a `Common\` foundation namespace (adapted from BeMusic's `common/foundation` pattern). Auth uses Sanctum + Fortify with a normalized permission system (permissions table + pivots, not JSON blobs). Frontend is React 19 + TypeScript SPA served via Vite 6. Settings use a key-value store pattern.

**Tech Stack:** Laravel 12, PHP 8.3, MySQL 8, Redis 7, Meilisearch, React 19, TypeScript 5.8, Vite 6 (SWC), Tailwind CSS 4, TanStack React Query 5, Zustand 5, Laravel Sanctum 4, Laravel Fortify 1.x

**Spec:** `docs/superpowers/specs/2026-03-28-church-community-platform-design.md`

**Existing codebase reference:** The current `/Users/siku/Documents/GitHub/church` project (Laravel 10, React 18) serves as domain reference. This plan builds a NEW project structure alongside it, then migrates.

---

## File Structure Overview

This plan creates the following key files. Each task references specific files.

```
church-platform/                          # New project root (or branch)
├── docker-compose.yml                    # MySQL + Redis + Meilisearch
├── Dockerfile                            # PHP 8.3 + extensions
├── .env.example                          # Environment template
├── composer.json                         # Laravel 12 + dependencies
├── package.json                          # React 19 + TypeScript
├── vite.config.ts                        # Vite 6 + SWC + Laravel plugin
├── tailwind.config.ts                    # Tailwind 4
├── tsconfig.json                         # TypeScript strict config
├── .github/workflows/ci.yml             # GitHub Actions CI
│
├── common/foundation/                    # Shared foundation (BeMusic pattern)
│   └── src/
│       ├── Auth/
│       │   ├── Models/
│       │   │   ├── User.php              # Base user with permission resolution
│       │   │   ├── Role.php              # Role model with permission pivot
│       │   │   └── Permission.php        # Permission model
│       │   ├── Policies/
│       │   │   └── UserPolicy.php
│       │   ├── Controllers/
│       │   │   ├── AuthController.php    # Login, register, logout, profile
│       │   │   ├── ForgotPasswordController.php
│       │   │   ├── RoleController.php
│       │   │   └── PermissionController.php
│       │   ├── Requests/
│       │   │   ├── LoginRequest.php
│       │   │   └── RegisterRequest.php
│       │   └── Middleware/
│       │       ├── CheckPermission.php
│       │       └── CheckRole.php
│       ├── Settings/
│       │   ├── Models/Setting.php        # Key-value setting model
│       │   ├── Services/SettingService.php
│       │   └── Controllers/SettingController.php
│       ├── Files/
│       │   ├── Services/FileUploadService.php
│       │   └── Controllers/FileController.php
│       └── Core/
│           ├── PluginManager.php         # Plugin registry + toggle
│           ├── BootstrapDataService.php  # Frontend hydration data
│           └── BasePolicy.php           # Shared policy with before() super admin check
│
├── app/
│   ├── Models/User.php                   # Extends Common\Auth\Models\User
│   ├── Providers/
│   │   ├── AppServiceProvider.php        # Morph map, gate registration
│   │   └── AuthServiceProvider.php
│   └── Http/
│       └── Kernel.php
│
├── config/
│   ├── plugins.json                      # Plugin enable/disable registry
│   └── permissions.php                   # Permission seeder config
│
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000001_create_users_table.php
│   │   ├── 0001_01_01_000002_create_roles_table.php
│   │   ├── 0001_01_01_000003_create_permissions_table.php
│   │   ├── 0001_01_01_000004_create_permission_role_table.php
│   │   ├── 0001_01_01_000005_create_user_role_table.php
│   │   ├── 0001_01_01_000006_create_permission_user_table.php
│   │   ├── 0001_01_01_000007_create_personal_access_tokens_table.php
│   │   ├── 0001_01_01_000008_create_settings_table.php
│   │   ├── 0001_01_01_000009_create_file_entries_table.php
│   │   ├── 0001_01_01_000010_create_social_profiles_table.php
│   │   └── 0001_01_01_000011_create_menus_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── PermissionSeeder.php          # 26 core permissions
│       └── RoleSeeder.php               # 8 default roles
│
├── routes/
│   ├── api.php                           # Foundation API routes
│   └── web.php                           # SPA fallback + SEO pre-render
│
├── resources/
│   └── client/                           # React 19 + TypeScript SPA
│       ├── main.tsx                      # Entry point
│       ├── app-router.tsx                # Route composition
│       ├── app.css                       # Global styles
│       ├── common/
│       │   ├── auth/
│       │   │   ├── use-permissions.ts    # Permission hook
│       │   │   ├── use-auth.ts           # Auth state hook
│       │   │   └── auth-guards.tsx       # Route guards
│       │   ├── http/
│       │   │   ├── api-client.ts         # Axios instance
│       │   │   └── query-client.ts       # TanStack Query config
│       │   ├── core/
│       │   │   ├── bootstrap-data.ts     # Bootstrap data store (Zustand)
│       │   │   └── site-config.tsx       # Site config context
│       │   └── ui/                       # Shared UI components
│       │       ├── Button.tsx
│       │       ├── Modal.tsx
│       │       ├── DataTable.tsx
│       │       ├── FormField.tsx
│       │       ├── Toast.tsx
│       │       └── Sidebar.tsx
│       ├── admin/
│       │   ├── AdminLayout.tsx           # Admin shell (sidebar + content)
│       │   ├── DashboardPage.tsx         # Dashboard with stat cards
│       │   ├── roles/
│       │   │   ├── RoleListPage.tsx
│       │   │   └── RolePermissionEditor.tsx  # Checkbox matrix
│       │   ├── users/
│       │   │   └── UserListPage.tsx
│       │   └── settings/
│       │       ├── SettingsLayout.tsx     # Settings sidebar + content
│       │       ├── GeneralSettings.tsx
│       │       ├── AuthSettings.tsx
│       │       ├── ThemeSettings.tsx
│       │       ├── UploadSettings.tsx
│       │       ├── EmailSettings.tsx
│       │       ├── SystemSettings.tsx
│       │       ├── SeoSettings.tsx
│       │       ├── ModuleSettings.tsx    # Plugin toggle
│       │       └── ...                   # Remaining settings sections
│       ├── auth/
│       │   ├── LoginPage.tsx
│       │   └── RegisterPage.tsx
│       └── landing/
│           └── LandingPage.tsx           # Public landing
│
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   │   ├── LoginTest.php
│   │   │   ├── RegisterTest.php
│   │   │   └── PermissionTest.php
│   │   ├── Settings/
│   │   │   └── SettingTest.php
│   │   └── Roles/
│   │       └── RoleTest.php
│   └── Unit/
│       ├── PluginManagerTest.php
│       └── PermissionResolutionTest.php
│
└── views/
    └── app.blade.php                     # SPA shell (single Blade view)
```

---

## Task 1: Docker Environment

**Files:**
- Create: `docker-compose.yml`
- Create: `Dockerfile`
- Create: `.env.example`

- [ ] **Step 1: Create Docker Compose file**

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
      - "5173:5173"
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis
      - meilisearch
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - SCOUT_DRIVER=meilisearch
      - MEILISEARCH_HOST=http://meilisearch:7700

  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: church_platform
      MYSQL_USER: church
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  meilisearch:
    image: getmeili/meilisearch:v1.6
    ports:
      - "7700:7700"
    environment:
      MEILI_MASTER_KEY: masterKey
    volumes:
      - meilisearch_data:/meili_data

volumes:
  mysql_data:
  meilisearch_data:
```

- [ ] **Step 2: Create Dockerfile**

```dockerfile
# Dockerfile
FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

- [ ] **Step 3: Create .env.example**

```env
APP_NAME="Church Platform"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=church_platform
DB_USERNAME=church
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

- [ ] **Step 4: Boot Docker and verify**

Run: `docker compose up -d`
Expected: All 4 services running (app, mysql, redis, meilisearch)

- [ ] **Step 5: Commit**

```bash
git add docker-compose.yml Dockerfile .env.example
git commit -m "infra: add Docker Compose with MySQL, Redis, Meilisearch"
```

---

## Task 2: Laravel 12 Project Scaffold

**Files:**
- Create: `composer.json` (new Laravel 12 project)
- Modify: existing project structure

- [ ] **Step 1: Create fresh Laravel 12 project**

Run:
```bash
composer create-project laravel/laravel church-platform-v2 --prefer-dist
```

Or if upgrading in-place on a new branch:
```bash
git checkout -b v5-foundation
```

Update `composer.json` require section:
```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "laravel/sanctum": "^4.0",
    "laravel/fortify": "^1.25",
    "laravel/socialite": "^5.18",
    "laravel/scout": "^10.6",
    "laravel/horizon": "^5.3",
    "laravel/reverb": "^1.4",
    "meilisearch/meilisearch-php": "^1.13",
    "intervention/image": "^3.0",
    "barryvdh/laravel-dompdf": "^2.0",
    "spatie/laravel-medialibrary": "^11.0",
    "league/flysystem-aws-s3-v3": "^3.2",
    "stripe/stripe-php": "^16.6",
    "guzzlehttp/guzzle": "^7.8"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "laravel/pint": "^1.0",
    "mockery/mockery": "^1.6",
    "itsgoingd/clockwork": "^5.1",
    "spatie/laravel-ignition": "^2.9"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Common\\": "common/foundation/src/",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

- [ ] **Step 2: Install dependencies**

Run: `composer install`
Expected: All packages install without errors

- [ ] **Step 3: Generate app key and copy env**

Run:
```bash
cp .env.example .env
php artisan key:generate
```

- [ ] **Step 4: Verify Laravel boots**

Run: `php artisan --version`
Expected: `Laravel Framework 12.x.x`

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock
git commit -m "feat: scaffold Laravel 12 project with foundation dependencies"
```

---

## Task 3: Frontend Scaffold (React 19 + TypeScript + Vite 6)

**Files:**
- Create: `package.json`
- Create: `vite.config.ts`
- Create: `tsconfig.json`
- Create: `tailwind.config.ts`
- Create: `postcss.config.js`
- Create: `resources/client/main.tsx`
- Create: `resources/client/app.css`
- Create: `resources/views/app.blade.php`

- [ ] **Step 1: Create package.json**

```json
{
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite --host",
    "build": "tsc && vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "react-router": "^7.6.0",
    "@tanstack/react-query": "^5.75.0",
    "zustand": "^5.0.0",
    "immer": "^10.1.0",
    "axios": "^1.7.0",
    "lucide-react": "^0.460.0",
    "framer-motion": "^12.0.0",
    "react-hook-form": "^7.56.0",
    "@react-aria/focus": "^3.0.0",
    "@react-aria/overlays": "^3.0.0"
  },
  "devDependencies": {
    "@types/react": "^19.0.0",
    "@types/react-dom": "^19.0.0",
    "@vitejs/plugin-react-swc": "^4.0.0",
    "laravel-vite-plugin": "^1.2.0",
    "typescript": "^5.8.0",
    "tailwindcss": "^4.0.0",
    "@tailwindcss/vite": "^4.0.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0",
    "@tanstack/react-query-devtools": "^5.75.0",
    "eslint": "^9.0.0",
    "@eslint/js": "^9.0.0",
    "typescript-eslint": "^8.0.0"
  }
}
```

- [ ] **Step 2: Install npm packages**

Run: `npm install`

- [ ] **Step 3: Create vite.config.ts**

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/client/main.tsx'],
      refresh: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@app': resolve(__dirname, 'resources/client'),
      '@common': resolve(__dirname, 'common/foundation/resources/client'),
      '@ui': resolve(__dirname, 'resources/client/common/ui'),
    },
  },
});
```

- [ ] **Step 4: Create tsconfig.json**

```json
{
  "compilerOptions": {
    "target": "ESNext",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "noEmit": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "lib": ["DOM", "DOM.Iterable", "ESNext"],
    "paths": {
      "@app/*": ["resources/client/*"],
      "@common/*": ["common/foundation/resources/client/*"],
      "@ui/*": ["resources/client/common/ui/*"]
    },
    "baseUrl": "."
  },
  "include": ["resources/client/**/*"],
  "exclude": ["node_modules"]
}
```

- [ ] **Step 5: Create app.css with Tailwind 4**

```css
/* resources/client/app.css */
@import "tailwindcss";

@theme {
  --color-primary-50: #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-200: #bfdbfe;
  --color-primary-300: #93c5fd;
  --color-primary-400: #60a5fa;
  --color-primary-500: #6366f1;
  --color-primary-600: #4f46e5;
  --color-primary-700: #4338ca;
  --color-primary-800: #3730a3;
  --color-primary-900: #312e81;
  --color-primary-950: #1e1b4b;

  --color-surface: #ffffff;
  --color-surface-dark: #111827;
  --color-text: #111827;
  --color-text-dark: #f9fafb;
}

.dark {
  --color-surface: #111827;
  --color-text: #f9fafb;
}
```

- [ ] **Step 6: Create SPA shell Blade view**

```php
{{-- resources/views/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Church Platform') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap data for React hydration --}}
    <script>
        window.__BOOTSTRAP_DATA__ = @json($bootstrapData ?? []);
    </script>

    @viteReactRefresh
    @vite(['resources/client/main.tsx'])
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
```

- [ ] **Step 7: Create React entry point**

```tsx
// resources/client/main.tsx
import './app.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { BrowserRouter } from 'react-router';
import { AppRouter } from './app-router';
import { queryClient } from './common/http/query-client';
import { BootstrapDataProvider } from './common/core/bootstrap-data';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BootstrapDataProvider>
        <BrowserRouter>
          <AppRouter />
        </BrowserRouter>
      </BootstrapDataProvider>
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  </StrictMode>
);
```

- [ ] **Step 8: Create placeholder app router**

```tsx
// resources/client/app-router.tsx
import { Routes, Route } from 'react-router';

export function AppRouter() {
  return (
    <Routes>
      <Route path="/" element={<div className="p-8 text-2xl">Church Platform v5 — Foundation</div>} />
    </Routes>
  );
}
```

- [ ] **Step 9: Verify frontend builds**

Run: `npm run build`
Expected: Build completes without TypeScript or Vite errors

- [ ] **Step 10: Commit**

```bash
git add package.json package-lock.json vite.config.ts tsconfig.json postcss.config.js \
  resources/client/ resources/views/app.blade.php
git commit -m "feat: scaffold React 19 + TypeScript + Vite 6 + Tailwind 4 frontend"
```

---

## Task 4: Foundation Directory Structure

**Files:**
- Create: `common/foundation/src/` directory tree
- Modify: `composer.json` (autoload already configured in Task 2)

- [ ] **Step 1: Create foundation directory structure**

Run:
```bash
mkdir -p common/foundation/src/{Auth/{Models,Policies,Controllers,Requests,Middleware},Settings/{Models,Services,Controllers},Files/{Services,Controllers},Core}
mkdir -p common/foundation/config
mkdir -p common/foundation/database/{migrations,seeders}
mkdir -p common/foundation/routes
```

- [ ] **Step 2: Verify autoloading**

Run: `composer dump-autoload`
Expected: No errors, `Common\` namespace resolves to `common/foundation/src/`

- [ ] **Step 3: Commit**

```bash
git add common/
git commit -m "feat: create common/foundation directory structure (BeMusic pattern)"
```

---

## Task 5: Database Migrations — Auth Tables

**Files:**
- Create: `database/migrations/0001_01_01_000001_create_users_table.php`
- Create: `database/migrations/0001_01_01_000002_create_roles_table.php`
- Create: `database/migrations/0001_01_01_000003_create_permissions_table.php`
- Create: `database/migrations/0001_01_01_000004_create_permission_role_table.php`
- Create: `database/migrations/0001_01_01_000005_create_user_role_table.php`
- Create: `database/migrations/0001_01_01_000006_create_permission_user_table.php`
- Create: `database/migrations/0001_01_01_000007_create_personal_access_tokens_table.php`
- Create: `database/migrations/0001_01_01_000008_create_social_profiles_table.php`

- [ ] **Step 1: Create users migration**

```php
<?php
// database/migrations/0001_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('provider', 50)->nullable();
            $table->string('provider_id')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->string('theme', 20)->default('dark');
            $table->json('custom_fields')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
```

Note: The `church_id` foreign key will fail if churches table doesn't exist yet. We'll handle this with a deferred foreign key or by creating churches migration first. For now, make it a plain `unsignedBigInteger` without the constraint, and add the constraint later when the ChurchBuilder plugin is created.

Replace the church_id line with:
```php
$table->unsignedBigInteger('church_id')->nullable();
```

- [ ] **Step 2: Create roles migration**

```php
<?php
// database/migrations/0001_01_01_000002_create_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['system', 'church', 'custom'])->default('custom');
            $table->unsignedInteger('level')->default(10);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('church_id')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('church_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
```

- [ ] **Step 3: Create permissions migration**

```php
<?php
// database/migrations/0001_01_01_000003_create_permissions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('group', 100);
            $table->enum('type', ['global', 'church'])->default('global');
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
```

- [ ] **Step 4: Create pivot tables migration**

```php
<?php
// database/migrations/0001_01_01_000004_create_auth_pivot_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('granted')->default(true);
            $table->primary(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('permission_role');
    }
};
```

- [ ] **Step 5: Create personal access tokens migration**

```php
<?php
// database/migrations/0001_01_01_000005_create_personal_access_tokens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
```

- [ ] **Step 6: Create social profiles migration**

```php
<?php
// database/migrations/0001_01_01_000006_create_social_profiles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_id');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_profiles');
    }
};
```

- [ ] **Step 7: Run migrations**

Run: `php artisan migrate`
Expected: All 6 migrations run successfully, tables created

- [ ] **Step 8: Commit**

```bash
git add database/migrations/
git commit -m "feat: add auth database schema (users, roles, permissions, pivots, social profiles)"
```

---

## Task 6: Foundation Models — User, Role, Permission

**Files:**
- Create: `common/foundation/src/Auth/Models/User.php`
- Create: `common/foundation/src/Auth/Models/Role.php`
- Create: `common/foundation/src/Auth/Models/Permission.php`
- Create: `app/Models/User.php`
- Test: `tests/Unit/PermissionResolutionTest.php`

- [ ] **Step 1: Write the Permission model**

```php
<?php
// common/foundation/src/Auth/Models/Permission.php

namespace Common\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $guarded = ['id'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'permission_user'
        )->withPivot('granted');
    }
}
```

- [ ] **Step 2: Write the Role model**

```php
<?php
// common/foundation/src/Auth/Models/Role.php

namespace Common\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
        'level' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'user_role'
        );
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
}
```

- [ ] **Step 3: Write the base User model in foundation**

```php
<?php
// common/foundation/src/Auth/Models/User.php

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

    public function socialProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialProfile::class);
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

    /**
     * Bootstrap data sent to React frontend on first load.
     */
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
```

- [ ] **Step 4: Create app User model that extends foundation**

```php
<?php
// app/Models/User.php

namespace App\Models;

use Common\Auth\Models\User as BaseUser;

class User extends BaseUser
{
    // App-specific user methods and relationships will go here
    // (e.g., church relationship, plugin-specific relations)
}
```

- [ ] **Step 5: Write the failing permission resolution test**

```php
<?php
// tests/Unit/PermissionResolutionTest.php

namespace Tests\Unit;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function createPermission(string $name, string $group = 'test'): Permission
    {
        return Permission::create([
            'name' => $name,
            'display_name' => ucwords(str_replace('.', ' ', $name)),
            'group' => $group,
        ]);
    }

    public function test_user_gets_permissions_from_role(): void
    {
        $perm = $this->createPermission('posts.create');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('posts.create'));
        $this->assertFalse($user->hasPermission('posts.delete'));
    }

    public function test_direct_grant_overrides_missing_role_permission(): void
    {
        $perm = $this->createPermission('posts.delete');
        $user = User::factory()->create();
        // No role has this permission, but we grant it directly
        $user->directPermissions()->attach($perm, ['granted' => true]);
        $user->clearPermissionCache();

        $this->assertTrue($user->hasPermission('posts.delete'));
    }

    public function test_direct_deny_overrides_role_permission(): void
    {
        $perm = $this->createPermission('posts.create');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);
        // Explicitly deny this permission for this user
        $user->directPermissions()->attach($perm, ['granted' => false]);
        $user->clearPermissionCache();

        $this->assertFalse($user->hasPermission('posts.create'));
    }

    public function test_permissions_are_cached(): void
    {
        $perm = $this->createPermission('posts.view');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // First call — cache miss, resolves from DB
        $this->assertTrue($user->hasPermission('posts.view'));

        // Detach the permission from role (DB changes)
        $role->permissions()->detach($perm);

        // Should still return true (cached)
        $this->assertTrue($user->hasPermission('posts.view'));

        // Clear cache — now should reflect DB
        $user->clearPermissionCache();
        $this->assertFalse($user->hasPermission('posts.view'));
    }

    public function test_multiple_roles_merge_permissions(): void
    {
        $permA = $this->createPermission('posts.create');
        $permB = $this->createPermission('events.create');

        $roleA = Role::create(['name' => 'Writer', 'slug' => 'writer', 'type' => 'custom']);
        $roleA->permissions()->attach($permA);

        $roleB = Role::create(['name' => 'EventOrg', 'slug' => 'event-org', 'type' => 'custom']);
        $roleB->permissions()->attach($permB);

        $user = User::factory()->create();
        $user->roles()->attach([$roleA->id, $roleB->id]);

        $this->assertTrue($user->hasPermission('posts.create'));
        $this->assertTrue($user->hasPermission('events.create'));
    }

    public function test_role_level_returns_highest(): void
    {
        $roleA = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $roleB = Role::create(['name' => 'Moderator', 'slug' => 'moderator', 'type' => 'system', 'level' => 40]);

        $user = User::factory()->create();
        $user->roles()->attach([$roleA->id, $roleB->id]);

        $this->assertEquals(40, $user->getRoleLevel());
    }

    public function test_bootstrap_data_includes_permissions(): void
    {
        $perm = $this->createPermission('posts.view');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $data = $user->getBootstrapData();

        $this->assertArrayHasKey('permissions', $data);
        $this->assertTrue($data['permissions']['posts.view']);
        $this->assertEquals(20, $data['role_level']);
        $this->assertContains('member', $data['roles']);
    }
}
```

- [ ] **Step 6: Create User factory**

```php
<?php
// database/factories/UserFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['banned_at' => now()]);
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Unit/PermissionResolutionTest.php --verbose`
Expected: All 6 tests pass

- [ ] **Step 8: Commit**

```bash
git add common/foundation/src/Auth/Models/ app/Models/User.php \
  database/factories/UserFactory.php tests/Unit/PermissionResolutionTest.php
git commit -m "feat: add User/Role/Permission models with layered permission resolution"
```

---

## Task 7: Permission & Role Seeders

**Files:**
- Create: `config/permissions.php`
- Create: `database/seeders/PermissionSeeder.php`
- Create: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create permissions config (core permissions only — plugins add their own later)**

```php
<?php
// config/permissions.php
// Core foundation permissions. Each plugin will register its own.

return [
    'admin' => [
        'admin.access'           => 'Access Admin Panel',
        'admin.dashboard'        => 'View Dashboard Analytics',
    ],
    'users' => [
        'users.view'             => 'View User Profiles',
        'users.create'           => 'Create Users',
        'users.update'           => 'Edit Users',
        'users.delete'           => 'Delete Users',
        'users.impersonate'      => 'Login As User',
        'users.ban'              => 'Ban/Unban Users',
        'users.export'           => 'Export User Data',
    ],
    'roles' => [
        'roles.view'             => 'View Roles',
        'roles.create'           => 'Create Roles',
        'roles.update'           => 'Edit Roles',
        'roles.delete'           => 'Delete Roles',
        'roles.assign'           => 'Assign Roles to Users',
    ],
    'settings' => [
        'settings.view'          => 'View Settings',
        'settings.update'        => 'Update Settings',
    ],
    'files' => [
        'files.upload'           => 'Upload Files',
        'files.delete'           => 'Delete Any File',
        'files.manage'           => 'Manage File Storage',
    ],
    'appearance' => [
        'appearance.themes'      => 'Manage Themes',
        'appearance.menus'       => 'Manage Navigation Menus',
        'appearance.custom_code' => 'Edit Custom CSS/JS',
    ],
    'localizations' => [
        'localizations.view'     => 'View Translations',
        'localizations.update'   => 'Edit Translations',
    ],
    'seo' => [
        'seo.manage'             => 'Manage SEO Settings',
        'seo.sitemap'            => 'Generate Sitemap',
    ],
];
```

- [ ] **Step 2: Create PermissionSeeder**

```php
<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Common\Auth\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = config('permissions');

        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $name => $displayName) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                        'type' => 'global',
                    ]
                );
            }
        }
    }
}
```

- [ ] **Step 3: Create RoleSeeder**

```php
<?php
// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::pluck('id', 'name');

        // Super Admin — ALL permissions
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'type' => 'system', 'level' => 100, 'is_default' => false]
        );
        $superAdmin->permissions()->sync($allPermissions->values());

        // Platform Admin — all except system-critical
        $platformAdmin = Role::firstOrCreate(
            ['slug' => 'platform-admin'],
            ['name' => 'Platform Admin', 'type' => 'system', 'level' => 80, 'is_default' => false]
        );
        $platformAdminPerms = $allPermissions->except([
            'settings.update', 'users.impersonate', 'roles.delete',
        ]);
        $platformAdmin->permissions()->sync($platformAdminPerms->values());

        // Church Admin
        $churchAdmin = Role::firstOrCreate(
            ['slug' => 'church-admin'],
            ['name' => 'Church Admin', 'type' => 'system', 'level' => 60, 'is_default' => false]
        );
        $churchAdminPerms = $allPermissions->only([
            'admin.access', 'admin.dashboard',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.assign',
            'settings.view',
            'files.upload', 'files.delete',
            'appearance.themes', 'appearance.menus',
            'localizations.view',
            'seo.manage', 'seo.sitemap',
        ]);
        $churchAdmin->permissions()->sync($churchAdminPerms->values());

        // Pastor / Elder
        $pastor = Role::firstOrCreate(
            ['slug' => 'pastor'],
            ['name' => 'Pastor / Elder', 'type' => 'system', 'level' => 50, 'is_default' => false]
        );
        $pastor->permissions()->sync($churchAdminPerms->values()); // Same as church admin for core; plugins extend

        // Moderator
        $moderator = Role::firstOrCreate(
            ['slug' => 'moderator'],
            ['name' => 'Moderator', 'type' => 'system', 'level' => 40, 'is_default' => false]
        );
        $moderatorPerms = $allPermissions->only([
            'admin.access', 'users.view', 'files.upload',
        ]);
        $moderator->permissions()->sync($moderatorPerms->values());

        // Ministry Leader
        $ministryLeader = Role::firstOrCreate(
            ['slug' => 'ministry-leader'],
            ['name' => 'Ministry Leader', 'type' => 'system', 'level' => 30, 'is_default' => false]
        );
        $ministryLeader->permissions()->sync($allPermissions->only([
            'files.upload',
        ])->values());

        // Member (default role)
        $member = Role::firstOrCreate(
            ['slug' => 'member'],
            ['name' => 'Member', 'type' => 'system', 'level' => 20, 'is_default' => true]
        );
        $member->permissions()->sync($allPermissions->only([
            'files.upload',
        ])->values());

        // Guest
        Role::firstOrCreate(
            ['slug' => 'guest'],
            ['name' => 'Guest', 'type' => 'system', 'level' => 10, 'is_default' => false]
        );
        // Guest has no permissions (read-only public content handled by policies)
    }
}
```

- [ ] **Step 4: Update DatabaseSeeder**

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
```

- [ ] **Step 5: Run seeders**

Run: `php artisan db:seed`
Expected: 26 permissions created, 8 roles created with permission assignments

- [ ] **Step 6: Verify with tinker**

Run:
```bash
php artisan tinker --execute="echo Common\Auth\Models\Role::with('permissions')->where('slug','super-admin')->first()->permissions->count();"
```
Expected: `26` (all core permissions)

- [ ] **Step 7: Commit**

```bash
git add config/permissions.php database/seeders/
git commit -m "feat: add permission and role seeders (26 core permissions, 8 default roles)"
```

---

## Task 8: Settings Engine

**Files:**
- Create: `database/migrations/0001_01_01_000009_create_settings_table.php`
- Create: `common/foundation/src/Settings/Models/Setting.php`
- Create: `common/foundation/src/Settings/Services/SettingService.php`
- Create: `common/foundation/src/Settings/Controllers/SettingController.php`
- Test: `tests/Feature/Settings/SettingTest.php`

- [ ] **Step 1: Create settings migration**

```php
<?php
// database/migrations/0001_01_01_000009_create_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

- [ ] **Step 2: Create Setting model**

```php
<?php
// common/foundation/src/Settings/Models/Setting.php

namespace Common\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];
}
```

- [ ] **Step 3: Create SettingService**

```php
<?php
// common/foundation/src/Settings/Services/SettingService.php

namespace Common\Settings\Services;

use Common\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'app.settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    public function getByGroup(string $prefix): array
    {
        $all = $this->getAll();
        return collect($all)
            ->filter(fn ($v, $k) => str_starts_with($k, $prefix . '.'))
            ->toArray();
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->clearCache();
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
```

- [ ] **Step 4: Create SettingController**

```php
<?php
// common/foundation/src/Settings/Controllers/SettingController.php

namespace Common\Settings\Controllers;

use Common\Settings\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $settings,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['settings' => $this->settings->getAll()]);
    }

    public function show(string $group): JsonResponse
    {
        return response()->json(['settings' => $this->settings->getByGroup($group)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        $this->settings->setMany($validated['settings']);

        return response()->json(['message' => 'Settings updated']);
    }
}
```

- [ ] **Step 5: Write settings feature test**

```php
<?php
// tests/Feature/Settings/SettingTest.php

namespace Tests\Feature\Settings;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $perm = Permission::create([
            'name' => 'settings.update',
            'display_name' => 'Update Settings',
            'group' => 'settings',
        ]);
        Permission::create([
            'name' => 'settings.view',
            'display_name' => 'View Settings',
            'group' => 'settings',
        ]);

        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'type' => 'system', 'level' => 100]);
        $role->permissions()->attach(Permission::pluck('id'));

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_can_save_and_retrieve_settings(): void
    {
        $user = $this->actingAsAdmin();

        $this->actingAs($user)->putJson('/api/v1/settings', [
            'settings' => [
                'general.site_name' => 'Grace Church',
                'general.tagline' => 'Loving God, Loving People',
            ],
        ])->assertOk();

        $this->actingAs($user)->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('settings.general.site_name', 'Grace Church');
    }

    public function test_can_retrieve_settings_by_group(): void
    {
        $user = $this->actingAsAdmin();

        $this->actingAs($user)->putJson('/api/v1/settings', [
            'settings' => [
                'general.site_name' => 'Grace Church',
                'email.driver' => 'smtp',
                'email.from' => 'hello@grace.church',
            ],
        ])->assertOk();

        $response = $this->actingAs($user)->getJson('/api/v1/settings/email');
        $response->assertOk();

        $settings = $response->json('settings');
        $this->assertArrayHasKey('email.driver', $settings);
        $this->assertArrayNotHasKey('general.site_name', $settings);
    }

    public function test_unauthenticated_cannot_update_settings(): void
    {
        $this->putJson('/api/v1/settings', [
            'settings' => ['general.site_name' => 'Hacked'],
        ])->assertUnauthorized();
    }
}
```

- [ ] **Step 6: Add settings routes to api.php**

```php
<?php
// routes/api.php

use Common\Auth\Controllers\AuthController;
use Common\Settings\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{group}', [SettingController::class, 'show']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('settings', [SettingController::class, 'update'])
            ->middleware('permission:settings.update');
    });
});
```

- [ ] **Step 7: Register SettingService in AppServiceProvider**

```php
<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Common\Settings\Services\SettingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    public function boot(): void
    {
        //
    }
}
```

- [ ] **Step 8: Register CheckPermission middleware**

Create the middleware:

```php
<?php
// common/foundation/src/Auth/Middleware/CheckPermission.php

namespace Common\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
```

Register in `bootstrap/app.php` (Laravel 12 style) or `app/Http/Kernel.php`:

```php
// In Laravel 12, middleware aliases go in bootstrap/app.php:
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \Common\Auth\Middleware\CheckPermission::class,
    ]);
})
```

- [ ] **Step 9: Run tests**

Run: `php artisan test tests/Feature/Settings/ --verbose`
Expected: All 3 tests pass

- [ ] **Step 10: Commit**

```bash
git add database/migrations/0001_01_01_000009_create_settings_table.php \
  common/foundation/src/Settings/ common/foundation/src/Auth/Middleware/ \
  routes/api.php app/Providers/AppServiceProvider.php tests/Feature/Settings/
git commit -m "feat: add key-value settings engine with group filtering and permission check"
```

---

## Task 9: Plugin Manager

**Files:**
- Create: `common/foundation/src/Core/PluginManager.php`
- Create: `config/plugins.json`
- Test: `tests/Unit/PluginManagerTest.php`

- [ ] **Step 1: Create plugins.json**

```json
{
  "timeline":       { "enabled": true,  "version": "1.0.0" },
  "groups":         { "enabled": true,  "version": "1.0.0" },
  "events":         { "enabled": true,  "version": "1.0.0" },
  "sermons":        { "enabled": true,  "version": "1.0.0" },
  "prayer":         { "enabled": true,  "version": "1.0.0" },
  "giving":         { "enabled": true,  "version": "1.0.0" },
  "chat":           { "enabled": true,  "version": "1.0.0" },
  "library":        { "enabled": true,  "version": "1.0.0" },
  "church_builder": { "enabled": true,  "version": "1.0.0" },
  "blog":           { "enabled": true,  "version": "1.0.0" },
  "live_meeting":   { "enabled": true,  "version": "1.0.0" },
  "volunteers":     { "enabled": false, "version": "1.0.0" },
  "fundraising":    { "enabled": false, "version": "1.0.0" },
  "stories":        { "enabled": false, "version": "1.0.0" },
  "pastoral":       { "enabled": false, "version": "1.0.0" }
}
```

- [ ] **Step 2: Create PluginManager**

```php
<?php
// common/foundation/src/Core/PluginManager.php

namespace Common\Core;

use Illuminate\Support\Facades\Cache;

class PluginManager
{
    private const CACHE_KEY = 'app.plugins';
    private const CACHE_TTL = 300; // 5 minutes

    private string $path;

    public function __construct()
    {
        $this->path = config_path('plugins.json');
    }

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            if (!file_exists($this->path)) {
                return [];
            }
            return json_decode(file_get_contents($this->path), true) ?? [];
        });
    }

    public function isEnabled(string $plugin): bool
    {
        $plugins = $this->all();
        return ($plugins[$plugin]['enabled'] ?? false) === true;
    }

    public function getEnabled(): array
    {
        return collect($this->all())
            ->filter(fn (array $config) => $config['enabled'] === true)
            ->keys()
            ->toArray();
    }

    public function getDisabled(): array
    {
        return collect($this->all())
            ->filter(fn (array $config) => $config['enabled'] === false)
            ->keys()
            ->toArray();
    }

    public function enable(string $plugin): void
    {
        $this->setEnabled($plugin, true);
    }

    public function disable(string $plugin): void
    {
        $this->setEnabled($plugin, false);
    }

    private function setEnabled(string $plugin, bool $enabled): void
    {
        $plugins = $this->all();
        if (!isset($plugins[$plugin])) {
            return;
        }
        $plugins[$plugin]['enabled'] = $enabled;
        file_put_contents($this->path, json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Cache::forget(self::CACHE_KEY);
    }
}
```

- [ ] **Step 3: Write PluginManager test**

```php
<?php
// tests/Unit/PluginManagerTest.php

namespace Tests\Unit;

use Common\Core\PluginManager;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    public function test_reads_enabled_plugins(): void
    {
        $manager = app(PluginManager::class);
        $enabled = $manager->getEnabled();

        $this->assertContains('timeline', $enabled);
        $this->assertContains('sermons', $enabled);
        $this->assertNotContains('volunteers', $enabled);
    }

    public function test_checks_single_plugin(): void
    {
        $manager = app(PluginManager::class);

        $this->assertTrue($manager->isEnabled('timeline'));
        $this->assertFalse($manager->isEnabled('volunteers'));
        $this->assertFalse($manager->isEnabled('nonexistent'));
    }
}
```

- [ ] **Step 4: Register PluginManager as singleton**

Add to `AppServiceProvider::register()`:
```php
$this->app->singleton(\Common\Core\PluginManager::class);
```

- [ ] **Step 5: Run tests**

Run: `php artisan test tests/Unit/PluginManagerTest.php --verbose`
Expected: Both tests pass

- [ ] **Step 6: Commit**

```bash
git add common/foundation/src/Core/PluginManager.php config/plugins.json \
  tests/Unit/PluginManagerTest.php app/Providers/AppServiceProvider.php
git commit -m "feat: add PluginManager with enable/disable toggle and caching"
```

---

## Task 10: Bootstrap Data Service

**Files:**
- Create: `common/foundation/src/Core/BootstrapDataService.php`
- Create: `common/foundation/src/Core/Controllers/BootstrapController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create BootstrapDataService**

```php
<?php
// common/foundation/src/Core/BootstrapDataService.php

namespace Common\Core;

use Common\Settings\Services\SettingService;

class BootstrapDataService
{
    public function __construct(
        private SettingService $settings,
        private PluginManager $plugins,
    ) {}

    public function get(): array
    {
        $user = auth()->user();

        return [
            'user' => $user ? $user->getBootstrapData() : null,
            'settings' => $this->getPublicSettings(),
            'plugins' => $this->plugins->getEnabled(),
        ];
    }

    private function getPublicSettings(): array
    {
        // Only expose non-sensitive settings to the frontend
        $all = $this->settings->getAll();
        $publicPrefixes = [
            'general.', 'theme.', 'seo.', 'landing.',
            'player.', 'captcha.site_key', 'gdpr.',
        ];

        return collect($all)
            ->filter(function ($value, $key) use ($publicPrefixes) {
                foreach ($publicPrefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) return true;
                }
                return false;
            })
            ->toArray();
    }
}
```

- [ ] **Step 2: Create web route that serves SPA with bootstrap data**

```php
<?php
// routes/web.php

use Common\Core\BootstrapDataService;
use Illuminate\Support\Facades\Route;

// SPA catch-all — serves React app with bootstrap data
Route::get('/{any?}', function (BootstrapDataService $bootstrap) {
    return view('app', ['bootstrapData' => $bootstrap->get()]);
})->where('any', '.*');
```

- [ ] **Step 3: Register BootstrapDataService**

Add to `AppServiceProvider::register()`:
```php
$this->app->singleton(\Common\Core\BootstrapDataService::class);
```

- [ ] **Step 4: Commit**

```bash
git add common/foundation/src/Core/BootstrapDataService.php routes/web.php \
  app/Providers/AppServiceProvider.php
git commit -m "feat: add BootstrapDataService for React frontend hydration"
```

---

## Task 11: Auth Controllers (Login, Register, Logout)

**Files:**
- Create: `common/foundation/src/Auth/Controllers/AuthController.php`
- Create: `common/foundation/src/Auth/Requests/LoginRequest.php`
- Create: `common/foundation/src/Auth/Requests/RegisterRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Auth/LoginTest.php`
- Test: `tests/Feature/Auth/RegisterTest.php`

- [ ] **Step 1: Create LoginRequest**

```php
<?php
// common/foundation/src/Auth/Requests/LoginRequest.php

namespace Common\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}
```

- [ ] **Step 2: Create RegisterRequest**

```php
<?php
// common/foundation/src/Auth/Requests/RegisterRequest.php

namespace Common\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
```

- [ ] **Step 3: Create AuthController**

```php
<?php
// common/foundation/src/Auth/Controllers/AuthController.php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Role;
use Common\Auth\Requests\LoginRequest;
use Common\Auth\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->isBanned()) {
            return response()->json(['message' => 'Account suspended'], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $user->getBootstrapData(),
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $userModel = config('auth.providers.users.model');

        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Attach default role
        $defaultRole = Role::where('is_default', true)->first();
        if ($defaultRole) {
            $user->roles()->attach($defaultRole);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $user->getBootstrapData(),
            'token' => $token,
        ], 201);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::user()->getBootstrapData(),
        ]);
    }
}
```

- [ ] **Step 4: Add auth routes**

Add to `routes/api.php` inside the `v1` prefix group:

```php
// Auth
Route::post('login', [Common\Auth\Controllers\AuthController::class, 'login']);
Route::post('register', [Common\Auth\Controllers\AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [Common\Auth\Controllers\AuthController::class, 'logout']);
    Route::get('me', [Common\Auth\Controllers\AuthController::class, 'me']);

    // Settings (already added in Task 8)
    Route::put('settings', [Common\Settings\Controllers\SettingController::class, 'update'])
        ->middleware('permission:settings.update');
});
```

- [ ] **Step 5: Write login test**

```php
<?php
// tests/Feature/Auth/LoginTest.php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->banned()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }
}
```

- [ ] **Step 6: Write register test**

```php
<?php
// tests/Feature/Auth/RegisterTest.php

namespace Tests\Feature\Auth;

use App\Models\User;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        // Create default role
        Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20, 'is_default' => true]);

        $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.name', 'John Doe');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

        // Verify default role was assigned
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_registration_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Jane',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }
}
```

- [ ] **Step 7: Run auth tests**

Run: `php artisan test tests/Feature/Auth/ --verbose`
Expected: All 5 tests pass

- [ ] **Step 8: Commit**

```bash
git add common/foundation/src/Auth/Controllers/ common/foundation/src/Auth/Requests/ \
  routes/api.php tests/Feature/Auth/
git commit -m "feat: add auth controllers (login, register, logout, me) with tests"
```

---

## Task 12: Role & Permission Admin API

**Files:**
- Create: `common/foundation/src/Auth/Controllers/RoleController.php`
- Create: `common/foundation/src/Auth/Controllers/PermissionController.php`
- Create: `common/foundation/src/Auth/Policies/UserPolicy.php`
- Create: `common/foundation/src/Core/BasePolicy.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Roles/RoleTest.php`

- [ ] **Step 1: Create BasePolicy (shared super-admin bypass)**

```php
<?php
// common/foundation/src/Core/BasePolicy.php

namespace Common\Core;

use App\Models\User;

abstract class BasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Super admins bypass all checks
        if ($user->getRoleLevel() >= 100) {
            return true;
        }
        return null;
    }
}
```

- [ ] **Step 2: Create RoleController**

```php
<?php
// common/foundation/src/Auth/Controllers/RoleController.php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name,display_name,group')
            ->orderByDesc('level')
            ->get();

        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:system,church,custom',
            'level' => 'integer|min:1|max:99',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'custom',
            'level' => $validated['level'] ?? 10,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'role' => $role->load('permissions:id,name,display_name,group'),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'level' => 'integer|min:1|max:99',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->update(collect($validated)->except('permissions')->toArray());

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);

            // Clear permission cache for all users with this role
            $role->users->each(fn ($user) => $user->clearPermissionCache());
        }

        return response()->json([
            'role' => $role->fresh()->load('permissions:id,name,display_name,group'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->type === 'system') {
            return response()->json(['message' => 'Cannot delete system roles'], 403);
        }

        $role->delete();
        return response()->noContent();
    }
}
```

- [ ] **Step 3: Create PermissionController**

```php
<?php
// common/foundation/src/Auth/Controllers/PermissionController.php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        // Group by the 'group' field for the admin checkbox matrix UI
        $grouped = $permissions->groupBy('group')->map(fn ($perms) => $perms->values());

        return response()->json([
            'permissions' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
```

- [ ] **Step 4: Add role/permission routes**

Add inside the `auth:sanctum` group in `routes/api.php`:

```php
// Roles & Permissions
Route::middleware('permission:roles.view')->group(function () {
    Route::get('roles', [\Common\Auth\Controllers\RoleController::class, 'index']);
    Route::get('permissions', [\Common\Auth\Controllers\PermissionController::class, 'index']);
});
Route::middleware('permission:roles.create')->post('roles', [\Common\Auth\Controllers\RoleController::class, 'store']);
Route::middleware('permission:roles.update')->put('roles/{role}', [\Common\Auth\Controllers\RoleController::class, 'update']);
Route::middleware('permission:roles.delete')->delete('roles/{role}', [\Common\Auth\Controllers\RoleController::class, 'destroy']);
```

- [ ] **Step 5: Write role test**

```php
<?php
// tests/Feature/Roles/RoleTest.php

namespace Tests\Feature\Roles;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_super_admin_can_list_roles(): void
    {
        $user = $this->actingAsSuperAdmin();

        $this->actingAs($user)->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonStructure(['roles' => [['id', 'name', 'slug', 'level', 'permissions']]]);
    }

    public function test_super_admin_can_create_role(): void
    {
        $user = $this->actingAsSuperAdmin();
        $permIds = Permission::whereIn('name', ['files.upload'])->pluck('id')->toArray();

        $this->actingAs($user)->postJson('/api/v1/roles', [
            'name' => 'Worship Leader',
            'description' => 'Manages worship content',
            'level' => 35,
            'permissions' => $permIds,
        ])
            ->assertStatus(201)
            ->assertJsonPath('role.slug', 'worship-leader');
    }

    public function test_cannot_delete_system_roles(): void
    {
        $user = $this->actingAsSuperAdmin();
        $memberRole = Role::where('slug', 'member')->first();

        $this->actingAs($user)->deleteJson("/api/v1/roles/{$memberRole->id}")
            ->assertStatus(403);
    }

    public function test_member_cannot_access_roles(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        $this->actingAs($user)->getJson('/api/v1/roles')
            ->assertStatus(403);
    }
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test tests/Feature/Roles/ --verbose`
Expected: All 4 tests pass

- [ ] **Step 7: Commit**

```bash
git add common/foundation/src/Auth/Controllers/RoleController.php \
  common/foundation/src/Auth/Controllers/PermissionController.php \
  common/foundation/src/Core/BasePolicy.php \
  routes/api.php tests/Feature/Roles/
git commit -m "feat: add Role/Permission CRUD API with permission-based access control"
```

---

## Task 13: React Frontend Core — HTTP Client, Bootstrap Data, Auth Hooks

**Files:**
- Create: `resources/client/common/http/api-client.ts`
- Create: `resources/client/common/http/query-client.ts`
- Create: `resources/client/common/core/bootstrap-data.ts`
- Create: `resources/client/common/auth/use-permissions.ts`
- Create: `resources/client/common/auth/use-auth.ts`

- [ ] **Step 1: Create API client (Axios)**

```typescript
// resources/client/common/http/api-client.ts
import axios from 'axios';

export const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
});

// Auto-refresh CSRF on 419
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 419) {
      await axios.get('/sanctum/csrf-cookie');
      return apiClient.request(error.config);
    }
    return Promise.reject(error);
  }
);

// Attach bearer token if stored
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

- [ ] **Step 2: Create TanStack Query client**

```typescript
// resources/client/common/http/query-client.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30 * 1000, // 30 seconds (BeMusic pattern)
      retry: (failureCount, error: any) => {
        // Don't retry 401, 403, 404
        const status = error?.response?.status;
        if ([401, 403, 404].includes(status)) return false;
        return failureCount < 2;
      },
    },
  },
});
```

- [ ] **Step 3: Create bootstrap data store (Zustand)**

```typescript
// resources/client/common/core/bootstrap-data.ts
import { create } from 'zustand';
import { ReactNode, createContext, useContext } from 'react';

interface BootstrapUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  permissions: Record<string, boolean>;
  role_level: number;
  roles: string[];
  theme: string;
  language: string;
}

interface BootstrapData {
  user: BootstrapUser | null;
  settings: Record<string, string>;
  plugins: string[];
}

interface BootstrapStore extends BootstrapData {
  setUser: (user: BootstrapUser | null) => void;
}

declare global {
  interface Window {
    __BOOTSTRAP_DATA__: BootstrapData;
  }
}

export const useBootstrapStore = create<BootstrapStore>((set) => ({
  ...(window.__BOOTSTRAP_DATA__ ?? { user: null, settings: {}, plugins: [] }),
  setUser: (user) => set({ user }),
}));

export function useBootstrapData() {
  return useBootstrapStore();
}

export function BootstrapDataProvider({ children }: { children: ReactNode }) {
  // Store is already hydrated from window.__BOOTSTRAP_DATA__ via create()
  return <>{children}</>;
}
```

- [ ] **Step 4: Create permission hook**

```typescript
// resources/client/common/auth/use-permissions.ts
import { useBootstrapStore } from '../core/bootstrap-data';

export function useUserPermissions() {
  const user = useBootstrapStore((s) => s.user);
  const permissions = user?.permissions ?? {};

  return {
    hasPermission: (name: string): boolean => {
      return permissions[name] === true;
    },
    can: (action: string, resource: string): boolean => {
      return permissions[`${resource}.${action}`] === true;
    },
    canAny: (...perms: string[]): boolean => {
      return perms.some((p) => permissions[p] === true);
    },
    isAdmin: permissions['admin.access'] === true,
    roleLevel: user?.role_level ?? 0,
  };
}
```

- [ ] **Step 5: Create auth hook**

```typescript
// resources/client/common/auth/use-auth.ts
import { useMutation } from '@tanstack/react-query';
import { apiClient } from '../http/api-client';
import { useBootstrapStore } from '../core/bootstrap-data';

export function useAuth() {
  const { user, setUser } = useBootstrapStore();

  const loginMutation = useMutation({
    mutationFn: async (data: { email: string; password: string }) => {
      const res = await apiClient.post('/login', data);
      localStorage.setItem('auth_token', res.data.token);
      setUser(res.data.user);
      return res.data;
    },
  });

  const registerMutation = useMutation({
    mutationFn: async (data: { name: string; email: string; password: string; password_confirmation: string }) => {
      const res = await apiClient.post('/register', data);
      localStorage.setItem('auth_token', res.data.token);
      setUser(res.data.user);
      return res.data;
    },
  });

  const logout = async () => {
    await apiClient.post('/logout');
    localStorage.removeItem('auth_token');
    setUser(null);
    window.location.href = '/';
  };

  return {
    user,
    isAuthenticated: !!user,
    login: loginMutation,
    register: registerMutation,
    logout,
  };
}
```

- [ ] **Step 6: Verify TypeScript compiles**

Run: `npx tsc --noEmit`
Expected: No type errors

- [ ] **Step 7: Commit**

```bash
git add resources/client/common/
git commit -m "feat: add React core (API client, query client, bootstrap data, auth/permission hooks)"
```

---

## Task 14: Admin Layout Shell + Dashboard

**Files:**
- Create: `resources/client/admin/AdminLayout.tsx`
- Create: `resources/client/admin/DashboardPage.tsx`
- Create: `resources/client/common/auth/auth-guards.tsx`
- Modify: `resources/client/app-router.tsx`

- [ ] **Step 1: Create auth guard component**

```tsx
// resources/client/common/auth/auth-guards.tsx
import { Navigate, Outlet } from 'react-router';
import { useAuth } from './use-auth';
import { useUserPermissions } from './use-permissions';

export function RequireAuth() {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  return <Outlet />;
}

export function RequirePermission({ permission }: { permission: string }) {
  const { hasPermission } = useUserPermissions();
  if (!hasPermission(permission)) return <Navigate to="/" replace />;
  return <Outlet />;
}
```

- [ ] **Step 2: Create AdminLayout**

```tsx
// resources/client/admin/AdminLayout.tsx
import { NavLink, Outlet } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';

const sidebarItems = [
  { label: 'Dashboard', path: '/admin', icon: 'LayoutDashboard', permission: 'admin.access' },
  { label: 'Users', path: '/admin/users', icon: 'Users', permission: 'users.view' },
  { label: 'Roles', path: '/admin/roles', icon: 'Shield', permission: 'roles.view' },
  { label: 'Settings', path: '/admin/settings', icon: 'Settings', permission: 'settings.view' },
];

export function AdminLayout() {
  const { hasPermission } = useUserPermissions();

  return (
    <div className="flex h-screen bg-gray-100 dark:bg-gray-900">
      {/* Sidebar */}
      <aside className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <h1 className="text-lg font-bold text-gray-900 dark:text-white">Admin Panel</h1>
        </div>
        <nav className="p-2 space-y-1">
          {sidebarItems
            .filter((item) => hasPermission(item.permission))
            .map((item) => (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.path === '/admin'}
                className={({ isActive }) =>
                  `block px-3 py-2 rounded-md text-sm ${
                    isActive
                      ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                  }`
                }
              >
                {item.label}
              </NavLink>
            ))}
        </nav>
      </aside>

      {/* Content */}
      <main className="flex-1 overflow-auto p-6">
        <Outlet />
      </main>
    </div>
  );
}
```

- [ ] **Step 3: Create DashboardPage**

```tsx
// resources/client/admin/DashboardPage.tsx
export function DashboardPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Dashboard</h1>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {['Members', 'Groups', 'Events', 'Donations'].map((label) => (
          <div key={label} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p className="text-sm text-gray-500 dark:text-gray-400">{label}</p>
            <p className="text-3xl font-bold text-gray-900 dark:text-white mt-1">—</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

- [ ] **Step 4: Update app-router with admin routes**

```tsx
// resources/client/app-router.tsx
import { Routes, Route } from 'react-router';
import { lazy, Suspense } from 'react';
import { RequireAuth, RequirePermission } from './common/auth/auth-guards';

const AdminLayout = lazy(() => import('./admin/AdminLayout').then((m) => ({ default: m.AdminLayout })));
const DashboardPage = lazy(() => import('./admin/DashboardPage').then((m) => ({ default: m.DashboardPage })));
const LoginPage = lazy(() => import('./auth/LoginPage').then((m) => ({ default: m.LoginPage })));

function Loading() {
  return <div className="flex items-center justify-center h-screen">Loading...</div>;
}

export function AppRouter() {
  return (
    <Suspense fallback={<Loading />}>
      <Routes>
        {/* Public */}
        <Route path="/" element={<div className="p-8 text-2xl">Church Platform v5</div>} />
        <Route path="/login" element={<LoginPage />} />

        {/* Admin */}
        <Route element={<RequireAuth />}>
          <Route element={<RequirePermission permission="admin.access" />}>
            <Route path="/admin" element={<AdminLayout />}>
              <Route index element={<DashboardPage />} />
              {/* More admin routes added in subsequent tasks */}
            </Route>
          </Route>
        </Route>
      </Routes>
    </Suspense>
  );
}
```

- [ ] **Step 5: Create minimal LoginPage**

```tsx
// resources/client/auth/LoginPage.tsx
import { useState } from 'react';
import { useAuth } from '@app/common/auth/use-auth';
import { useNavigate } from 'react-router';

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await login.mutateAsync(form);
      navigate('/admin');
    } catch {
      // Error handled by mutation state
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
      <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md w-full max-w-sm space-y-4">
        <h1 className="text-xl font-bold text-center text-gray-900 dark:text-white">Sign In</h1>

        <input
          type="email"
          placeholder="Email"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          className="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          required
        />

        <input
          type="password"
          placeholder="Password"
          value={form.password}
          onChange={(e) => setForm({ ...form, password: e.target.value })}
          className="w-full px-3 py-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
          required
        />

        {login.error && (
          <p className="text-red-500 text-sm">Invalid credentials</p>
        )}

        <button
          type="submit"
          disabled={login.isPending}
          className="w-full py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
        >
          {login.isPending ? 'Signing in...' : 'Sign In'}
        </button>
      </form>
    </div>
  );
}
```

- [ ] **Step 6: Verify build**

Run: `npm run build`
Expected: No TypeScript or build errors

- [ ] **Step 7: Commit**

```bash
git add resources/client/admin/ resources/client/auth/ resources/client/app-router.tsx \
  resources/client/common/auth/auth-guards.tsx
git commit -m "feat: add admin layout shell with sidebar, dashboard, login page, and route guards"
```

---

## Task 15: GitHub Actions CI Pipeline

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Create CI workflow**

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main, v5-foundation]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, pdo_mysql, redis
          coverage: none

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy env
        run: cp .env.example .env && php artisan key:generate

      - name: Run migrations
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: secret
        run: php artisan migrate

      - name: Run tests
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: secret
        run: php artisan test --verbose

  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm

      - name: Install npm dependencies
        run: npm ci

      - name: TypeScript check
        run: npx tsc --noEmit

      - name: Build frontend
        run: npm run build

  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Pint (PHP linter)
        run: vendor/bin/pint --test
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add GitHub Actions pipeline (PHP tests, TypeScript build, lint)"
```

---

## Summary

After completing all 15 tasks, you will have:

| Component | Status |
|-----------|--------|
| Docker environment (MySQL, Redis, Meilisearch) | Ready |
| Laravel 12 project scaffold | Ready |
| React 19 + TypeScript + Vite 6 + Tailwind 4 | Ready |
| `common/foundation/` directory structure | Ready |
| Database: users, roles, permissions, pivots, settings, social_profiles | 10 migrations |
| Permission system (DB-backed, layered resolution, cached) | 26 core permissions |
| Role system (8 presets, permission pivot, level hierarchy) | 8 roles seeded |
| Auth API (login, register, logout, me) | 4 endpoints |
| Role/Permission admin API (CRUD) | 5 endpoints |
| Settings engine (key-value store, group filtering) | 3 endpoints |
| Plugin manager (enable/disable, cached) | Working |
| Bootstrap data service (frontend hydration) | Working |
| React: API client, query client, bootstrap store | Working |
| React: Permission hook, auth hook, route guards | Working |
| React: Admin layout (sidebar + dashboard) | Working |
| React: Login page | Working |
| Tests: 15+ tests (unit + feature) | Passing |
| CI: GitHub Actions (tests, build, lint) | Configured |

**Next plan:** Plan 2 (Timeline + Reactions + Comments) builds on this foundation to add the first church-specific plugin, establishing the plugin development pattern for all subsequent plans.

---

*Plan created: 2026-03-28 | Estimated effort: Week 1-2 (Tasks 1-12 backend, Tasks 13-15 frontend + CI)*
