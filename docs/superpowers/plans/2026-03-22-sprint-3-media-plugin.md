# Media Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Media plugin that lets members attach photos and videos to posts, backed by admin-configurable storage backends (local, S3, R2, Backblaze, DigitalOcean, WebDAV, FTP, SFTP).

**Architecture:** A `StorageBackend` model stores driver config (encrypted) in the database; `MediaDiskResolver` resolves any backend by ID to a Laravel filesystem disk at runtime; `MediaStorageService` orchestrates all-or-nothing upload validation and storage; `MediaFile` rows record uploads without storing a URL (the accessor computes it from `disk_path` + backend domain at read time). The plugin registers itself in `bootstrap/providers.php`.

**Tech Stack:** Laravel 11, Eloquent encrypted cast, league/flysystem adapters, Laravel signed routes, Pest, React, TypeScript.

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `plugins/Media/MediaServiceProvider.php` | Create | Boot routes, migrations, register scoped MediaDiskResolver |
| `plugins/Media/plugin.json` | Create | Plugin manifest |
| `plugins/Media/Models/StorageBackend.php` | Create | Encrypted config cast, url accessor helper |
| `plugins/Media/Models/MediaFile.php` | Create | SoftDeletes, computed `url` accessor, `backend` relationship |
| `plugins/Media/Services/MediaDiskResolver.php` | Create | Resolves backend row → Laravel disk config by ID |
| `plugins/Media/Services/MediaStorageService.php` | Create | MIME validate, store files, create MediaFile rows, all-or-nothing |
| `plugins/Media/Controllers/MediaController.php` | Create | Upload, soft-delete, signed serve |
| `plugins/Media/Controllers/StorageBackendController.php` | Create | Admin CRUD + activate |
| `plugins/Media/Jobs/DeleteMediaFileJob.php` | Create | Physical deletion after reference check |
| `plugins/Media/Exceptions/NoActiveStorageBackendException.php` | Create | Thrown when no active backend |
| `plugins/Media/database/migrations/2026_04_15_000001_create_storage_backends_table.php` | Create | storage_backends table |
| `plugins/Media/database/migrations/2026_04_15_000002_create_media_files_table.php` | Create | media_files table |
| `plugins/Media/routes/api.php` | Create | All media + admin routes |
| `plugins/Post/Controllers/PostController.php` | Modify | Add media[] validation on store() |
| `bootstrap/providers.php` | Modify | Register MediaServiceProvider |
| `database/factories/MediaFileFactory.php` | Create | Test factory |
| `tests/Feature/StorageBackendTest.php` | Create | Backend CRUD + activate tests |
| `tests/Feature/MediaUploadTest.php` | Create | Upload, validation, all-or-nothing tests |
| `resources/js/plugins/media/MediaUploader.tsx` | Create | Drag-drop uploader with progress + preview |
| `resources/js/plugins/media/MediaGrid.tsx` | Create | Responsive media grid |
| `resources/js/plugins/media/VideoPlayer.tsx` | Create | HTML5 video with controls |
| `resources/js/plugins/media/StorageBackendManager.tsx` | Create | Admin backend config UI |
| `resources/js/plugins/feed/PostCard.tsx` | Modify | Render MediaGrid/VideoPlayer when media present |

---

### Task 1: Migrations — storage_backends and media_files tables

**Files:**
- Create: `plugins/Media/database/migrations/2026_04_15_000001_create_storage_backends_table.php`
- Create: `plugins/Media/database/migrations/2026_04_15_000002_create_media_files_table.php`

> Run all tests with: `./vendor/bin/pest --stop-on-failure`
> The test suite uses SQLite in-memory, so MySQL-specific SQL (partial unique index) must be conditional on DB driver.

- [ ] **Step 1: Create the storage_backends migration**

```php
<?php
// plugins/Media/database/migrations/2026_04_15_000001_create_storage_backends_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_backends', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['local', 's3', 's3_compatible', 'backblaze', 'digitalocean', 'webdav', 'ftp', 'sftp']);
            $table->json('config');           // encrypted at model level
            $table->string('custom_domain')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('max_file_size_kb')->default(20480);
            $table->json('allowed_mimes')->nullable();
            $table->timestamps();
        });

        // Partial unique index: only one active backend at a time.
        // SQLite does not support partial unique indexes — skip for testing env.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE storage_backends ADD UNIQUE INDEX storage_backends_one_active (is_active) USING HASH');
            // MySQL partial: use a generated column trick instead
            // Better: application enforces this; DB unique index on a nullable sentinel column
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_backends');
    }
};
```

> **Note on partial unique index:** MySQL does not natively support `CREATE UNIQUE INDEX … WHERE` (that is PostgreSQL syntax). The spec enforces "only one active backend" via an application-level transaction (deactivate all, then activate one). The DB uniqueness constraint for MySQL uses the activate transaction described in the spec — not a DB partial index. Remove the `DB::statement` block above and rely on the transaction in `StorageBackendController@activate`.

- [ ] **Step 2: Correct the migration — no DB::statement needed**

Replace the migration with this clean version:

```php
<?php
// plugins/Media/database/migrations/2026_04_15_000001_create_storage_backends_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_backends', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['local', 's3', 's3_compatible', 'backblaze', 'digitalocean', 'webdav', 'ftp', 'sftp']);
            $table->json('config');
            $table->string('custom_domain')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('max_file_size_kb')->default(20480);
            $table->json('allowed_mimes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_backends');
    }
};
```

