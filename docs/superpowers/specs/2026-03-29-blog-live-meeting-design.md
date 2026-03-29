# Plan 8: Blog + Live Meeting Plugins — Design Spec

## Overview

Two independent plugins built as a combined plan. Blog provides a full CMS for church articles with Tiptap WYSIWYG editing, categories, tags, and SEO pre-rendering. Live Meeting provides a link-based meeting system with automatic "Live Now" detection.

## Blog Plugin

### Models

**Article**
- `id`, `title`, `slug`, `content` (HTML from Tiptap), `excerpt`, `cover_image`
- `author_id` (FK → users), `category_id` (FK → article_categories, nullable), `church_id` (FK → churches, nullable)
- `status` (enum: draft, published, scheduled)
- `published_at` (datetime, nullable — set when published or scheduled)
- `view_count` (int, default 0)
- `is_featured` (boolean, default false), `is_active` (boolean, default true)
- `meta_title`, `meta_description` (nullable strings for SEO)
- `created_at`, `updated_at`
- Trait: `HasReactions`
- Tags: many-to-many via `article_tag` pivot table
- Comments: deferred to a future plan. Articles support reactions only for V1.

**ArticleCategory**
- `id`, `name`, `slug`, `description` (nullable), `image` (nullable)
- `sort_order` (int, default 0), `is_active` (boolean, default true)
- `created_at`, `updated_at`
- Flat categories only (no `parent_id` nesting, unlike BookCategory). Blog categories are typically few and flat (News, Devotionals, Announcements, etc.).

**Tag**
- `id`, `name`, `slug`
- `created_at`, `updated_at`
- Many-to-many with Article via `article_tag` (article_id, tag_id)
- Tags are platform-global (not scoped per church). Church-scoping is handled at the Article level via `church_id`.
- No individual show endpoint needed — tags are only used as filter chips and multi-select options.

### Features

- **Tiptap WYSIWYG editor**: bold, italic, headings (H2, H3), bullet/ordered lists, blockquotes, links, image embeds. Content stored as HTML in `content` column.
- **Tiptap image uploads**: Image embeds use the Foundation layer's existing file upload endpoint (`POST /api/v1/uploads`). Tiptap's image extension is configured to upload via this endpoint and insert the returned URL. No new upload endpoint needed.
- **Status workflow**: Articles start as `draft`. Authors can save drafts. Users with `blog.publish` permission can set status to `published` (sets `published_at` to now) or `scheduled` (sets `published_at` to a future datetime). Scheduled articles are surfaced via query-time filtering: the PaginateArticles service treats articles where `status = scheduled AND published_at <= now` as published (and updates their status to `published` in the same query for consistency). No cron job needed.
- **Category sidebar + tag filtering**: Category sidebar on list page (same pattern as Library). Tags displayed as filter chips.
- **View count tracking**: Incremented on detail page view (same pattern as Library books).
- **Featured articles**: `is_featured` flag, displayed in a banner section at the top of the blog list page.
- **SEO pre-rendering**: A Blade route at `GET /blog/{slug}` renders meta tags + JSON-LD Article structured data for crawlers. The SPA handles the interactive view for authenticated users at the same URL (`/blog/:slug`).

### ArticlePolicy

- `viewAny`: any user with `blog.view` permission (including unauthenticated users — published articles are public for SEO)
- `view`: published articles are viewable by anyone; draft/scheduled articles are viewable only by the author or users with `blog.update` permission
- `create`: requires `blog.create` permission
- `update`: author can always edit their own articles; other users require `blog.update` permission. Status changes to `published` or `scheduled` additionally require `blog.publish` permission (enforced in controller, not policy).
- `delete`: author can delete their own drafts; other users require `blog.delete` permission
- `publish`: requires `blog.publish` permission (checked when setting status to published/scheduled)
- `manageCategories`: requires `blog.manage_categories` permission
- `manageTags`: requires `blog.manage_tags` permission

### Permissions

