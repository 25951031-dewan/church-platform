# CLAUDE.md — Church Platform Codebase Guide

This document provides AI assistants with a comprehensive understanding of the church-platform codebase structure, conventions, and development workflows.

---

## Project Overview

**Church Platform** is a production-ready, multi-tenant church management platform built with:
- **Backend**: Laravel 11 (PHP 8.3+)
- **Frontend**: React 18 SPA (Vite, Tailwind CSS, TanStack Query)
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis 7
- **Search**: MeiliSearch 1.6 (optional)
- **Auth**: Laravel Sanctum + Socialite (Google, Facebook)

The platform supports two operating modes: **single-church** (one organization) and **multi-church** (directory of organizations with members/followers).

---

## Directory Structure

```
church-platform/
├── app/
│   ├── Core/                  # SettingsManager, ThemeManager, MenuBuilder
│   ├── Http/
│   │   ├── Controllers/Api/   # REST controllers (Admin/, SduiController)
│   │   └── Middleware/        # ResolvePlatformMode, VerifyCaptcha, TrackPageView
│   ├── Models/                # User, Church, ChurchMember, Page
│   ├── Jobs/                  # Queue jobs
│   ├── Services/              # PlatformModeService, CacheWarmingService
│   └── Providers/             # AppServiceProvider, etc.
├── plugins/                   # Modular plugin system
│   ├── Analytics/             # Dashboard & data collection
│   ├── ChurchPage/            # Church page management & CSV imports
│   ├── Community/             # Counsel groups & community features
│   ├── Faq/                   # FAQ management with categories
│   └── Post/                  # Social posts & content sharing
├── database/
│   ├── migrations/            # Schema definitions (12 migration files)
│   ├── factories/             # Eloquent model factories
│   └── seeders/               # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── admin/         # 7+ admin management components + settings/
│   │   │   ├── shared/        # CaptchaWidget, OfflineIndicator
│   │   │   └── plugins/       # Plugin-specific React components
│   │   ├── hooks/             # useOfflineStorage (IndexedDB)
│   │   ├── app.js             # Frontend entry point
│   │   └── bootstrap.js       # Axios setup
│   ├── css/                   # Tailwind CSS
│   └── views/                 # Blade templates (minimal — SPA approach)
├── routes/
│   ├── api.php                # /api/v1/* REST routes
│   ├── web.php                # Web routes (SPA catch-all)
│   └── console.php            # Artisan command schedules
├── config/                    # Laravel config files + plugins.php
├── tests/
│   ├── Feature/               # HTTP/integration tests (Pest)
│   └── Unit/                  # Unit tests (Pest)
├── deploy.sh                  # Bash deployment script (VPS)
├── docker-compose.yml         # Local/VPS Docker stack
├── .cpanel.yml                # Shared hosting deployment
├── vite.config.js             # Vite + PWA config
└── phpunit.xml                # Test suite configuration
```

---

## Key Architectural Patterns

### 1. Server-Driven UI (SDUI)
The backend returns **JSON component trees** that the frontend maps to React components. This allows layout changes without frontend redeployment.

- **Endpoint**: `GET /api/v1/sdui/home`, `GET /api/v1/sdui/church/{id}`
- **Controller**: `app/Http/Controllers/Api/SduiController.php`
- Frontend reads the `type` field of each component and renders the matching React component.

### 2. Plugin Architecture
All features beyond core auth/settings live in `/plugins/`. Each plugin is self-contained with its own routes, models, controllers, migrations, and views.

