# Plan 8: Blog + Live Meeting Plugins — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build two independent plugins — Blog (article CMS with Tiptap WYSIWYG, categories, tags, SEO) and Live Meeting (link-based meeting system with "Live Now" detection). Combined into one plan because they share the same migration series and are both Plan 8 deliverables.

**Architecture:** Follows the established plugin pattern: `app/Plugins/{Name}/` with Loader, Crupdate, Paginate, Delete services, Policy, form request, and PermissionSeeder. Blog has three models (Article, ArticleCategory, Tag) and Live Meeting has one (Meeting). Both use the `0008_*` migration series. Article uses `HasReactions` trait; Meeting does not (meetings are transient).

**Key Design Decisions:**
1. **Tiptap WYSIWYG from V1.** Content stored as HTML. Image uploads use Foundation's existing `POST /api/v1/uploads` endpoint.
2. **Link-based meetings.** Admin pastes Zoom/Meet/YouTube URL. No API integration.
3. **Time-based "Live Now".** Eloquent accessor: `is_live` when `starts_at <= now <= ends_at`. No manual toggle.
4. **Standalone meetings.** Own model/routes — no dependency on Events plugin.
5. **Blog SEO included.** Blade route at `/blog/{slug}` with JSON-LD Article schema.
6. **`blog.publish` separate from `blog.create`.** Authors draft, only editors/admins publish.
7. **Scheduled publishing via query-time filtering.** PaginateArticles promotes scheduled articles past `published_at` to published. No cron.
8. **Flat blog categories.** No `parent_id` nesting (unlike BookCategory).
9. **Tags platform-global.** Not church-scoped. No update endpoint — rename by delete+create.
10. **Comments deferred.** Reactions only for V1.
11. **Public article access.** Published articles viewable without auth (for SEO).
12. **Recurring meetings informational.** `recurrence_rule` enum stored but no auto-generation in V1.

**Tech Stack:** Laravel 12 plugin, Eloquent, TanStack React Query, Tailwind CSS, Tiptap.

**Spec:** `docs/superpowers/specs/2026-03-29-blog-live-meeting-design.md`

**Depends on:** Plan 2 (Reactions/Comments) — `HasReactions` trait, morph map, plugin loading, permission seeder pattern.

---

## File Structure Overview

```
app/Plugins/Blog/
├── Models/
│   ├── Article.php                     # HasReactions, HasFactory, slug binding
│   ├── ArticleCategory.php             # Flat categories (no parent_id)
│   └── Tag.php                         # Platform-global tags
├── Services/
│   ├── ArticleLoader.php               # API response formatting
│   ├── PaginateArticles.php            # Filters + scheduled→published promotion
│   ├── CrupdateArticle.php             # Create/update articles + tag sync
│   ├── DeleteArticles.php              # Delete articles + cleanup reactions
│   ├── PaginateArticleCategories.php   # Category listing
│   └── CrupdateArticleCategory.php     # Create/update categories
├── Controllers/
│   ├── ArticleController.php           # Article CRUD + view count
│   ├── ArticleCategoryController.php   # Category CRUD
│   └── TagController.php               # Tag list + create + delete
├── Policies/
│   └── ArticlePolicy.php               # CRUD + publish + manage perms
├── Requests/
│   ├── ModifyArticle.php               # Validation for articles
│   └── ModifyArticleCategory.php       # Validation for categories
├── Routes/
│   ├── api.php                         # Authenticated blog routes
│   └── public.php                      # Public blog routes (no auth, for SEO)
├── Database/
│   └── Seeders/
│       └── BlogPermissionSeeder.php    # 7 permissions across roles

app/Plugins/LiveMeeting/
├── Models/
│   └── Meeting.php                     # is_live accessor, platform enum
├── Services/
│   ├── MeetingLoader.php               # API response formatting
│   ├── PaginateMeetings.php            # Upcoming + live filters
│   ├── CrupdateMeeting.php             # Create/update meetings
│   └── DeleteMeetings.php              # Delete meetings
├── Controllers/
│   └── MeetingController.php           # Meeting CRUD + /live endpoint
├── Policies/
│   └── MeetingPolicy.php               # CRUD with host self-edit
├── Requests/
│   └── ModifyMeeting.php               # Validation for meetings
├── Routes/
│   └── api.php                         # Meeting routes
├── Database/
│   └── Seeders/
│       └── LiveMeetingPermissionSeeder.php  # 4 permissions

database/
├── migrations/
│   ├── 0008_01_01_000000_create_blog_tables.php
│   └── 0008_01_02_000000_create_meeting_tables.php
├── factories/
│   ├── ArticleFactory.php
│   ├── ArticleCategoryFactory.php
│   └── MeetingFactory.php

tests/Feature/
├── Blog/
│   ├── ArticleTest.php                 # 9 tests
│   ├── ArticleCategoryTest.php         # 4 tests
│   └── TagTest.php                     # 3 tests
├── LiveMeeting/
│   └── MeetingTest.php                 # 7 tests

resources/client/
├── plugins/blog/
│   ├── queries.ts                      # TanStack Query hooks + types
│   ├── pages/
│   │   ├── BlogListPage.tsx            # Grid, categories, tags, featured
│   │   ├── ArticleDetailPage.tsx       # Rendered HTML content, reactions
│   │   └── ArticleEditorPage.tsx       # Tiptap editor, metadata fields
│   └── components/
│       ├── ArticleCard.tsx             # Blog card (cover, title, excerpt)
│       ├── CategorySidebar.tsx         # Flat category list
│       └── TiptapEditor.tsx            # Tiptap wrapper component
├── plugins/live-meeting/
│   ├── queries.ts                      # TanStack Query hooks + types
│   ├── pages/
│   │   ├── MeetingsPage.tsx            # Live Now + Upcoming sections
│   │   └── MeetingDetailPage.tsx       # Detail with Join button
│   └── components/
│       └── MeetingCard.tsx             # Card with platform icon, time

resources/views/public/
└── article.blade.php                   # SEO Blade view with JSON-LD
```

---

## Tasks

### Task 1: Migration — create blog and meeting tables