- [ ] **Step 3: Create the media_files migration**

```php
<?php
// plugins/Media/database/migrations/2026_04_15_000002_create_media_files_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('backend_id')
                  ->nullable()
                  ->constrained('storage_backends')
                  ->nullOnDelete();
            $table->string('disk_path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_kb');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
```

- [ ] **Step 4: Create the Media plugin directory structure**

```bash
mkdir -p plugins/Media/{Models,Services,Controllers,Jobs,Exceptions,database/migrations,routes}
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Media/database/migrations/
git commit -m "feat(media): add storage_backends and media_files migrations"
```

---

### Task 2: Models — StorageBackend and MediaFile

**Files:**
- Create: `plugins/Media/Models/StorageBackend.php`
- Create: `plugins/Media/Models/MediaFile.php`
- Create: `database/factories/MediaFileFactory.php`

- [ ] **Step 1: Write failing tests for model behaviour**

```php
<?php
// tests/Feature/MediaUploadTest.php  (partial — just model tests first)
use App\Models\User;
use Plugins\Media\Models\StorageBackend;
use Plugins\Media\Models\MediaFile;

test('MediaFile url uses custom_domain when set', function () {
    $backend = StorageBackend::factory()->create([
        'custom_domain' => 'https://cdn.church.com',
        'config' => ['path' => storage_path('app/media')],
    ]);
    $file = MediaFile::factory()->create([
        'backend_id' => $backend->id,
        'disk_path'  => 'media/photo.jpg',
    ]);

    expect($file->url)->toBe('https://cdn.church.com/media/photo.jpg');
});

test('MediaFile url falls back to Storage when no custom_domain', function () {
    $backend = StorageBackend::factory()->create([
        'custom_domain' => null,
        'type' => 'local',
        'config' => ['path' => storage_path('app/media')],
    ]);
    $file = MediaFile::factory()->create([
        'backend_id' => $backend->id,
        'disk_path'  => 'media/photo.jpg',
    ]);

    expect($file->url)->toBeString()->toContain('photo.jpg');
});
```

- [ ] **Step 2: Run to verify tests fail**

```bash
./vendor/bin/pest tests/Feature/MediaUploadTest.php --stop-on-failure 2>&1 | head -30
```
Expected: FAIL (class not found)

- [ ] **Step 3: Create StorageBackend model**

```php
<?php
// plugins/Media/Models/StorageBackend.php
namespace Plugins\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageBackend extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'config', 'custom_domain', 'is_active', 'max_file_size_kb', 'allowed_mimes'];

    protected $casts = [
        'config'        => 'encrypted:array',
        'allowed_mimes' => 'array',
        'is_active'     => 'boolean',
    ];

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'backend_id');
    }

    /** Default MIME allowlist — SVG and HTML are always blocked at controller level regardless of this. */
    public function getAllowedMimesAttribute(?array $value): array
    {
        return $value ?? ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm'];
    }
}
```

- [ ] **Step 4: Create MediaFile model**

```php
<?php
// plugins/Media/Models/MediaFile.php
namespace Plugins\Media\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'backend_id', 'disk_path', 'mime_type', 'size_kb', 'width', 'height', 'duration_seconds'];

    public function backend(): BelongsTo
    {
        return $this->belongsTo(StorageBackend::class, 'backend_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Compute URL at read-time — no url column stored to prevent stale URLs.
     * Uses custom_domain if set on the backend, otherwise the default Storage disk URL.
     */
    public function getUrlAttribute(): string
    {
        $backend = $this->relationLoaded('backend') ? $this->backend : $this->backend()->first();

        if ($backend && $backend->custom_domain) {
            return rtrim($backend->custom_domain, '/') . '/' . ltrim($this->disk_path, '/');
        }

        return Storage::disk('local')->url($this->disk_path);
    }
}
```

- [ ] **Step 5: Create factories**

```php
<?php
// database/factories/StorageBackendFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Media\Models\StorageBackend;

class StorageBackendFactory extends Factory
{
    protected $model = StorageBackend::class;

    public function definition(): array
    {
        return [
            'name'            => $this->faker->words(2, true),
            'type'            => 'local',
            'config'          => ['path' => storage_path('app/media')],
            'custom_domain'   => null,
            'is_active'       => false,
            'max_file_size_kb' => 20480,
            'allowed_mimes'   => null,
        ];
    }
}
```

```php
<?php
// database/factories/MediaFileFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Media\Models\MediaFile;

class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        return [
            'user_id'   => \App\Models\User::factory(),
            'backend_id' => \Plugins\Media\Models\StorageBackend::factory(),
            'disk_path' => 'media/' . $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size_kb'   => $this->faker->numberBetween(10, 5000),
            'width'     => 1280,
            'height'    => 720,
        ];
    }
}
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/MediaUploadTest.php --filter="MediaFile url" 2>&1 | tail -10
```

- [ ] **Step 7: Commit**

```bash
git add plugins/Media/Models/ database/factories/
git commit -m "feat(media): add StorageBackend and MediaFile models with url accessor"
```

---

### Task 3: MediaDiskResolver and MediaStorageService

**Files:**
- Create: `plugins/Media/Services/MediaDiskResolver.php`
- Create: `plugins/Media/Services/MediaStorageService.php`
- Create: `plugins/Media/Exceptions/NoActiveStorageBackendException.php`

- [ ] **Step 1: Create the exception**

