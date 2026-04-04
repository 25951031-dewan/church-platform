# Plan 7: Library Plugin — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Library plugin — a book catalog with hierarchical categories, PDF viewing, role-based download permissions, and search. Books have covers, PDFs, metadata (author, publisher, ISBN, page count), view/download tracking, and featured/active flags.

**Architecture:** Follows the established plugin pattern: `app/Plugins/Library/` with Loader, Crupdate, Paginate, Delete services, Policy, form request, and a PermissionSeeder. A new migration creates `books` and `book_categories` tables. The plugin model replaces the legacy `App\Models\Book` (same concept, different table structure — the legacy model used `$fillable` and the old migration system; the plugin model uses `$guarded = ['id']` and the v5 `0007_*` migration series). `BookCategory` supports hierarchical nesting via `parent_id`. The frontend provides a catalog page (grid/list toggle, category sidebar, search) and a book detail page with metadata and download button.

**Key Design Decisions:**
1. **Separate `book_categories` table.** The spec defines `book_categories` as its own table with `parent_id` for hierarchy, not sharing with the existing `App\Models\Category`. This keeps the Library plugin self-contained.
2. **No PDF.js in V1.** The spec mentions PDF.js reader with page-flip — this is a frontend-heavy feature better added as an enhancement. V1 provides download + external PDF viewing (browser's native PDF viewer via link). The detail page shows book metadata, cover, description, and download/view buttons. The `library.read` permission is seeded now for forward compatibility — it will gate the online reader when added.
3. **Download permission gated.** `library.download` permission controls who can download PDFs. View (catalog browsing) is gated by `library.view`. Reading online (future PDF.js) will use `library.read`.
4. **Church-scoped books.** Books have an optional `church_id` FK. When null, they're platform-wide.
5. **Legacy coexistence.** The legacy `App\Models\Book` and `App\Http\Controllers\Api\BookController` remain untouched for backward compatibility. The plugin adds the community-facing catalog features.
6. **SEO pre-rendering deferred.** The spec requires `/library/{slug}` pre-rendered with Book schema.org structured data. This is a cross-cutting concern (shared with Church Builder, Sermons, etc.) that will be addressed in a dedicated SEO plan. No web routes for pre-rendering are included here.
7. **Bulk upload deferred.** The spec's admin section mentions bulk upload. This requires a file import pipeline (CSV or multi-file PDF upload) that is better added as an enhancement after the core CRUD is stable.

**Tech Stack:** Laravel 12 plugin, Eloquent, TanStack React Query, Tailwind CSS.

**Spec:** `docs/superpowers/specs/2026-03-28-church-community-platform-design.md` (Library Plugin section)

**Depends on:** Plan 2 (Reactions/Comments) — specifically: `HasReactions` trait, morph map pattern, plugin loading via `PluginManager`, permission seeder pattern.

---

## File Structure Overview

```
app/Plugins/Library/
├── Models/
│   ├── Book.php                        # Plugin model with HasReactions, categories, scopes
│   └── BookCategory.php                # Hierarchical category (parent_id)
├── Services/
│   ├── BookLoader.php                  # API response formatting (detail with counts)
│   ├── PaginateBooks.php               # Catalog with category/search/featured filters
│   ├── CrupdateBook.php                # Create/update books
│   ├── DeleteBooks.php                 # Delete books + cleanup reactions
│   ├── PaginateBookCategories.php      # Category listing with book counts
│   └── CrupdateBookCategory.php        # Create/update categories
├── Controllers/
│   ├── BookController.php              # Book CRUD + download tracking
│   └── BookCategoryController.php      # Category CRUD
├── Policies/
│   └── BookPolicy.php                  # CRUD + download + manage_categories
├── Requests/
│   ├── ModifyBook.php                  # Validation for books
│   └── ModifyBookCategory.php          # Validation for categories
├── Routes/
│   └── api.php                         # Plugin routes
├── Database/
│   └── Seeders/
│       └── LibraryPermissionSeeder.php # 7 permissions across roles

database/
├── migrations/
│   └── 0007_01_01_000000_create_library_tables.php
├── factories/
│   ├── BookFactory.php
│   └── BookCategoryFactory.php

tests/Feature/Library/
├── BookTest.php                        # 7 tests
├── BookCategoryTest.php                # 4 tests
└── BookDownloadTest.php                # 3 tests

resources/client/
├── plugins/library/
│   ├── queries.ts                      # TanStack Query hooks + types
│   ├── pages/
│   │   ├── LibraryCatalogPage.tsx      # Grid/list catalog with category sidebar + search
│   │   └── BookDetailPage.tsx          # Cover, metadata, description, download
│   └── components/
│       ├── BookCard.tsx                # Catalog card (cover, title, author)
│       └── CategorySidebar.tsx         # Hierarchical category tree
```

---

## Tasks

### Task 1: Migration — create books and book_categories tables

**Files:**
- Create: `database/migrations/0007_01_01_000000_create_library_tables.php`

- [ ] **Step 1:** Create migration `0007_01_01_000000_create_library_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('cover')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->integer('pages_count')->nullable();
            $table->year('published_year')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_categories');
    }
};
```

- [ ] **Step 2:** Verify syntax: `php -l database/migrations/0007_01_01_000000_create_library_tables.php`

---

### Task 2: Models — Book, BookCategory

**Files:**
- Create: `app/Plugins/Library/Models/Book.php`
- Create: `app/Plugins/Library/Models/BookCategory.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Models/Book.php`

```php
<?php

namespace App\Plugins\Library\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'pages_count' => 'integer',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\BookFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Book $book) {
            if (empty($book->slug)) {
                $slug = Str::slug($book->title);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $book->slug = $slug;
            }
        });
    }

    // --- Relationships ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'uploaded_by');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(\App\Plugins\ChurchBuilder\Models\Church::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->uploaded_by === $userId;
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    public function incrementDownload(): void
    {
        $this->increment('download_count');
    }

    public function hasPdf(): bool
    {
        return !empty($this->pdf_path);
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Library/Models/BookCategory.php`

```php
<?php

namespace App\Plugins\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\BookCategoryFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (BookCategory $category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $category->slug = $slug;
            }
        });
    }

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BookCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'category_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l app/Plugins/Library/Models/Book.php && php -l app/Plugins/Library/Models/BookCategory.php
```

---

### Task 3: Services — BookLoader, PaginateBooks, CrupdateBook, DeleteBooks

**Files:**
- Create: `app/Plugins/Library/Services/BookLoader.php`
- Create: `app/Plugins/Library/Services/PaginateBooks.php`
- Create: `app/Plugins/Library/Services/CrupdateBook.php`
- Create: `app/Plugins/Library/Services/DeleteBooks.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Services/BookLoader.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class BookLoader
{
    public function load(Book $book): Book
    {
        return $book->load([
            'category:id,name,slug',
            'uploader:id,name,avatar',
        ])->loadCount('reactions');
    }

    public function loadForDetail(Book $book): array
    {
        $this->load($book);

        $data = $book->toArray();
        $data['has_pdf'] = $book->hasPdf();

        $userId = auth()->id();
        if ($userId) {
            $data['can_download'] = auth()->user()->hasPermission('library.download');
        }

        return $data;
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Library/Services/PaginateBooks.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateBooks
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Book::query()
            ->active()
            ->with(['category:id,name,slug', 'uploader:id,name,avatar'])
            ->withCount('reactions');

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->has('category_id')) {
            $query->byCategory((int) $request->input('category_id'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $orderBy = $request->input('order_by', 'created_at');
        $orderDir = $request->input('order_dir', 'desc');
        $allowedOrders = ['created_at', 'title', 'author', 'view_count', 'download_count'];

        if (in_array($orderBy, $allowedOrders)) {
            $query->orderBy($orderBy, $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        return $query->paginate(min((int) $request->input('per_page', 12), 50));
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Library/Services/CrupdateBook.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class CrupdateBook
{
    public function execute(array $data, ?Book $book = null): Book
    {
        $fields = [
            'title', 'slug', 'author', 'description', 'content',
            'cover', 'pdf_path', 'isbn', 'publisher', 'pages_count',
            'published_year', 'category_id', 'is_featured', 'is_active',
            'meta_title', 'meta_description',
        ];

        if ($book) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $book->update($updateData);
        } else {
            $createData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            if (isset($data['uploaded_by'])) {
                $createData['uploaded_by'] = $data['uploaded_by'];
            }
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            $book = Book::create($createData);
        }

        return $book;
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/Library/Services/DeleteBooks.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;

class DeleteBooks
{
    public function execute(array $ids): void
    {
        $books = Book::whereIn('id', $ids)->get();

        foreach ($books as $book) {
            $book->reactions()->delete();
            $book->delete();
        }
    }
}
```

- [ ] **Step 5:** Verify syntax:

```bash
php -l app/Plugins/Library/Services/BookLoader.php && \
php -l app/Plugins/Library/Services/PaginateBooks.php && \
php -l app/Plugins/Library/Services/CrupdateBook.php && \
php -l app/Plugins/Library/Services/DeleteBooks.php
```

---

### Task 4: Category Services — PaginateBookCategories, CrupdateBookCategory

**Files:**
- Create: `app/Plugins/Library/Services/PaginateBookCategories.php`
- Create: `app/Plugins/Library/Services/CrupdateBookCategory.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Services/PaginateBookCategories.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\BookCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaginateBookCategories
{
    /**
     * Returns a flat list of all active categories with book counts,
     * ordered for tree rendering (roots + children by sort_order).
     */
    public function execute(Request $request): Collection
    {
        $query = BookCategory::query()
            ->withCount(['books' => fn ($q) => $q->where('is_active', true)]);

        if (!$request->boolean('include_inactive')) {
            $query->active();
        }

        return $query->orderBy('parent_id')->orderBy('sort_order')->get();
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Library/Services/CrupdateBookCategory.php`

```php
<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\BookCategory;

class CrupdateBookCategory
{
    public function execute(array $data, ?BookCategory $category = null): BookCategory
    {
        $fields = ['name', 'slug', 'description', 'image', 'parent_id', 'sort_order', 'is_active'];

        if ($category) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $category->update($updateData);
        } else {
            $createData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $category = BookCategory::create($createData);
        }

        return $category;
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l app/Plugins/Library/Services/PaginateBookCategories.php && \
php -l app/Plugins/Library/Services/CrupdateBookCategory.php
```

---

### Task 5: Policy — BookPolicy

**Files:**
- Create: `app/Plugins/Library/Policies/BookPolicy.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Policies/BookPolicy.php`

```php
<?php

namespace App\Plugins\Library\Policies;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use Common\Core\BasePolicy;

class BookPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('library.view');
    }

    public function view(?User $user, Book $book): bool
    {
        // Active books visible to anyone with library.view
        if ($book->is_active) {
            return $user ? $user->hasPermission('library.view') : false;
        }
        // Inactive books: uploader or admin only
        if ($user && $book->isOwnedBy($user->id)) {
            return true;
        }
        return $user ? $user->hasPermission('library.update') : false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('library.create');
    }

    public function update(User $user, Book $book): bool
    {
        return $user->hasPermission('library.update');
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->hasPermission('library.delete');
    }

    // library.read permission is seeded but not gated here yet —
    // it will gate the online PDF.js reader when that feature is added.

    public function download(User $user, Book $book): bool
    {
        if (!$book->is_active) {
            return false;
        }
        return $user->hasPermission('library.download');
    }

    public function manageCategories(User $user): bool
    {
        return $user->hasPermission('library.manage_categories');
    }
}
```

- [ ] **Step 2:** Verify syntax: `php -l app/Plugins/Library/Policies/BookPolicy.php`

---

### Task 6: Form Requests — ModifyBook, ModifyBookCategory

**Files:**
- Create: `app/Plugins/Library/Requests/ModifyBook.php`
- Create: `app/Plugins/Library/Requests/ModifyBookCategory.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Requests/ModifyBook.php`

```php
<?php

namespace App\Plugins\Library\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyBook extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:10000',
            'content' => 'nullable|string',
            'cover' => 'nullable|string|max:500',
            'pdf_path' => 'nullable|string|max:500',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'pages_count' => 'nullable|integer|min:1',
            'published_year' => 'nullable|integer|min:1000|max:2100',
            'category_id' => 'nullable|integer|exists:book_categories,id',
            'church_id' => 'nullable|integer|exists:churches,id',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Library/Requests/ModifyBookCategory.php`

```php
<?php

namespace App\Plugins\Library\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyBookCategory extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'parent_id' => 'nullable|integer|exists:book_categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l app/Plugins/Library/Requests/ModifyBook.php && \
php -l app/Plugins/Library/Requests/ModifyBookCategory.php
```

---

### Task 7: Controllers — BookController, BookCategoryController

**Files:**
- Create: `app/Plugins/Library/Controllers/BookController.php`
- Create: `app/Plugins/Library/Controllers/BookCategoryController.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Controllers/BookController.php`

```php
<?php

namespace App\Plugins\Library\Controllers;

use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Requests\ModifyBook;
use App\Plugins\Library\Services\BookLoader;
use App\Plugins\Library\Services\CrupdateBook;
use App\Plugins\Library\Services\DeleteBooks;
use App\Plugins\Library\Services\PaginateBooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class BookController extends Controller
{
    public function __construct(
        private BookLoader $loader,
        private CrupdateBook $crupdate,
        private PaginateBooks $paginator,
        private DeleteBooks $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Book::class);
        $books = $this->paginator->execute($request);
        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        Gate::authorize('view', $book);
        $book->incrementView();
        return response()->json(['book' => $this->loader->loadForDetail($book)]);
    }

    public function store(ModifyBook $request): JsonResponse
    {
        Gate::authorize('create', Book::class);

        $data = $request->validated();
        $data['uploaded_by'] = $request->user()->id;

        $book = $this->crupdate->execute($data);

        return response()->json([
            'book' => $this->loader->loadForDetail($book),
        ], 201);
    }

    public function update(ModifyBook $request, Book $book): JsonResponse
    {
        Gate::authorize('update', $book);

        $book = $this->crupdate->execute($request->validated(), $book);

        return response()->json([
            'book' => $this->loader->loadForDetail($book),
        ]);
    }

    public function destroy(Book $book): JsonResponse
    {
        Gate::authorize('delete', $book);

        $this->deleter->execute([$book->id]);

        return response()->noContent();
    }

    public function trackDownload(Book $book): JsonResponse
    {
        Gate::authorize('download', $book);

        $book->incrementDownload();

        return response()->json([
            'pdf_path' => $book->pdf_path,
            'download_count' => $book->download_count,
        ]);
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Library/Controllers/BookCategoryController.php`

```php
<?php

namespace App\Plugins\Library\Controllers;

use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use App\Plugins\Library\Requests\ModifyBookCategory;
use App\Plugins\Library\Services\CrupdateBookCategory;
use App\Plugins\Library\Services\PaginateBookCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class BookCategoryController extends Controller
{
    public function __construct(
        private PaginateBookCategories $paginator,
        private CrupdateBookCategory $crupdate,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Book::class);
        $categories = $this->paginator->execute($request);
        return response()->json(['categories' => $categories]);
    }

    public function store(ModifyBookCategory $request): JsonResponse
    {
        Gate::authorize('manageCategories', Book::class);

        $category = $this->crupdate->execute($request->validated());

        return response()->json(['category' => $category], 201);
    }

    public function update(ModifyBookCategory $request, BookCategory $bookCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Book::class);

        $category = $this->crupdate->execute($request->validated(), $bookCategory);

        return response()->json(['category' => $category]);
    }

    public function destroy(BookCategory $bookCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Book::class);

        // Unlink books from this category before deleting
        Book::where('category_id', $bookCategory->id)->update(['category_id' => null]);
        $bookCategory->delete();

        return response()->noContent();
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l app/Plugins/Library/Controllers/BookController.php && \
php -l app/Plugins/Library/Controllers/BookCategoryController.php
```

---

### Task 8: Routes + Permission Seeder

**Files:**
- Create: `app/Plugins/Library/Routes/api.php`
- Create: `app/Plugins/Library/Database/Seeders/LibraryPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/Library/Routes/api.php`

```php
<?php

use App\Plugins\Library\Controllers\BookController;
use App\Plugins\Library\Controllers\BookCategoryController;
use Illuminate\Support\Facades\Route;

// Books CRUD
Route::get('books', [BookController::class, 'index']);
Route::get('books/{book}', [BookController::class, 'show']);
Route::post('books', [BookController::class, 'store']);
Route::put('books/{book}', [BookController::class, 'update']);
Route::delete('books/{book}', [BookController::class, 'destroy']);

// Download tracking
Route::post('books/{book}/download', [BookController::class, 'trackDownload']);

// Book categories
Route::get('book-categories', [BookCategoryController::class, 'index']);
Route::post('book-categories', [BookCategoryController::class, 'store']);
Route::put('book-categories/{bookCategory}', [BookCategoryController::class, 'update']);
Route::delete('book-categories/{bookCategory}', [BookCategoryController::class, 'destroy']);
```

- [ ] **Step 2:** Create `app/Plugins/Library/Database/Seeders/LibraryPermissionSeeder.php`

```php
<?php

namespace App\Plugins\Library\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class LibraryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'library' => [
                'library.view' => 'Browse Library',
                'library.read' => 'Read Books Online',
                'library.download' => 'Download Books',
                'library.create' => 'Add Books',
                'library.update' => 'Edit Books',
                'library.delete' => 'Delete Books',
                'library.manage_categories' => 'Manage Book Categories',
            ],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $name => $displayName) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    ['display_name' => $displayName, 'group' => $group, 'type' => 'global']
                );
            }
        }

        // Members: browse + read + download
        $memberPerms = Permission::whereIn('name', [
            'library.view', 'library.read', 'library.download',
        ])->pluck('id');

        // Moderators: members + create + update
        $moderatorPerms = Permission::whereIn('name', [
            'library.view', 'library.read', 'library.download',
            'library.create', 'library.update',
        ])->pluck('id');

        // All permissions
        $allPerms = Permission::where('group', 'library')->pluck('id');

        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['moderator', 'ministry-leader'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
        }

        foreach (['member'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l app/Plugins/Library/Routes/api.php && \
php -l app/Plugins/Library/Database/Seeders/LibraryPermissionSeeder.php
```

---

### Task 9: Integration — AppServiceProvider, routes/api.php, morph map, ReactionController

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/api.php`
- Modify: `common/foundation/src/Reactions/Controllers/ReactionController.php` (add `book` to allowlist)

- [ ] **Step 1:** Add Book imports and policy to `app/Providers/AppServiceProvider.php`

Add these imports at the top (after the existing Prayer imports):

```php
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Policies\BookPolicy;
```

Add this Gate policy registration in `boot()` (after the ChurchPage policy line):

```php
Gate::policy(Book::class, BookPolicy::class);
```

Add `'book'` to the morph map array (after the `'church'` entry):

```php
'book' => Book::class,
```

- [ ] **Step 2:** Add library plugin route block to `routes/api.php`

Inside the `auth:sanctum` V1 group (after the `church_builder` block around line 108), add:

```php
// Library Plugin routes
if (app(\Common\Core\PluginManager::class)->isEnabled('library')) {
    require app_path('Plugins/Library/Routes/api.php');
}
```

- [ ] **Step 3:** Add `'book'` to ReactionController allowlist

Open `common/foundation/src/Reactions/Controllers/ReactionController.php` and add `'book'` to the `$allowedTypes` array.

- [ ] **Step 4:** Verify syntax:

```bash
php -l app/Providers/AppServiceProvider.php && php -l routes/api.php
```

---

### Task 10: Factories — BookFactory, BookCategoryFactory

**Files:**
- Create: `database/factories/BookFactory.php`
- Create: `database/factories/BookCategoryFactory.php`

- [ ] **Step 1:** Create `database/factories/BookFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\Library\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'author' => fake()->name(),
            'description' => fake()->paragraphs(2, true),
            'isbn' => fake()->isbn13(),
            'publisher' => fake()->company(),
            'pages_count' => fake()->numberBetween(50, 500),
            'published_year' => fake()->year(),
            'is_featured' => false,
            'is_active' => true,
            'view_count' => 0,
            'download_count' => 0,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPdf(): static
    {
        return $this->state(fn () => ['pdf_path' => 'books/sample-' . Str::random(8) . '.pdf']);
    }
}
```

- [ ] **Step 2:** Create `database/factories/BookCategoryFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\Library\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookCategoryFactory extends Factory
{
    protected $model = BookCategory::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
```

- [ ] **Step 3:** Verify syntax:

```bash
php -l database/factories/BookFactory.php && php -l database/factories/BookCategoryFactory.php
```

---

### Task 11: Tests — BookTest, BookCategoryTest, BookDownloadTest

**Files:**
- Create: `tests/Feature/Library/BookTest.php`
- Create: `tests/Feature/Library/BookCategoryTest.php`
- Create: `tests/Feature/Library/BookDownloadTest.php`

- [ ] **Step 1:** Create `tests/Feature/Library/BookTest.php`

```php
<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        // Simulate permissions via role or direct assignment
        $user->forceFill(['permissions' => array_fill_keys($permissions, true)]);
        return $user;
    }

    public function test_can_list_active_books(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        Book::factory()->count(3)->create();
        Book::factory()->inactive()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_show_book_detail(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonPath('book.id', $book->id);
    }

    public function test_show_increments_view_count(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        $book = Book::factory()->create(['view_count' => 5]);

        $this->actingAs($user)->getJson("/api/v1/books/{$book->id}");

        $this->assertDatabaseHas('books', ['id' => $book->id, 'view_count' => 6]);
    }

    public function test_can_create_book(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.create']);
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/books', [
            'title' => 'Test Book',
            'author' => 'John Doe',
            'description' => 'A test book',
            'category_id' => $category->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('book.title', 'Test Book');
        $this->assertDatabaseHas('books', ['title' => 'Test Book', 'uploaded_by' => $user->id]);
    }

    public function test_can_update_book(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.update']);
        $book = Book::factory()->create(['uploaded_by' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/v1/books/{$book->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
    }

    public function test_can_delete_book(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.delete']);
        $book = Book::factory()->create(['uploaded_by' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_can_filter_books_by_category(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        $category = BookCategory::factory()->create();
        Book::factory()->count(2)->create(['category_id' => $category->id]);
        Book::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/v1/books?category_id={$category->id}");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }
}
```

- [ ] **Step 2:** Create `tests/Feature/Library/BookCategoryTest.php`

```php
<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Models\BookCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->forceFill(['permissions' => array_fill_keys($permissions, true)]);
        return $user;
    }

    public function test_can_list_categories(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        BookCategory::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/book-categories');

        $response->assertOk();
        $response->assertJsonCount(3, 'categories');
    }

    public function test_can_create_category(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.manage_categories']);

        $response = $this->actingAs($user)->postJson('/api/v1/book-categories', [
            'name' => 'Theology',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('book_categories', ['name' => 'Theology']);
    }

    public function test_can_create_child_category(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.manage_categories']);
        $parent = BookCategory::factory()->create(['name' => 'Theology']);

        $response = $this->actingAs($user)->postJson('/api/v1/book-categories', [
            'name' => 'Systematic Theology',
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Systematic Theology',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_delete_category_unlinks_books(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.manage_categories']);
        $category = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/book-categories/{$category->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'category_id' => null]);
    }
}
```

- [ ] **Step 3:** Create `tests/Feature/Library/BookDownloadTest.php`

```php
<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->forceFill(['permissions' => array_fill_keys($permissions, true)]);
        return $user;
    }

    public function test_can_track_download(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.download']);
        $book = Book::factory()->withPdf()->create(['download_count' => 10]);

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertOk();
        $response->assertJsonPath('download_count', 11);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'download_count' => 11]);
    }

    public function test_cannot_download_without_permission(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view']);
        $book = Book::factory()->withPdf()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertForbidden();
    }

    public function test_cannot_download_inactive_book(): void
    {
        $user = $this->actingAsUserWithPermissions(['library.view', 'library.download']);
        $book = Book::factory()->inactive()->withPdf()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/books/{$book->id}/download");

        $response->assertForbidden();
    }
}
```

- [ ] **Step 4:** Verify syntax:

```bash
php -l tests/Feature/Library/BookTest.php && \
php -l tests/Feature/Library/BookCategoryTest.php && \
php -l tests/Feature/Library/BookDownloadTest.php
```

- [ ] **Step 5:** Run tests: `php artisan test --filter=Library`

Expected: All 14 tests pass.

- [ ] **Step 6:** Commit backend

```bash
git add app/Plugins/Library/ database/migrations/0007_01_01_000000_create_library_tables.php \
  database/factories/BookFactory.php database/factories/BookCategoryFactory.php \
  tests/Feature/Library/ app/Providers/AppServiceProvider.php routes/api.php \
  common/foundation/src/Reactions/Controllers/ReactionController.php
git commit -m "feat: add Library plugin backend (Plan 7) — books, categories, CRUD, download tracking, permissions"
```

---

### Task 12: Frontend — queries.ts (types + hooks)

**Files:**
- Create: `resources/client/plugins/library/queries.ts`

- [ ] **Step 1:** Create `resources/client/plugins/library/queries.ts`

```typescript
import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface BookCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  parent_id: number | null;
  sort_order: number;
  is_active: boolean;
  books_count: number;
}

export interface Book {
  id: number;
  title: string;
  slug: string;
  author: string;
  description: string | null;
  content: string | null;
  cover: string | null;
  pdf_path: string | null;
  isbn: string | null;
  publisher: string | null;
  pages_count: number | null;
  published_year: number | null;
  category_id: number | null;
  category: {id: number; name: string; slug: string} | null;
  church_id: number | null;
  uploaded_by: number | null;
  uploader: {id: number; name: string; avatar: string | null} | null;
  view_count: number;
  download_count: number;
  is_featured: boolean;
  is_active: boolean;
  has_pdf: boolean;
  can_download?: boolean;
  reactions_count: number;
  created_at: string;
}

// --- Book Queries ---

export function useBooks(params: Record<string, string | number | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['books', params],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get('books', {params: {...params, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useBook(bookId: number | string) {
  return useQuery({
    queryKey: ['books', bookId],
    queryFn: () => apiClient.get(`books/${bookId}`).then(r => r.data.book),
  });
}

// --- Book Mutations ---

export function useCreateBook() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Book>) =>
      apiClient.post('books', data).then(r => r.data.book),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useUpdateBook(bookId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Book>) =>
      apiClient.put(`books/${bookId}`, data).then(r => r.data.book),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books', bookId]});
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useDeleteBook() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (bookId: number) => apiClient.delete(`books/${bookId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useTrackDownload(bookId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () =>
      apiClient.post(`books/${bookId}/download`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books', bookId]});
    },
  });
}

// --- Category Queries ---

export function useBookCategories() {
  return useQuery({
    queryKey: ['book-categories'],
    queryFn: () =>
      apiClient.get('book-categories').then(r => r.data.categories as BookCategory[]),
  });
}

export function useCreateBookCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<BookCategory>) =>
      apiClient.post('book-categories', data).then(r => r.data.category),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
    },
  });
}

export function useUpdateBookCategory(categoryId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<BookCategory>) =>
      apiClient.put(`book-categories/${categoryId}`, data).then(r => r.data.category),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
    },
  });
}

export function useDeleteBookCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (categoryId: number) => apiClient.delete(`book-categories/${categoryId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}
```

- [ ] **Step 2:** Verify: `npx tsc --noEmit resources/client/plugins/library/queries.ts` (or check no red squiggles in IDE).

---

### Task 13: Frontend — BookCard, CategorySidebar components

**Files:**
- Create: `resources/client/plugins/library/components/BookCard.tsx`
- Create: `resources/client/plugins/library/components/CategorySidebar.tsx`

- [ ] **Step 1:** Create `resources/client/plugins/library/components/BookCard.tsx`

```tsx
import {Book} from '../queries';

interface BookCardProps {
  book: Book;
  onClick: (book: Book) => void;
}

export function BookCard({book, onClick}: BookCardProps) {
  return (
    <button
      onClick={() => onClick(book)}
      className="group text-left bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow"
    >
      {/* Cover */}
      <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-700 overflow-hidden">
        {book.cover ? (
          <img
            src={book.cover}
            alt={book.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-3">
        <h3 className="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2">
          {book.title}
        </h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{book.author}</p>
        <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
          {book.category && (
            <span className="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
              {book.category.name}
            </span>
          )}
          {book.is_featured && (
            <span className="text-amber-500 font-medium">Featured</span>
          )}
        </div>
      </div>
    </button>
  );
}
```

- [ ] **Step 2:** Create `resources/client/plugins/library/components/CategorySidebar.tsx`

```tsx
import {BookCategory} from '../queries';

interface CategorySidebarProps {
  categories: BookCategory[];
  selectedId: number | null;
  onSelect: (id: number | null) => void;
}

export function CategorySidebar({categories, selectedId, onSelect}: CategorySidebarProps) {
  const roots = categories.filter(c => !c.parent_id);

  function getChildren(parentId: number): BookCategory[] {
    return categories.filter(c => c.parent_id === parentId);
  }

  function renderCategory(cat: BookCategory, depth: number = 0) {
    const children = getChildren(cat.id);
    const isSelected = selectedId === cat.id;

    return (
      <div key={cat.id}>
        <button
          onClick={() => onSelect(isSelected ? null : cat.id)}
          className={`w-full text-left px-3 py-2 text-sm rounded-md transition-colors ${
            isSelected
              ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-medium'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
          }`}
          style={{paddingLeft: `${12 + depth * 16}px`}}
        >
          <span>{cat.name}</span>
          <span className="ml-auto text-xs text-gray-400">{cat.books_count}</span>
        </button>
        {children.map(child => renderCategory(child, depth + 1))}
      </div>
    );
  }

  return (
    <div className="space-y-1">
      <button
        onClick={() => onSelect(null)}
        className={`w-full text-left px-3 py-2 text-sm rounded-md transition-colors ${
          selectedId === null
            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-medium'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
        }`}
      >
        All Books
      </button>
      {roots.map(cat => renderCategory(cat))}
    </div>
  );
}
```

---

### Task 14: Frontend — LibraryCatalogPage, BookDetailPage

**Files:**
- Create: `resources/client/plugins/library/pages/LibraryCatalogPage.tsx`
- Create: `resources/client/plugins/library/pages/BookDetailPage.tsx`

- [ ] **Step 1:** Create `resources/client/plugins/library/pages/LibraryCatalogPage.tsx`

```tsx
import {useState, Fragment} from 'react';
import {useNavigate} from 'react-router';
import {useBooks, useBookCategories, Book} from '../queries';
import {BookCard} from '../components/BookCard';
import {CategorySidebar} from '../components/CategorySidebar';

export function LibraryCatalogPage() {
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

  const params: Record<string, string | number | boolean> = {};
  if (search) params.search = search;
  if (categoryId) params.category_id = categoryId;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = useBooks(params);
  const {data: categories} = useBookCategories();

  const books = data?.pages.flatMap((page: any) => page.data) ?? [];

  function handleBookClick(book: Book) {
    navigate(`/library/${book.id}`);
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Library</h1>
        <div className="flex items-center gap-3">
          {/* View toggle */}
          <div className="flex border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
            <button
              onClick={() => setViewMode('grid')}
              className={`px-3 py-2 text-sm ${viewMode === 'grid' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'}`}
            >
              Grid
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`px-3 py-2 text-sm ${viewMode === 'list' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'}`}
            >
              List
            </button>
          </div>
          <div className="relative">
          <input
            type="text"
            placeholder="Search books..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
          <svg className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          </div>
        </div>
      </div>

      <div className="flex gap-6">
        {/* Category sidebar */}
        {categories && categories.length > 0 && (
          <aside className="w-56 shrink-0 hidden md:block">
            <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
              Categories
            </h2>
            <CategorySidebar
              categories={categories}
              selectedId={categoryId}
              onSelect={setCategoryId}
            />
          </aside>
        )}

        {/* Book grid */}
        <div className="flex-1">
          {isLoading ? (
            <div className="text-center py-12 text-gray-500">Loading books...</div>
          ) : books.length === 0 ? (
            <div className="text-center py-12 text-gray-500">No books found.</div>
          ) : (
            <>
              {viewMode === 'grid' ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                  {books.map((book: Book) => (
                    <BookCard key={book.id} book={book} onClick={handleBookClick} />
                  ))}
                </div>
              ) : (
                <div className="space-y-3">
                  {books.map((book: Book) => (
                    <button
                      key={book.id}
                      onClick={() => handleBookClick(book)}
                      className="w-full flex items-center gap-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-sm transition-shadow text-left"
                    >
                      <div className="w-12 h-16 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden shrink-0">
                        {book.cover && <img src={book.cover} alt="" className="w-full h-full object-cover" />}
                      </div>
                      <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-sm text-gray-900 dark:text-white truncate">{book.title}</h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{book.author}</p>
                      </div>
                      {book.category && (
                        <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">
                          {book.category.name}
                        </span>
                      )}
                    </button>
                  ))}
                </div>
              )}

              {hasNextPage && (
                <div className="flex justify-center mt-8">
                  <button
                    onClick={() => fetchNextPage()}
                    disabled={isFetchingNextPage}
                    className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium"
                  >
                    {isFetchingNextPage ? 'Loading...' : 'Load More'}
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 2:** Create `resources/client/plugins/library/pages/BookDetailPage.tsx`

```tsx
import {useParams, useNavigate} from 'react-router';
import {useBook, useTrackDownload} from '../queries';

export function BookDetailPage() {
  const {bookId} = useParams<{bookId: string}>();
  const navigate = useNavigate();
  const {data: book, isLoading} = useBook(bookId!);
  const trackDownload = useTrackDownload(bookId!);

  if (isLoading || !book) {
    return <div className="flex items-center justify-center h-64">Loading...</div>;
  }

  function handleDownload() {
    trackDownload.mutate(undefined, {
      onSuccess: (data) => {
        if (data.pdf_path) {
          window.open(data.pdf_path, '_blank');
        }
      },
    });
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <button
        onClick={() => navigate('/library')}
        className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4 inline-flex items-center gap-1"
      >
        &larr; Back to Library
      </button>

      <div className="flex flex-col md:flex-row gap-8">
        {/* Cover */}
        <div className="w-full md:w-64 shrink-0">
          <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
            {book.cover ? (
              <img src={book.cover} alt={book.title} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400">
                <svg className="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
            )}
          </div>

          {/* Download button */}
          {book.has_pdf && book.can_download && (
            <button
              onClick={handleDownload}
              disabled={trackDownload.isPending}
              className="w-full mt-4 px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium flex items-center justify-center gap-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {trackDownload.isPending ? 'Preparing...' : 'Download PDF'}
            </button>
          )}
        </div>

        {/* Details */}
        <div className="flex-1">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{book.title}</h1>
          <p className="text-lg text-gray-600 dark:text-gray-400 mt-1">by {book.author}</p>

          {book.category && (
            <span className="inline-block mt-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm px-3 py-1 rounded-full">
              {book.category.name}
            </span>
          )}

          {/* Metadata */}
          <div className="mt-6 grid grid-cols-2 gap-4 text-sm">
            {book.publisher && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Publisher</span>
                <p className="text-gray-900 dark:text-white">{book.publisher}</p>
              </div>
            )}
            {book.published_year && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Year</span>
                <p className="text-gray-900 dark:text-white">{book.published_year}</p>
              </div>
            )}
            {book.pages_count && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Pages</span>
                <p className="text-gray-900 dark:text-white">{book.pages_count}</p>
              </div>
            )}
            {book.isbn && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">ISBN</span>
                <p className="text-gray-900 dark:text-white">{book.isbn}</p>
              </div>
            )}
            <div>
              <span className="text-gray-500 dark:text-gray-400">Views</span>
              <p className="text-gray-900 dark:text-white">{book.view_count}</p>
            </div>
            <div>
              <span className="text-gray-500 dark:text-gray-400">Downloads</span>
              <p className="text-gray-900 dark:text-white">{book.download_count}</p>
            </div>
          </div>

          {/* Description */}
          {book.description && (
            <div className="mt-6">
              <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Description
              </h2>
              <div className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                {book.description}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
```

---

### Task 15: Frontend Integration — app-router.tsx + AdminLayout.tsx

**Files:**
- Modify: `resources/client/app-router.tsx`
- Modify: `resources/client/admin/AdminLayout.tsx`

- [ ] **Step 1:** Add lazy imports to `resources/client/app-router.tsx`

After the ChurchProfilePage import (around line 42), add:

```tsx
const LibraryCatalogPage = lazy(() => import('./plugins/library/pages/LibraryCatalogPage').then(m => ({default: m.LibraryCatalogPage})));
const BookDetailPage = lazy(() => import('./plugins/library/pages/BookDetailPage').then(m => ({default: m.BookDetailPage})));
```

- [ ] **Step 2:** Add routes inside the `<Route element={<RequireAuth />}>` block

After the `/churches/:churchId` route (around line 71), add:

```tsx
<Route path="/library" element={<LibraryCatalogPage />} />
<Route path="/library/:bookId" element={<BookDetailPage />} />
```

- [ ] **Step 3:** Add Library to sidebar in `resources/client/admin/AdminLayout.tsx`

In the `sidebarItems` array, add after the `Churches` entry:

```tsx
{ label: 'Library', path: '/library', icon: 'BookOpen', permission: 'library.view' },
```

- [ ] **Step 4:** Verify: `npx tsc --noEmit` (or `npm run build` for full check).

- [ ] **Step 5:** Commit frontend

```bash
git add resources/client/plugins/library/ resources/client/app-router.tsx resources/client/admin/AdminLayout.tsx
git commit -m "feat: add Library plugin frontend (Plan 7) — catalog, book detail, categories, download"
```

---

## Summary

| Artifact | Count |
|----------|-------|
| Migration | 1 (creates `books` + `book_categories`) |
| Models | 2 (Book, BookCategory) |
| Services | 6 (BookLoader, PaginateBooks, CrupdateBook, DeleteBooks, PaginateBookCategories, CrupdateBookCategory) |
| Controllers | 2 (BookController, BookCategoryController) |
| Policy | 1 (BookPolicy) |
| Requests | 2 (ModifyBook, ModifyBookCategory) |
| Routes | 12 API endpoints |
| Permissions | 7 (view, read, download, create, update, delete, manage_categories) |
| Factories | 2 (BookFactory, BookCategoryFactory) |
| Tests | 14 (7 book CRUD + 4 category + 3 download) |
| Frontend files | 5 (queries.ts, 2 pages, 2 components) |
| Integration changes | 3 (AppServiceProvider, routes/api.php, ReactionController) |