| Permission | Description |
|---|---|
| `blog.view` | Browse articles |
| `blog.create` | Create article drafts |
| `blog.update` | Edit articles |
| `blog.delete` | Delete articles |
| `blog.publish` | Publish or schedule articles (separate from create) |
| `blog.manage_categories` | CRUD article categories |
| `blog.manage_tags` | CRUD tags |

**Role assignments:**
- Member: `blog.view`
- Moderator / Ministry Leader: `blog.view`, `blog.create`, `blog.update`
- Church Admin / Pastor: all permissions
- Super Admin / Platform Admin: all permissions

### API Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/articles` | List articles (paginated, filterable by category_id, tag, search, featured). Default: published only. Supports `?status=draft,scheduled` for authors/admins to see their own non-published articles. |
| POST | `/api/v1/articles` | Create article |
| GET | `/api/v1/articles/{article:slug}` | Show article detail (increments view_count). Route model binding on `slug` column. |
| PUT | `/api/v1/articles/{article:slug}` | Update article |
| DELETE | `/api/v1/articles/{article:slug}` | Delete article |
| GET | `/api/v1/article-categories` | List categories (no individual show endpoint needed) |
| POST | `/api/v1/article-categories` | Create category |
| PUT | `/api/v1/article-categories/{articleCategory}` | Update category |
| DELETE | `/api/v1/article-categories/{articleCategory}` | Delete category (sets `articles.category_id = null` for related articles, does not cascade-delete) |
| GET | `/api/v1/tags` | List tags (no individual show endpoint needed) |
| POST | `/api/v1/tags` | Create tag |
| DELETE | `/api/v1/tags/{tag}` | Delete tag (detaches from articles via pivot) |
| GET | `/blog/{slug}` | SEO pre-rendered Blade route (public, no auth required) |

### Frontend Pages

- **BlogListPage** (`/blog`): article cards in grid, category sidebar, tag filter chips, search bar, featured articles banner at top, infinite scroll.
- **ArticleDetailPage** (`/blog/:slug`): cover image, title, author avatar + name, published date, Tiptap-rendered HTML content, category badge, tags, reaction bar, view count.
- **ArticleEditorPage** (`/blog/new` and `/blog/:slug/edit`): Tiptap editor, title input, category select, tag multi-select, cover image upload field, excerpt textarea, status dropdown (draft/published/scheduled), publish date picker (when scheduled), meta title/description fields.

---

## Live Meeting Plugin

### Model

**Meeting**
- `id`, `title`, `description` (nullable), `meeting_url`, `platform` (enum: zoom, google_meet, youtube, other)
- `church_id` (FK → churches, nullable), `host_id` (FK → users)
- `starts_at` (datetime), `ends_at` (datetime), `timezone` (string, default 'UTC')
- `is_recurring` (boolean, default false), `recurrence_rule` (nullable enum: weekly, biweekly, monthly — informational for V1, no auto-generation)
- `cover_image` (nullable)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`
- No traits (no reactions — meetings are transient, not content to react to)

### Features

- **Link-based meetings**: Admin pastes a Zoom/Meet/YouTube/custom URL when creating a meeting. No API integration — just a URL field.
- **"Live Now" detection**: Automatic, time-based. A meeting `is_live` when `starts_at <= now <= ends_at`. Exposed as an Eloquent accessor. No manual toggle.
- **Upcoming meetings list**: Sorted by `starts_at`, filtered to future + active.
- **"Join Meeting" button**: Opens `meeting_url` in a new browser tab.
- **Platform icon display**: Shows Zoom/Meet/YouTube icon based on `platform` field.
- **Recurring flag**: Informational for V1. `is_recurring` and `recurrence_rule` stored but no auto-generation of recurring instances. Admin creates each occurrence manually.

### MeetingPolicy

- `viewAny`: any user with `live_meeting.view` permission
- `view`: any user with `live_meeting.view` permission (meetings are not draft-able)
- `create`: requires `live_meeting.create` permission
- `update`: host can always update their own meetings; other users require `live_meeting.update` permission
- `delete`: host can delete their own meetings; other users require `live_meeting.delete` permission

### Permissions

| Permission | Description |
|---|---|
| `live_meeting.view` | Browse and join meetings |
| `live_meeting.create` | Create meetings |
| `live_meeting.update` | Edit meetings |
| `live_meeting.delete` | Delete meetings |

**Role assignments:**
- Member: `live_meeting.view`
- Moderator / Ministry Leader: `live_meeting.view`, `live_meeting.create`, `live_meeting.update`
- Church Admin / Pastor: all permissions
- Super Admin / Platform Admin: all permissions

### API Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/meetings` | List upcoming/active meetings (paginated) |
| POST | `/api/v1/meetings` | Create meeting |
| GET | `/api/v1/meetings/{meeting}` | Show meeting detail |
| PUT | `/api/v1/meetings/{meeting}` | Update meeting |
| DELETE | `/api/v1/meetings/{meeting}` | Delete meeting |
| GET | `/api/v1/meetings/live` | List currently live meetings |