```php
<?php
// plugins/Media/Exceptions/NoActiveStorageBackendException.php
namespace Plugins\Media\Exceptions;

use RuntimeException;

class NoActiveStorageBackendException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Media storage is not configured.');
    }
}
```

- [ ] **Step 2: Create MediaDiskResolver**

```php
<?php
// plugins/Media/Services/MediaDiskResolver.php
namespace Plugins\Media\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Plugins\Media\Exceptions\NoActiveStorageBackendException;
use Plugins\Media\Models\StorageBackend;

class MediaDiskResolver
{
    /**
     * Resolve the active backend to a filesystem disk.
     * Throws NoActiveStorageBackendException if no active backend exists.
     */
    public function active(): FilesystemAdapter
    {
        $backend = StorageBackend::where('is_active', true)->first();
        if (! $backend) {
            throw new NoActiveStorageBackendException();
        }
        return $this->resolve($backend);
    }

    /**
     * Resolve any backend by ID (for serving historical files after backend switch).
     */
    public function forBackend(StorageBackend $backend): FilesystemAdapter
    {
        return $this->resolve($backend);
    }

    private function resolve(StorageBackend $backend): FilesystemAdapter
    {
        $config = $backend->config ?? [];

        return match ($backend->type) {
            'local' => Storage::build([
                'driver' => 'local',
                'root'   => $config['path'] ?? storage_path('app/media'),
                'serve'  => false,
            ]),
            's3', 'backblaze', 'digitalocean', 's3_compatible' => Storage::build([
                'driver'   => 's3',
                'key'      => $config['key'] ?? '',
                'secret'   => $config['secret'] ?? '',
                'region'   => $config['region'] ?? 'us-east-1',
                'bucket'   => $config['bucket'] ?? '',
                'url'      => $config['url'] ?? null,
                'endpoint' => $config['endpoint'] ?? null,
                'use_path_style_endpoint' => (bool) ($config['path_style'] ?? false),
            ]),
            'webdav' => Storage::build([
                'driver' => 'webdav',
                'baseUri' => $config['base_uri'] ?? '',
                'userName' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'pathPrefix' => $config['path_prefix'] ?? '',
            ]),
            'ftp' => Storage::build([
                'driver'   => 'ftp',
                'host'     => $config['host'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'port'     => (int) ($config['port'] ?? 21),
                'root'     => $config['root'] ?? '/',
            ]),
            'sftp' => Storage::build([
                'driver'   => 'sftp',
                'host'     => $config['host'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? null,
                'privateKey' => $config['private_key'] ?? null,
                'port'     => (int) ($config['port'] ?? 22),
                'root'     => $config['root'] ?? '/',
            ]),
            default => throw new \InvalidArgumentException("Unsupported backend type: {$backend->type}"),
        };
    }
}
```

- [ ] **Step 3: Create MediaStorageService**

```php
<?php
// plugins/Media/Services/MediaStorageService.php
namespace Plugins\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Plugins\Media\Models\MediaFile;
use Plugins\Media\Models\StorageBackend;

class MediaStorageService
{
    private const BLOCKED_MIMES = ['image/svg+xml', 'text/html', 'text/xml', 'application/xhtml+xml'];

    public function __construct(private MediaDiskResolver $resolver) {}

    /**
     * Upload 1–10 files. All-or-nothing: if any file fails validation,
     * returns errors without storing anything. If a file fails mid-storage
     * after others have been stored, rolls back stored files.
     *
     * @param  UploadedFile[]  $files
     * @param  int  $userId
     * @return MediaFile[]
     */
    public function upload(array $files, int $userId): array
    {
        $backend = StorageBackend::where('is_active', true)->firstOrFail();
        $disk    = $this->resolver->active();
        $stored  = [];

        $disk = $this->resolver->active();

        try {
            foreach ($files as $file) {
                $this->validateMime($file, $backend);
                $this->validateSize($file, $backend);

                // Use the resolved disk adapter directly — it is not registered as a named disk.
                $path = $disk->putFile('media', $file);
                if (! $path) {
                    throw new \RuntimeException("Failed to store file: {$file->getClientOriginalName()}");
                }

                $stored[] = DB::transaction(function () use ($file, $path, $userId, $backend) {
                    return MediaFile::create([
                        'user_id'    => $userId,
                        'backend_id' => $backend->id,
                        'disk_path'  => $path,
                        'mime_type'  => $file->getMimeType(),
                        'size_kb'    => (int) ceil($file->getSize() / 1024),
                        'width'      => null,
                        'height'     => null,
                    ]);
                });
            }
        } catch (\Throwable $e) {
            // Roll back any files already stored to disk
            foreach ($stored as $mediaFile) {
                $mediaFile->forceDelete();
                // physical cleanup queued via DeleteMediaFileJob
            }
            throw $e;
        }

        return $stored;
    }

    private function validateMime(UploadedFile $file, StorageBackend $backend): void
    {
        $mime = $file->getMimeType();

        // SVG and HTML always blocked regardless of admin config
        if (in_array($mime, self::BLOCKED_MIMES, true)) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['message' => "File type {$mime} is not allowed."], 422)
            );
        }

        $allowed = $backend->allowed_mimes;
        if (! in_array($mime, $allowed, true)) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['message' => "File type {$mime} is not allowed by this backend."], 422)
            );
        }
    }

    private function validateSize(UploadedFile $file, StorageBackend $backend): void
    {
        $sizeKb = ceil($file->getSize() / 1024);
        if ($sizeKb > $backend->max_file_size_kb) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['message' => 'File exceeds the maximum allowed size.'], 422)
            );
        }
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add plugins/Media/Services/ plugins/Media/Exceptions/
git commit -m "feat(media): add MediaDiskResolver, MediaStorageService, NoActiveStorageBackendException"
```

