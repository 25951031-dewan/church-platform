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
| is_active | bool default false | Enforced unique: DB partial index `WHERE is_active = true` ensures only one active backend at a time |
| max_file_size_kb | int default 20480 | Per-file limit (default 20 MB) |
| allowed_mimes | json | Default: `["image/jpeg","image/png","image/webp","image/gif","video/mp4","video/webm"]`. SVG and HTML are **never permitted** regardless of admin config — hard-blocked at the validator level. |
| created_at/updated_at | timestamps | |

`media_files`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users cascadeOnDelete | Uploader |
| backend_id | FK → storage_backends nullOnDelete | Which backend stored this file. NullOnDelete (not cascade) — preserves row for cleanup tracking. |
| disk_path | string | Path on the storage disk |
| mime_type | string | Validated via `finfo` server-side, not extension |
| size_kb | int | File size in KB |
| width | int nullable | Image/video width px |
| height | int nullable | Image/video height px |
| duration_seconds | int nullable | Video duration |
| deleted_at | timestamp nullable | Soft delete. Physical deletion queued on soft-delete. |
| created_at/updated_at | timestamps | |

**No `url` column** — URLs are **computed at read time** from `disk_path` + the backend's `custom_domain` (or `Storage::disk()->url(disk_path)` if no custom domain). This prevents stale URLs when admins change backends or CDN domains. A `MediaFile::getUrlAttribute()` accessor computes the URL on the fly.

**Existing column used:** `social_posts.media` (JSON, already in schema) — stores an array of `{id, url, type, mime, width?, height?, duration?}`. `url` in the JSON is a snapshot at post-creation time for display; the authoritative URL is always `MediaFile::url` accessor. The `id` field allows refreshing the URL if needed.

**Media ownership:** A `MediaFile` belongs to exactly one uploader (`user_id`). A post stores media `id` references; multiple posts can reference the same `MediaFile` (e.g. cross-posting). Physical deletion only happens when `media_files.deleted_at` is set AND no `social_posts.media` JSON contains that file's `id`. The `DeleteMediaFileJob` checks this before deleting the physical file.

### Storage Backend Driver Mapping

