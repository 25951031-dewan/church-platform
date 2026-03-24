# Architecture

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | React 18, TypeScript 5, Vite 6 |
| Database | MySQL (prod), SQLite (tests) |
| Auth | Laravel Sanctum (SPA token) |
| Realtime | Pusher / Laravel Echo |
| Search | Laravel Scout |
| Styles | Tailwind CSS 3 |
| State | TanStack Query v5 |
| Testing | PestPHP + pest-plugin-laravel |

## Plugin System

All features are implemented as Laravel service-provider plugins in `plugins/`. The core `app/` contains only User model, core migrations, and AppServiceProvider.

```
plugins/
├── Analytics/      ← Admin dashboard charts
├── ChurchPage/     ← Church profile pages + CSV import
├── Comment/        ← Threaded comments on posts
├── Community/      ← Communities + Counsel Groups
├── Event/          ← Church events (Sprint 4)
├── Faq/            ← FAQ categories + articles
├── Feed/           ← Home/community/church feeds
├── Post/           ← Post types: post/prayer/blessing/poll/bible_study (Sprint 5)
└── Reaction/       ← Emoji reactions (polymorphic)
```

### Plugin Registration Flow

```
HTTP Request
  → bootstrap/app.php (api middleware group, /api prefix)
  → bootstrap/providers.php (lists all plugin ServiceProviders)
  → PluginServiceProvider::boot()
      → Router::class->middleware('api')->prefix('api')->group(routes/api.php)
      → loadMigrationsFrom(__DIR__ . '/database/migrations')
```

### Route Namespace
All API routes resolve to `POST /api/v1/resource`. The `api` prefix comes from `bootstrap/app.php`. The `v1` prefix is in each plugin's `routes/api.php`.

## Data Model

### Core Tables (app migrations)
- `users` — `id`, `name`, `email`, `password`, `avatar`, `cover_image`, `bio`, `location`, `website`, `is_admin`, `two_factor_*`
- `churches` — `id`, `name`, `slug`, `description`, `logo`, `cover_image`, `address`, `platform_mode`
- `church_members` — `user_id`, `church_id`, `role` (admin|leader|member), `status`
- `settings` — `key`, `value` (platform-wide config)
- `pages` — CMS pages with GrapesJS page builder data
- `notifications` — Standard Laravel notifications (UUID PK)

### Plugin Tables
- `social_posts` — `id`, `user_id`, `type`, `body`, `meta` (JSON), `status`, `is_anonymous`, `shares_count`, `event_id` (generated)
- `poll_votes` — `post_id`, `user_id`, `option_id` — UNIQUE(post_id, user_id, option_id)
- `comments` — `post_id`, `user_id`, `parent_id`, `body`
- `reactions` — polymorphic (reactable_type, reactable_id), `user_id`, `emoji`
- `communities` — `name`, `slug`, `description`, `church_id`, `created_by`, `is_public`
- `community_users` — `community_id`, `user_id`, `role`, `status`
- `events` — `title`, `start_at`, `end_at`, `category`, `status`, `going_count`, `maybe_count`, `max_capacity`, `recurrence_rule`, `meeting_url`
- `event_attendees` — `event_id`, `user_id`, `status` — UNIQUE(event_id, user_id)

### Post Types
`social_posts.type` enum: `post | prayer | blessing | poll | bible_study | event_post`

Each type stores type-specific data in the `meta` JSON column:
- **poll**: `{options: [{id, label}], expires_at}`
- **prayer**: `{answered: bool, answered_at: timestamp}`
- **bible_study**: `{scripture_reference, passage, study_guide}`
- **blessing**: `{scripture}`
- **event_post**: stored via `event_id` generated column referencing `events.id`

## Frontend Architecture

```
resources/js/
├── app.js              ← Vite entry, React root, QueryClient
├── bootstrap.js        ← Axios defaults, CSRF
├── components/         ← Shared: Avatar, Modal, Button, etc.
├── hooks/              ← Custom React hooks
└── plugins/
    ├── community/      ← CommunityPage, CommunityCard
    ├── events/         ← EventsPage, EventCard, EventCalendar, EventDetailPage, CreateEventForm
    ├── faq/            ← FaqPage
    ├── feed/           ← FeedPage, PostCard, PrayerCard, BlessingCard, BibleStudyCard, PollCard, CreatePostModal
    └── profile/        ← ProfilePage
```

### API Communication Pattern
```ts
// All requests via axios with base URL /api/v1/
import axios from 'axios'

const { data } = await axios.get('/api/v1/events', { params: { category: 'worship' } })

// TanStack Query for data fetching + caching
const { data: events } = useQuery({
  queryKey: ['events', filters],
  queryFn: () => axios.get('/api/v1/events', { params: filters }).then(r => r.data)
})
```

## Auth Flow
1. Frontend POSTs credentials to `/api/v1/login` (Sanctum)
2. Receives token → stored in `localStorage`
3. All subsequent requests include `Authorization: Bearer {token}`
4. Backend uses `auth:sanctum` middleware on protected routes

## Realtime Broadcasting
Events broadcast via Pusher. Frontend subscribes with Laravel Echo. Used for: new comments, reactions, notifications.

## Background Jobs
- `SendEventRemindersJob` — runs every 15 minutes (console scheduler), sends database notifications to going attendees 24h before event start
- Job scheduling in `routes/console.php`

## Testing Architecture
Tests use `RefreshDatabase` + SQLite in-memory. Each test is fully isolated. Plugin factories are in `database/factories/` with `newFactory()` override on model. Bootstrap at `tests/bootstrap.php` handles PSR-4 override for worktree isolation.