---

### Task 4: DeleteMediaFileJob

**Files:**
- Create: `plugins/Media/Jobs/DeleteMediaFileJob.php`

- [ ] **Step 1: Write the job**

```php
<?php
// plugins/Media/Jobs/DeleteMediaFileJob.php
namespace Plugins\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Plugins\Media\Models\MediaFile;
use Plugins\Media\Services\MediaDiskResolver;

class DeleteMediaFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $mediaFileId) {}

    public function handle(MediaDiskResolver $resolver): void
    {
        $file = MediaFile::withTrashed()->find($this->mediaFileId);
        if (! $file || ! $file->deleted_at) {
            return; // Not soft-deleted — skip
        }

        // Check if any live social_posts reference this file by id
        $referenced = DB::table('social_posts')
            ->whereNull('deleted_at')
            ->whereRaw("JSON_CONTAINS(media, ?, '$')", [json_encode(['id' => $file->id])])
            ->exists();

        if ($referenced) {
            return; // Still referenced — don't delete
        }

        // Physical deletion
        if ($file->backend) {
            try {
                $disk = $resolver->forBackend($file->backend);
                $disk->delete($file->disk_path);
            } catch (\Throwable) {
                // Log but don't fail the job — file may already be gone
            }
        }

        $file->forceDelete();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/Media/Jobs/
git commit -m "feat(media): add DeleteMediaFileJob with reference check before physical deletion"
```

---

### Task 5: MediaController and StorageBackendController

**Files:**
- Create: `plugins/Media/Controllers/MediaController.php`
- Create: `plugins/Media/Controllers/StorageBackendController.php`
- Create: `plugins/Media/routes/api.php`

- [ ] **Step 1: Create MediaController**

```php
<?php
// plugins/Media/Controllers/MediaController.php
namespace Plugins\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Media\Exceptions\NoActiveStorageBackendException;
use Plugins\Media\Jobs\DeleteMediaFileJob;
use Plugins\Media\Models\MediaFile;
use Plugins\Media\Services\MediaStorageService;

class MediaController extends Controller
{
    public function __construct(private MediaStorageService $storage) {}

    /**
     * POST /api/v1/media/upload
     * Upload 1–10 files. Rate: 20/min.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file'],
        ]);

        try {
            $uploaded = $this->storage->upload($request->file('files'), $request->user()->id);
        } catch (NoActiveStorageBackendException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $e->getResponse();
        }

        $result = collect($uploaded)->map(fn ($f) => [
            'id'               => $f->id,
            'url'              => $f->url,
            'mime_type'        => $f->mime_type,
            'width'            => $f->width,
            'height'           => $f->height,
            'duration_seconds' => $f->duration_seconds,
        ]);

        return response()->json($result, 200);
    }

    /**
     * DELETE /api/v1/media/{id}
     * Soft-delete own file. Queues physical deletion.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $file = MediaFile::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $file->delete();

        DeleteMediaFileJob::dispatch($file->id);

        return response()->json(['message' => 'Deleted.']);
    }
}
```

- [ ] **Step 2: Create StorageBackendController**

```php
<?php
// plugins/Media/Controllers/StorageBackendController.php
namespace Plugins\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Media\Models\StorageBackend;

class StorageBackendController extends Controller
{
    public function __construct()
    {
        // All admin endpoints require is_admin = true on the users table.
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->is_admin, 403, 'Admin access required.');
            return $next($request);
        });
    }

    /** GET /api/v1/admin/storage-backends */
    public function index(): JsonResponse
    {
        return response()->json(StorageBackend::withCount('mediaFiles')->get());
    }

    /** POST /api/v1/admin/storage-backends */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'type'             => ['required', 'in:local,s3,s3_compatible,backblaze,digitalocean,webdav,ftp,sftp'],
            'config'           => ['required', 'array'],
            'custom_domain'    => ['nullable', 'url'],
            'max_file_size_kb' => ['nullable', 'integer', 'min:1'],
            'allowed_mimes'    => ['nullable', 'array'],
        ]);

        $backend = StorageBackend::create($data);
        return response()->json($backend, 201);
    }

    /** PATCH /api/v1/admin/storage-backends/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $backend = StorageBackend::findOrFail($id);

        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'max:100'],
            'type'             => ['sometimes', 'in:local,s3,s3_compatible,backblaze,digitalocean,webdav,ftp,sftp'],
            'config'           => ['sometimes', 'array'],
            'custom_domain'    => ['nullable', 'url'],
            'max_file_size_kb' => ['nullable', 'integer', 'min:1'],
            'allowed_mimes'    => ['nullable', 'array'],
        ]);

        $backend->update($data);
        return response()->json($backend);
    }

    /** DELETE /api/v1/admin/storage-backends/{id} */
    public function destroy(int $id): JsonResponse
    {
        $backend = StorageBackend::withCount('mediaFiles')->findOrFail($id);

        if ($backend->media_files_count > 0) {
            return response()->json([
                'message' => "Cannot delete: {$backend->media_files_count} files are stored on this backend.",
            ], 422);
        }

        $backend->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * POST /api/v1/admin/storage-backends/{id}/activate
     * Atomically deactivates all backends and activates this one.
     */
    public function activate(int $id): JsonResponse
    {
        StorageBackend::findOrFail($id);

        DB::transaction(function () use ($id) {
            StorageBackend::query()->update(['is_active' => false]);
            StorageBackend::where('id', $id)->update(['is_active' => true]);
        });

        return response()->json(['message' => 'Backend activated.']);
    }
}
```