- Plugin registry: `storage/app/plugins.json`
- Plugin config: `config/plugins.php`
- Core plugins (Auth, Settings) cannot be disabled.
- Namespace: `Plugins\<PluginName>\`

### 3. Platform Mode
`ResolvePlatformMode` middleware injects `church_id` into every request.

- **Single-church**: All users belong to one default church (ID from settings).
- **Multi-church**: Multiple churches in a directory; users can be members or followers of any church.
- Service: `app/Services/PlatformModeService.php`

### 4. Caching Strategy
- **Driver**: Redis with tag-based invalidation (falls back to file cache on shared hosting).
- **TTL**: 1 hour for theme, menu, and platform settings.
- **Tags**: `theme`, `menu`, `settings` — invalidated on update.
- **Warming**: `app/Services/CacheWarmingService.php` pre-loads critical data.
- **Core class**: `app/Core/SettingsManager.php`, `app/Core/ThemeManager.php`

---

## Database Schema & Models

### Core Models

| Model | File | Key Fields |
|---|---|---|
| `User` | `app/Models/User.php` | id, name, email, password |
| `Church` | `app/Models/Church.php` | name, slug, status, coordinates, settings (JSON), soft deletes |
| `ChurchMember` | `app/Models/ChurchMember.php` | church_id, user_id, type (member/follow), role (admin/moderator) |
| `Page` | `app/Models/Page.php` | title, slug, content, builder_data (JSON), use_builder, status, soft deletes |

### Conventions
- **Soft deletes** on `churches`, `pages`, `faqs`, `social_posts`.
- **JSON columns** for flexible schema: `settings`, `social_links`, `custom_pages`, `builder_data`.
- **Eloquent scopes**: `published()`, `featured()`, `active()`, `forChurch($id)`.
- **Relationships naming**: `creator()`, `author()`, `church()` (belongsTo).
- **Performance indexes**: `(status, is_featured)` on churches; `(church_id, created_at)` on posts.

---

## API Structure

**Base prefix**: `/api/v1`

### Public Endpoints
```
GET  /api/v1/sdui/home
GET  /api/v1/sdui/church/{id}
GET  /api/v1/captcha/config
GET  /api/v1/auth/oauth/{provider}
GET  /api/v1/auth/oauth/{provider}/callback
```

### Admin Endpoints (Sanctum auth required)
```
GET|PATCH  /api/v1/admin/settings
GET|POST   /api/v1/admin/pages
PATCH|DELETE /api/v1/admin/pages/{page}
GET|PUT    /api/v1/admin/pages/{page}/builder
```

### Conventions
- REST with JSON responses and standard HTTP status codes.
- Validation errors → **422 Unprocessable Entity**.
- Authentication via **Laravel Sanctum** bearer tokens.
- API documentation auto-generated by **Scribe** (`/docs`).

---

## Frontend Conventions

### Component Structure
- **Admin components**: `resources/js/components/admin/` — each manages a specific admin feature.
- **Settings sub-components**: `resources/js/components/admin/settings/` — 8 settings panels (General, Platform, Appearance, Email, Auth, API, SEO, Cache).
- **Shared**: `CaptchaWidget.tsx` (Turnstile), `OfflineIndicator.tsx`.
- **Plugin components**: `resources/js/components/plugins/<PluginName>/`.

### State Management
- **Server state**: TanStack React Query (caching, background refetch).
- **UI state**: `useState` hooks (local component state).
- **Offline/PWA**: `useOfflineStorage` custom hook (IndexedDB via `hooks/useOfflineStorage.ts`).

### Styling
- **Tailwind CSS** (utility-first, `tailwind.config.js`).
- Custom font: **Figtree**.
- Theme color: `#2563eb` (blue-600).

### PWA & Caching (Workbox via `vite.config.js`)
- PDFs → CacheFirst, 30-day TTL.
- Hymn audio → CacheFirst, 7-day TTL.
- Bible/verse API → StaleWhileRevalidate, 7-day TTL.
- Sermons/library → StaleWhileRevalidate, 3-day TTL.

---

## Development Workflow

### Setup
```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Run all services concurrently (PHP server + queue + Vite + logs)
npm run dev
```

### Running Tests
```bash
# All tests
php artisan test

# Or directly with Pest
./vendor/bin/pest

# With coverage
./vendor/bin/pest --coverage
```

Test environment uses array cache, sync queue, array mail driver (see `phpunit.xml`).

### Code Quality
```bash
# Laravel Pint (code style)
./vendor/bin/pint

# Generate API docs (Scribe)
php artisan scribe:generate
```