**Files:**
- Create: `database/migrations/0008_01_01_000000_create_blog_tables.php`
- Create: `database/migrations/0008_01_02_000000_create_meeting_tables.php`

- [ ] **Step 1:** Create migration `0008_01_01_000000_create_blog_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, published, scheduled
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_categories');
    }
};
```

- [ ] **Step 2:** Create migration `0008_01_02_000000_create_meeting_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('meeting_url');
            $table->string('platform')->default('other'); // zoom, google_meet, youtube, other
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable(); // weekly, biweekly, monthly
            $table->string('cover_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
```

- [ ] **Step 3:** Verify syntax: `php -l database/migrations/0008_01_01_000000_create_blog_tables.php && php -l database/migrations/0008_01_02_000000_create_meeting_tables.php`

---

### Task 2: Blog Models — Article, ArticleCategory, Tag

**Files:**
- Create: `app/Plugins/Blog/Models/Article.php`
- Create: `app/Plugins/Blog/Models/ArticleCategory.php`
- Create: `app/Plugins/Blog/Models/Tag.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Models/Article.php`

```php
<?php

namespace App\Plugins\Blog\Models;

use App\Models\User;
use App\Models\Church;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ArticleFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            if (empty($article->slug)) {
                $slug = Str::slug($article->title);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $article->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Models/ArticleCategory.php`

```php
<?php

namespace App\Plugins\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ArticleCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ArticleCategoryFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (ArticleCategory $category) {
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

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Blog/Models/Tag.php`

```php
<?php

namespace App\Plugins\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $slug = Str::slug($tag->name);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $tag->slug = $slug;
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
```

- [ ] **Step 4:** Verify syntax: `php -l app/Plugins/Blog/Models/Article.php && php -l app/Plugins/Blog/Models/ArticleCategory.php && php -l app/Plugins/Blog/Models/Tag.php`

---

### Task 3: Blog Services — ArticleLoader, PaginateArticles, CrupdateArticle, DeleteArticles