- [ ] **Step 3: Create routes**

```php
<?php
// plugins/Media/routes/api.php
use Illuminate\Support\Facades\Route;
use Plugins\Media\Controllers\MediaController;
use Plugins\Media\Controllers\StorageBackendController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/media/upload', [MediaController::class, 'upload'])
        ->middleware('throttle:20,1')
        ->name('api.v1.media.upload');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])
        ->name('api.v1.media.destroy');
});

// Admin routes — require auth + is_admin check (enforced in controller via middleware or gate)
Route::prefix('v1/admin')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/storage-backends', [StorageBackendController::class, 'index']);
    Route::post('/storage-backends', [StorageBackendController::class, 'store']);
    Route::patch('/storage-backends/{id}', [StorageBackendController::class, 'update']);
    Route::delete('/storage-backends/{id}', [StorageBackendController::class, 'destroy']);
    Route::post('/storage-backends/{id}/activate', [StorageBackendController::class, 'activate']);
});
```

- [ ] **Step 4: Commit**

```bash
git add plugins/Media/Controllers/ plugins/Media/routes/
git commit -m "feat(media): add MediaController, StorageBackendController, and routes"
```

---

### Task 6: MediaServiceProvider + plugin.json + register in bootstrap/providers.php

**Files:**
- Create: `plugins/Media/MediaServiceProvider.php`
- Create: `plugins/Media/plugin.json`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Create MediaServiceProvider**

```php
<?php
// plugins/Media/MediaServiceProvider.php
namespace Plugins\Media;

use Illuminate\Support\ServiceProvider;
use Plugins\Media\Services\MediaDiskResolver;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Scoped: resolved once per HTTP request/job, refreshed between requests.
        $this->app->scoped(MediaDiskResolver::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

- [ ] **Step 2: Create plugin.json**

```json
{
  "name": "Media",
  "slug": "media",
  "version": "1.0.0",
  "description": "Photo and video attachments on posts with admin-configurable storage backends.",
  "author": "Church Platform",
  "icon": "photo",
  "category": "Feature",
  "requires": ["Post"],
  "settings_page": true,
  "can_disable": true,
  "can_remove": false,
  "enabled_by_default": true
}
```

- [ ] **Step 3: Register in bootstrap/providers.php**

Add `Plugins\Media\MediaServiceProvider::class` to the array in `bootstrap/providers.php`:

```php
// bootstrap/providers.php — add before the closing ];
Plugins\Media\MediaServiceProvider::class,
```

- [ ] **Step 4: Commit**

```bash
git add plugins/Media/MediaServiceProvider.php plugins/Media/plugin.json bootstrap/providers.php
git commit -m "feat(media): register MediaServiceProvider"
```

---

### Task 7: Extend PostController to accept media[] on store

**Files:**
- Modify: `plugins/Post/Controllers/PostController.php`

- [ ] **Step 1: Write failing test for media on post**

```php
// In tests/Feature/MediaUploadTest.php — add this test:
test('post can be created with media attachment', function () {
    $user    = User::factory()->create();
    $backend = \Plugins\Media\Models\StorageBackend::factory()->create(['is_active' => true]);
    $media   = \Plugins\Media\Models\MediaFile::factory()->create(['user_id' => $user->id, 'backend_id' => $backend->id]);

    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body'  => 'Check this out',
        'media' => [['id' => $media->id]],
    ])->assertStatus(201)->assertJsonPath('media.0.id', $media->id);
});

test('cannot attach another users media to a post', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $media = \Plugins\Media\Models\MediaFile::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body'  => 'Hi',
        'media' => [['id' => $media->id]],
    ])->assertStatus(422);
});
```

- [ ] **Step 2: Run to verify tests fail**

```bash
./vendor/bin/pest tests/Feature/MediaUploadTest.php --filter="post can be" 2>&1 | tail -10
```

- [ ] **Step 3: Modify PostController@store**

In `plugins/Post/Controllers/PostController.php`, update the validation and store logic:

```php
// Replace the $data = $request->validate([...]) block with:
$data = $request->validate([
    'body'                => ['required_without:media', 'nullable', 'string'],
    'media'               => ['nullable', 'array', 'max:10'],
    'media.*.id'          => [
        'required', 'integer',
        \Illuminate\Validation\Rule::exists('media_files', 'id')
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at'),
    ],
    'type'                => ['nullable', 'string'],
    'church_id'           => ['nullable', 'integer', 'exists:churches,id'],
    'community_id'        => ['nullable', 'integer', 'exists:communities,id'],
    'is_anonymous'        => ['boolean'],
    'cross_post_targets'  => ['nullable', 'array'],
    'cross_post_targets.*.community_id' => ['nullable', 'integer', 'exists:communities,id'],
    'cross_post_targets.*.church_id'    => ['nullable', 'integer', 'exists:churches,id'],
]);

