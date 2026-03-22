# Sprint 3 — Media Plugin Design Spec

## Goal

Allow members to attach photos and videos to posts. Admins configure one or more storage backends (local disk, S3, R2, Backblaze, etc.) from the admin panel — no code or env-var changes required to switch providers.

## Architecture

### New Plugin: `plugins/Media/`

**Database tables:**

`storage_backends`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string(100) | Human label, e.g. "Local", "R2 Production" |
| type | enum | `local`, `s3`, `s3_compatible`, `backblaze`, `digitalocean`, `webdav`, `ftp`, `sftp` |
| config | json (encrypted) | Driver-specific credentials — stored via Laravel's `encrypted` cast. Never logged. |
| custom_domain | string nullable | CDN URL prefix, e.g. `https://cdn.mychurch.com` |
| is_active | bool default false | Only one backend may be active at a time. Enforced at application level via transaction (MySQL does not support partial/conditional indexes). |
| max_file_size_kb | int default 20480 | Per-file limit (default 20 MB) |
| allowed_mimes | json | Default: `["image/jpeg","image/png","image/webp","image/gif","video/mp4","video/webm"]`. SVG and HTML are **never permitted** regardless of admin config — hard-blocked at the validator level. |
| created_at/updated_at | timestamps | |

`media_files`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users cascadeOnDelete | Uploader |
| backend_id | FK → storage_backends nullOnDelete nullable | Which backend stored this file. `nullOnDelete` (not cascade) — preserves row for cleanup tracking when a backend is deleted. |
| disk_path | string | Path on the storage disk |
| mime_type | string | Validated via `finfo` server-side, not extension |
| size_kb | int | File size in KB |
| width | int nullable | Image width px. Extracted via `getimagesize()` at upload time. |
| height | int nullable | Image height px. Same. |
| duration_seconds | int nullable | Video duration. **Deferred — always null in Sprint 3.** Requires ffprobe integration (future sprint). |
| deleted_at | timestamp nullable | Soft delete. Physical deletion queued on soft-delete. |
| created_at/updated_at | timestamps | |

**No `url` column** — URLs are **computed at read time** from `disk_path` + the backend's `custom_domain` (or `Storage::disk()->url(disk_path)` if no custom domain). A `MediaFile::getUrlAttribute()` accessor computes the URL on the fly.

- If `backend_id` is `null` (backend was deleted): `getUrlAttribute()` returns `null`. `MediaGrid`/`VideoPlayer` render a broken-image placeholder. This is intentional — the file reference is preserved for auditing but the file is gone.
- If `custom_domain` is set: URL = `rtrim($backend->custom_domain, '/') . '/' . ltrim($this->disk_path, '/')`.
- Otherwise: `Storage::disk($resolvedDiskName)->url($this->disk_path)`.

**Existing column used:** `social_posts.media` (JSON, already in schema) — stores an array of `{id, url, type, mime, width?, height?, duration?}`. `url` in the JSON is a snapshot at post-creation time for display fallback; the authoritative URL is always `MediaFile::url` accessor (refreshed on demand via `id`).

**Media ownership:** A `MediaFile` belongs to exactly one uploader (`user_id`). A post stores media `id` references; multiple posts can reference the same `MediaFile` (e.g. cross-posting). Physical deletion only happens when `media_files.deleted_at` is set AND no `social_posts.media` JSON contains that file's `id`. The `DeleteMediaFileJob` checks this before deleting the physical file.

**Media on reshares:** When a post with media is reshared, the reshare row stores `media = null`. The frontend renders the parent's media from the already-loaded `post.parent` relationship. No new `MediaFile` attachment occurs on reshare; the ownership check is bypassed entirely for reshare operations.

### Storage Backend Driver Mapping