`MediaDiskResolver` service maps `storage_backends.type` + `storage_backends.config` to a Laravel filesystem disk config at runtime. It resolves backends **by `backend_id`** (not just the active one) so that existing `media_files` rows referencing non-active historical backends can still be served. **Registered as a `scoped` binding** (Laravel's `$this->app->scoped()`), so it is resolved once per HTTP request / queue job but refreshes between requests — safe for Octane and Horizon workers.

`MediaStorageService` is a higher-level service that orchestrates the upload flow: validates MIME/size, calls `MediaDiskResolver` to get the correct disk, stores the file, creates the `media_files` row, and handles the all-or-nothing cleanup on partial failure. `MediaController` delegates to this service. `MediaDiskResolver` is a focused dependency injected into `MediaStorageService`.

```
local          → Storage::disk built with configured path (files stored outside webroot, never PHP-executable)
s3             → Storage::disk built from key/secret/region/bucket
s3_compatible  → Storage::disk S3 with custom endpoint (R2, MinIO)
backblaze      → S3-compatible using B2 S3 API endpoint
digitalocean   → S3-compatible using DO Spaces endpoint
webdav         → league/flysystem-webdav adapter
ftp            → Storage::disk('ftp')
sftp           → league/flysystem-sftp-v3 adapter
```

**No active backend:** If no backend has `is_active = true`, `MediaDiskResolver` throws a `NoActiveStorageBackendException`, which the `MediaController` catches and returns as a `503 Service Unavailable` with message "Media storage is not configured."

### Security

- MIME validated server-side via `finfo` (not extension). SVG (`image/svg+xml`) and `text/html` blocked unconditionally regardless of `allowed_mimes` config.
- Polyglot defence: local-disk files stored under `storage/app/media/` (outside `public/` and outside any PHP auto-executed path). Served via a signed `GET /media/{id}` route — never directly accessible.
- Credentials in `storage_backends.config` stored via Laravel's `encrypted` cast. Never written to logs.
- Race condition on `is_active`: the DB has a unique partial index `(is_active) WHERE is_active = true`. The `activate` endpoint uses a transaction: `UPDATE storage_backends SET is_active = false` then `UPDATE SET is_active = true` on the target row.
- Rate limiting on upload: `throttle:20,1` (20 uploads per minute per user). Configurable via `MEDIA_UPLOAD_RATE` env.
- Upload is **all-or-nothing**: files are stored one by one; if any file fails MIME/size validation the entire request returns 422 and no files are stored. If a file fails mid-storage after previous files were stored, a cleanup job removes the already-stored files.
- Queue fallback: if no queue worker is running, `DeleteMediaFileJob` and cleanup jobs should be dispatched with `onConnection('sync')` as a fallback. Admin panel shows a warning if `QUEUE_CONNECTION=sync`.

### API Endpoints

All under `/api/v1`:

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/media/upload` | sanctum | Upload 1–10 files. Rate: 20/min. Returns `[{id, url, mime_type, width, height, duration_seconds}]` |
| DELETE | `/media/{id}` | sanctum (owner) | Soft-delete. Queues physical deletion if file unreferenced by any post. |
| GET | `/admin/storage-backends` | admin | List backends |
| POST | `/admin/storage-backends` | admin | Create backend |
| PATCH | `/admin/storage-backends/{id}` | admin | Update backend. Warning returned if changing type/path/credentials. |
| DELETE | `/admin/storage-backends/{id}` | admin | Fails with 422 if any `media_files` row has this `backend_id`. |
| POST | `/admin/storage-backends/{id}/activate` | admin | Set as active (deactivates others atomically). |

### Post with Media

`PostController@store` extended to:
- Accept `media` array: `[{id: integer}]`, max 10 items
- Validate each item: `exists:media_files,id` AND `media_files.user_id = auth()->id()` (ownership check — prevents attaching another user's files)
- Validate `media_files.deleted_at IS NULL` (cannot attach soft-deleted files)
- Snapshot `url` from `MediaFile::url` accessor into `social_posts.media` JSON at post-creation time

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
| `MediaGrid` | `resources/js/plugins/media/MediaGrid.tsx` | 1 file: full width. 2–4: grid. 5+: grid with "+N more" overlay |
| `VideoPlayer` | `resources/js/plugins/media/VideoPlayer.tsx` | HTML5 `<video>` with controls, poster frame |
| `StorageBackendManager` | `resources/js/plugins/media/StorageBackendManager.tsx` | Admin backend config UI |

`PostCard` updated to render `<MediaGrid>` or `<VideoPlayer>` when `post.media` is non-empty.

## File Structure

```
plugins/Media/
  MediaServiceProvider.php
  plugin.json                   { "name": "Media", "requires": ["Post"] }
  Models/
    MediaFile.php               (HasFactory, SoftDeletes, encrypted config, url accessor)
    StorageBackend.php          (encrypted cast on config)
  Services/
    MediaStorageService.php
    MediaDiskResolver.php       (scoped binding)
  Controllers/
    MediaController.php
    StorageBackendController.php
  Jobs/
    DeleteMediaFileJob.php
  Exceptions/
    NoActiveStorageBackendException.php
  database/migrations/
    2026_04_15_000001_create_storage_backends_table.php
    2026_04_15_000002_create_media_files_table.php
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

## Testing

**MediaUploadTest:**
- Upload valid image → 200, returns id + url
- Upload valid video → 200
- Reject SVG (blocked unconditionally) → 422
- Reject oversized file → 422
- Reject file belonging to another user attached to a post → 422
- Upload 11 files → 422
- No active backend → 503
- All-or-nothing: one invalid file in batch → 422, no files stored

**StorageBackendTest:**
- Create backend → listed
- Activate sets is_active=true, deactivates others atomically
- Cannot delete backend with files (returns 422 with count)
- Concurrent activate does not produce two active backends (unique constraint test)