**Files:**
- Create: `app/Plugins/Blog/Services/ArticleLoader.php`
- Create: `app/Plugins/Blog/Services/PaginateArticles.php`
- Create: `app/Plugins/Blog/Services/CrupdateArticle.php`
- Create: `app/Plugins/Blog/Services/DeleteArticles.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Services/ArticleLoader.php`

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class ArticleLoader
{
    public function load(Article $article): Article
    {
        return $article->load(['author', 'category', 'tags'])
            ->loadCount('reactions');
    }

    public function loadForDetail(Article $article): array
    {
        $this->load($article);

        return [
            'article' => $article,
        ];
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Services/PaginateArticles.php`

Follow PaginateBooks pattern. Include scheduled→published promotion via query scope:
- `scopePublishedOrDue` includes both `status=published` and `status=scheduled WHERE published_at <= now`.
- Filter by `category_id`, `tag` (slug), `search`, `featured`, `status` (for admins/authors).
- Default: published + due-scheduled articles only.

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PaginateArticles
{
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Article::query()->with(['author', 'category', 'tags']);

        // Default to published-only unless status filter is provided
        if (isset($params['status'])) {
            $statuses = explode(',', $params['status']);
            $query->whereIn('status', $statuses);
        } else {
            // Include published + scheduled articles past their publish date
            $query->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'scheduled')
                         ->where('published_at', '<=', Carbon::now());
                  });
            });
        }

        if ($categoryId = ($params['category_id'] ?? null)) {
            $query->where('category_id', $categoryId);
        }

        if ($tag = ($params['tag'] ?? null)) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $tag));
        }

        if ($search = ($params['search'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if (filter_var($params['featured'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }

        $query->where('is_active', true);

        $orderBy = $params['order_by'] ?? 'published_at';
        $orderDir = $params['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        $perPage = min($params['per_page'] ?? 15, 50);
        return $query->paginate($perPage);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Blog/Services/CrupdateArticle.php`

Follow CrupdateBook pattern. Whitelist fields. Sync tags via `article_tag` pivot.

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class CrupdateArticle
{
    public function execute(Article $article, array $data): Article
    {
        $attributes = [
            'title' => $data['title'] ?? $article->title,
            'content' => $data['content'] ?? $article->content,
            'excerpt' => $data['excerpt'] ?? $article->excerpt,
            'cover_image' => $data['cover_image'] ?? $article->cover_image,
            'category_id' => $data['category_id'] ?? $article->category_id,
            'church_id' => $data['church_id'] ?? $article->church_id,
            'status' => $data['status'] ?? $article->status,
            'published_at' => $data['published_at'] ?? $article->published_at,
            'is_featured' => $data['is_featured'] ?? $article->is_featured,
            'is_active' => $data['is_active'] ?? $article->is_active,
            'meta_title' => $data['meta_title'] ?? $article->meta_title,
            'meta_description' => $data['meta_description'] ?? $article->meta_description,
        ];

        if (!$article->exists) {
            $attributes['author_id'] = $data['author_id'];
            $article = Article::create($attributes);
        } else {
            $article->update($attributes);
        }

        if (isset($data['tag_ids'])) {
            $article->tags()->sync($data['tag_ids']);
        }

        return $article->load(['author', 'category', 'tags']);
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/Blog/Services/DeleteArticles.php`

Follow DeleteBooks pattern — delete reactions before deleting.

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\Article;

class DeleteArticles
{
    public function execute(array $ids): void
    {
        $articles = Article::whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            $article->reactions()->delete();
            $article->tags()->detach();
            $article->delete();
        }
    }
}
```

- [ ] **Step 5:** Verify syntax: `find app/Plugins/Blog/Services -name '*.php' -exec php -l {} \;`

---

### Task 4: Blog Category Services — PaginateArticleCategories, CrupdateArticleCategory

**Files:**
- Create: `app/Plugins/Blog/Services/PaginateArticleCategories.php`
- Create: `app/Plugins/Blog/Services/CrupdateArticleCategory.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Services/PaginateArticleCategories.php`

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\ArticleCategory;
use Illuminate\Support\Collection;

class PaginateArticleCategories
{
    public function execute(array $params): Collection
    {
        $query = ArticleCategory::query()->withCount('articles');

        if (!filter_var($params['include_inactive'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Services/CrupdateArticleCategory.php`

```php
<?php

namespace App\Plugins\Blog\Services;

use App\Plugins\Blog\Models\ArticleCategory;

class CrupdateArticleCategory
{
    public function execute(ArticleCategory $category, array $data): ArticleCategory
    {
        $attributes = [
            'name' => $data['name'] ?? $category->name,
            'description' => $data['description'] ?? $category->description,
            'image' => $data['image'] ?? $category->image,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'is_active' => $data['is_active'] ?? $category->is_active,
        ];

        if (!$category->exists) {
            $category = ArticleCategory::create($attributes);
        } else {
            $category->update($attributes);
        }

        return $category;
    }
}
```

- [ ] **Step 3:** Verify syntax: `php -l app/Plugins/Blog/Services/PaginateArticleCategories.php && php -l app/Plugins/Blog/Services/CrupdateArticleCategory.php`

---

### Task 5: Blog Policy + Requests — ArticlePolicy, ModifyArticle, ModifyArticleCategory

**Files:**
- Create: `app/Plugins/Blog/Policies/ArticlePolicy.php`
- Create: `app/Plugins/Blog/Requests/ModifyArticle.php`
- Create: `app/Plugins/Blog/Requests/ModifyArticleCategory.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Policies/ArticlePolicy.php`

Follow BookPolicy pattern. Key differences: `viewAny` allows unauthenticated users (public for SEO). Author can always edit own articles. `blog.publish` checked separately.

```php
<?php

namespace App\Plugins\Blog\Policies;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use Common\Auth\BasePolicy;

class ArticlePolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // Published articles are public for SEO
    }

    public function view(?User $user, Article $article): bool
    {
        if ($article->status === 'published') {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $article->author_id === $user->id
            || $user->hasPermission('blog.update');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('blog.create');
    }

    public function update(User $user, Article $article): bool
    {
        return $article->author_id === $user->id
            || $user->hasPermission('blog.update');
    }

    public function delete(User $user, Article $article): bool
    {
        if ($article->author_id === $user->id && $article->status === 'draft') {
            return true;
        }

        return $user->hasPermission('blog.delete');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermission('blog.publish');
    }

    public function manageCategories(User $user): bool
    {
        return $user->hasPermission('blog.manage_categories');
    }

    public function manageTags(User $user): bool
    {
        return $user->hasPermission('blog.manage_tags');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Requests/ModifyArticle.php`

Follow ModifyBook pattern with PUT/PATCH stripping `required`.

```php
<?php

namespace App\Plugins\Blog\Requests;

use Common\Core\BaseFormRequest;

class ModifyArticle extends BaseFormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'title' => $required . 'string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:article_categories,id',
            'church_id' => 'nullable|exists:churches,id',
            'status' => 'nullable|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Blog/Requests/ModifyArticleCategory.php`

```php
<?php

namespace App\Plugins\Blog\Requests;

use Common\Core\BaseFormRequest;

class ModifyArticleCategory extends BaseFormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'name' => $required . 'string|max:255',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
```

- [ ] **Step 4:** Verify syntax: `php -l app/Plugins/Blog/Policies/ArticlePolicy.php && php -l app/Plugins/Blog/Requests/ModifyArticle.php && php -l app/Plugins/Blog/Requests/ModifyArticleCategory.php`

---

### Task 6: Blog Controllers — ArticleController, ArticleCategoryController, TagController

**Files:**
- Create: `app/Plugins/Blog/Controllers/ArticleController.php`
- Create: `app/Plugins/Blog/Controllers/ArticleCategoryController.php`
- Create: `app/Plugins/Blog/Controllers/TagController.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Controllers/ArticleController.php`

Follow BookController pattern. Key differences: `show()` uses slug binding, `store()` sets `author_id` from auth user, publish permission checked when status is published/scheduled.

```php
<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Requests\ModifyArticle;
use App\Plugins\Blog\Services\ArticleLoader;
use App\Plugins\Blog\Services\CrupdateArticle;
use App\Plugins\Blog\Services\DeleteArticles;
use App\Plugins\Blog\Services\PaginateArticles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController
{
    public function __construct(
        private ArticleLoader $loader,
        private PaginateArticles $paginator,
        private CrupdateArticle $crupdater,
        private DeleteArticles $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $results = $this->paginator->execute($request->all());

        return response()->json(['pagination' => $results]);
    }

    public function show(Article $article): JsonResponse
    {
        Gate::authorize('view', $article);

        $article->incrementView();
        $article->refresh();

        return response()->json($this->loader->loadForDetail($article));
    }

    public function store(ModifyArticle $request): JsonResponse
    {
        Gate::authorize('create', Article::class);

        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        // Check publish permission if setting status to published/scheduled
        if (in_array($data['status'] ?? 'draft', ['published', 'scheduled'])) {
            Gate::authorize('publish', Article::class);
        }

        $article = $this->crupdater->execute(new Article(), $data);

        return response()->json(['article' => $article], 201);
    }

    public function update(ModifyArticle $request, Article $article): JsonResponse
    {
        Gate::authorize('update', $article);

        $data = $request->validated();

        // Check publish permission if changing status to published/scheduled
        if (in_array($data['status'] ?? $article->status, ['published', 'scheduled'])
            && ($data['status'] ?? null) !== null
            && $data['status'] !== $article->status) {
            Gate::authorize('publish', Article::class);
        }

        $article = $this->crupdater->execute($article, $data);

        return response()->json(['article' => $article]);
    }

    public function destroy(Article $article): JsonResponse
    {
        Gate::authorize('delete', $article);

        $this->deleter->execute([$article->id]);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Controllers/ArticleCategoryController.php`

Follow BookCategoryController pattern. Category delete sets `articles.category_id = null`.

```php
<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Models\ArticleCategory;
use App\Plugins\Blog\Requests\ModifyArticleCategory;
use App\Plugins\Blog\Services\CrupdateArticleCategory;
use App\Plugins\Blog\Services\PaginateArticleCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleCategoryController
{
    public function __construct(
        private PaginateArticleCategories $paginator,
        private CrupdateArticleCategory $crupdater,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $params = $request->all();
        if (!Gate::allows('manageCategories', Article::class)) {
            $params['include_inactive'] = false;
        }

        $categories = $this->paginator->execute($params);

        return response()->json(['categories' => $categories]);
    }

    public function store(ModifyArticleCategory $request): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        $category = $this->crupdater->execute(new ArticleCategory(), $request->validated());

        return response()->json(['category' => $category], 201);
    }

    public function update(ModifyArticleCategory $request, ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        $category = $this->crupdater->execute($articleCategory, $request->validated());

        return response()->json(['category' => $category]);
    }

    public function destroy(ArticleCategory $articleCategory): JsonResponse
    {
        Gate::authorize('manageCategories', Article::class);

        // Unlink articles from this category
        Article::where('category_id', $articleCategory->id)->update(['category_id' => null]);
        $articleCategory->delete();

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Blog/Controllers/TagController.php`

Minimal controller — index, store, destroy. No update (rename by delete+create).

```php
<?php

namespace App\Plugins\Blog\Controllers;

use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Article::class);

        $tags = Tag::query()
            ->withCount('articles')
            ->orderBy('name')
            ->get();

        return response()->json(['tags' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manageTags', Article::class);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        $tag = Tag::create($data);

        return response()->json(['tag' => $tag], 201);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('manageTags', Article::class);

        $tag->articles()->detach();
        $tag->delete();

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 4:** Verify syntax: `find app/Plugins/Blog/Controllers -name '*.php' -exec php -l {} \;`

---

### Task 7: Blog Routes + BlogPermissionSeeder

**Files:**
- Create: `app/Plugins/Blog/Routes/api.php`
- Create: `app/Plugins/Blog/Database/Seeders/BlogPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/Blog/Routes/api.php` (authenticated routes only — loaded inside `auth:sanctum` group)

```php
<?php

use App\Plugins\Blog\Controllers\ArticleController;
use App\Plugins\Blog\Controllers\ArticleCategoryController;
use App\Plugins\Blog\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Authenticated routes (this file is loaded inside auth:sanctum group in routes/api.php)
Route::post('articles', [ArticleController::class, 'store']);
Route::put('articles/{article}', [ArticleController::class, 'update']);
Route::delete('articles/{article}', [ArticleController::class, 'destroy']);

Route::post('article-categories', [ArticleCategoryController::class, 'store']);
Route::put('article-categories/{articleCategory}', [ArticleCategoryController::class, 'update']);
Route::delete('article-categories/{articleCategory}', [ArticleCategoryController::class, 'destroy']);

Route::post('tags', [TagController::class, 'store']);
Route::delete('tags/{tag}', [TagController::class, 'destroy']);
```

- [ ] **Step 1b:** Create `app/Plugins/Blog/Routes/public.php` (public routes — loaded outside `auth:sanctum` for SEO)

```php
<?php

use App\Plugins\Blog\Controllers\ArticleController;
use App\Plugins\Blog\Controllers\ArticleCategoryController;
use App\Plugins\Blog\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required — articles are public for SEO)
Route::get('articles', [ArticleController::class, 'index']);
Route::get('articles/{article}', [ArticleController::class, 'show']);
Route::get('article-categories', [ArticleCategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);
```

- [ ] **Step 2:** Create `app/Plugins/Blog/Database/Seeders/BlogPermissionSeeder.php`

Follow LibraryPermissionSeeder pattern. 7 permissions. Member gets `blog.view`. Moderator/Ministry Leader gets view+create+update. Admin/Pastor gets all.

```php
<?php

namespace App\Plugins\Blog\Database\Seeders;

use Common\Auth\Permissions\Permission;
use Common\Auth\Roles\Role;
use Illuminate\Database\Seeder;

class BlogPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'blog.view' => 'Browse articles',
            'blog.create' => 'Create article drafts',
            'blog.update' => 'Edit articles',
            'blog.delete' => 'Delete articles',
            'blog.publish' => 'Publish or schedule articles',
            'blog.manage_categories' => 'Manage article categories',
            'blog.manage_tags' => 'Manage tags',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $description, 'group' => 'blog'],
            );
        }

        // Member: view only
        if ($member = Role::where('name', 'member')->first()) {
            $member->permissions()->syncWithoutDetaching([
                $permissionModels['blog.view']->id,
            ]);
        }

        // Moderator / Ministry Leader: view + create + update
        foreach (['moderator', 'ministry_leader'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->permissions()->syncWithoutDetaching([
                    $permissionModels['blog.view']->id,
                    $permissionModels['blog.create']->id,
                    $permissionModels['blog.update']->id,
                ]);
            }
        }

        // Admin roles: all permissions
        foreach (['church_admin', 'pastor', 'admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->permissions()->syncWithoutDetaching(
                    collect($permissionModels)->pluck('id')->toArray()
                );
            }
        }
    }
}
```

- [ ] **Step 3:** Verify syntax: `php -l app/Plugins/Blog/Routes/api.php && php -l app/Plugins/Blog/Database/Seeders/BlogPermissionSeeder.php`

---

### Task 8: Meeting Model

**Files:**
- Create: `app/Plugins/LiveMeeting/Models/Meeting.php`

- [ ] **Step 1:** Create `app/Plugins/LiveMeeting/Models/Meeting.php`

Key feature: `is_live` accessor returns `true` when `starts_at <= now <= ends_at`.

```php
<?php

namespace App\Plugins\LiveMeeting\Models;

use App\Models\User;
use App\Models\Church;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_live'];

    protected static function newFactory()
    {
        return \Database\Factories\MeetingFactory::new();
    }

    public function getIsLiveAttribute(): bool
    {
        $now = Carbon::now();
        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLive($query)
    {
        $now = Carbon::now();
        return $query->where('starts_at', '<=', $now)->where('ends_at', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', Carbon::now());
    }
}
```

- [ ] **Step 2:** Verify syntax: `php -l app/Plugins/LiveMeeting/Models/Meeting.php`

---

### Task 9: Meeting Services — MeetingLoader, PaginateMeetings, CrupdateMeeting, DeleteMeetings

**Files:**
- Create: `app/Plugins/LiveMeeting/Services/MeetingLoader.php`
- Create: `app/Plugins/LiveMeeting/Services/PaginateMeetings.php`
- Create: `app/Plugins/LiveMeeting/Services/CrupdateMeeting.php`
- Create: `app/Plugins/LiveMeeting/Services/DeleteMeetings.php`

- [ ] **Step 1:** Create `app/Plugins/LiveMeeting/Services/MeetingLoader.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class MeetingLoader
{
    public function load(Meeting $meeting): Meeting
    {
        return $meeting->load(['host']);
    }

    public function loadForDetail(Meeting $meeting): array
    {
        $this->load($meeting);

        return [
            'meeting' => $meeting,
        ];
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/LiveMeeting/Services/PaginateMeetings.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginateMeetings
{
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Meeting::query()->with(['host'])->active();

        if ($filter = ($params['filter'] ?? null)) {
            if ($filter === 'live') {
                $query->live();
            } elseif ($filter === 'upcoming') {
                $query->upcoming();
            }
        }

        if ($search = ($params['search'] ?? null)) {
            $query->where('title', 'like', "%{$search}%");
        }

        $query->orderBy('starts_at', 'asc');

        $perPage = min($params['per_page'] ?? 15, 50);
        return $query->paginate($perPage);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/LiveMeeting/Services/CrupdateMeeting.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class CrupdateMeeting
{
    public function execute(Meeting $meeting, array $data): Meeting
    {
        $attributes = [
            'title' => $data['title'] ?? $meeting->title,
            'description' => $data['description'] ?? $meeting->description,
            'meeting_url' => $data['meeting_url'] ?? $meeting->meeting_url,
            'platform' => $data['platform'] ?? $meeting->platform,
            'church_id' => $data['church_id'] ?? $meeting->church_id,
            'starts_at' => $data['starts_at'] ?? $meeting->starts_at,
            'ends_at' => $data['ends_at'] ?? $meeting->ends_at,
            'timezone' => $data['timezone'] ?? $meeting->timezone,
            'is_recurring' => $data['is_recurring'] ?? $meeting->is_recurring,
            'recurrence_rule' => $data['recurrence_rule'] ?? $meeting->recurrence_rule,
            'cover_image' => $data['cover_image'] ?? $meeting->cover_image,
            'is_active' => $data['is_active'] ?? $meeting->is_active,
        ];

        if (!$meeting->exists) {
            $attributes['host_id'] = $data['host_id'];
            $meeting = Meeting::create($attributes);
        } else {
            $meeting->update($attributes);
        }

        return $meeting->load(['host']);
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/LiveMeeting/Services/DeleteMeetings.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class DeleteMeetings
{
    public function execute(array $ids): void
    {
        Meeting::whereIn('id', $ids)->delete();
    }
}
```

- [ ] **Step 5:** Verify syntax: `find app/Plugins/LiveMeeting/Services -name '*.php' -exec php -l {} \;`

---

### Task 10: Meeting Policy + Request + Controller

**Files:**
- Create: `app/Plugins/LiveMeeting/Policies/MeetingPolicy.php`
- Create: `app/Plugins/LiveMeeting/Requests/ModifyMeeting.php`
- Create: `app/Plugins/LiveMeeting/Controllers/MeetingController.php`

- [ ] **Step 1:** Create `app/Plugins/LiveMeeting/Policies/MeetingPolicy.php`

Host can always update/delete own meetings.

```php
<?php

namespace App\Plugins\LiveMeeting\Policies;

use App\Models\User;
use App\Plugins\LiveMeeting\Models\Meeting;
use Common\Auth\BasePolicy;

class MeetingPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('live_meeting.view');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('live_meeting.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('live_meeting.create');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $meeting->host_id === $user->id
            || $user->hasPermission('live_meeting.update');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $meeting->host_id === $user->id
            || $user->hasPermission('live_meeting.delete');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/LiveMeeting/Requests/ModifyMeeting.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Requests;

use Common\Core\BaseFormRequest;

class ModifyMeeting extends BaseFormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'title' => $required . 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'meeting_url' => $required . 'url|max:500',
            'platform' => 'nullable|in:zoom,google_meet,youtube,other',
            'church_id' => 'nullable|exists:churches,id',
            'starts_at' => $required . 'date',
            'ends_at' => $required . 'date|after:starts_at',
            'timezone' => 'nullable|string|max:50',
            'is_recurring' => 'nullable|boolean',
            'recurrence_rule' => 'nullable|in:weekly,biweekly,monthly',
            'cover_image' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/LiveMeeting/Controllers/MeetingController.php`

Includes a `live()` endpoint for listing currently live meetings.

```php
<?php

namespace App\Plugins\LiveMeeting\Controllers;

use App\Plugins\LiveMeeting\Models\Meeting;
use App\Plugins\LiveMeeting\Requests\ModifyMeeting;
use App\Plugins\LiveMeeting\Services\CrupdateMeeting;
use App\Plugins\LiveMeeting\Services\DeleteMeetings;
use App\Plugins\LiveMeeting\Services\MeetingLoader;
use App\Plugins\LiveMeeting\Services\PaginateMeetings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MeetingController
{
    public function __construct(
        private MeetingLoader $loader,
        private PaginateMeetings $paginator,
        private CrupdateMeeting $crupdater,
        private DeleteMeetings $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meeting::class);

        $results = $this->paginator->execute($request->all());

        return response()->json(['pagination' => $results]);
    }

    public function live(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meeting::class);

        $meetings = Meeting::query()
            ->with(['host'])
            ->active()
            ->live()
            ->orderBy('starts_at')
            ->get();

        return response()->json(['meetings' => $meetings]);
    }

    public function show(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        return response()->json($this->loader->loadForDetail($meeting));
    }

    public function store(ModifyMeeting $request): JsonResponse
    {
        Gate::authorize('create', Meeting::class);

        $data = $request->validated();
        $data['host_id'] = $request->user()->id;

        $meeting = $this->crupdater->execute(new Meeting(), $data);

        return response()->json(['meeting' => $meeting], 201);
    }

    public function update(ModifyMeeting $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('update', $meeting);

        $meeting = $this->crupdater->execute($meeting, $request->validated());

        return response()->json(['meeting' => $meeting]);
    }

    public function destroy(Meeting $meeting): JsonResponse
    {
        Gate::authorize('delete', $meeting);

        $this->deleter->execute([$meeting->id]);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 4:** Verify syntax: `php -l app/Plugins/LiveMeeting/Policies/MeetingPolicy.php && php -l app/Plugins/LiveMeeting/Requests/ModifyMeeting.php && php -l app/Plugins/LiveMeeting/Controllers/MeetingController.php`

---

### Task 11: Meeting Routes + LiveMeetingPermissionSeeder

**Files:**
- Create: `app/Plugins/LiveMeeting/Routes/api.php`
- Create: `app/Plugins/LiveMeeting/Database/Seeders/LiveMeetingPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/LiveMeeting/Routes/api.php`

```php
<?php

use App\Plugins\LiveMeeting\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('meetings', [MeetingController::class, 'index']);
    Route::get('meetings/live', [MeetingController::class, 'live']);
    Route::get('meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('meetings', [MeetingController::class, 'store']);
    Route::put('meetings/{meeting}', [MeetingController::class, 'update']);
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy']);
});
```

- [ ] **Step 2:** Create `app/Plugins/LiveMeeting/Database/Seeders/LiveMeetingPermissionSeeder.php`

```php
<?php

namespace App\Plugins\LiveMeeting\Database\Seeders;

use Common\Auth\Permissions\Permission;
use Common\Auth\Roles\Role;
use Illuminate\Database\Seeder;

class LiveMeetingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'live_meeting.view' => 'Browse and join meetings',
            'live_meeting.create' => 'Create meetings',
            'live_meeting.update' => 'Edit meetings',
            'live_meeting.delete' => 'Delete meetings',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $description, 'group' => 'live_meeting'],
            );
        }

        // Member: view only
        if ($member = Role::where('name', 'member')->first()) {
            $member->permissions()->syncWithoutDetaching([
                $permissionModels['live_meeting.view']->id,
            ]);
        }

        // Moderator / Ministry Leader: view + create + update
        foreach (['moderator', 'ministry_leader'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->permissions()->syncWithoutDetaching([
                    $permissionModels['live_meeting.view']->id,
                    $permissionModels['live_meeting.create']->id,
                    $permissionModels['live_meeting.update']->id,
                ]);
            }
        }

        // Admin roles: all permissions
        foreach (['church_admin', 'pastor', 'admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->permissions()->syncWithoutDetaching(
                    collect($permissionModels)->pluck('id')->toArray()
                );
            }
        }
    }
}
```

- [ ] **Step 3:** Verify syntax: `php -l app/Plugins/LiveMeeting/Routes/api.php && php -l app/Plugins/LiveMeeting/Database/Seeders/LiveMeetingPermissionSeeder.php`

---

### Task 12: Integration — AppServiceProvider, routes/api.php, ReactionController

**Files:**
- Edit: `app/Providers/AppServiceProvider.php`
- Edit: `routes/api.php`
- Edit: `common/foundation/src/Reactions/Controllers/ReactionController.php`

- [ ] **Step 1:** Register Blog policies and morph map in `AppServiceProvider`

Add to the `boot()` method:
```php
Gate::policy(\App\Plugins\Blog\Models\Article::class, \App\Plugins\Blog\Policies\ArticlePolicy::class);
Gate::policy(\App\Plugins\LiveMeeting\Models\Meeting::class, \App\Plugins\LiveMeeting\Policies\MeetingPolicy::class);
```

Add to morph map:
```php
'article' => \App\Plugins\Blog\Models\Article::class,
// No morph map entry for Meeting — it has no reactions
```

- [ ] **Step 2:** Add Blog + LiveMeeting route loading in `routes/api.php`

**Important:** Blog has a split route file. Authenticated routes go inside `auth:sanctum` (like Library). Public routes go outside, in the public section near the prayer/newsletter routes.

Inside `auth:sanctum` group (after Library):
```php
if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
    require app_path('Plugins/Blog/Routes/api.php');
}

if (app(\Common\Core\PluginManager::class)->isEnabled('live_meeting')) {
    require app_path('Plugins/LiveMeeting/Routes/api.php');
}
```

Outside `auth:sanctum` group (in the public routes section):
```php
if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
    require app_path('Plugins/Blog/Routes/public.php');
}
```

- [ ] **Step 3:** Add `'article'` to ReactionController's allowed types

In `ReactionController.php`, find the `allowedTypes` validation string and add `article` to the list.

- [ ] **Step 4:** Verify `config/plugins.json` contains `blog` and `live_meeting` entries set to enabled. If not, add them.

- [ ] **Step 5:** Verify syntax: `php -l app/Providers/AppServiceProvider.php && php -l routes/api.php`

---

### Task 13: Blog SEO Blade view

**Files:**
- Create: `resources/views/public/article.blade.php`
- Edit: `routes/web.php` (or create if needed)

- [ ] **Step 1:** Create `resources/views/public/article.blade.php`

Follow the existing `resources/views/public/post.blade.php` pattern. Include:
- Article title, excerpt, cover image in meta tags
- JSON-LD `Article` schema with author, datePublished, dateModified, headline, image
- Extends `layouts.public`

```blade
@extends('layouts.public')

@section('title', $article->meta_title ?? $article->title)
@section('meta_description', $article->meta_description ?? $article->excerpt)

@if($article->cover_image)
@section('og_image', $article->cover_image)
@endif

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ $article->title }}",
    "description": "{{ $article->excerpt }}",
    @if($article->cover_image)
    "image": "{{ $article->cover_image }}",
    @endif
    "author": {
        "@type": "Person",
        "name": "{{ $article->author->display_name ?? '' }}"
    },
    "datePublished": "{{ $article->published_at?->toIso8601String() }}",
    "dateModified": "{{ $article->updated_at->toIso8601String() }}"
}
</script>
@endsection

@section('content')
<article>
    <h1>{{ $article->title }}</h1>
    @if($article->cover_image)
        <img src="{{ $article->cover_image }}" alt="{{ $article->title }}">
    @endif
    <div>{!! $article->content !!}</div>
</article>
@endsection
```

- [ ] **Step 2:** Replace the existing `/blog/{slug}` web route in `routes/web.php`

The existing route at line ~120 is: `Route::get('/blog/{slug}', [PublicContentController::class, 'post'])->name('public.post');`

Replace it with the new Blog plugin route, gated by `PluginManager::isEnabled('blog')` with a fallback to the legacy route:

```php
if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
    Route::get('/blog/{slug}', function ($slug) {
        $article = \App\Plugins\Blog\Models\Article::where('slug', $slug)
            ->published()
            ->with('author')
            ->firstOrFail();

        return view('public.article', compact('article'));
    })->name('public.article');
} else {
    Route::get('/blog/{slug}', [PublicContentController::class, 'post'])->name('public.post');
}
```

- [ ] **Step 3:** Verify the Blade file has no syntax errors (visual inspection — Blade doesn't lint with `php -l`)

---

### Task 14: Factories — ArticleFactory, ArticleCategoryFactory, MeetingFactory

**Files:**
- Create: `database/factories/ArticleFactory.php`
- Create: `database/factories/ArticleCategoryFactory.php`
- Create: `database/factories/MeetingFactory.php`

- [ ] **Step 1:** Create `database/factories/ArticleFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'excerpt' => fake()->sentence(10),
            'cover_image' => null,
            'author_id' => User::factory(),
            'category_id' => null,
            'church_id' => null,
            'status' => 'draft',
            'published_at' => null,
            'view_count' => 0,
            'is_featured' => false,
            'is_active' => true,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn() => [
            'status' => 'scheduled',
            'published_at' => now()->addDays(7),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn() => [
            'is_featured' => true,
        ]);
    }
}
```

- [ ] **Step 2:** Create `database/factories/ArticleCategoryFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\Blog\Models\ArticleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleCategoryFactory extends Factory
{
    protected $model = ArticleCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'image' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 3:** Create `database/factories/MeetingFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use App\Plugins\LiveMeeting\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 hour', '+7 days');
        $endsAt = (clone $startsAt)->modify('+1 hour');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(10),
            'meeting_url' => 'https://zoom.us/j/' . fake()->numerify('##########'),
            'platform' => fake()->randomElement(['zoom', 'google_meet', 'youtube', 'other']),
            'church_id' => null,
            'host_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => 'UTC',
            'is_recurring' => false,
            'recurrence_rule' => null,
            'cover_image' => null,
            'is_active' => true,
        ];
    }

    public function live(): static
    {
        return $this->state(fn() => [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(30),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn() => [
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
        ]);
    }
}
```

- [ ] **Step 4:** Verify syntax: `php -l database/factories/ArticleFactory.php && php -l database/factories/ArticleCategoryFactory.php && php -l database/factories/MeetingFactory.php`

---

### Task 15: Tests — ArticleTest, ArticleCategoryTest, TagTest, MeetingTest

**Files:**
- Create: `tests/Feature/Blog/ArticleTest.php`
- Create: `tests/Feature/Blog/ArticleCategoryTest.php`
- Create: `tests/Feature/Blog/TagTest.php`
- Create: `tests/Feature/LiveMeeting/MeetingTest.php`

- [ ] **Step 1:** Create `tests/Feature/Blog/ArticleTest.php`

Follow BookTest pattern. 9 tests:
1. `guest_can_list_published_articles` — unauthenticated, returns published only
2. `member_can_list_articles` — authenticated with `blog.view`
3. `admin_can_list_draft_articles` — filter by `status=draft`
4. `guest_can_view_published_article` — unauthenticated, increments view count
5. `guest_cannot_view_draft_article` — returns 403
6. `author_can_create_draft_article` — with `blog.create`
7. `author_cannot_publish_without_permission` — has `blog.create` but not `blog.publish`, setting status to published returns 403
8. `admin_can_update_article` — with `blog.update`
9. `admin_can_delete_article` — with `blog.delete`, also verifies reactions+tags cleaned up

- [ ] **Step 2:** Create `tests/Feature/Blog/ArticleCategoryTest.php`

4 tests:
1. `member_can_list_categories`
2. `admin_can_create_category`
3. `admin_can_update_category`
4. `admin_can_delete_category_and_unlink_articles`

- [ ] **Step 3:** Create `tests/Feature/Blog/TagTest.php`

3 tests:
1. `member_can_list_tags`
2. `admin_can_create_tag`
3. `admin_can_delete_tag`

- [ ] **Step 4:** Create `tests/Feature/LiveMeeting/MeetingTest.php`

7 tests:
1. `member_can_list_meetings` — returns upcoming meetings
2. `member_can_view_live_meetings` — `/meetings/live` returns currently live meetings
3. `member_can_view_meeting_detail`
4. `moderator_can_create_meeting` — host_id set from auth user
5. `host_can_update_own_meeting` — without `live_meeting.update` permission
6. `member_cannot_update_others_meeting` — returns 403
7. `admin_can_delete_meeting`

- [ ] **Step 5:** Verify syntax: `find tests/Feature/Blog tests/Feature/LiveMeeting -name '*.php' -exec php -l {} \;`

---

### Task 16: Frontend queries.ts for Blog + Live Meeting

**Files:**
- Create: `resources/client/plugins/blog/queries.ts`
- Create: `resources/client/plugins/live-meeting/queries.ts`

- [ ] **Step 1:** Create `resources/client/plugins/blog/queries.ts`

Types: `Article`, `ArticleCategory`, `Tag`. TanStack Query hooks for article CRUD, category list, tag list. Follow Library `queries.ts` pattern.

- [ ] **Step 2:** Create `resources/client/plugins/live-meeting/queries.ts`

Types: `Meeting` (with `is_live` boolean). Hooks for meeting CRUD + `useLiveMeetings()`.

- [ ] **Step 3:** Verify no TypeScript errors: `npx tsc --noEmit --pretty 2>&1 | head -20`

---

### Task 17: Blog components — ArticleCard, CategorySidebar, TiptapEditor

**Files:**
- Create: `resources/client/plugins/blog/components/ArticleCard.tsx`
- Create: `resources/client/plugins/blog/components/CategorySidebar.tsx`
- Create: `resources/client/plugins/blog/components/TiptapEditor.tsx`

- [ ] **Step 1:** Create `resources/client/plugins/blog/components/ArticleCard.tsx`

Card with cover image, title, excerpt, author name, published date, category badge. Follow BookCard pattern.

- [ ] **Step 2:** Create `resources/client/plugins/blog/components/CategorySidebar.tsx`

Flat category list (no tree, unlike Library's hierarchical sidebar). Active category highlighted. Click filters articles.

- [ ] **Step 3:** Create `resources/client/plugins/blog/components/TiptapEditor.tsx`

Tiptap React wrapper component. Uses `@tiptap/react`, `@tiptap/starter-kit`, `@tiptap/extension-image`, `@tiptap/extension-link`. Toolbar with bold, italic, headings (H2, H3), bullet/ordered lists, blockquote, link, image upload (calls Foundation `POST /api/v1/uploads`). Outputs HTML string.

- [ ] **Step 4:** Verify no TypeScript errors: `npx tsc --noEmit --pretty 2>&1 | head -20`

---

### Task 18: Blog pages — BlogListPage, ArticleDetailPage, ArticleEditorPage

**Files:**
- Create: `resources/client/plugins/blog/pages/BlogListPage.tsx`
- Create: `resources/client/plugins/blog/pages/ArticleDetailPage.tsx`
- Create: `resources/client/plugins/blog/pages/ArticleEditorPage.tsx`

- [ ] **Step 1:** Create `resources/client/plugins/blog/pages/BlogListPage.tsx`

Featured articles banner at top, category sidebar on left, tag filter chips, search bar, article cards in grid, infinite scroll. Follow LibraryCatalogPage pattern.

- [ ] **Step 2:** Create `resources/client/plugins/blog/pages/ArticleDetailPage.tsx`

Cover image, title, author avatar + name, published date, category badge, tags, Tiptap-rendered HTML content (render the HTML string safely via a container element with `innerHTML` — sanitize server-side since content is authored by trusted users only), reaction bar, view count.

- [ ] **Step 3:** Create `resources/client/plugins/blog/pages/ArticleEditorPage.tsx`

Used for both `/blog/new` and `/blog/:slug/edit`. TiptapEditor component, title input, category select, tag multi-select, cover image upload, excerpt textarea, status dropdown (draft/published/scheduled), date picker (visible when scheduled), meta title/description fields. Save button calls create or update mutation.

- [ ] **Step 4:** Verify no TypeScript errors: `npx tsc --noEmit --pretty 2>&1 | head -20`

---

### Task 19: Live Meeting pages — MeetingsPage, MeetingDetailPage

**Files:**
- Create: `resources/client/plugins/live-meeting/components/MeetingCard.tsx`
- Create: `resources/client/plugins/live-meeting/pages/MeetingsPage.tsx`
- Create: `resources/client/plugins/live-meeting/pages/MeetingDetailPage.tsx`

- [ ] **Step 1:** Create `resources/client/plugins/live-meeting/components/MeetingCard.tsx`

Card with title, platform icon (Zoom/Meet/YouTube), host name, start time, "Live" badge (pulsing red when `is_live`), "Join" button (opens `meeting_url` in new tab).

- [ ] **Step 2:** Create `resources/client/plugins/live-meeting/pages/MeetingsPage.tsx`

Two sections: "Live Now" (highlighted, uses `useLiveMeetings()`) and "Upcoming" (chronological list with MeetingCard). Search bar for filtering.

- [ ] **Step 3:** Create `resources/client/plugins/live-meeting/pages/MeetingDetailPage.tsx`

Title, description, platform icon, host info, time range with timezone, large "Join Meeting" button (new tab), countdown timer if upcoming (not yet started).

- [ ] **Step 4:** Verify no TypeScript errors: `npx tsc --noEmit --pretty 2>&1 | head -20`

---

### Task 20: Frontend Integration — app-router.tsx, AdminLayout.tsx, Tiptap npm packages

**Files:**
- Edit: `resources/client/app-router.tsx`
- Edit: `resources/client/admin/AdminLayout.tsx`
- Run: `npm install @tiptap/react @tiptap/starter-kit @tiptap/extension-image @tiptap/extension-link @tiptap/pm`

- [ ] **Step 1:** Install Tiptap npm packages

```bash
npm install @tiptap/react @tiptap/starter-kit @tiptap/extension-image @tiptap/extension-link @tiptap/pm
```

- [ ] **Step 2:** Add lazy imports and routes to `app-router.tsx`

```tsx
const BlogListPage = React.lazy(() => import('./plugins/blog/pages/BlogListPage'));
const ArticleDetailPage = React.lazy(() => import('./plugins/blog/pages/ArticleDetailPage'));
const ArticleEditorPage = React.lazy(() => import('./plugins/blog/pages/ArticleEditorPage'));
const MeetingsPage = React.lazy(() => import('./plugins/live-meeting/pages/MeetingsPage'));
const MeetingDetailPage = React.lazy(() => import('./plugins/live-meeting/pages/MeetingDetailPage'));
```

Routes:
```tsx
{ path: '/blog', element: <BlogListPage /> },
{ path: '/blog/new', element: <ArticleEditorPage /> },
{ path: '/blog/:slug/edit', element: <ArticleEditorPage /> },
{ path: '/blog/:slug', element: <ArticleDetailPage /> },
{ path: '/meetings', element: <MeetingsPage /> },
{ path: '/meetings/:meetingId', element: <MeetingDetailPage /> },
```

Note: `/blog/new` must come before `/blog/:slug` to avoid matching "new" as a slug.

- [ ] **Step 3:** Add sidebar entries to `AdminLayout.tsx`

```tsx
{ label: 'Blog', path: '/blog', icon: 'FileText', permission: 'blog.view' },
{ label: 'Meetings', path: '/meetings', icon: 'Video', permission: 'live_meeting.view' },
```

- [ ] **Step 4:** Verify the app builds: `npx tsc --noEmit --pretty 2>&1 | head -20`