`MediaDiskResolver` service maps `storage_backends.type` + `storage_backends.config` to a Laravel filesystem disk config at runtime. It resolves backends **by `backend_id`** (not just the active one) so that existing `media_files` rows referencing non-active historical backends can still be served. **Registered as a `scoped` binding** (Laravel's `$this->app->scoped()`), so it is resolved once per HTTP request / queue job but refreshes between requests — safe for Octane and Horizon workers.

`MediaStorageService` is a higher-level service that orchestrates the upload flow: validates MIME/size, calls `MediaDiskResolver` to get the correct disk, stores the file, extracts image metadata via `getimagesize()`, creates the `media_files` row, and handles the all-or-nothing cleanup on partial failure. `MediaController` delegates to this service. `MediaDiskResolver` is a focused dependency injected into `MediaStorageService`.

```
local          → Storage::disk built with configured path (files stored outside webroot, never PHP-executable)
s3             → Storage::disk built from key/secret/region/bucket
s3_compatible  → Storage::disk S3 with custom endpoint (R2, MinIO)
backblaze      → S3-compatible using B2 S3 API endpoint
digitalocean   → S3-compatible using DO Spaces endpoint
webdav         → league/flysystem-webdav adapter  [requires: composer require league/flysystem-webdav]
ftp            → Storage::disk('ftp')
sftp           → league/flysystem-sftp-v3 adapter
```

**No active backend:** If no backend has `is_active = true`, `MediaDiskResolver` throws a `NoActiveStorageBackendException`, which the `MediaController` catches and returns as a `503 Service Unavailable` with message "Media storage is not configured."

### Security

- MIME validated server-side via `finfo` (not extension). SVG (`image/svg+xml`) and `text/html` blocked unconditionally regardless of `allowed_mimes` config.
- Polyglot defence: local-disk files stored under `storage/app/media/` (outside `public/` and outside any PHP auto-executed path). Served via a **time-limited signed URL**: `URL::temporarySignedRoute('media.stream', now()->addHour(), ['media' => $media->id])`. `MediaStreamController@stream` verifies the signature, checks that `auth()->id()` (or any authenticated user for public posts) is allowed to view, then streams the file via `response()->streamDownload()`. Signature TTL: 1 hour.
- Credentials in `storage_backends.config` stored via Laravel's `encrypted` cast. Never written to logs.
- Race condition on `is_active`: MySQL does not support partial/conditional indexes — uniqueness is enforced **purely via the application-level transaction** in the `activate` endpoint: `DB::transaction { UPDATE storage_backends SET is_active = 0; UPDATE storage_backends SET is_active = 1 WHERE id = ? }`. No concurrent activate can produce two active backends because both updates are serialized within a single transaction. No DB-level unique constraint exists for this column.
- Rate limiting on upload: named rate limiter registered in `AppServiceProvider` (or `RouteServiceProvider`): `RateLimiter::for('media-upload', fn($req) => Limit::perMinute(config('media.upload_rate', 20))->by($req->user()->id))`. Applied to the upload route via `->middleware('throttle:media-upload')`. Configurable via `MEDIA_UPLOAD_RATE` env → `config('media.upload_rate')`.
- Upload is **all-or-nothing**: if any file fails MIME/size validation the entire request returns 422 and no files are stored. If a file fails mid-storage after previous files were stored, cleanup of already-stored files runs **synchronously** (not queued) — i.e., `MediaStorageService` catches the storage exception, iterates the already-stored paths, and calls `Storage::disk(...)->delete(path)` inline before returning a 500. This makes the all-or-nothing guarantee hold regardless of queue availability.
- Queue fallback: `DeleteMediaFileJob` (for user-initiated soft-delete) is a queued job. If `QUEUE_CONNECTION=sync`, it runs inline. Admin panel shows a warning if `QUEUE_CONNECTION=sync`.

### Admin Authorization

Admin endpoints (`/admin/storage-backends/*`) require: `auth:sanctum` + `$request->user()->is_admin === true`. Applied via route middleware group defined in `MediaServiceProvider::boot()`:
```php
Route::middleware(['auth:sanctum', 'can:platform-admin'])->prefix('api/v1/admin')->group(...);
```
The `platform-admin` gate is registered in `AppServiceProvider`: `Gate::define('platform-admin', fn($user) => $user->is_admin)`. **A migration adding `is_admin` boolean to `users` is included in this sprint** (see file structure below).

### API Endpoints

All under `/api/v1`:

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/media/upload` | sanctum | Upload 1–10 files. Rate: 20/min (named limiter). Returns `[{id, url, mime_type, width, height, duration_seconds}]`. `duration_seconds` is always null in Sprint 3. |
| DELETE | `/media/{id}` | sanctum (owner) | Soft-delete. Queues physical deletion if file unreferenced by any post. |
| GET | `/media/{id}` | signed URL | Stream/serve local-disk file. Requires valid `?signature=` query param (generated by `URL::temporarySignedRoute`, TTL 1 hour). For cloud backends, the accessor returns the direct CDN/S3 URL instead; this route is only reached for `local` backend files. |
| GET | `/admin/storage-backends` | admin | List backends with file counts |
| POST | `/admin/storage-backends` | admin | Create backend |
| PATCH | `/admin/storage-backends/{id}` | admin | Update backend. Warning returned if changing type/path/credentials. |
| DELETE | `/admin/storage-backends/{id}` | admin | Fails with 422 if any `media_files` row has this `backend_id` (shows count). |
| POST | `/admin/storage-backends/{id}/activate` | admin | Set as active (deactivates others atomically in a transaction). |

### Post with Media

`PostController@store` extended to:
- Accept `media` array: `[{id: integer}]`, max 10 items
- Validate each item: `exists:media_files,id` AND `media_files.user_id = auth()->id()` (ownership check — prevents attaching another user's files)
- Validate `media_files.deleted_at IS NULL` (cannot attach soft-deleted files)
- **Hydration step:** Before `Post::create`, each validated `{id}` is resolved to its full shape by loading the `MediaFile` models and mapping to `{id, url, type, mime, width, height, duration}` using `$media->url` accessor (snapshot). This hydrated array is stored as `social_posts.media` JSON.

When a post is soft-deleted, its media files are **not** automatically deleted — they may be referenced by other posts (reshares). The `DeleteMediaFileJob` always checks for live references before physical deletion.

### Admin Panel UI

`StorageBackendManager` React component (admin-only route):
- Table: name, type, file count, active badge
- "Add backend" → modal: Name, Type (dropdown), dynamic credential fields per type, Custom Domain, Max file size, Allowed MIME types
- "Activate" button with confirmation warning: "Switching backends will not move existing files. New uploads will go to the new backend."
- "Update" / "Delete" (delete disabled if files exist, showing count)

### Frontend Components

| Component | Location | Purpose |
|---|---|---|
| `MediaUploader` | `resources/js/plugins/media/MediaUploader.tsx` | Drag-drop / file picker, per-file progress bar, thumbnail previews, error display per file |
| `MediaGrid` | `resources/js/plugins/media/MediaGrid.tsx` | 1 file: full width. 2–4: grid. 5+: grid with "+N more" overlay. Renders placeholder icon when `url` is null. |
| `VideoPlayer` | `resources/js/plugins/media/VideoPlayer.tsx` | HTML5 `<video>` with controls, poster frame |
| `StorageBackendManager` | `resources/js/plugins/media/StorageBackendManager.tsx` | Admin backend config UI |

`PostCard` updated to render `<MediaGrid>` or `<VideoPlayer>` when `post.media` is non-empty.

## File Structure

```
plugins/Media/
  MediaServiceProvider.php
  plugin.json                   { "name": "Media", "requires": ["Post"] }
  Models/
    MediaFile.php               (HasFactory, SoftDeletes, getUrlAttribute accessor)
    StorageBackend.php          (encrypted cast on config)
  Services/
    MediaStorageService.php     (upload orchestration, sync cleanup on partial failure, getimagesize for images)
    MediaDiskResolver.php       (scoped binding, resolves by backend_id)
  Controllers/
    MediaController.php
    MediaStreamController.php   (serves local-disk files via signed route)
    StorageBackendController.php
  Jobs/
    DeleteMediaFileJob.php
  Exceptions/
    NoActiveStorageBackendException.php
  database/migrations/
    2026_04_15_000001_create_storage_backends_table.php
    2026_04_15_000002_create_media_files_table.php
    2026_04_15_000003_add_is_admin_to_users_table.php   ← adds is_admin boolean default false
  routes/
    api.php
resources/js/plugins/media/
  MediaUploader.tsx
  MediaGrid.tsx
  VideoPlayer.tsx
  StorageBackendManager.tsx
tests/Feature/
  MediaUploadTest.php
  StorageBackendTest.php
```

**Composer dependencies to add:**
- `league/flysystem-webdav` — required for WebDAV backend type. Added to `composer.json` in this sprint.

## Testing

**MediaUploadTest:**
- Upload valid image → 200, returns id + url + width + height
- Upload valid video → 200, `duration_seconds` is null (deferred)
- Reject SVG (blocked unconditionally) → 422
- Reject oversized file → 422
- Reject file belonging to another user attached to a post → 422
- Upload 11 files → 422
- No active backend → 503
- All-or-nothing: one invalid file in batch → 422, no files stored
- Partial storage failure → cleanup runs synchronously, no orphaned files
- Null backend (backend_id null): `getUrlAttribute()` returns null, MediaGrid renders placeholder

**StorageBackendTest:**
- Create backend → listed
- Activate sets is_active=true, deactivates others atomically
- Cannot delete backend with files (returns 422 with count)
- Concurrent activate does not produce two active backends (transaction serialization)
- Signed media route: valid signature → streams file; expired/tampered → 403
