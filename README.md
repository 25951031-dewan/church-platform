# Church Platform

A full-featured church management and community platform built with **Laravel 11** + **React 18** + **TypeScript**.

## Features

| Plugin | Description |
|---|---|
| **Feed** | Home, community, and church feeds with post type filtering |
| **Post** | Post types: text, prayer, blessing, poll, bible study |
| **Comment** | Threaded comments with soft delete |
| **Reaction** | Polymorphic emoji reactions |
| **Community** | Communities + private counsel groups |
| **Event** | Church events with RSVP, recurring rules, online/hybrid, discussion threads |
| **ChurchPage** | Church profiles with member management + CSV import/export |
| **FAQ** | FAQ categories and articles with admin CRUD |
| **Analytics** | Admin dashboard with platform-wide charts |

## Tech Stack

- **Backend**: Laravel 11, PHP 8.3, MySQL
- **Frontend**: React 18, TypeScript 5, Vite 6, Tailwind CSS
- **Auth**: Laravel Sanctum (SPA token)
- **Realtime**: Pusher + Laravel Echo
- **Search**: Laravel Scout
- **Testing**: PestPHP

## Quick Start

See [docs/onboarding.md](docs/onboarding.md) for full setup instructions.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run dev
```

## Documentation

- [Architecture](docs/architecture.md) — Plugin system, data model, frontend structure
- [API Reference](docs/api.md) — All endpoints with request/response examples
- [Onboarding](docs/onboarding.md) — Setup, testing, creating plugins
