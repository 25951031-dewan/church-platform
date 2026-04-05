# Church Community Platform - Complete Architecture Spec

> **Type**: White-label SaaS product
> **Architecture**: Foundation Fork (BeMusic `common/foundation` pattern)
> **Frontend**: Full React 19 SPA + TypeScript with BeMusic SEO pre-rendering
> **Created**: 2026-03-28

---

## Table of Contents

1. [Vision & Product Strategy](#1-vision--product-strategy)
2. [Tech Stack](#2-tech-stack)
3. [Architecture Overview](#3-architecture-overview)
4. [Foundation Core (Forked from BeMusic)](#4-foundation-core)
5. [Plugin System](#5-plugin-system)
6. [Authorization System (Roles / Permissions / Policies)](#6-authorization-system)
7. [Admin Settings Panel (23 Sections)](#7-admin-settings-panel)
8. [Plugin Inventory (12 Modules)](#8-plugin-inventory)
9. [Database Schema](#9-database-schema)
10. [SEO Strategy](#10-seo-strategy)
11. [Frontend Architecture](#11-frontend-architecture)
12. [Phased Roadmap](#12-phased-roadmap)
13. [Appendix: Permission Registry](#appendix-permission-registry)

---

## 1. Vision & Product Strategy

### Product

A **white-label church community platform** that combines:

- **BeMusic's tech backend** - Laravel foundation module, React component library, service patterns (Loader/Crupdate/Paginate), audio player, search, file management, billing
- **Sngine's community features** - Social feed, groups (Facebook-style), real-time chat, reactions, live meetings, prayer wall, giving/donations
- **Church-specific domain** - Sermons, Bible study, prayer requests, church directory, giving, pastoral care, volunteer management

### Deployment Model

- **White-label product** sold/licensed to churches
- Each installation is fully brandable (name, logo, colors, domain)
- Feature toggling via plugin registry (`config/plugins.json`)
- Supports both self-hosted and managed hosting
- Per-church pages within a single installation (Facebook Pages model)

### Key Differentiators

| Differentiator | How |
|----------------|-----|
| Scalable architecture | BeMusic's proven `common/foundation` module |
| Plugin modularity | Enable/disable features per client (white-label toggle) |
| Church Builder | Each church gets a branded mini-site with SEO (like Sngine Pages) |
| Audio player | Persistent sermon/worship player (BeMusic engine) |
| Social community | Facebook-style groups, reactions, real-time chat |
| Modern frontend | React 19 SPA with instant navigation, offline support |

---

## 2. Tech Stack

### Backend

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Framework | Laravel | 12 | Foundation compatibility, latest features |
| PHP | PHP | 8.3+ | Performance, typed properties, fibers |
| API Auth | Laravel Sanctum | 4.x | Token-based API authentication |
| Auth Scaffold | Laravel Fortify | 1.x | Login, register, verify, 2FA |
| OAuth | Laravel Socialite | 5.x | Google, Facebook, Apple, Twitter |
| Database | MySQL | 8+ | JSON columns, full-text indexes, CTEs |
| Cache | Redis | 7+ | Sessions, cache, queues, real-time presence |
| Search | Laravel Scout + Meilisearch | Scout 10.x | Typo-tolerant, faceted full-text search |
| Queue | Laravel Horizon (Redis) | 5.x | Job processing with monitoring dashboard |
| WebSocket | Laravel Reverb | 1.x | Native Laravel WebSocket server |
| File Storage | Flysystem | 3.x | S3, local, GCS, Dropbox, FTP, SFTP |
| Payments | Stripe, PayPal, Flutterwave, Paystack | Latest | Global + Africa coverage |
| Email | Laravel Mail | Built-in | SMTP, Mailgun, Postmark, SendGrid, SES |
| Push | OneSignal | Latest | Browser + mobile push notifications |
| SMS | Twilio | 8.x | SMS notifications and phone verification |
| PDF | DomPDF / Barryvdh | 2.x | Tax receipts, certificates, exports |
| Image | Intervention Image | 3.x | Resize, crop, optimize uploads |
| Audio Metadata | getID3 | 1.9.x | Extract sermon audio metadata |

### Frontend

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| UI Framework | React | 19.x | Concurrent rendering, Suspense |
| Language | TypeScript | 5.8+ | Strict type safety |
| Build | Vite + SWC | 6.x | Rust-speed compilation |
| Routing | React Router | 7.x | Client-side routing, lazy loading |
| Server State | TanStack React Query | 5.x | Caching, background refetch |
| Client State | Zustand + Immer | 5.x / 10.x | Lightweight stores, immutable updates |
| CSS | Tailwind CSS | 4.x | Utility-first, tree-shaken |
| UI Components | Forked BeMusic foundation | ~2,700 | Accessible (@react-aria), production-ready |
| Rich Text | Tiptap | 2.x | 10+ extensions, collaborative-ready |
| Animations | Framer Motion | 12.x | Page transitions, micro-interactions |
| Forms | React Hook Form | 7.x | Performant form validation |
| Charts | Chart.js | 4.x | Analytics dashboards |
| Virtual Scroll | @tanstack/react-virtual | 3.x | Long list performance |
| Icons | Lucide React | Latest | Consistent icon system |
| PWA | Workbox + vite-plugin-pwa | Latest | Offline support, install prompt |
| Realtime | Laravel Echo + Pusher.js | Latest | WebSocket client |
| Payments | @stripe/react-stripe-js | Latest | Stripe checkout components |

### Infrastructure

| Component | Technology |
|-----------|-----------|
| Containers | Docker + Docker Compose |
| CI/CD | GitHub Actions |
| Error Tracking | Sentry |
| Log Viewer | Opcodesio Log Viewer |
| Dev Tools | Clockwork, IDE Helper, Laravel Pail |

---

## 3. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    CHURCH COMMUNITY PLATFORM                             │
│                    White-Label Architecture                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────┐          ┌───────────────────────────────────┐ │
│  │  PUBLIC FRONTEND     │          │  ADMIN PANEL (React SPA)         │ │
│  │  (React SPA)         │          │                                   │ │
│  │                      │   API    │  - Dashboard (analytics)          │ │
│  │  - Church Profiles   │ <─────>  │  - 23 Settings Sections          │ │
│  │  - Newsfeed          │  REST    │  - Content Managers               │ │
│  │  - Groups            │  JSON    │  - Role/Permission Editor         │ │
│  │  - Events/Sermons    │          │  - Plugin Toggle (white-label)    │ │
│  │  - Chat/Prayer       │          │  - Church Builder Admin           │ │
│  │  - Giving            │          │                                   │ │
│  │  - Library           │          │  BeMusic Foundation Components    │ │
│  │  - Dark/Light Theme  │          │  (~2,700 shared React components) │ │
│  │  - PWA + Offline     │          │                                   │ │
│  └──────────┬──────────┘          └────────────────┬──────────────────┘ │
│             │                                      │                    │
│             │         ┌────────────────────┐       │                    │
│             └────────>│   SEO PRE-RENDER   │<──────┘                    │
│                       │  Laravel web routes │                           │
│                       │  /church/{slug}     │                           │
│                       │  /sermon/{slug}     │                           │
│                       │  /event/{slug}      │                           │
│                       │  /blog/{slug}       │                           │
│                       │  /library/{slug}    │                           │
│                       └─────────┬──────────┘                            │
│                                 │                                       │
│                                 v                                       │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                    LARAVEL BACKEND (API)                          │   │
│  │                                                                  │   │
│  │  ┌──────────────────────────────────────────────────────────┐    │   │
│  │  │  common/foundation/ (forked from BeMusic)                │    │   │
│  │  │  ├── Auth (users, roles, permissions, OAuth, 2FA)       │    │   │
│  │  │  ├── Billing (Stripe subscriptions, invoices)           │    │   │
│  │  │  ├── Files (multi-storage: S3/local/GCS/Dropbox)        │    │   │
│  │  │  ├── Search (Scout: Meilisearch/Algolia/TNTSearch)      │    │   │
│  │  │  ├── Comments (polymorphic comment system)              │    │   │
│  │  │  ├── Tags (polymorphic tagging)                         │    │   │
│  │  │  ├── Settings (key-value store)                         │    │   │
│  │  │  ├── Notifications (email + push)                       │    │   │
│  │  │  ├── Localizations (i18n / multi-language)              │    │   │
│  │  │  └── Pages (custom static pages)                        │    │   │
│  │  └──────────────────────────────────────────────────────────┘    │   │
│  │                                                                  │   │
│  │  ┌──────────────────────────────────────────────────────────┐    │   │
│  │  │  app/Plugins/ (church domain modules)                    │    │   │
│  │  │  ├── Timeline/    ├── Groups/      ├── Events/          │    │   │
│  │  │  ├── Sermons/     ├── Prayer/      ├── Giving/          │    │   │
│  │  │  ├── Chat/        ├── Library/     ├── ChurchBuilder/   │    │   │
│  │  │  ├── Blog/        ├── LiveMeeting/ └── ...              │    │   │
│  │  └──────────────────────────────────────────────────────────┘    │   │
│  │                                                                  │   │
│  │  Sanctum Auth │ Policy-per-Model │ 98 Permissions │ Redis Cache  │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │  INFRASTRUCTURE                                                  │   │
│  │  MySQL 8+ │ Redis 7+ │ Meilisearch │ Laravel Reverb (WebSocket) │   │
│  └──────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Foundation Core (Forked from BeMusic)

### What We Fork

The `common/foundation/` git submodule from BeMusic provides:

| Foundation Module | What It Gives Us | Church Adaptation |
|-------------------|-------------------|-------------------|
| `Common\Auth` | Users, roles, permissions, OAuth, policies, 2FA | Extended with church-specific fields, member directory |
| `Common\Billing` | Stripe subscriptions, invoices, plans | Adapted for giving/donations, fund management |
| `Common\Files` | Multi-storage file uploads (S3/local/GCS/Dropbox) | Used for sermon audio, book PDFs, images |
| `Common\Search` | Scout integration (Meilisearch/Algolia/TNTSearch) | Index sermons, events, members, posts, groups, books |
| `Common\Comments` | Polymorphic comment system | Shared across posts, prayers, sermons, articles |
| `Common\Tags` | Polymorphic tagging | Hashtags on posts, topic tags on sermons/groups |
| `Common\Settings` | Key-value settings store | All 23 admin settings sections |
| `Common\Notifications` | Email + push notification system | Multi-channel: push, email, SMS, in-app |
| `Common\Localizations` | i18n / multi-language support | Translations manager, RTL support |
| `Common\Pages` | Custom static pages | Church custom pages, about pages |
| `Common\Channels` | Content curation | Homepage widget feeds, featured content |

### What We Remove/Ignore from BeMusic

- Music-specific models (Artist, Album, Track, Playlist, Genre, Lyric, Radio)
- Spotify/Deezer/LastFM integrations
- Music licensing/Envato validation
- Backstage request system
- Music-specific waveform/import controllers

### What We Keep and Repurpose

| BeMusic Component | Church Platform Use |
|-------------------|---------------------|
| Audio player engine | Sermon player (persistent bottom bar) |
| Track plays logging | Sermon play tracking + analytics |
| Album collections | Sermon series |
| Artist profiles | Speaker profiles |
| Playlist system | Curated sermon/worship playlists |
| Loader pattern | API response formatting per plugin |
| Crupdate pattern | Create+Update per resource |
| Paginate pattern | Paginated listings with filters |
| Bootstrap data | User, settings, permissions on first load |

### Design Patterns (from BeMusic)

| Pattern | Description | Example |
|---------|-------------|---------|
| **Loader** | Format model data for API responses, handle eager-loading | `SermonLoader`, `GroupLoader`, `ChurchLoader` |
| **Crupdate** | Single class for Create + Update operations | `CrupdateSermon`, `CrupdateGroup`, `CrupdateChurch` |
| **Paginate** | Dedicated class for paginated listings with filters | `PaginateSermons`, `PaginateGroups` |
| **Delete** | Bulk/single deletion with cleanup | `DeleteSermons`, `DeleteGroups` |
| **Policy** | Per-model authorization using permissions | `SermonPolicy`, `GroupPolicy`, `ChurchPolicy` |
| **Form Request** | Per-resource validation | `ModifySermon`, `ModifyGroup` |
| **Query Builder** | Complex queries in dedicated classes | `FeedQuery`, `ChurchSearchQuery` |

---

## 5. Plugin System

### Architecture

```
config/plugins.json              ← Master toggle (white-label control)
{
  "timeline":      { "enabled": true,  "version": "1.0.0" },
  "groups":        { "enabled": true,  "version": "1.0.0" },
  "events":        { "enabled": true,  "version": "1.0.0" },
  "sermons":       { "enabled": true,  "version": "1.0.0" },
  "prayer":        { "enabled": true,  "version": "1.0.0" },
  "giving":        { "enabled": true,  "version": "1.0.0" },
  "chat":          { "enabled": true,  "version": "1.0.0" },
  "library":       { "enabled": true,  "version": "1.0.0" },
  "church_builder":{ "enabled": true,  "version": "1.0.0" },
  "blog":          { "enabled": true,  "version": "1.0.0" },
  "live_meeting":  { "enabled": true,  "version": "1.0.0" },
  "volunteers":    { "enabled": false, "version": "1.0.0" },
  "fundraising":   { "enabled": false, "version": "1.0.0" },
  "stories":       { "enabled": false, "version": "1.0.0" },
  "pastoral":      { "enabled": false, "version": "1.0.0" }
}
```

### Plugin Directory Structure

Each plugin follows the same structure (BeMusic service patterns):

```
app/Plugins/{PluginName}/
├── Models/                    ← Eloquent models
│   └── {Name}.php
├── Services/                  ← Business logic
│   ├── {Name}Loader.php       ← API response formatting + eager loading
│   ├── Crupdate{Name}.php     ← Create-or-Update (single class)
│   ├── Paginate{Names}.php    ← Paginated listing with filters
│   └── Delete{Names}.php      ← Bulk/single deletion with cleanup
├── Controllers/               ← API controllers
│   └── {Name}Controller.php
├── Policies/                  ← Model authorization
│   └── {Name}Policy.php
├── Requests/                  ← Form validation
│   └── Modify{Name}.php
├── Routes/                    ← Plugin API routes
│   └── api.php
├── Database/
│   ├── Migrations/
│   └── Seeders/
└── resources/client/          ← React components (lazy-loaded)
    ├── pages/
    ├── components/
    └── queries.ts             ← TanStack Query definitions
```

### Plugin Manager Service

```php
// app/Core/PluginManager.php
class PluginManager
{
    public function isEnabled(string $plugin): bool;
    public function getEnabled(): array;
    public function enable(string $plugin): void;
    public function disable(string $plugin): void;
    public function getRoutes(): array;        // Collect routes from enabled plugins
    public function getPermissions(): array;   // Collect permissions from enabled plugins
    public function getMigrations(): array;    // Collect migrations from enabled plugins
}
```

---

## 6. Authorization System

### Database Schema

```sql
-- Roles table
CREATE TABLE roles (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) UNIQUE NOT NULL,
    description     TEXT NULL,
    type            ENUM('system', 'church', 'custom') DEFAULT 'custom',
    level           INT UNSIGNED DEFAULT 10,
    is_default      BOOLEAN DEFAULT FALSE,
    church_id       BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);

-- Permissions table (each permission is a DB record, grouped by plugin)
CREATE TABLE permissions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(255) UNIQUE NOT NULL,
    display_name    VARCHAR(255) NOT NULL,
    description     TEXT NULL,
    `group`         VARCHAR(100) NOT NULL,
    type            ENUM('global', 'church') DEFAULT 'global',
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);

-- Role <-> Permission pivot
CREATE TABLE permission_role (
    role_id         BIGINT UNSIGNED NOT NULL,
    permission_id   BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- User <-> Role pivot (multiple roles per user)
CREATE TABLE user_role (
    user_id         BIGINT UNSIGNED NOT NULL,
    role_id         BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Direct user permission overrides (grant or deny)
CREATE TABLE permission_user (
    user_id         BIGINT UNSIGNED NOT NULL,
    permission_id   BIGINT UNSIGNED NOT NULL,
    granted         BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

### Permission Resolution Order

```
1. Direct user DENY  (permission_user.granted = false)  → DENY (highest priority)
2. Direct user GRANT (permission_user.granted = true)   → GRANT
3. Role permissions  (permission_role pivot)             → GRANT if any role has it
4. Default                                               → DENY
```

Resolved permissions are cached per-user for 5 minutes (Redis). Cache is cleared on role change or direct permission change.

### Default Roles (8 Preset)

| Role | Level | Type | Key Permissions |
|------|-------|------|-----------------|
| **Super Admin** | 100 | system | ALL 98 permissions |
| **Platform Admin** | 80 | system | All except system settings, impersonation, gateway config |
| **Church Admin** | 60 | system/church | All content CRUD, church management, giving reports, notifications |
| **Pastor / Elder** | 50 | system | Church Admin + pastoral care, prayer flagging, chat moderation, user banning |
| **Moderator** | 40 | system | Content moderation, comment moderation, post pinning/deletion |
| **Ministry Leader** | 30 | system | Create groups/events, manage volunteers, write blog, create meetings |
| **Member** | 20 | system (default) | View/create posts, join groups, RSVP events, give, chat, read library, submit prayers |
| **Guest** | 10 | system | Read-only public content (posts, events, sermons, churches, blog, library catalog) |

### Policy Pattern

Every plugin model has a policy. All policies follow the same structure:

```php
class {Name}Policy
{
    // Super admin bypass
    public function before(User $user, string $ability): ?bool;

    // Standard CRUD
    public function index(User $user): bool;                    // {name}.view
    public function show(?User $user, Model $model): bool;      // {name}.view + visibility
    public function store(User $user): bool;                    // {name}.create
    public function update(User $user, Model $model): bool;     // {name}.update (own) or {name}.update_any
    public function destroy(User $user, Model $model): bool;    // {name}.delete (own) or {name}.delete_any

    // Plugin-specific abilities
    public function {action}(User $user, ...): bool;            // {name}.{action}
}
```

The `own` vs `any` pattern: the policy checks if the user owns the resource. If yes, check `{name}.update`. If no, check `{name}.update_any`. This single pattern handles 90% of authorization.

### Policies Per Plugin

| Plugin | Policy | Protects |
|--------|--------|----------|
| Timeline | PostPolicy, CommentPolicy | Post CRUD, moderation, pinning, scheduling |
| Groups | GroupPolicy | Group CRUD, membership, moderation |
| Events | EventPolicy | Event CRUD, RSVP management |
| Sermons | SermonPolicy | Sermon CRUD, series, speakers |
| Prayer | PrayerPolicy | Prayer CRUD, moderation, pastoral flagging |
| Giving | DonationPolicy | Donation access, fund management, reports |
| Chat | ConversationPolicy | Chat creation, moderation |
| Library | BookPolicy | Book CRUD, download permissions |
| Church Builder | ChurchPolicy | Church CRUD, verification, member management |
| Blog | ArticlePolicy | Article CRUD, publishing, categories |
| LiveMeeting | MeetingPolicy | Meeting link CRUD |

### Gate Registration (AppServiceProvider)

All policies are registered via `Gate::policy()` in `AppServiceProvider::boot()`. Morph map enforced for all polymorphic relations.

### Frontend Permission Integration

Bootstrap data includes the user's resolved permissions as a flat object:

```typescript
// Hydrated on first page load
{
  user: {
    id: 1,
    name: "John",
    permissions: {
      "posts.view": true,
      "posts.create": true,
      "posts.update": true,
      "sermons.view": true,
      "giving.donate": true,
      // ... all resolved permissions
    },
    role_level: 20
  }
}
```

React hook `useUserPermissions()` provides `hasPermission()`, `can()`, `canAny()`, and `isAdmin` for conditional UI rendering.

---

## 7. Admin Settings Panel (23 Sections)

All settings use the `Common\Settings` key-value store pattern: flat key-value pairs in a `settings` table (e.g., `key: "player.default_volume"`, `value: "80"`). Adding a new settings section = new React form component + new keys. No migrations needed.

### Settings Sections

| # | Section | Key Settings |
|---|---------|-------------|
| 1 | **General** | Church name, tagline, description, logo, favicon, address, phone, email, timezone, service times, pastor name/photo, mission, vision, denomination, founding year |
| 2 | **Automation** | Auto-approve testimonies, auto-publish prayers, auto-welcome members, scheduled verse of the day, auto-archive past events, auto-generate sitemap |
| 3 | **Media Player** | Persistent sermon player, worship music player, podcast settings, audio quality (128k/256k/320k), autoplay next, default volume |
| 4 | **Landing Page** | Homepage widget builder (drag-drop), hero banner, CTA buttons, featured sections toggle, welcome video, service times widget |
| 5 | **Local Search** | Meilisearch/TNTSearch/Algolia config, searchable content types, search result weights per content type |
| 6 | **Themes** | Color scheme (primary, secondary, accent), dark/light default, custom CSS, font selection, border radius, layout density, church presets (Modern, Traditional, Minimalist) |
| 7 | **Menus** | Header nav builder, footer nav builder, mobile bottom nav, sidebar menu, per-role visibility, icon selection per item |
| 8 | **Localization** | Multi-language, RTL, translation manager, default language, Bible translation preference (KJV/NIV/ESV) |
| 9 | **Authentication** | OAuth providers (Google, Facebook, Apple), 2FA toggle, email verification required, phone verification (Twilio), registration mode (open/invite/approval), custom registration fields |
| 10 | **Uploading** | Storage driver (local/S3/GCS/Dropbox), max file sizes, allowed types, image optimization, sermon audio limits, CDN URL |
| 11 | **Email** | Mail driver (SMTP/Mailgun/Postmark/SendGrid/SES), from address, welcome template, event reminder template, prayer notification template, newsletter settings |
| 12 | **System** | PHP/MySQL info, cache clear, queue status, Horizon link, storage usage, update checker, license key, backup/restore, debug mode |
| 13 | **Analytics** | Google Analytics 4 ID, internal analytics toggle (page views, sermon plays, member growth, giving trends), data retention |
| 14 | **Custom Code** | Custom CSS, custom JS (head), custom JS (body), custom meta tags |
| 15 | **Captcha** | reCAPTCHA v2/v3, hCaptcha, Turnstile, applied to: registration, contact, prayer, giving forms |
| 16 | **GDPR** | Cookie consent banner, privacy policy link, data export, data deletion request, consent checkboxes, cookie categories |
| 17 | **SEO** | Default meta title/description, Open Graph defaults, Twitter card config, JSON-LD (Church schema.org), sitemap, canonical URLs, robots.txt editor |
| 18 | **Ads** | Internal promotion banners, Google AdSense zones, ad placement positions (header, sidebar, feed), ad scheduling |
| 19 | **Giving** | Payment gateways (Stripe/PayPal/Flutterwave/Paystack), currency, giving categories, recurring toggle, tax receipt settings, giving goal display |
| 20 | **Modules** | Enable/disable plugins: Timeline, Groups, Events, Sermons, Prayer, Chat, Library, Church Builder, Blog, LiveMeeting, etc. (white-label toggle) |
| 21 | **Notifications** | OneSignal keys, Twilio credentials, notification channels per event type matrix |
| 22 | **Live Meetings** | Zoom API credentials, Google Meet integration, default platform, auto-create links |
| 23 | **Member Fields** | Custom profile fields: phone, ministry role, baptism date, membership date, spiritual gifts, small group assignment + unlimited custom fields |

---

## 8. Plugin Inventory (12 Modules)

### Phase 1 Plugins (Core Church Platform)

#### Timeline Plugin

| Aspect | Detail |
|--------|--------|
| Models | Post, PostMedia, Reaction, Comment |
| Post types | Text, photo, video, announcement |
| Reactions | Polymorphic: like, pray, amen, love, celebrate |
| Comments | Nested, threaded, polymorphic (shared with sermons, prayers, articles) |
| Features | Infinite scroll, virtual list, post composer, media upload, visibility controls, pinning, scheduling |
| Sngine features adopted | Newsfeed, reactions, sharing, saving, hiding, post moderation |
| Admin | Post moderation queue, announcement manager |

#### Groups Plugin (Facebook-style)

| Aspect | Detail |
|--------|--------|
| Models | Group, GroupMember, GroupPost |
| Privacy | Public, private, secret |
| Membership | Join/leave, request approval, invite |
| Roles | Admin, moderator, member |
| Features | Group feed (scoped posts), pinned posts, group events (linked to Events plugin), discussion threads (pinnable, lockable), member management, group discovery/search |
| Sngine features adopted | Group CRUD, membership, admin/mod roles, group-specific feeds, group rules |
| Admin | Group moderation, featured groups |

#### Events Plugin

| Aspect | Detail |
|--------|--------|
| Models | Event, EventAttendee |
| Features | RSVP (attending/interested/not going + headcount), recurring events, calendar view (month/week/list), .ics export, Zoom/Meet link field + "Join Live" button, location with map |
| Sngine features adopted | Event CRUD, RSVP system, event invitations |
| Admin | Event manager, RSVP export |

#### Sermons Plugin

| Aspect | Detail |
|--------|--------|
| Models | Sermon, SermonSeries, Speaker |
| BeMusic mapping | Track → Sermon, Album → Series, Artist → Speaker |
| Features | Persistent audio player (bottom bar), sermon series collections, speaker profiles, scripture references, play tracking/analytics, search by speaker/scripture/topic/date, download option |
| Admin | Sermon uploader, series builder, speaker manager |

#### Giving Plugin

| Aspect | Detail |
|--------|--------|
| Models | Donation, Fund, RecurringGift, TaxReceipt |
| Gateways | Stripe, PayPal, Flutterwave, Paystack |
| Features | Multi-fund giving (tithe, offering, missions, building, custom), recurring donations (Stripe subscriptions), giving history dashboard, tax receipt PDF generation, giving goals + progress display, webhook handlers |
| Admin | Donation reports, fund management, CSV export, gateway config |

#### Church Builder Plugin

| Aspect | Detail |
|--------|--------|
| Models | Church, ChurchMember, ChurchPost, ChurchPage |
| Layout | Facebook Page style: cover, logo, name, follow, tabs (Timeline, About, Events, Sermons, Members, Pages) |
| Features | Church directory (list + map view, geo-search), church profile page, church-scoped feed, membership (join/leave, roles, approval), custom static pages per church, church-specific theming (colors via wrapper), per-church custom domain mapping (optional) |
| SEO | Pre-rendered `/church/{slug}`, schema.org Church structured data, Open Graph, auto-sitemap, local SEO (city + denomination keywords) |
| Sngine features adopted | Page system, followers, page-specific feed, page admin tools |
| Admin | Church directory management, verification badges, featured churches |

### Phase 2 Plugins (Build the Community)

#### Chat Plugin

| Aspect | Detail |
|--------|--------|
| Models | Conversation, Message, ConversationUser |
| Technology | Laravel Reverb (WebSocket) |
| Features | 1-on-1 and group chats, real-time delivery, read receipts, typing indicators, media sharing (images, files, voice notes), online/offline presence, unread count badges |
| Sngine features adopted | Direct messaging, group chats, message status, real-time delivery |
| Admin | Chat moderation tools |

#### Prayer Plugin

| Aspect | Detail |
|--------|--------|
| Models | PrayerRequest, PrayerUpdate |
| Features | Prayer wall (filterable feed), anonymous option, "I Prayed" counter (polymorphic reaction), prayer updates (progress notes), categories (health, family, financial, spiritual), pastoral flag |
| Admin | Prayer moderation, pastoral dashboard |

#### Library Plugin

| Aspect | Detail |
|--------|--------|
| Models | Book, BookCategory |
| Features | PDF.js reader (page-flip animation, zoom, fullscreen, page navigation), book catalog (grid/list view), hierarchical category tree, search, download permissions (role-based) |
| SEO | Pre-rendered `/library/{slug}` with Book schema.org structured data |
| Admin | Book CRUD, bulk upload, category management, download stats |

#### Notifications Plugin (cross-cutting)

| Aspect | Detail |
|--------|--------|
| Channels | Push (OneSignal), Email (Laravel Mail), SMS (Twilio), In-app (real-time) |
| Features | Per-member channel preferences, notification center (dropdown + page), event-driven notifications matrix |
| Triggers | New sermon → push, prayer update → push+email, event reminder (24h, 1h) → push+SMS, new group post → push, chat message → push (if offline), new member → admin email |
| Admin | Notification templates, delivery logs, channel config |

#### LiveMeeting Plugin

| Aspect | Detail |
|--------|--------|
| Models | Meeting (extends/linked to Event) |
| Features | Zoom API integration (auto-create meeting), Google Meet link generation, "Live Now" badge on active meetings, meeting schedule, auto-reminders |
| Admin | Meeting platform config, upcoming meetings dashboard |

### Phase 3 Plugins (Grow & Monetize)

#### Blog Plugin

| Models | Article, Category, Tag |
| Features | Rich text editor (Tiptap), categories, tags, featured images, social sharing, view tracking |
| SEO | Pre-rendered `/blog/{slug}`, JSON-LD Article structured data |

#### Volunteers Plugin

| Models | VolunteerSlot, VolunteerSignup, CheckIn |
| Features | Opportunity listings, sign-up/scheduling, QR code check-in, attendance tracking, kids ministry check-in with parent notification |

#### Fundraising Plugin

| Models | Campaign, CampaignDonation |
| Features | Campaign goals + progress bar, donor management, campaign updates, thank-you emails |

#### Stories Plugin

| Models | Story |
| Features | 24-hour ephemeral content (daily devotional, verse, prayer), photo/video stories, viewer list |

#### Pastoral Care Plugin

| Models | PastoralRequest |
| Features | Confidential care requests, routed to pastors, counseling notes (encrypted), member milestones (baptism, anniversary alerts) |

---

## 9. Database Schema

### Core Tables (Foundation)

```sql
users                    -- Extended with church_id, custom_fields, banned_at, 2FA
roles                    -- name, slug, type, level, is_default, church_id
permissions              -- name, display_name, group, type
permission_role          -- pivot
user_role                -- pivot (multiple roles per user)
permission_user          -- direct overrides (grant/deny)
social_profiles          -- OAuth provider connections
settings                 -- key-value store (all admin settings)
file_entries             -- file storage metadata (all uploads)
personal_access_tokens   -- Sanctum API tokens
```

### Plugin Tables

```sql
-- Timeline
posts                    -- user_id, church_id, type, content, visibility, pinned, scheduled_at
posts_media              -- post_id, file_entry_id, type, order
reactions                -- user_id, reactable_id, reactable_type, type (polymorphic)
comments                 -- user_id, commentable_id, commentable_type, body, parent_id (polymorphic, nested)

-- Groups
groups                   -- name, slug, description, cover, privacy, church_id, creator_id
groups_members           -- group_id, user_id, role, status, joined_at

-- Events
events                   -- title, slug, description, start, end, location, lat, lng, cover, church_id, meeting_url, is_recurring, recurrence_rule
events_attendees         -- event_id, user_id, status (attending/interested/declined)

-- Sermons
sermons                  -- title, slug, description, audio_path, video_url, duration, speaker_id, series_id, scripture_ref, plays, church_id
sermon_series            -- title, slug, description, cover, church_id
speakers                 -- name, slug, bio, photo, church_id

-- Prayer
prayer_requests          -- user_id, title, description, is_anonymous, category, status, church_id, pastoral_flag
prayer_updates           -- prayer_request_id, user_id, content

-- Giving
donations                -- user_id, fund_id, amount, currency, gateway, transaction_id, status, church_id
funds                    -- name, description, goal_amount, church_id, is_active
recurring_gifts          -- user_id, fund_id, amount, gateway, subscription_id, interval, status
tax_receipts             -- user_id, year, amount, pdf_path, church_id

-- Chat
conversations            -- type (direct/group), name, created_by
conversations_users      -- conversation_id, user_id, last_read_at, is_muted
messages                 -- conversation_id, user_id, body, type (text/image/file/audio), read_at

-- Library
books                    -- title, slug, author, description, cover, pdf_path, category_id, pages_count, downloads, church_id
book_categories          -- name, slug, parent_id

-- Church Builder
churches                 -- name, slug, description, logo, cover, address, city, state, country, lat, lng, phone, email, website, denomination, pastor_name, pastor_photo, service_times (JSON), social_links (JSON), theme_config (JSON), is_verified, is_featured, seo_title, seo_description, admin_user_id
church_members           -- church_id, user_id, role, status, joined_at
church_posts             -- church_id, post_id
church_pages             -- church_id, title, slug, content, order

-- Blog
articles                 -- title, slug, content, excerpt, cover, user_id, category_id, status (draft/published), published_at, views, church_id
categories               -- name, slug, parent_id (shared with books)
taggables                -- tag_id, taggable_id, taggable_type

-- Notifications
notifications            -- id (UUID), type, notifiable_id, notifiable_type, data (JSON), read_at
notification_preferences -- user_id, type, channels (JSON: {push: true, email: true, sms: false})

-- Live Meetings
meetings                 -- event_id, platform (zoom/google_meet), meeting_url, meeting_id, password, is_live, started_at, ended_at

-- Phase 3
volunteer_slots          -- title, event_id, ministry_id, max_volunteers, start, end
volunteer_signups        -- slot_id, user_id, status
check_ins               -- user_id, church_id, event_id, checked_in_at, qr_code
campaigns               -- title, slug, description, goal_amount, raised_amount, church_id, status, end_date
campaign_donations      -- campaign_id, user_id, amount, gateway, transaction_id
stories                 -- user_id, church_id, type (photo/video), media_path, expires_at
pastoral_requests       -- user_id, church_id, type, description, status, assigned_to, is_confidential
```

### Model Count

| Phase | New Models | Running Total |
|-------|-----------|---------------|
| Phase 0 (Foundation) | User, Role, Permission, Setting, FileEntry, SocialProfile, Menu, Page | **8** |
| Phase 1 (Core) | Post, PostMedia, Reaction, Comment, Group, GroupMember, Event, EventAttendee, Sermon, SermonSeries, Speaker, Donation, Fund, RecurringGift, TaxReceipt, Church, ChurchMember, ChurchPost, ChurchPage | +19 = **27** |
| Phase 2 (Community) | Conversation, Message, ConversationUser, PrayerRequest, PrayerUpdate, Book, BookCategory, Notification, NotificationPreference, Meeting | +10 = **37** |
| Phase 3 (Grow) | Article, Category, Tag, VolunteerSlot, VolunteerSignup, CheckIn, Campaign, CampaignDonation, Story, PastoralRequest | +10 = **47** |

---

## 10. SEO Strategy

### BeMusic SEO Pattern (Selective Server-Side Pre-Rendering)

Public/crawlable routes are pre-rendered by Laravel web routes with full HTML + meta tags. Everything else is SPA (React handles client-side).

### Pre-Rendered Routes

| Route | Schema.org Type | Meta Tags |
|-------|----------------|-----------|
| `/church/{slug}` | Church | Name, address, geo, service times, denomination, og:image (logo) |
| `/sermon/{slug}` | AudioObject | Title, speaker, duration, scripture, series, og:audio |
| `/event/{slug}` | Event | Title, date, location, geo, og:image (cover) |
| `/blog/{slug}` | Article | Title, author, date, excerpt, og:image (cover) |
| `/library/{slug}` | Book | Title, author, description, og:image (cover) |
| `/group/{slug}` | Organization | Name, description, member count |
| `/` | WebSite | Site name, description, search action |

### SEO Features

- Auto-generated sitemap (per content type + per church)
- Canonical URLs (prevent duplicates between platform and church domains)
- JSON-LD structured data on all pre-rendered pages
- Open Graph + Twitter Card meta tags
- robots.txt editor in admin
- Custom meta title/description per page
- Alt text enforcement on image uploads
- Breadcrumb structured data

---

## 11. Frontend Architecture

### Directory Structure

```
resources/client/
├── main.tsx                     # Entry: React root + Sentry + error boundary
├── app-router.tsx               # Route composition (lazy-loaded plugin modules)
├── app-queries.ts               # Centralized TanStack Query definitions
├── app.css                      # Global styles
├── site-config.tsx              # Site configuration context
├── sw.ts                        # Service worker (Workbox + offline)
│
├── common/                      # Shared app-level components
│   ├── auth/
│   │   ├── use-permissions.ts   # Permission hook (hasPermission, can, canAny)
│   │   └── auth-guards.tsx      # Route guards (RequireAuth, RequirePermission)
│   ├── layout/
│   │   ├── AppLayout.tsx        # Main app shell (nav, sidebar, content)
│   │   ├── ChurchLayout.tsx     # Church profile layout (cover, tabs)
│   │   └── AdminLayout.tsx      # Admin panel layout (sidebar, content)
│   └── components/
│       ├── ReactionBar.tsx      # Shared reaction component
│       ├── CommentThread.tsx    # Shared nested comments
│       ├── MediaUploader.tsx    # Shared file uploader
│       └── InfiniteList.tsx     # Virtual scroll wrapper
│
├── plugins/                     # Lazy-loaded plugin frontends
│   ├── timeline/
│   │   ├── pages/
│   │   │   └── NewsfeedPage.tsx
│   │   ├── components/
│   │   │   ├── PostComposer.tsx
│   │   │   ├── PostCard.tsx
│   │   │   └── PostFeed.tsx
│   │   └── queries.ts
│   ├── groups/
│   ├── events/
│   ├── sermons/
│   │   ├── pages/
│   │   │   ├── SermonArchive.tsx
│   │   │   └── SermonPlayer.tsx
│   │   └── components/
│   │       ├── SermonCard.tsx
│   │       └── PersistentPlayer.tsx  # Bottom bar (BeMusic player)
│   ├── prayer/
│   ├── giving/
│   ├── chat/
│   ├── library/
│   │   └── components/
│   │       └── PdfViewer.tsx     # PDF.js reader
│   ├── church-builder/
│   ├── blog/
│   └── live-meeting/
│
├── admin/                       # Admin dashboard
│   ├── settings/                # 23 settings section components
│   │   ├── GeneralSettings.tsx
│   │   ├── AuthSettings.tsx
│   │   ├── ThemeSettings.tsx
│   │   ├── ModuleSettings.tsx   # Plugin toggle (white-label)
│   │   └── ...
│   ├── roles/
│   │   └── RolePermissionEditor.tsx  # Checkbox matrix
│   └── plugins/                 # Per-plugin admin managers
│       ├── SermonManager.tsx
│       ├── EventManager.tsx
│       └── ...
│
└── landing/                     # Public landing page (pre-auth)
    └── LandingPage.tsx

common/foundation/resources/client/    # Forked BeMusic shared components
├── ui/library/                  # ~2,700 components
│   ├── buttons/
│   ├── forms/
│   ├── inputs/
│   ├── overlays/ (dialogs, modals)
│   ├── navigation/ (breadcrumbs, menus, tabs)
│   ├── tables/ (datatables)
│   ├── toasts/
│   ├── badges/
│   ├── icons/ (Material design)
│   └── layout/ (dashboard, sidebars)
├── http/                        # Axios client, query-client
├── player/                      # Audio player engine (→ sermon player)
├── auth/                        # Auth guards & utilities
├── core/                        # CommonProvider, context providers
├── billing/                     # Stripe checkout components
└── admin/                       # Admin utilities (datatables, filters)
```

### State Management (3-Layer — BeMusic Pattern)

| Layer | Tool | Purpose | Examples |
|-------|------|---------|----------|
| Server State | TanStack React Query | Remote data (API) | Posts, events, sermons, members, search results |
| Client State | Zustand + Immer | Local UI state | Player state, chat UI, notification badges, reaction cache |
| Bootstrap Data | Custom store | Server-rendered initial payload | User info, permissions, settings, enabled plugins |

### TypeScript Path Aliases

```json
{
  "@ui/*":     "common/foundation/resources/client/ui/library/*",
  "@common/*": "common/foundation/resources/client/*",
  "@app/*":    "resources/client/*"
}
```

---

## 12. Phased Roadmap

### Phase 0 — Foundation Setup (Weeks 1-3)

**Goal:** Bootable platform with auth, settings, admin shell. No church features yet.

**Week 1: Project Scaffold**
- Fork BeMusic `common/foundation` and adapt namespaces
- Fresh Laravel 12 project with foundation wired in
- MySQL + Redis + Meilisearch Docker Compose
- Vite 6 + React 19 + TypeScript + Tailwind 4 setup
- Path aliases (@ui, @common, @app)
- TanStack Query + Zustand + Immer configured
- CI/CD pipeline (GitHub Actions: lint, test, build)

**Week 2: Auth & Admin Shell**
- Sanctum + Fortify auth (login, register, verify, 2FA)
- Socialite OAuth (Google, Facebook)
- Role-permission system (permissions table, permission_role pivot, user_role pivot, permission_user overrides)
- 8 default roles seeded with 98 permissions
- Policy-per-model pattern established
- Admin layout (sidebar + content area from foundation components)
- Admin dashboard skeleton (stat cards, quick actions)
- Role/Permission editor (checkbox matrix UI)
- Bootstrap data hydration (user, settings, permissions)

**Week 3: Settings Engine & White-Label Core**
- Settings key-value store (Common\Settings)
- All 23 settings sections (React forms → API → settings table)
- Plugin registry (config/plugins.json + PluginManager service)
- Theme engine (CSS variables, dark/light, custom colors)
- Menu builder (header, footer, mobile bottom nav)
- File upload system (Common\Files — local + S3)
- SEO pre-rendering for public routes
- Landing page builder (configurable hero, sections, CTA)

**Deliverable:** Working admin panel with settings, auth, theming. White-label ready (change name/logo/colors/domain).

---

### Phase 1 — "Open the Doors" (Weeks 4-8)

**Goal:** Core church platform. Members join, post, attend, give.

**Week 4-5: Timeline + Member Profiles**
- Post model (text, photo, video, announcement types)
- Reaction system (polymorphic: like, pray, amen, love, celebrate)
- Nested comment system (polymorphic, from Common\Comments)
- Newsfeed page (infinite scroll, virtual list)
- Post composer (rich text, media upload, visibility controls)
- Member profile pages (avatar, bio, ministry roles, custom fields)
- Member directory (searchable, privacy controls)
- PostPolicy + CommentPolicy
- Admin: Post moderation, member management

**Week 5-6: Groups (Facebook-style)**
- Group model (name, cover, description, rules, privacy: public/private/secret)
- Membership (join/leave, request approval, admin/mod roles)
- Group feed (group-scoped posts, pinned posts)
- Group events (linked to Events plugin)
- Group discussions (threaded, pinnable, lockable)
- Group member management (invite, remove, promote)
- Group discovery page (browse, search, suggested)
- GroupPolicy
- Admin: Group moderation, featured groups

**Week 6-7: Events + Sermons**
- Event CRUD (date, time, location, cover, description)
- RSVP system (attending/interested/not going + headcount)
- Recurring events (weekly service, monthly meeting)
- Calendar view (month/week/list) + .ics export
- Zoom/Meet link field + "Join Live" button
- Sermon model (audio, video, speaker, series, scripture)
- Sermon audio player (persistent bottom bar — BeMusic engine)
- Sermon series (album-like collections)
- Speaker profiles (adapted from BeMusic Artist)
- Sermon search (by speaker, scripture, topic, date)
- EventPolicy + SermonPolicy
- Admin: Event manager, Sermon uploader, Series builder

**Week 7-8: Giving + Church Builder**
- Payment gateway integration (Stripe, PayPal, Flutterwave, Paystack)
- Giving page (fund selection, amount, recurring toggle)
- Fund management (tithe, offering, missions, building, custom)
- Recurring donations (Stripe subscriptions)
- Giving history (member dashboard) + tax receipt PDF
- Church model with full profile fields
- Church directory (list + map view, geo-search)
- Church profile page (Facebook Page layout with tabs)
- Church-scoped feed, membership, custom pages
- Church-specific theming + SEO pre-rendering
- DonationPolicy + ChurchPolicy
- Admin: Donation reports, Church directory management, verification badges
- Webhook handlers (Stripe, PayPal, Flutterwave, Paystack)

**Deliverable:** Fully functional church platform. Members sign up, post, join groups, attend events, listen to sermons, give online. Churches have branded profile pages.

---

### Phase 2 — "Build the Community" (Weeks 9-14)

**Goal:** Real-time engagement. Chat, prayer, library, live services.

**Week 9-10: Real-Time Chat**
- Laravel Reverb WebSocket server setup
- Conversation model (1-on-1, group chats)
- Message model (text, images, files, audio voice notes)
- Real-time delivery (WebSocket broadcast)
- Read receipts + typing indicators
- Chat UI (sidebar conversation list, message thread)
- Online/offline presence indicators
- Notification badges (unread count)
- ConversationPolicy
- Admin: Chat moderation tools

**Week 10-11: Prayer Wall**
- Prayer request CRUD (title, description, anonymous toggle)
- "I Prayed" counter (polymorphic reaction)
- Prayer updates (progress notes)
- Prayer categories (health, family, financial, spiritual)
- Pastoral flag (mark as needing pastoral attention)
- Prayer wall page (filterable feed)
- PrayerPolicy
- Admin: Prayer moderation, pastoral dashboard

**Week 11-12: Library**
- Book model with PDF upload, auto-slug, categories
- Book catalog page (grid/list, category sidebar, search)
- PDF.js reader (page-flip, zoom, fullscreen, page navigation)
- Hierarchical book categories
- Download permissions (role-based via `library.download` permission)
- Book SEO (`/library/{slug}` pre-rendered, Book schema.org)
- BookPolicy
- Admin: Book manager, bulk upload, category tree, download stats

**Week 12-13: Notifications + Live Meetings**
- Multi-channel notifications (push, email, SMS, in-app)
- OneSignal push + Twilio SMS integration
- Notification preferences (per-member, per-channel toggle)
- Notification center (dropdown + full page)
- Event-driven notification matrix
- Zoom API integration (auto-create meeting for events)
- Google Meet link generation
- "Live Now" badge on active meetings
- MeetingPolicy
- Admin: Notification templates, delivery logs

**Week 13-14: Polish & Integration Testing**
- Cross-plugin integration (group events, group prayers, church sermons)
- Performance optimization (query N+1, cache layers, eager loading)
- Mobile responsive audit (all pages)
- PWA manifest + service worker (offline sermon playback)
- Load testing (concurrent users, WebSocket connections)
- Security audit (OWASP top 10)

**Deliverable:** Full community platform with real-time engagement. Chat, pray, read, join live services.

---

### Phase 3 — "Grow & Monetize" (Weeks 15-20)

**Goal:** Revenue features + advanced engagement.

**Week 15-16: Blog + Fundraising**
- Blog CMS (articles, categories, tags, rich text)
- Blog SEO (pre-rendered routes, JSON-LD Article)
- Fundraising campaigns (goal, progress bar, donors)
- Campaign updates, donor management, thank-you emails
- ArticlePolicy
- Admin: Blog editor, campaign manager

**Week 17-18: Volunteer + Check-In**
- Volunteer opportunity listings
- Sign-up / scheduling (per-event, recurring)
- QR code service check-in (generate + scan)
- Attendance tracking + reports
- Kids ministry check-in (parent notification)
- Admin: Volunteer dashboard, attendance reports

**Week 19-20: Stories + Video + Pastoral Care**
- Stories (24-hour ephemeral: daily devotional, verse, prayer)
- Short-form video (testimony clips, worship highlights)
- Pastoral care requests (confidential, routed to pastors)
- Counseling notes (encrypted, pastor-only access)
- Member milestones (baptism, anniversary alerts)
- Admin: Pastoral dashboard, care request queue

**Deliverable:** Complete white-label church community platform. Ready for sales/licensing/deployment.

---

## Appendix: Permission Registry

### 17 Groups, 98 Permissions

#### Core (always present)

| Group | Permission | Display Name |
|-------|-----------|-------------|
| admin | admin.access | Access Admin Panel |
| admin | admin.dashboard | View Dashboard Analytics |
| users | users.view | View User Profiles |
| users | users.create | Create Users |
| users | users.update | Edit Users |
| users | users.delete | Delete Users |
| users | users.impersonate | Login As User |
| users | users.ban | Ban/Unban Users |
| users | users.export | Export User Data |
| roles | roles.view | View Roles |
| roles | roles.create | Create Roles |
| roles | roles.update | Edit Roles |
| roles | roles.delete | Delete Roles |
| roles | roles.assign | Assign Roles to Users |
| settings | settings.view | View Settings |
| settings | settings.update | Update Settings |
| files | files.upload | Upload Files |
| files | files.delete | Delete Any File |
| files | files.manage | Manage File Storage |
| appearance | appearance.themes | Manage Themes |
| appearance | appearance.menus | Manage Navigation Menus |
| appearance | appearance.custom_code | Edit Custom CSS/JS |
| localizations | localizations.view | View Translations |
| localizations | localizations.update | Edit Translations |
| seo | seo.manage | Manage SEO Settings |
| seo | seo.sitemap | Generate Sitemap |

#### Plugin: Timeline

| Group | Permission | Display Name |
|-------|-----------|-------------|
| posts | posts.view | View Posts |
| posts | posts.create | Create Posts |
| posts | posts.update | Edit Own Posts |
| posts | posts.update_any | Edit Any Post |
| posts | posts.delete | Delete Own Posts |
| posts | posts.delete_any | Delete Any Post |
| posts | posts.pin | Pin Posts |
| posts | posts.schedule | Schedule Posts |
| posts | posts.moderate | Moderate Posts |
| posts | posts.announce | Create Announcements |
| comments | comments.create | Post Comments |
| comments | comments.update | Edit Own Comments |
| comments | comments.delete_any | Delete Any Comment |
| comments | comments.moderate | Moderate Comments |
| reactions | reactions.create | React to Content |

#### Plugin: Groups

| Group | Permission | Display Name |
|-------|-----------|-------------|
| groups | groups.view | View Groups |
| groups | groups.create | Create Groups |
| groups | groups.update | Edit Own Groups |
| groups | groups.update_any | Edit Any Group |
| groups | groups.delete | Delete Own Groups |
| groups | groups.delete_any | Delete Any Group |
| groups | groups.join | Join Groups |
| groups | groups.moderate | Moderate Any Group |
| groups | groups.feature | Feature/Unfeature Groups |

#### Plugin: Events

| Group | Permission | Display Name |
|-------|-----------|-------------|
| events | events.view | View Events |
| events | events.create | Create Events |
| events | events.update | Edit Own Events |
| events | events.update_any | Edit Any Event |
| events | events.delete | Delete Own Events |
| events | events.delete_any | Delete Any Event |
| events | events.rsvp | RSVP to Events |
| events | events.manage_rsvp | View/Export RSVP Lists |

#### Plugin: Sermons

| Group | Permission | Display Name |
|-------|-----------|-------------|
| sermons | sermons.view | View Sermons |
| sermons | sermons.create | Upload Sermons |
| sermons | sermons.update | Edit Sermons |
| sermons | sermons.delete | Delete Sermons |
| sermons | sermons.manage_series | Manage Sermon Series |
| sermons | sermons.manage_speakers | Manage Speakers |
| sermons | sermons.download | Download Sermon Audio |

#### Plugin: Prayer

| Group | Permission | Display Name |
|-------|-----------|-------------|
| prayers | prayers.view | View Prayer Wall |
| prayers | prayers.create | Submit Prayer Requests |
| prayers | prayers.update | Edit Own Prayers |
| prayers | prayers.delete_any | Delete Any Prayer |
| prayers | prayers.moderate | Moderate Prayer Requests |
| prayers | prayers.respond | Add Prayer Updates |
| prayers | prayers.flag_pastoral | Flag for Pastoral Attention |

#### Plugin: Giving

| Group | Permission | Display Name |
|-------|-----------|-------------|
| giving | giving.donate | Make Donations |
| giving | giving.view_own | View Own Giving History |
| giving | giving.view_all | View All Donations |
| giving | giving.manage_funds | Manage Giving Funds |
| giving | giving.export | Export Donation Records |
| giving | giving.manage_gateways | Configure Payment Gateways |
| giving | giving.issue_receipts | Issue Tax Receipts |

#### Plugin: Chat

| Group | Permission | Display Name |
|-------|-----------|-------------|
| chat | chat.send | Send Messages |
| chat | chat.create_group | Create Group Chats |
| chat | chat.attach_files | Send File Attachments |
| chat | chat.moderate | Moderate Any Chat |

#### Plugin: Library

| Group | Permission | Display Name |
|-------|-----------|-------------|
| library | library.view | Browse Library |
| library | library.read | Read Books Online |
| library | library.download | Download Books |
| library | library.create | Add Books |
| library | library.update | Edit Books |
| library | library.delete | Delete Books |
| library | library.manage_categories | Manage Book Categories |

#### Plugin: Church Builder

| Group | Permission | Display Name |
|-------|-----------|-------------|
| churches | churches.view | View Church Directory |
| churches | churches.create | Register a Church |
| churches | churches.update | Edit Own Church |
| churches | churches.update_any | Edit Any Church |
| churches | churches.delete | Delete Own Church |
| churches | churches.delete_any | Delete Any Church |
| churches | churches.verify | Verify/Unverify Churches |
| churches | churches.feature | Feature Churches |
| churches | churches.manage_members | Manage Church Members |
| churches | churches.manage_pages | Manage Church Pages |

#### Plugin: Blog

| Group | Permission | Display Name |
|-------|-----------|-------------|
| blog | blog.view | View Blog Posts |
| blog | blog.create | Write Blog Articles |
| blog | blog.update | Edit Own Articles |
| blog | blog.update_any | Edit Any Article |
| blog | blog.delete | Delete Own Articles |
| blog | blog.delete_any | Delete Any Article |
| blog | blog.publish | Publish Articles |
| blog | blog.manage_categories | Manage Blog Categories |

#### Plugin: Live Meetings

| Group | Permission | Display Name |
|-------|-----------|-------------|
| meetings | meetings.view | View Meeting Links |
| meetings | meetings.create | Create Meeting Links |
| meetings | meetings.update | Edit Meeting Links |
| meetings | meetings.delete | Delete Meeting Links |

#### Plugin: Notifications

| Group | Permission | Display Name |
|-------|-----------|-------------|
| notifications | notifications.send_push | Send Push Notifications |
| notifications | notifications.send_email | Send Bulk Emails |
| notifications | notifications.send_sms | Send SMS Notifications |
| notifications | notifications.manage | Manage Notification Templates |

#### Phase 3 Plugins

| Group | Permission | Display Name |
|-------|-----------|-------------|
| volunteers | volunteers.view | View Opportunities |
| volunteers | volunteers.signup | Sign Up to Volunteer |
| volunteers | volunteers.manage | Manage Volunteer Slots |
| volunteers | volunteers.checkin | Check-in Volunteers |
| fundraising | fundraising.view | View Campaigns |
| fundraising | fundraising.create | Create Campaigns |
| fundraising | fundraising.donate | Donate to Campaigns |
| fundraising | fundraising.manage | Manage All Campaigns |
| stories | stories.view | View Stories |
| stories | stories.create | Post Stories |
| stories | stories.moderate | Moderate Stories |
| pastoral | pastoral.request | Submit Care Requests |
| pastoral | pastoral.view_assigned | View Assigned Requests |
| pastoral | pastoral.view_all | View All Requests |
| pastoral | pastoral.respond | Respond to Requests |
| pastoral | pastoral.manage | Manage Pastoral Care |

---

*Generated: 2026-03-28 | Architecture: Foundation Fork (BeMusic pattern) | Status: Design approved, ready for implementation planning*