// Snapshot media URLs at creation time
if (! empty($data['media'])) {
    $mediaIds = collect($data['media'])->pluck('id');
    $mediaFiles = \Plugins\Media\Models\MediaFile::with('backend')
        ->whereIn('id', $mediaIds)->get()->keyBy('id');

    $data['media'] = $mediaIds->map(fn ($id) => [
        'id'               => $id,
        'url'              => $mediaFiles[$id]->url,
        'type'             => str_starts_with($mediaFiles[$id]->mime_type, 'video/') ? 'video' : 'image',
        'mime'             => $mediaFiles[$id]->mime_type,
        'width'            => $mediaFiles[$id]->width,
        'height'           => $mediaFiles[$id]->height,
        'duration'         => $mediaFiles[$id]->duration_seconds,
    ])->values()->all();
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/MediaUploadTest.php 2>&1 | tail -10
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Post/Controllers/PostController.php
git commit -m "feat(media): extend PostController to accept and snapshot media attachments"
```

---

### Task 8: StorageBackendTest and MediaUploadTest (full coverage)

**Files:**
- Create/expand: `tests/Feature/StorageBackendTest.php`
- Create/expand: `tests/Feature/MediaUploadTest.php`

- [ ] **Step 1: Write StorageBackendTest**

```php
<?php
// tests/Feature/StorageBackendTest.php
use App\Models\User;
use Plugins\Media\Models\StorageBackend;
use Plugins\Media\Models\MediaFile;

test('admin can create a storage backend', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->postJson('/api/v1/admin/storage-backends', [
        'name'   => 'Local',
        'type'   => 'local',
        'config' => ['path' => '/tmp/media'],
    ])->assertStatus(201)->assertJsonFragment(['name' => 'Local']);

    expect(StorageBackend::count())->toBe(1);
});

test('activate sets is_active true and deactivates others atomically', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $a     = StorageBackend::factory()->create(['is_active' => true]);
    $b     = StorageBackend::factory()->create(['is_active' => false]);

    $this->actingAs($admin)->postJson("/api/v1/admin/storage-backends/{$b->id}/activate")
        ->assertStatus(200);

    expect($a->fresh()->is_active)->toBeFalse();
    expect($b->fresh()->is_active)->toBeTrue();
});

test('cannot delete backend with files', function () {
    $admin   = User::factory()->create(['is_admin' => true]);
    $backend = StorageBackend::factory()->create();
    MediaFile::factory()->create(['backend_id' => $backend->id]);

    $this->actingAs($admin)->deleteJson("/api/v1/admin/storage-backends/{$backend->id}")
        ->assertStatus(422)->assertJsonFragment(['1 files']);
});
```

- [ ] **Step 2: Write MediaUploadTest (complete)**

```php
<?php
// tests/Feature/MediaUploadTest.php
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plugins\Media\Models\MediaFile;
use Plugins\Media\Models\StorageBackend;

beforeEach(function () {
    Storage::fake('local');
});

test('upload valid image returns id and url', function () {
    $user    = User::factory()->create();
    StorageBackend::factory()->create(['is_active' => true, 'type' => 'local', 'config' => ['path' => storage_path('app/media')]]);

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->actingAs($user)->postJson('/api/v1/media/upload', [
        'files' => [$file],
    ])->assertStatus(200)->assertJsonStructure([['id', 'url', 'mime_type']]);

    expect(MediaFile::count())->toBe(1);
});

test('upload rejected when no active backend', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('photo.jpg');

    $this->actingAs($user)->postJson('/api/v1/media/upload', [
        'files' => [$file],
    ])->assertStatus(503);
});

test('upload more than 10 files returns 422', function () {
    $user = User::factory()->create();
    StorageBackend::factory()->create(['is_active' => true]);
    $files = array_fill(0, 11, UploadedFile::fake()->image('x.jpg'));

    $this->actingAs($user)->postJson('/api/v1/media/upload', ['files' => $files])
        ->assertStatus(422);
});

test('upload valid video returns 200', function () {
    $user = User::factory()->create();
    StorageBackend::factory()->create(['is_active' => true, 'type' => 'local', 'config' => ['path' => storage_path('app/media')]]);

    $file = UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4');

    $this->actingAs($user)->postJson('/api/v1/media/upload', ['files' => [$file]])
        ->assertStatus(200)->assertJsonPath('0.mime_type', 'video/mp4');
});

test('SVG is rejected unconditionally even if allowed_mimes contains it', function () {
    $user = User::factory()->create();
    StorageBackend::factory()->create([
        'is_active'     => true,
        'allowed_mimes' => ['image/svg+xml', 'image/jpeg'],
    ]);

    $file = UploadedFile::fake()->create('icon.svg', 10, 'image/svg+xml');

    $this->actingAs($user)->postJson('/api/v1/media/upload', ['files' => [$file]])
        ->assertStatus(422);
});

test('oversized file returns 422', function () {
    $user = User::factory()->create();
    StorageBackend::factory()->create([
        'is_active'       => true,
        'max_file_size_kb' => 100,  // 100 KB limit
    ]);
    $file = UploadedFile::fake()->create('big.jpg', 200, 'image/jpeg'); // 200 KB

    $this->actingAs($user)->postJson('/api/v1/media/upload', ['files' => [$file]])
        ->assertStatus(422);
});