### Frontend Pages

- **MeetingsPage** (`/meetings`): Two sections — "Live Now" (highlighted cards with pulsing badge) and "Upcoming" (chronological list). Each card shows title, platform icon, host name, start time, "Join" button.
- **MeetingDetailPage** (`/meetings/:meetingId`): Title, description, platform, host, time range, large "Join Meeting" button (opens URL in new tab), countdown timer if upcoming.

---

## Shared Architecture

### Plugin Pattern
Both plugins follow the established `app/Plugins/{Name}/` structure:
- Models, Services (Loader, Crupdate, Paginate, Delete), Controllers, Policies, Requests, Routes, Database/Seeders

### Integration Points
- `AppServiceProvider`: Gate policies (`Article::class => ArticlePolicy::class`, `Meeting::class => MeetingPolicy::class`), morph map entry (`'article' => Article::class`; no morph map entry for Meeting since it has no reactions)
- `routes/api.php`: Plugin route loading gated by `PluginManager::isEnabled()`
- `ReactionController`: Add `'article'` to allowed types (Blog only)
- `config/plugins.json`: Both plugins already listed and enabled (`blog`, `live_meeting`)

### Migration
- `0008_01_01_000000_create_blog_tables.php`: Creates `article_categories`, `tags`, `articles`, `article_tag`
- `0008_01_02_000000_create_meeting_tables.php`: Creates `meetings`

### Frontend Integration
- Lazy imports + routes in `app-router.tsx`
- Sidebar entries: Blog (icon: FileText, permission: blog.view), Meetings (icon: Video, permission: live_meeting.view)
- Tiptap packages: `@tiptap/react`, `@tiptap/starter-kit`, `@tiptap/extension-image`, `@tiptap/extension-link`

### Key Design Decisions
1. **Tiptap integrated from V1** — production-ready WYSIWYG rather than textarea. Content stored as HTML. Image uploads use Foundation's existing `POST /api/v1/uploads` endpoint.
2. **Link-based meetings** — admin pastes meeting URL. No Zoom/Meet API integration. Deferred to later plan.
3. **Time-based "Live Now"** — automatic detection from `starts_at`/`ends_at`. No manual toggle.
4. **Standalone meetings** — no dependency on Events plugin. Own model and routes.
5. **Blog SEO included** — Blade route + JSON-LD for `/blog/{slug}`. SPA and Blade routes both use slug-based URLs.
6. **`blog.publish` separate from `blog.create`** — authors can draft, only editors/admins can publish.
7. **Tags are platform-global** — not church-scoped. No update endpoint; rename by delete+create.
8. **Recurring meetings informational** — `is_recurring` flag + `recurrence_rule` enum stored but no auto-generation in V1.
9. **Flat blog categories** — no `parent_id` nesting (unlike BookCategory). Blog categories are typically few and flat.
10. **Scheduled publishing via query-time filtering** — no cron job. PaginateArticles treats scheduled articles past their `published_at` as published.
11. **Comments deferred** — articles support reactions only for V1. Comment system is a future plan.
12. **Public article access** — published articles are viewable without authentication (required for SEO).
13. **Category delete unlinks** — sets `articles.category_id = null`, does not cascade-delete articles.
