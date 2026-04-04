# Bemusic Script — Full System Architecture Reference

> Scraped from the Bemusic Script codebase for reuse in the Church Platform project.
> Generated: 2026-03-27

---

## Table of Contents

1. [Backend Architecture](#backend-architecture)
2. [Shared Foundation Module](#shared-foundation-module)
3. [Autoloading & Namespaces](#autoloading--namespaces)
4. [Routes](#routes)
5. [Models & Relationships](#models--relationships)
6. [Controllers](#controllers)
7. [Services & Design Patterns](#services--design-patterns)
8. [Database Schema](#database-schema)
9. [Authentication & Authorization](#authentication--authorization)
10. [API Structure](#api-structure)
11. [Configuration Files](#configuration-files)
12. [Backend Dependencies](#backend-dependencies)
13. [Frontend Architecture](#frontend-architecture)
14. [Frontend File Structure](#frontend-file-structure)
15. [Routing (Frontend)](#routing-frontend)
16. [State Management](#state-management)
17. [API Client Integration](#api-client-integration)
18. [Component Architecture](#component-architecture)
19. [Styling System](#styling-system)
20. [TypeScript Configuration](#typescript-configuration)
21. [Offline & PWA Support](#offline--pwa-support)
22. [Frontend Dependencies](#frontend-dependencies)
23. [Mapping Bemusic → Church Platform](#mapping-bemusic--church-platform)

---

## Backend Architecture

**Framework:** Laravel 12
**PHP Namespaces:** `App\` (app-specific), `Common\` (shared foundation)

### Directory Structure

```
app/
├── Console/Commands/            # Artisan commands
├── Http/
│   ├── Controllers/             # Domain-grouped controllers
│   │   ├── Artist/              # ArtistTracksController, ArtistFollowersController
│   │   ├── UserProfile/         # UserProfileController, UserPlaylistsController
│   │   ├── UserLibrary/         # UserLibraryTracksController, AlbumsController, ArtistsController
│   │   ├── Search/              # AlbumSearchSuggestionsController, ArtistSearchSuggestionsController
│   │   ├── ArtistController.php
│   │   ├── AlbumController.php
│   │   ├── TrackController.php
│   │   ├── PlaylistController.php
│   │   ├── GenreController.php
│   │   ├── SearchController.php
│   │   ├── LyricsController.php
│   │   ├── RadioController.php
│   │   ├── RepostController.php
│   │   ├── TrackPlaysController.php
│   │   ├── PlaylistTracksController.php
│   │   ├── PlaylistTracksOrderController.php
│   │   ├── BackstageRequestController.php
│   │   ├── TrackFileMetadataController.php
│   │   ├── DownloadLocalTrackController.php
│   │   ├── WaveController.php
│   │   ├── ImportMediaController.php
│   │   ├── PlayerTracksController.php
│   │   ├── MinutesLimitController.php
│   │   ├── NotificationController.php
│   │   ├── InsightsReportController.php
│   │   ├── YoutubeLogController.php
│   │   ├── LandingPageController.php
│   │   └── AppHomeController.php
│   ├── Requests/                # Form request validation
│   │   ├── ModifyArtists.php
│   │   ├── ModifyTracks.php
│   │   ├── ModifyUsers.php
│   │   ├── ModifyAlbums.php
│   │   ├── ModifyPlaylist.php
│   │   ├── CrupdateChannelRequest.php
│   │   └── CrupdateBackstageRequestRequest.php
│   └── Middleware/
├── Models/
│   ├── Artist.php
│   ├── Album.php
│   ├── Track.php
│   ├── Playlist.php
│   ├── Genre.php (extends Tag)
│   ├── Channel.php (extends BaseChannel)
│   ├── Lyric.php
│   ├── TrackPlay.php
│   ├── ProfileDetails.php
│   ├── ProfileImage.php
│   ├── ProfileLink.php
│   ├── Like.php (polymorphic)
│   ├── Repost.php (polymorphic)
│   ├── BackstageRequest.php
│   └── Tag.php
├── Policies/
│   ├── TrackPolicy.php
│   ├── ArtistPolicy.php
│   ├── AlbumPolicy.php
│   ├── PlaylistPolicy.php
│   ├── LyricPolicy.php
│   ├── TrackCommentPolicy.php
│   ├── MusicUploadPolicy.php
│   ├── AppUserPolicy.php
│   └── BackstageRequestPolicy.php
├── Providers/
│   └── AppServiceProvider.php   # Morph map, policy bindings, gates, events, service bindings
├── Services/                    # Domain-organized business logic (see Services section)
├── Traits/
└── Listeners/
```

---

## Shared Foundation Module

**Location:** `common/foundation/` (git submodule, shared across Vebto products)
**Namespace:** `Common\`

```
common/foundation/
├── src/                         # PHP: namespace Common\
│   ├── Auth/                    # Users, roles, permissions, OAuth, policies
│   ├── Billing/                 # Stripe subscriptions, invoices, plans
│   ├── Channels/                # Content curation channels
│   ├── Comments/                # Polymorphic comment system
│   ├── Files/                   # File upload, multi-storage adapters
│   ├── Search/                  # Scout integration (Meilisearch/Elasticsearch/TNTSearch)
│   ├── Settings/                # Global key-value settings store
│   ├── Tags/                    # Polymorphic tagging system
│   ├── Localizations/           # i18n / multi-language
│   ├── Notifications/           # Email + push notifications
│   ├── Pages/                   # Custom static pages
│   └── Workspaces/              # Multi-tenant support (optional)
├── config/                      # Foundation config files
│   ├── app.php
│   ├── broadcasting.php
│   ├── cache.php
│   ├── database.php
│   ├── fortify.php
│   ├── mail.php
│   ├── sanctum.php
│   ├── sentry.php
│   ├── seo/common.php
│   ├── setting-validators.php
│   ├── services.php
│   ├── analytics.php
│   ├── geoip.php
│   └── log-viewer.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/api.php               # Common API routes
└── resources/client/            # Shared React components (~2,700 files)
    ├── ui/library/              # Buttons, forms, overlays, tables, icons, layout
    ├── http/                    # Axios client, query-client setup
    ├── player/                  # Music player logic & providers
    ├── auth/                    # Auth guards & utilities
    ├── core/                    # CommonProvider, context providers
    ├── workspace/               # Multi-workspace support
    ├── billing/                 # Stripe checkout components
    ├── admin/                   # Admin utilities
    └── shared.tailwind.js       # Tailwind extensions
```

---

## Autoloading & Namespaces

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Common\\": "common/foundation/src/",
      "Common\\Database\\Seeders\\": "common/foundation/database/seeders",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

---

## Routes

### API Routes (`routes/api.php`)

All under `/api/v1` prefix with `optionalAuth:sanctum` and `verified` middleware.

| Resource | Endpoints |
|----------|-----------|
| Artists | index, show, store, update, destroy, followers, tracks, albums |
| Albums | index, show, store, update, destroy |
| Tracks | index, show, store, update, destroy, plays logging |
| Playlists | index, show, store, update, follow/unfollow |
| Channels | CRUD, content management, ordering |
| Search | global search, suggestions, audio search |
| Lyrics | CRUD, track lyrics |
| User Library | liked tracks, albums, artists, playlists |
| Other | reposts, followers, tags, genres, backstage requests |

### Web Routes (`routes/web.php`)

- Pre-rendered pages for SEO: `/artist/{artist}`, `/album/{album}/{artistName}/{albumName}`, `/track/{track}`, `/playlist/{id}`
- SPA fallback route for client-side routing

---

## Models & Relationships

| Model | Morph Type | Key Relationships | Traits |
|-------|------------|-------------------|--------|
| **User** (extends BaseUser) | `user` | artists (M2M), profile (HasOne), likedTracks, likedAlbums, likedArtists, followers, followedUsers, reposts, playlists | HasApiTokens, Notifiable, HasFactory |
| **Artist** | `artist` | albums (M2M), tracks (M2M), genres (MorphToMany), similar, profile (HasOne), profileImages, links, followers | OrdersByPopularity, Searchable, HasAttachedFileEntries |
| **Album** | `album` | artists (M2M), tracks (HasMany), plays (HasManyThrough), tags (MorphToMany), genres (MorphToMany), likes (MorphToMany), comments, reposts | OrdersByPopularity, Searchable, HasAttachedFileEntries |
| **Track** | `track` | album (BelongsTo), artists (M2M), plays (HasMany), likes (MorphToMany), comments, reposts, tags, genres | OrdersByPopularity, Searchable, HasAttachedFileEntries |
| **Playlist** | `playlist` | owner (BelongsTo), users (M2M), editors (M2M filtered), tracks (M2M with position) | OrdersByPopularity, Searchable, HasAttachedFileEntries |
| **Genre** (extends Tag) | `genre` | artists, tracks, albums (all MorphToMany) | OrdersByPopularity |
| **Channel** (extends BaseChannel) | `channel` | artists, albums, tracks, users, genres, playlists (all MorphToMany) | Dynamic content loading |
| **Lyric** | `lyric` | track (BelongsTo) | — |
| **TrackPlay** | — | track (BelongsTo) | HasFactory, UPDATED_AT=null |
| **Like** | polymorphic | morphs to Track/Album/Playlist | — |
| **Repost** | polymorphic | morphs to Track/Album/Playlist | — |
| **BackstageRequest** | `backstageRequest` | user (BelongsTo), artist (BelongsTo) | — |
| **Tag** | — | tracks (MorphToMany), albums (MorphToMany) | — |
| **ProfileDetails** | — | artist (BelongsTo) | HasFactory |
| **ProfileImage** | — | artist/user (polymorphic) | — |
| **ProfileLink** | — | linkeable (polymorphic) | — |

### Shared Traits

- `HasAttachedFileEntries` — File attachment relationship
- `HasApiTokens` — Sanctum API tokens
- `Notifiable` — Notification support
- `OrdersByPopularity` — Sort by external or local popularity
- `OrdersByPosition` — Sort by position field
- `Searchable` — Laravel Scout search indexing

---

## Controllers

### Main CRUD Controllers

| Controller | Methods |
|------------|---------|
| ArtistController | index, show, store, update, destroy |
| AlbumController | index, show, store, update, destroy |
| TrackController | index, show, store, update, destroy |
| PlaylistController | index, show, store, update, destroy, follow/unfollow |
| GenreController | index, store, update, destroy |
| LyricsController | CRUD for track lyrics |

### Domain-Grouped Controllers

| Group | Controllers |
|-------|------------|
| Artist/ | ArtistTracksController, ArtistFollowersController, ArtistAlbumsController |
| UserProfile/ | UserProfileController (show, update), UserPlaylistsController |
| UserLibrary/ | UserLibraryTracksController, UserLibraryAlbumsController, UserLibraryArtistsController |
| Search/ | AlbumSearchSuggestionsController, ArtistSearchSuggestionsController |

### Specialized Controllers

| Controller | Purpose |
|------------|---------|
| SearchController | Global search, model-specific search, audio search, suggestions |
| PlaylistTracksController | Add/remove tracks in playlists |
| PlaylistTracksOrderController | Reorder playlist tracks |
| TrackPlaysController | Log track plays |
| RepostController | Toggle reposts |
| RadioController | Radio/recommendations API |
| BackstageRequestController | Artist request management (approve/deny) |
| TrackFileMetadataController | Extract metadata from uploaded audio |
| DownloadLocalTrackController | File downloads |
| WaveController | Waveform visualization data |
| ImportMediaController | Import from external sources |
| PlayerTracksController | Queue/player management |
| MinutesLimitController | User minute quota enforcement |
| InsightsReportController | Analytics/insights |
| LandingPageController | Landing page data API |
| AppHomeController | Landing page rendering |

---

## Services & Design Patterns

### Service Directory Structure

```
app/Services/
├── Admin/                       # Admin-specific services
├── Albums/
│   ├── AlbumLoader.php          # API resource formatting + eager loading
│   ├── PaginateAlbums.php       # Paginated listing
│   ├── CrupdateAlbum.php        # Create-or-Update (single class for both)
│   └── DeleteAlbums.php         # Bulk/single deletion
├── Artists/
│   ├── ArtistLoader.php
│   ├── PaginateArtists.php
│   ├── CrupdateArtist.php
│   ├── DeleteArtists.php
│   └── GetSimilarArtists.php
├── Tracks/
│   ├── TrackLoader.php
│   ├── PaginateTracks.php
│   ├── CrupdateTrack.php
│   ├── DeleteTracks.php
│   ├── ExtractMetadataFromTrackFile.php
│   ├── LogTrackPlay.php
│   └── Queries/                 # Complex query builders
│       ├── BaseTrackQuery.php
│       ├── HistoryTrackQuery.php
│       ├── PlaylistTrackQuery.php
│       └── LibraryTracksQuery.php
├── Playlists/
│   ├── PlaylistLoader.php
│   ├── PaginatePlaylists.php
│   └── DeletePlaylists.php
├── Genres/
│   ├── PaginateGenres.php
│   └── GenreToApiResource.php
├── Channels/
│   ├── FetchContentForChannelFromSpotify.php
│   ├── FetchContentForChannelFromDeezer.php
│   └── FetchContentForChannelFromLocal.php
├── Lyrics/
│   ├── ImportLyrics.php
│   ├── LyricsProvider.php       # Abstract base
│   ├── GoogleLyricsProvider.php
│   ├── AzLyricsProvider.php
│   └── LrclibLyricsProvider.php
├── Search/
├── Users/
│   ├── UserProfileLoader.php
│   └── PaginateUserProfiles.php
├── Settings/Validators/
│   ├── SpotifyCredentialsValidator.php
│   ├── YoutubeCredentialsValidator.php
│   ├── LastfmCredentialsValidator.php
│   └── WikipediaCredentialsValidator.php
├── Providers/                   # External data provider abstraction
│   ├── MusicMetadataProvider.php       # Factory (returns Spotify or Deezer)
│   ├── ContentProvider.php
│   ├── FetchesExternalArtistBio.php
│   ├── UpsertsDataIntoDB.php
│   ├── Spotify/
│   │   ├── SpotifyBase.php
│   │   ├── SpotifyMetadataProvider.php
│   │   ├── SpotifyNormalizer.php
│   │   ├── SpotifyArtist.php
│   │   ├── SpotifyAlbum.php
│   │   ├── SpotifyTrack.php
│   │   ├── SpotifyGenre.php
│   │   ├── SpotifySearch.php
│   │   ├── SpotifyTopTracks.php
│   │   └── SpotifyCharts.php
│   └── Deezer/
│       ├── DeezerMetadataProvider.php
│       ├── DeezerArtist.php
│       └── DeezerSearch.php
├── Backstage/
├── AppBootstrapData.php         # Initial app data for frontend hydration
├── AppValueLists.php            # Dropdown/select data
├── BuildInsightsReport.php      # Analytics
├── ChannelPresets.php           # Channel preset configs
├── IncrementModelViews.php      # View counting
├── SitemapGenerator.php         # SEO sitemap
└── UrlGenerator.php             # URL generation
```

### Design Patterns Summary

| Pattern | Description | Example |
|---------|-------------|---------|
| **Loader** | Format model data for API responses, handle eager-loading and context | `ArtistLoader`, `AlbumLoader`, `TrackLoader` |
| **Crupdate** | Single class for Create + Update operations | `CrupdateAlbum`, `CrupdateArtist`, `CrupdateTrack` |
| **Paginate** | Dedicated class for paginated listings with filters | `PaginateAlbums`, `PaginateArtists` |
| **Delete** | Bulk/single deletion with cleanup | `DeleteAlbums`, `DeleteTracks` |
| **Provider** | Abstract external API integration behind contracts | `MusicMetadataProvider` → Spotify or Deezer |
| **Query Builder** | Complex queries wrapped in dedicated classes | `BaseTrackQuery`, `LibraryTracksQuery` |
| **Validator** | Per-integration credential validators | `SpotifyCredentialsValidator` |

---

## Database Schema

### Core Music Tables

```sql
artists       -- id, name, image_small, image_large, spotify_id, verified, fully_scraped, views, external_popularity, disabled
albums        -- id, name, image, spotify_id, release_date, external_popularity, plays, views, fully_scraped, owner_id, record_type
tracks        -- id, name, image, duration, spotify_id, src, plays, views, album_id, owner_id, fully_scraped, external_popularity
genres        -- id, name, display_name (slug), image, popularity
```

### User & Profile Tables

```sql
users             -- id, email, password, name, image, artist_id, email_verified_at, two_factor_*, banned_at
profile_details   -- artist_id, bio, header_image
profile_images    -- id, artist_id, user_id, url
profile_links     -- id, url, title, linkeable_id, linkeable_type (polymorphic)
```

### Junction / Relationship Tables

```sql
artist_album      -- artist_id, album_id, primary (bool)
artist_track      -- artist_id, track_id, primary (bool)
genre_artist      -- artist_id, genre_id (via genreable morph)
genre_track       -- track_id, genre_id (via genreable morph)
genre_album       -- album_id, genre_id (via genreable morph)
likes             -- id, user_id, likeable_id, likeable_type, created_at (polymorphic)
reposts           -- id, user_id, repostable_id, repostable_type, created_at
follows           -- user_id, followable_id, followable_type (users/artists)
```

### Playlist Tables

```sql
playlists         -- id, name, description, image, owner_id, public, collaborative, spotify_id, plays, views
playlist_track    -- id, playlist_id, track_id, position, added_by, created_at
playlist_user     -- playlist_id, user_id, editor (pivot bool)
```

### Other App Tables

```sql
lyrics            -- id, track_id, text, is_synced, duration
plays             -- id, track_id, user_id, created_at (TrackPlay model)
channels          -- id, name, slug, config (JSON), type, public, created_at
channelables      -- id, channel_id, channelable_id, channelable_type, order, created_at
tags              -- id, name, type, user_id, created_at
taggables         -- tag_id, taggable_id, taggable_type
backstage_requests -- id, user_id, artist_id, type, data (JSON), status
comments          -- id, user_id, commentable_id, commentable_type, body, deleted_at (from common)
file_entries      -- file storage metadata (from common)
```

### Common Foundation Tables

```sql
users                  -- extended with roles, permissions, billing info
roles                  -- id, name, internal, description
permissions            -- id, name, description
user_role              -- pivot
permission_role        -- pivot
social_profiles        -- OAuth integration
settings               -- key-value configuration
subscriptions          -- billing/subscription info
invoices               -- billing invoices
personal_access_tokens -- Sanctum API tokens
```

---

## Authentication & Authorization

### Auth Stack

| Layer | Implementation |
|-------|---------------|
| API Guard | Laravel Sanctum (token-based) |
| Auth Scaffolding | Laravel Fortify (login, register, verify, 2FA) |
| OAuth/Social | Laravel Socialite (Spotify, Google, etc.) |
| Permissions | Common foundation role-permission system |

### Policies

| Policy | Protects |
|--------|----------|
| TrackPolicy | Track CRUD + minute limits |
| ArtistPolicy | Artist CRUD |
| AlbumPolicy | Album CRUD |
| PlaylistPolicy | Playlist CRUD |
| LyricPolicy | Lyric management |
| TrackCommentPolicy | Comment moderation |
| MusicUploadPolicy | File uploads |
| AppUserPolicy | User management |
| BackstageRequestPolicy | Backstage requests |

### Key Permissions

```
music.view, music.create, music.update, music.download, music.offline
artists.view, artists.create, artists.update, artists.delete
tracks.view, tracks.create, tracks.update, tracks.delete
admin (full admin access)
```

### Gates (AppServiceProvider)

```php
FileEntry  → MusicUploadPolicy
Comment    → TrackCommentPolicy
User       → AppUserPolicy
```

---

## API Structure

### General

- **Version:** v1 (prefix `/api/v1`)
- **Middleware:** `optionalAuth:sanctum`, `verified`
- **Response:** JSON with consistent structure, pagination support
- **Method override:** PUT/DELETE/PATCH sent as POST with `_method` field

### Resource Formatting (Loaders)

Each domain has a Loader class that handles API response shaping:

```
ArtistLoader   → format artist + eager-loaded relations
AlbumLoader    → format album + tracks + metadata
TrackLoader    → format track + metadata
PlaylistLoader → format playlist + tracks
UserProfileLoader → format user profile
```

### Request Validation

All extend `BaseFormRequest` from common foundation:

```
ModifyArtists, ModifyTracks, ModifyUsers, ModifyAlbums,
ModifyPlaylist, CrupdateChannelRequest, CrupdateBackstageRequestRequest
```

### Pagination

Custom classes: `CustomLengthAwarePaginator`, `CustomSimplePaginator`

---

## Configuration Files

### App Config (`config/`)

| File | Purpose |
|------|---------|
| `app.php` | Envato item ID for licensing |
| `services.php` | Spotify (ID, secret), LastFM (API key), RapidAPI |
| `scout.php` | Meilisearch config, index settings per model |
| `filesystems.php` | Storage disks (local, s3, ftp, sftp, dropbox, webdav) |
| `logging.php` | Monolog error logging |
| `menus.php` | Dynamic menu configuration |
| `themes.php` | Theme configuration system |

### Foundation Config (`common/foundation/config/`)

| File | Purpose |
|------|---------|
| `app.php` | App name, locale defaults |
| `broadcasting.php` | Broadcasting driver config |
| `cache.php` | Cache driver config |
| `database.php` | DB connections |
| `fortify.php` | Auth route config |
| `mail.php` | Email driver config |
| `sanctum.php` | Token config |
| `sentry.php` | Error tracking |
| `seo/common.php` | SEO metadata defaults |
| `setting-validators.php` | Settings validation rules |
| `services.php` | External service credentials |
| `analytics.php` | Analytics config |
| `geoip.php` | GeoIP lookup config |
| `log-viewer.php` | Log viewer UI config |

---

## Backend Dependencies

### Framework & Core

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^12.0 | Main framework |
| laravel/sanctum | ^4.0 | API authentication |
| laravel/fortify | ^1.25 | Auth scaffolding |
| laravel/socialite | ^5.18 | OAuth providers |

### Search & Indexing

| Package | Purpose |
|---------|---------|
| laravel/scout ^10.6 | Search abstraction |
| meilisearch/meilisearch-php ^1.13 | Meilisearch driver |
| matchish/laravel-scout-elasticsearch ^7.5 | Elasticsearch driver |
| teamtnt/laravel-scout-tntsearch-driver ^15.0 | TNTSearch driver |
| algolia/algoliasearch-client-php ^3.4 | Algolia driver |

### Storage & Files

| Package | Purpose |
|---------|---------|
| league/flysystem-aws-s3-v3 ^3.2 | AWS S3 |
| league/flysystem-ftp ^3.0 | FTP |
| league/flysystem-sftp-v3 ^3.0 | SFTP |
| league/flysystem-webdav ^3.0 | WebDAV |
| spatie/flysystem-dropbox ^3.0 | Dropbox |
| maennchen/zipstream-php ^3.1 | ZIP streaming |

### Image & Media

| Package | Purpose |
|---------|---------|
| intervention/image ^3.0 | Image manipulation |
| james-heinrich/getid3 ^1.9 | Audio/video metadata |
| intervention/gif ^3.x | GIF generation |

### Payments & Billing

| Package | Purpose |
|---------|---------|
| stripe/stripe-php ^16.6 | Stripe payments |
| moneyphp/money ^4.6 | Money handling |

### Notifications & Communication

| Package | Purpose |
|---------|---------|
| laravel/slack-notification-channel ^3.5 | Slack |
| symfony/mailgun-mailer ^7.2 | Mailgun |
| symfony/postmark-mailer ^7.2 | Postmark |
| webklex/php-imap ^6.1 | IMAP |
| ably/ably-php ^1.1 | Realtime messaging |

### Infrastructure

| Package | Purpose |
|---------|---------|
| predis/predis ^2.3 | Redis client |
| pda/pheanstalk ^5.0 | Beanstalk queue |
| laravel/horizon ^5.3 | Redis queue dashboard |
| laravel/reverb ^1.4 | WebSocket server |

### External APIs

| Package | Purpose |
|---------|---------|
| google/apiclient ^2.12 | Google APIs |
| google/analytics-data ^0.11 | Google Analytics |
| guzzlehttp/guzzle ^7.8 | HTTP client |
| openai-php/client ^0.10.1 | OpenAI integration |
| muxinc/mux-php ^3.11 | Mux video API |

### Utilities

| Package | Purpose |
|---------|---------|
| cocur/slugify ^4.5 | URL slugs |
| adbario/php-dot-notation ^3.3 | Dot notation arrays |
| jenssegers/agent ^2.6 | User agent detection |
| bacon/bacon-qr-code ^3.0 | QR codes |
| torann/geoip ^3.0 | GeoIP lookups |
| ezyang/htmlpurifier ^4.18 | HTML sanitization |
| league/html-to-markdown ^5.1 | HTML → Markdown |
| league/color-extractor 0.4 | Color extraction |
| smalot/pdfparser ^2.11 | PDF parsing |
| fivefilters/readability.php ^3.3 | Content extraction |

### Dev Dependencies

| Package | Purpose |
|---------|---------|
| itsgoingd/clockwork ^5.1 | Debug dashboard |
| spatie/laravel-ignition ^2.9 | Error page |
| barryvdh/laravel-ide-helper ^3.1 | IDE autocomplete |
| laravel/pail ^1.2 | Log tailing |
| opcodesio/log-viewer ^3.10 | Log viewer UI |
| sentry/sentry-laravel ^4.1 | Error tracking |

---

## Frontend Architecture

### Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| React | 19.2.0 | UI framework |
| TypeScript | 5.8.3 | Type safety |
| Vite | 6.3.4 | Build tool (SWC compiler) |
| React Router | 7.6.1 | Client-side routing |
| TanStack React Query | 5.75.1 | Server state management |
| Zustand | 5.0.4 | Client state management |
| Immer | 10.1.1 | Immutable state updates |
| Tailwind CSS | 3.4 | Utility-first styling |
| Framer Motion | 12.9.4 | Animations |

### Build Scripts (package.json)

```json
{
  "dev": "vite --host",
  "build": "tsc && vite build",
  "preview": "vite preview",
  "iconIndex": "generate icon index from material icons",
  "extract": "extract translations from codebase"
}
```

### Vite Configuration

- Laravel Vite plugin for asset management
- PWA support via `vite-plugin-pwa` with Workbox
- Source maps enabled in production build
- Base path from HTML base tag

---

## Frontend File Structure

```
resources/client/
├── main.tsx                     # Entry: React root + Sentry + error boundary
├── app-router.tsx               # Route composition (lazy-loaded modules)
├── app-queries.ts               # TanStack query definitions (centralized)
├── app.css                      # Global styles
├── site-config.tsx              # Site configuration context
├── sw.ts                        # Service worker (Workbox + OPFS)
├── web-player/                  # Main music player UI
│   ├── state/
│   │   ├── player-store-options.ts    # Zustand: player state
│   │   └── player-overlay-store.ts    # Zustand: overlay UI
│   ├── library/state/
│   │   ├── likes-store.ts             # Zustand: likes cache
│   │   └── reposts-store.ts           # Zustand: reposts cache
│   ├── albums/
│   ├── artists/
│   ├── tracks/
│   ├── playlists/
│   └── channels/
├── admin/                       # Admin dashboard (datatables, settings, reports)
├── auth/                        # Auth pages
├── landing-page/                # Public landing page
└── offline/                     # Offline mode components
    └── offline-entities-store.ts  # Zustand: offline cache

common/foundation/resources/client/
├── ui/library/                  # Shared UI components (~2,700 files)
│   ├── buttons/
│   ├── forms/
│   ├── inputs/
│   ├── overlays/ (dialogs, modals)
│   ├── navigation/ (breadcrumbs, menus, tabs)
│   ├── tables/ (datatables)
│   ├── progress/
│   ├── toasts/
│   ├── badges/
│   ├── icons/ (Material design)
│   └── layout/ (dashboard, sidebars)
├── http/                        # Axios client, query-client
├── player/                      # Player logic & providers
├── auth/                        # Auth guards & utilities
├── core/                        # CommonProvider, context providers
├── workspace/                   # Multi-workspace
├── billing/                     # Stripe checkout
├── admin/                       # Admin utilities
└── shared.tailwind.js           # Tailwind config extensions
```

### TypeScript Path Aliases

```json
{
  "@ui/*":     "common/foundation/resources/client/ui/library/*",
  "@common/*": "common/foundation/resources/client/*",
  "@app/*":    "resources/client/*"
}
```

---

## Routing (Frontend)

### Route Composition (`app-router.tsx`)

Browser-based routing with React Router 7.6.1. All route modules are **lazy-loaded** for code splitting.

```typescript
// Composed from multiple modules:
authRoutes()        // Auth/login/register/verify pages
adminRoutes         // Admin dashboard
checkoutRoutes      // Billing/checkout flow
billingPageRoutes   // Billing management
webPlayerRoutes     // Main music player interface
backstageRoutes     // Artist backstage features
commonRoutes        // Shared platform routes
notificationRoutes  // Notifications
```

### Key Routes

| Route | Purpose |
|-------|---------|
| `/track/:trackId/:trackName` | Track detail & embed |
| `/album/:albumId/:artistName/:albumName` | Album page |
| `/artist/:artistId/:artistName` | Artist profile |
| `/artist/:artistId/:artistName/albums` | Artist albums |
| `/lyrics` | Lyrics viewer |
| `/playlist/:id/:name` | Playlist page |
| `/admin/*` | Admin dashboard |
| `/checkout/*` | Checkout flow |
| `/api-docs` | Swagger API docs |
| `/landing` | Public landing page |

---

## State Management

### Three-Layer Strategy

| Layer | Tool | Purpose | Examples |
|-------|------|---------|----------|
| **Server State** | TanStack React Query | Remote data (API responses) | Posts, users, events, search results |
| **Client State** | Zustand + Immer | Local UI state | Player, overlay, likes cache, offline cache |
| **Bootstrap Data** | Custom store | Server-rendered initial payload | User info, settings, permissions |

### TanStack React Query Configuration

```typescript
// Default staleTime: 30 seconds
// Smart retry: skips 401, 403, 404 errors
// Query factory helpers: get(), paginate(), infiniteQuery()
// Infinite query support for pagination
```

### Zustand Stores

| Store | Location | Purpose |
|-------|----------|---------|
| Player Store | `web-player/state/player-store-options.ts` | Music player state |
| Player Overlay Store | `web-player/state/player-overlay-store.ts` | Player overlay UI |
| Likes Store | `web-player/library/state/likes-store.ts` | User likes tracking |
| Reposts Store | `web-player/library/state/reposts-store.ts` | Reposts tracking |
| Offline Store | `offline/offline-entities-store.ts` | Offline cache management |

### Bootstrap Data Store

```typescript
// Server-rendered initial data hydrated on first page load
// Contains: user info, settings, bootstrap loaders, playlists, likes/reposts
// Accessible globally via getBootstrapData()
```

### React Context

| Context | Purpose |
|---------|---------|
| SiteConfigContext | Global site configuration |
| PlayerContext | Shared player state across components |
| ThemeProvider | Dark/light mode |

---

## API Client Integration

### HTTP Client (`@common/http/query-client.ts`)

Axios-based with interceptors:

| Feature | Details |
|---------|---------|
| Auto-prefix | `api/v1/` |
| Array params | Converted to comma-separated strings |
| Method override | PUT/DELETE/PATCH → POST with `_method` |
| CSRF | Auto-refresh on 419 responses |
| Workspace | Workspace ID injection for multi-tenant |
| Realtime | Echo socket ID attached to requests |

### Centralized Queries (`app-queries.ts`)

```typescript
// Models: Artists, Albums, Tracks, Playlists, Genres, Users, Search, Radio
// Factory helpers: get(), paginate(), infiniteQuery()
// Lazy data loading with initial data from bootstrap
// Pagination via getNextPageParam()
```

---

## Component Architecture

### Scale

- **App-specific components:** ~248 files in `/resources/client/`
- **Shared foundation components:** ~2,758 files in `common/foundation/resources/client/`
- **Total:** ~3,000+ React components

### Shared UI Library Features

- Accessible components using `@react-aria`
- Dialog store-based modals
- Toast notification system
- Form validation with React Hook Form
- Color picker (react-colorful)
- Rich text editor (Tiptap with 10+ extensions)
- Data tables with sorting/filtering
- Material design icon system
- Avatar components with image handling
- Dashboard layout (two/three-column)

### Layout Components

| Layout | Purpose |
|--------|---------|
| DashboardLayout | Two/three-column dashboard |
| WebPlayerLayout | Main player with sidebars |
| Queue sidebar | Music playback queue |
| Responsive variants | Mobile/tablet adaptations |

---

## Styling System

### Tailwind CSS 3.4 Configuration

```javascript
// 8-pixel spacing scale
// Screens: sm(640px), md(768px), lg(1024px), xl(1146px)
// Dark mode: class strategy
// Plugins: @tailwindcss/typography, @tailwindcss/container-queries
// PostCSS: nesting support (13.0.1)
```

### Theme System

- Dark/light mode via CSS class toggle
- CSS custom properties for theming
- Theme configuration from foundation
- Responsive breakpoints with media queries

---

## TypeScript Configuration

```json
{
  "compilerOptions": {
    "target": "ESNext",
    "module": "ESNext",
    "moduleResolution": "Node",
    "jsx": "react-jsx",
    "strict": true,
    "lib": ["DOM", "WebWorker"],
    "paths": {
      "@ui/*": ["common/foundation/resources/client/ui/library/*"],
      "@common/*": ["common/foundation/resources/client/*"],
      "@app/*": ["resources/client/*"]
    }
  }
}
```

---

## Offline & PWA Support

| Feature | Implementation |
|---------|---------------|
| Service Worker | `resources/client/sw.ts` with Workbox |
| Offline Playback | OPFS (Origin Private File System) |
| Precaching | Workbox manifest filtering |
| Offline Queue | IndexedDB |
| Navigation | Network-only with offline fallback |

---

## Frontend Dependencies

### Core

| Package | Version | Purpose |
|---------|---------|---------|
| react | 19.2.0 | UI framework |
| react-dom | 19.1.0 | DOM rendering |
| react-router | 7.6.1 | Routing |
| @tanstack/react-query | 5.75.1 | Server state |
| zustand | 5.0.4 | Client state |
| immer | 10.1.1 | Immutable updates |
| axios | latest | HTTP client |

### UI & Interaction

| Package | Purpose |
|---------|---------|
| lucide-react | Icons |
| framer-motion 12.9.4 | Animations |
| @react-aria packages | Accessible primitives |
| react-colorful | Color picker |
| react-hook-form 7.56.1 | Form handling |
| chart.js | Data visualization |
| @xyflow/react | DAG/flow visualization |
| @tanstack/react-virtual 3.13.6 | Virtualized lists |

### Media

| Package | Purpose |
|---------|---------|
| hls.js | HLS video streaming |
| dashjs | DASH video streaming |
| tus-js-client | Resumable file uploads |
| ace-builds | Code editor |

### Editor

| Package | Purpose |
|---------|---------|
| @tiptap/* (10+ extensions) | Rich text editor |
| markdown-it | Markdown parsing |
| linkify-string | Auto-linking |

### Payments

| Package | Purpose |
|---------|---------|
| @stripe/react-stripe-js | Stripe React components |
| @stripe/stripe-js | Stripe JS SDK |

### Realtime

| Package | Purpose |
|---------|---------|
| laravel-echo | WebSocket client |
| pusher-js | Pusher realtime |

### PWA

| Package | Purpose |
|---------|---------|
| vite-plugin-pwa | PWA generation |
| workbox-* | Service worker toolkit |

### Dev Tools

| Package | Purpose |
|---------|---------|
| @tanstack/react-query-devtools | Query debugging |
| eslint + plugins | Linting |
| prettier | Formatting |
| @sentry/react | Error tracking |

---

## Mapping Bemusic → Church Platform

### Architecture Translation

| Bemusic Component | Church Platform Equivalent |
|-------------------|---------------------------|
| `common/foundation/` (git submodule) | `app/Core/` (PluginManager, ThemeManager, SettingsManager, MenuBuilder) |
| `Common\Auth` (roles, permissions, OAuth) | `Plugins\Auth` (always-on, Spatie Permission) |
| `Common\Settings` (key-value store) | `Plugins\Settings` + `app/Core/SettingsManager.php` |
| `Common\Comments` (polymorphic) | Shared across Post, Prayer, Question plugins |
| `Common\Tags` (polymorphic) | Hashtags on posts, communities, events |
| `Common\Files` (multi-storage) | Media uploads for posts, profiles, church pages |
| `Common\Channels` (content curation) | Timeline feed curation, DailyVerse |
| `Services/*/Loader.php` | Each plugin gets its own Loader for API formatting |
| `Services/*/Crupdate*.php` | Each plugin CRUD via single create-or-update service |
| `Services/*/Paginate*.php` | Paginated feeds, member lists, event lists |

### Frontend Translation

| Bemusic Pattern | Church Platform Equivalent |
|-----------------|---------------------------|
| `@common/*` shared components | `resources/js/components/ui/` (shadcn) + `components/shared/` |
| Zustand stores (likes, reposts) | Zustand stores (reactions: like/bless/pray/amen) |
| TanStack Query (centralized) | Same pattern for posts, events, prayers, verses |
| Lazy route modules | Lazy plugin components in `resources/js/plugins/` |
| `web-player/` domain folder | `plugins/Timeline/`, `plugins/Prayer/`, etc. |
| Bootstrap data hydration | Same: user, settings, permissions on first load |
| `plugins.json` runtime state | `storage/app/plugins.json` enable/disable toggle |

### Pattern Reuse Checklist

- [ ] **Loader pattern** — One `*Loader.php` per plugin for API response formatting
- [ ] **Crupdate pattern** — Single class handles create + update per resource
- [ ] **Paginate pattern** — Dedicated class per paginated listing
- [ ] **Policy pattern** — One policy per plugin's main model
- [ ] **Form Request validation** — One `Modify*.php` per resource
- [ ] **Zustand + React Query split** — Server state vs client state
- [ ] **Lazy route modules** — Code-split per plugin
- [ ] **Bootstrap data** — Server-rendered initial payload
- [ ] **Path aliases** — `@ui/*`, `@common/*`, `@app/*` equivalents