test('all-or-nothing: one invalid file in batch means no files stored', function () {
    $user = User::factory()->create();
    StorageBackend::factory()->create(['is_active' => true, 'max_file_size_kb' => 100]);

    $good = UploadedFile::fake()->image('ok.jpg');       // small
    $bad  = UploadedFile::fake()->create('big.jpg', 200, 'image/jpeg'); // oversized

    $this->actingAs($user)->postJson('/api/v1/media/upload', ['files' => [$good, $bad]])
        ->assertStatus(422);

    expect(MediaFile::count())->toBe(0);
});
```

- [ ] **Step 3: Run full test suite**

```bash
./vendor/bin/pest tests/Feature/StorageBackendTest.php tests/Feature/MediaUploadTest.php 2>&1 | tail -20
```
Expected: all tests pass.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/StorageBackendTest.php tests/Feature/MediaUploadTest.php
git commit -m "test(media): add StorageBackendTest and MediaUploadTest"
```

---

### Task 9: Frontend — MediaUploader, MediaGrid, VideoPlayer

**Files:**
- Create: `resources/js/plugins/media/MediaUploader.tsx`
- Create: `resources/js/plugins/media/MediaGrid.tsx`
- Create: `resources/js/plugins/media/VideoPlayer.tsx`

- [ ] **Step 1: Create MediaGrid**

```tsx
// resources/js/plugins/media/MediaGrid.tsx
import React, { useState } from 'react';

interface MediaItem { id: number; url: string; type: 'image' | 'video'; mime: string; width?: number; height?: number; duration?: number }

export default function MediaGrid({ items }: { items: MediaItem[] }) {
    const [lightbox, setLightbox] = useState<MediaItem | null>(null);

    const gridStyle: React.CSSProperties = items.length === 1
        ? { display: 'block' }
        : { display: 'grid', gridTemplateColumns: items.length === 2 ? '1fr 1fr' : '1fr 1fr', gap: 4 };

    const shown = items.slice(0, 4);
    const extra = items.length - 4;

    return (
        <>
            <div style={{ ...gridStyle, borderRadius: 8, overflow: 'hidden', marginTop: 8 }}>
                {shown.map((item, i) => (
                    <div key={item.id} style={{ position: 'relative', cursor: 'pointer' }}
                        onClick={() => setLightbox(item)}>
                        {item.type === 'video'
                            ? <video src={item.url} style={{ width: '100%', maxHeight: 300, objectFit: 'cover', display: 'block' }} />
                            : <img src={item.url} style={{ width: '100%', maxHeight: 300, objectFit: 'cover', display: 'block' }} alt="" />}
                        {i === 3 && extra > 0 && (
                            <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontSize: '1.5rem', fontWeight: 700 }}>
                                +{extra}
                            </div>
                        )}
                    </div>
                ))}
            </div>
            {lightbox && (
                <div onClick={() => setLightbox(null)}
                    style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.85)', zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {lightbox.type === 'video'
                        ? <video src={lightbox.url} controls style={{ maxWidth: '90vw', maxHeight: '90vh' }} />
                        : <img src={lightbox.url} style={{ maxWidth: '90vw', maxHeight: '90vh', objectFit: 'contain' }} alt="" />}
                </div>
            )}
        </>
    );
}
```

- [ ] **Step 2: Create VideoPlayer**

```tsx
// resources/js/plugins/media/VideoPlayer.tsx
import React from 'react';

interface Props { src: string; poster?: string; style?: React.CSSProperties }

export default function VideoPlayer({ src, poster, style }: Props) {
    return (
        <video controls src={src} poster={poster}
            style={{ width: '100%', borderRadius: 8, background: '#000', ...style }}>
            Your browser does not support HTML5 video.
        </video>
    );
}
```

- [ ] **Step 3: Create MediaUploader**

```tsx
// resources/js/plugins/media/MediaUploader.tsx
import React, { useRef, useState } from 'react';

interface UploadedMedia { id: number; url: string; type: 'image' | 'video'; mime: string; width?: number; height?: number; duration?: number }

interface Props { onUpload: (files: UploadedMedia[]) => void; maxFiles?: number }

export default function MediaUploader({ onUpload, maxFiles = 10 }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [previews, setPreviews] = useState<{ file: File; localUrl: string; progress: number; error?: string }[]>([]);
    const [uploading, setUploading] = useState(false);

    async function handleFiles(files: FileList | null) {
        if (!files || files.length === 0) return;
        const arr = Array.from(files).slice(0, maxFiles);
        const preview = arr.map(f => ({ file: f, localUrl: URL.createObjectURL(f), progress: 0 }));
        setPreviews(preview);
        setUploading(true);

        const form = new FormData();
        arr.forEach(f => form.append('files[]', f));

        try {
            const res = await fetch('/api/v1/media/upload', {
                method: 'POST',
                body: form,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error(await res.text());
            const data: UploadedMedia[] = await res.json();
            onUpload(data);
            setPreviews(p => p.map((x, i) => ({ ...x, progress: 100 })));
        } catch (e: any) {
            setPreviews(p => p.map(x => ({ ...x, error: e.message })));
        } finally {
            setUploading(false);
        }
    }

    return (
        <div>
            <div
                onClick={() => inputRef.current?.click()}
                onDragOver={e => e.preventDefault()}
                onDrop={e => { e.preventDefault(); handleFiles(e.dataTransfer.files); }}
                style={{ border: '2px dashed #cbd5e1', borderRadius: 8, padding: '1.5rem', textAlign: 'center', cursor: 'pointer', color: '#64748b' }}>
                <span>📷 Drop photos/videos here or click to browse</span>
                <input ref={inputRef} type="file" multiple accept="image/*,video/mp4,video/webm"
                    style={{ display: 'none' }} onChange={e => handleFiles(e.target.files)} />
            </div>
            {previews.length > 0 && (
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginTop: 8 }}>
                    {previews.map((p, i) => (
                        <div key={i} style={{ position: 'relative', width: 80, height: 80 }}>
                            <img src={p.localUrl} style={{ width: 80, height: 80, objectFit: 'cover', borderRadius: 6 }} alt="" />
                            {p.error && <div style={{ position: 'absolute', inset: 0, background: 'rgba(239,68,68,.7)', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 10, color: '#fff', padding: 4 }}>Error</div>}
                            {!p.error && p.progress < 100 && (
                                <div style={{ position: 'absolute', bottom: 0, left: 0, right: 0, height: 4, background: '#e2e8f0', borderRadius: '0 0 6px 6px' }}>
                                    <div style={{ width: `${p.progress}%`, height: '100%', background: '#2563eb', transition: 'width .3s' }} />
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
```