### Deployment (VPS)
```bash
./deploy.sh deploy     # Full deploy (maintenance → update → migrate → optimize)
./deploy.sh optimize   # Clear & rebuild config/route/view caches
./deploy.sh migrate    # Run pending migrations only
./deploy.sh warm       # Warm critical caches
./deploy.sh rollback   # Revert last migration batch
```

### Docker Stack
```bash
docker-compose up -d   # Start: app, queue, scheduler, mysql, redis, meilisearch
docker-compose down    # Stop
```

---

## Environment Configuration

Key `.env` variables:

| Variable | Description | Default |
|---|---|---|
| `APP_ENV` | Environment | `local` |
| `DB_DATABASE` | MySQL database name | `church_platform` |
| `CACHE_DRIVER` | Cache backend | `redis` |
| `SESSION_DRIVER` | Session backend | `redis` |
| `QUEUE_CONNECTION` | Queue backend | `database` (cPanel) / `redis` (VPS) |
| `SCOUT_DRIVER` | Search backend | `database` / `meilisearch` |
| `TURNSTILE_SITE_KEY` | Cloudflare Turnstile public key | — |
| `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile secret | — |
| `GOOGLE_CLIENT_ID` | Google OAuth | — |
| `FACEBOOK_CLIENT_ID` | Facebook OAuth | — |
| `PUSHER_APP_KEY` | Pusher broadcasting | — |
| `LIVEKIT_URL` | LiveKit video/voice (optional) | — |

Redis uses prefix `church_` and separate databases: cache → DB 1, sessions → DB 2.

---

## Security Conventions

- **CAPTCHA**: Cloudflare Turnstile on public forms (`VerifyCaptcha` middleware).
- **Authentication**: Laravel Sanctum (API tokens).
- **2FA**: PragmaRX Google2FA (TOTP).
- **CSRF**: Laravel's standard protection on all forms.
- **Passwords**: bcrypt.
- **HTML Sanitization**: DOMPurify 3.1 on the frontend.
- **Permissions**: Spatie Permission (roles & permissions on models).

---

## Plugin Development Guide

Each plugin in `/plugins/<Name>/` follows this structure:
```
plugins/MyPlugin/
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Routes/
├── database/
│   └── migrations/
├── resources/
│   └── views/
└── composer.json (or plugin manifest)
```

- Namespace: `Plugins\MyPlugin\`
- Register in `config/plugins.php`
- Keep plugin logic self-contained; minimize coupling to core `app/`.

---

## Common Pitfalls & Notes for AI Assistants

1. **Platform mode matters**: Always check if code should respect single-church vs. multi-church mode. Use `PlatformModeService` or the injected `church_id` from middleware.

2. **Cache invalidation**: When updating settings, themes, or menus, ensure the relevant Redis tags are flushed. See `SettingsManager`, `ThemeManager`.

3. **SDUI component types**: When adding frontend components, register the new component `type` string in the SDUI component map so the backend can reference it.

4. **Plugin vs. core**: Features that could be optional belong in `/plugins/`, not in `app/`. Core `app/` handles auth, settings, pages, and platform configuration only.

5. **JSON columns**: `settings`, `builder_data`, `social_links` are JSON. Access via Eloquent casts or `getSetting()` helper, not raw SQL concatenation.

6. **Soft deletes**: Don't hard-delete churches, pages, posts, or FAQs — they use `SoftDeletes`. Use `withTrashed()` or `onlyTrashed()` when querying archived data.

7. **Queue jobs**: Long-running operations (email, cache warming, imports) must be dispatched as jobs, not run inline in controllers.

8. **Testing**: Use `array` cache/session/queue drivers in tests (already configured in `phpunit.xml`). Factories exist in `database/factories/`.

9. **API versioning**: All routes live under `/api/v1/`. Do not add routes outside this prefix without discussion.

10. **Frontend imports**: Use absolute imports from `resources/js/` root. Avoid deep relative imports (`../../../../`).