- [ ] **Step 4: Verify Vite builds cleanly**

```bash
npm run build 2>&1 | tail -10
```
Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/plugins/media/
git commit -m "feat(media): add MediaGrid, VideoPlayer, MediaUploader frontend components"
```

---

### Task 10: StorageBackendManager admin UI + PostCard media rendering

**Files:**
- Create: `resources/js/plugins/media/StorageBackendManager.tsx`
- Modify: `resources/js/plugins/feed/PostCard.tsx`

- [ ] **Step 1: Create StorageBackendManager**

```tsx
// resources/js/plugins/media/StorageBackendManager.tsx
import React, { useEffect, useState } from 'react';

interface Backend { id: number; name: string; type: string; is_active: boolean; media_files_count: number; custom_domain: string | null }

export default function StorageBackendManager() {
    const [backends, setBackends] = useState<Backend[]>([]);
    const [loading, setLoading] = useState(true);

    async function load() {
        const res = await fetch('/api/v1/admin/storage-backends');
        setBackends(await res.json());
        setLoading(false);
    }

    async function activate(id: number) {
        await fetch(`/api/v1/admin/storage-backends/${id}/activate`, { method: 'POST' });
        load();
    }

    async function deleteBackend(id: number) {
        if (!confirm('Delete this backend?')) return;
        await fetch(`/api/v1/admin/storage-backends/${id}`, { method: 'DELETE' });
        load();
    }

    useEffect(() => { load(); }, []);

    if (loading) return <div style={{ padding: '1rem', color: '#64748b' }}>Loading…</div>;

    return (
        <div style={{ padding: '1rem' }}>
            <h2 style={{ fontSize: '1.1rem', fontWeight: 700, marginBottom: '1rem' }}>Storage Backends</h2>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.875rem' }}>
                <thead>
                    <tr style={{ borderBottom: '2px solid #e2e8f0', textAlign: 'left' }}>
                        <th style={{ padding: '0.5rem' }}>Name</th>
                        <th style={{ padding: '0.5rem' }}>Type</th>
                        <th style={{ padding: '0.5rem' }}>Files</th>
                        <th style={{ padding: '0.5rem' }}>Status</th>
                        <th style={{ padding: '0.5rem' }}>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {backends.map(b => (
                        <tr key={b.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                            <td style={{ padding: '0.5rem' }}>{b.name}</td>
                            <td style={{ padding: '0.5rem', textTransform: 'uppercase', fontSize: '0.75rem', color: '#64748b' }}>{b.type}</td>
                            <td style={{ padding: '0.5rem' }}>{b.media_files_count}</td>
                            <td style={{ padding: '0.5rem' }}>
                                {b.is_active
                                    ? <span style={{ background: '#dcfce7', color: '#15803d', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem' }}>Active</span>
                                    : <span style={{ color: '#94a3b8', fontSize: '0.75rem' }}>Inactive</span>}
                            </td>
                            <td style={{ padding: '0.5rem', display: 'flex', gap: 8 }}>
                                {!b.is_active && (
                                    <button onClick={() => activate(b.id)}
                                        style={{ fontSize: '0.75rem', background: '#2563eb', color: '#fff', border: 'none', borderRadius: 4, padding: '2px 10px', cursor: 'pointer' }}>
                                        Activate
                                    </button>
                                )}
                                <button onClick={() => deleteBackend(b.id)} disabled={b.media_files_count > 0}
                                    style={{ fontSize: '0.75rem', background: b.media_files_count > 0 ? '#e2e8f0' : '#fee2e2', color: b.media_files_count > 0 ? '#94a3b8' : '#dc2626', border: 'none', borderRadius: 4, padding: '2px 10px', cursor: b.media_files_count > 0 ? 'not-allowed' : 'pointer' }}>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
```

- [ ] **Step 2: Update PostCard to render media**

In `resources/js/plugins/feed/PostCard.tsx`, add `import MediaGrid from '../media/MediaGrid';` at the top, extend the `Post` interface to include `media?: MediaItem[]`, and add `{post.media && post.media.length > 0 && <MediaGrid items={post.media} />}` after the SafeHtml body block.

- [ ] **Step 3: Verify Vite builds cleanly**

```bash
npm run build 2>&1 | tail -10
```

- [ ] **Step 4: Run full test suite**

```bash
./vendor/bin/pest --stop-on-failure 2>&1 | tail -15
```
Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/plugins/media/StorageBackendManager.tsx resources/js/plugins/feed/PostCard.tsx
git commit -m "feat(media): add StorageBackendManager admin UI and wire MediaGrid into PostCard"
```
