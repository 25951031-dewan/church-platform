# Plan 6: Church Builder Plugin — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Church Builder plugin — a Facebook Page-style church profile system with community membership (join/leave), custom static pages per church, verification badges, enhanced directory with geo-search, and a tabbed profile page. The plugin replaces the legacy `App\Models\Church` with a proper plugin model while reusing the existing `churches`, `church_user`, and `church_settings` tables.

**Architecture:** Follows the established plugin pattern: `app/Plugins/ChurchBuilder/` with Loader, Crupdate, Paginate, Delete services, Policies, form requests, and a PermissionSeeder. The existing `churches` table (created by `2026_03_01_000001`) and `church_user` pivot (created by `2026_03_06_000002`) are reused — a new migration adds verification columns and a `church_pages` table for custom static pages. The plugin model adds `HasReactions` and new relationships (members, pages). The legacy admin controller at `App\Http\Controllers\Api\ChurchController` continues working for backward compatibility (same table) — the plugin adds the community-facing features: membership, pages, profile, directory.

**Key Design Decisions:**
1. **Plugin model replaces legacy.** `App\Plugins\ChurchBuilder\Models\Church` uses the same `churches` table but adds `HasReactions`, membership relationships, page relationships, geo scopes, and verification helpers. The morph map registers `church` → plugin model. The legacy `App\Models\Church` and `App\Http\Controllers\Api\ChurchController` remain untouched for backward compat.
2. **Membership is immediate.** For V1, joining a church sets `status = 'approved'` immediately. The `status` column is added to support future approval workflows. Church admins can remove members.
3. **Church Pages ≠ App Pages.** Church pages are church-scoped static content pages (like "Our Beliefs", "Staff", "History"). They live in a new `church_pages` table, not the existing app-level `pages` table.
4. **Verification = admin action.** Only platform admins can verify a church. Verified churches get a badge in the directory and profile.
5. **Geo-search uses Haversine.** The directory supports radius-based search using latitude/longitude with the Haversine formula. No external mapping library needed — the frontend shows a list view (map view is a future enhancement).
6. **Morph key is `church`.** Matches the model name and table convention.

**Tech Stack:** Laravel 12 plugin, Eloquent on existing + enhanced tables, TanStack React Query, Tailwind CSS.

**Spec:** `docs/superpowers/specs/2026-03-28-church-community-platform-design.md` (Church Builder section)

**Depends on:** Plan 4 (Events + Sermons) — specifically: `HasReactions` trait (Plan 2), morph map pattern (Plan 2), plugin loading via `PluginManager` (Plan 2), permission seeder pattern (Plan 2).

---

## File Structure Overview

```
app/Plugins/ChurchBuilder/
├── Models/
│   ├── Church.php                     # Plugin model (replaces App\Models\Church for plugin features)
│   ├── ChurchMember.php               # Eloquent model for church_user pivot
│   └── ChurchPage.php                 # Custom static pages per church
├── Services/
│   ├── ChurchLoader.php               # API response formatting (profile with counts)
│   ├── PaginateChurches.php           # Enhanced directory with geo-search
│   ├── ChurchMemberService.php        # Join/leave/members/remove
│   ├── CrupdateChurchPage.php         # Create/update church pages
│   └── DeleteChurchPages.php          # Delete pages
├── Controllers/
│   ├── ChurchProfileController.php    # Public profile + directory + verify/feature
│   ├── ChurchMemberController.php     # Membership actions
│   └── ChurchPageController.php       # Church pages CRUD
├── Policies/
│   ├── ChurchPolicy.php               # own/any + verify + feature + manage_members
│   └── ChurchPagePolicy.php           # Church page access control
├── Requests/
│   └── ModifyChurchPage.php           # Validation for church pages
├── Routes/
│   └── api.php                        # Plugin routes
├── Database/
│   └── Seeders/
│       └── ChurchBuilderPermissionSeeder.php # 10 permissions across 7 roles

database/
├── migrations/
│   └── 0006_01_01_000001_enhance_churches_and_add_church_pages.php
├── factories/
│   └── ChurchPageFactory.php

tests/Feature/ChurchBuilder/
├── ChurchProfileTest.php              # 5 tests
├── ChurchMembershipTest.php           # 6 tests
└── ChurchPageTest.php                 # 5 tests

resources/client/
├── plugins/church-builder/
│   ├── queries.ts                     # TanStack Query hooks
│   ├── pages/
│   │   ├── ChurchDirectoryPage.tsx    # Directory with search + geo-search + filters
│   │   └── ChurchProfilePage.tsx      # Tabbed profile (About, Members, Pages, Events)
│   └── components/
│       ├── ChurchCard.tsx             # Directory card
│       ├── ChurchAboutTab.tsx         # About/contact info tab
│       ├── ChurchMembersTab.tsx       # Members list + join/leave
│       └── ChurchPagesTab.tsx         # Custom pages list + viewer
```

---

## Tasks

### Task 1: Migration — enhance churches + add church_pages
**Files:** `database/migrations/0006_01_01_000001_enhance_churches_and_add_church_pages.php`

The existing `churches` table has all the core fields (name, slug, status, location, contact, appearance, SEO). We add verification columns. The existing `church_user` pivot has role (member/admin) — we add a `status` column for future approval flow. We also create a `church_pages` table for custom static content pages.

- [ ] **Step 1:** Create migration `0006_01_01_000001_enhance_churches_and_add_church_pages.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add verification to churches
        Schema::table('churches', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_featured');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });

        // Add status to church_user pivot for future approval workflow
        Schema::table('church_user', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved'])->default('approved')->after('role');
        });

        // Church pages (custom static pages per church)
        Schema::create('church_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
            $table->index(['church_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_pages');

        Schema::table('church_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['is_verified', 'verified_at', 'verified_by']);
        });
    }
};
```

- [ ] **Step 2:** Verify syntax: `php -l database/migrations/0006_01_01_000001_enhance_churches_and_add_church_pages.php`

---

### Task 2: Models — Church, ChurchMember, ChurchPage
**Files:** `app/Plugins/ChurchBuilder/Models/Church.php`, `app/Plugins/ChurchBuilder/Models/ChurchMember.php`, `app/Plugins/ChurchBuilder/Models/ChurchPage.php`

The plugin Church model replaces `App\Models\Church` for plugin features. It uses `HasReactions` and adds membership/page relationships plus a geo-search scope (Haversine). The `ChurchMember` model wraps the `church_user` pivot table. `ChurchPage` is for custom static pages.

- [ ] **Step 1:** Create `app/Plugins/ChurchBuilder/Models/Church.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Church extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'service_hours' => 'array',
        'documents' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['logo_url', 'cover_photo_url'];

    protected static function newFactory()
    {
        return \Database\Factories\ChurchFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Church $church) {
            if (empty($church->slug)) {
                $slug = Str::slug($church->name);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $church->slug = $slug;
            }
        });
    }

    // --- Relationships ---

    public function admin(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'admin_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'verified_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChurchMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->hasMany(ChurchMember::class)->where('status', 'approved');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ChurchPage::class)->orderBy('sort_order');
    }

    public function publishedPages(): HasMany
    {
        return $this->hasMany(ChurchPage::class)
            ->where('is_published', true)
            ->orderBy('sort_order');
    }

    // --- Scopes ---

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 50)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    // --- Accessors ---

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->admin_user_id === $userId || $this->created_by === $userId;
    }

    public function isChurchAdmin(int $userId): bool
    {
        return $this->admin_user_id === $userId
            || $this->members()->where('user_id', $userId)->where('role', 'admin')->exists();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->where('status', 'approved')->exists();
    }

    public function getMembership(int $userId): ?ChurchMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/ChurchBuilder/Models/ChurchMember.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMember extends Model
{
    protected $table = 'church_user';

    protected $guarded = ['id'];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/ChurchBuilder/Models/ChurchPage.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChurchPage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChurchPage $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\ChurchPageFactory::new();
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}
```

- [ ] **Step 4:** Verify: `php -l app/Plugins/ChurchBuilder/Models/*.php`

---

### Task 3: Services — Loader, Paginate, MemberService, CrupdateChurchPage, DeleteChurchPages
**Files:** `app/Plugins/ChurchBuilder/Services/ChurchLoader.php`, `app/Plugins/ChurchBuilder/Services/PaginateChurches.php`, `app/Plugins/ChurchBuilder/Services/ChurchMemberService.php`, `app/Plugins/ChurchBuilder/Services/CrupdateChurchPage.php`, `app/Plugins/ChurchBuilder/Services/DeleteChurchPages.php`

The Loader formats church profiles with counts. PaginateChurches adds geo-search to the directory. ChurchMemberService handles join/leave. CrupdateChurchPage and DeleteChurchPages manage custom static pages.

- [ ] **Step 1:** Create `ChurchLoader.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;

class ChurchLoader
{
    public function load(Church $church): Church
    {
        return $church->load([
            'admin:id,name,avatar',
        ])->loadCount(['approvedMembers', 'publishedPages', 'reactions']);
    }

    public function loadForDetail(Church $church): array
    {
        $this->load($church);
        $church->load(['publishedPages:id,church_id,title,slug,sort_order']);

        $data = $church->toArray();

        $userId = auth()->id();
        if ($userId) {
            $membership = $church->getMembership($userId);
            $data['current_user_membership'] = $membership ? [
                'role' => $membership->role,
                'status' => $membership->status,
                'joined_at' => $membership->joined_at,
            ] : null;
            $data['is_church_admin'] = $church->isChurchAdmin($userId);
        }

        return $data;
    }
}
```

- [ ] **Step 2:** Create `PaginateChurches.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateChurches
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Church::query()
            ->approved()
            ->with(['admin:id,name,avatar'])
            ->withCount('approvedMembers');

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->boolean('verified')) {
            $query->verified();
        }

        // Geo-search: lat, lng, radius (km)
        if ($request->has('lat') && $request->has('lng')) {
            $lat = (float) $request->input('lat');
            $lng = (float) $request->input('lng');
            $radius = (float) $request->input('radius', 50);
            $query->nearby($lat, $lng, $radius);
        } else {
            $query->latest();
        }

        if ($request->has('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->has('denomination')) {
            $query->where('denomination', $request->input('denomination'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('denomination', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(min((int) $request->input('per_page', 12), 50));
    }
}
```

- [ ] **Step 3:** Create `ChurchMemberService.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchMember;

class ChurchMemberService
{
    public function join(Church $church, int $userId): ChurchMember
    {
        return ChurchMember::firstOrCreate(
            ['church_id' => $church->id, 'user_id' => $userId],
            ['role' => 'member', 'status' => 'approved', 'joined_at' => now()]
        );
    }

    public function leave(Church $church, int $userId): void
    {
        ChurchMember::where('church_id', $church->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function removeMember(Church $church, int $userId): void
    {
        $this->leave($church, $userId);
    }

    public function updateRole(Church $church, int $userId, string $role): ?ChurchMember
    {
        $member = ChurchMember::where('church_id', $church->id)
            ->where('user_id', $userId)
            ->first();

        if ($member) {
            $member->update(['role' => $role]);
        }

        return $member;
    }
}
```

- [ ] **Step 4:** Create `CrupdateChurchPage.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\ChurchPage;

class CrupdateChurchPage
{
    public function execute(array $data, ?ChurchPage $page = null): ChurchPage
    {
        $fields = ['title', 'slug', 'body', 'sort_order', 'is_published'];

        if ($page) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $page->update($updateData);
        } else {
            $createData = [
                'church_id' => $data['church_id'],
                'created_by' => $data['created_by'] ?? null,
            ];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $page = ChurchPage::create($createData);
        }

        return $page;
    }
}
```

- [ ] **Step 5:** Create `DeleteChurchPages.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\ChurchPage;

class DeleteChurchPages
{
    public function execute(array $ids): void
    {
        ChurchPage::whereIn('id', $ids)->delete();
    }
}
```

- [ ] **Step 6:** Verify: `php -l app/Plugins/ChurchBuilder/Services/*.php`

---

### Task 4: Policy and form requests
**Files:** `app/Plugins/ChurchBuilder/Policies/ChurchPolicy.php`, `app/Plugins/ChurchBuilder/Policies/ChurchPagePolicy.php`, `app/Plugins/ChurchBuilder/Requests/ModifyChurchPage.php`

- [ ] **Step 1:** Create `app/Plugins/ChurchBuilder/Policies/ChurchPolicy.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Policies;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use Common\Core\BasePolicy;

class ChurchPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('churches.view');
    }

    public function view(User $user, Church $church): bool
    {
        // Approved churches are visible to all authenticated users
        if ($church->status === 'approved') {
            return true;
        }
        // Own church
        if ($church->isOwnedBy($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.update_any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('churches.create');
    }

    public function update(User $user, Church $church): bool
    {
        if ($church->isOwnedBy($user->id) || $church->isChurchAdmin($user->id)) {
            return $user->hasPermission('churches.update');
        }
        return $user->hasPermission('churches.update_any');
    }

    public function delete(User $user, Church $church): bool
    {
        if ($church->isOwnedBy($user->id)) {
            return $user->hasPermission('churches.delete');
        }
        return $user->hasPermission('churches.delete_any');
    }

    public function verify(User $user): bool
    {
        return $user->hasPermission('churches.verify');
    }

    public function feature(User $user): bool
    {
        return $user->hasPermission('churches.feature');
    }

    public function manageMembers(User $user, Church $church): bool
    {
        if ($church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_members');
    }

    public function managePages(User $user, Church $church): bool
    {
        if ($church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/ChurchBuilder/Policies/ChurchPagePolicy.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Policies;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Common\Core\BasePolicy;

class ChurchPagePolicy extends BasePolicy
{
    public function view(User $user, ChurchPage $page): bool
    {
        if ($page->is_published) {
            return true;
        }
        return $page->church && $page->church->isChurchAdmin($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('churches.manage_pages');
    }

    public function update(User $user, ChurchPage $page): bool
    {
        if ($page->church && $page->church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }

    public function delete(User $user, ChurchPage $page): bool
    {
        if ($page->church && $page->church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/ChurchBuilder/Requests/ModifyChurchPage.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyChurchPage extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
```

- [ ] **Step 4:** Verify: `php -l app/Plugins/ChurchBuilder/Policies/*.php && php -l app/Plugins/ChurchBuilder/Requests/*.php`

---

### Task 5: Controllers + routes + seeder
**Files:** `app/Plugins/ChurchBuilder/Controllers/ChurchProfileController.php`, `app/Plugins/ChurchBuilder/Controllers/ChurchMemberController.php`, `app/Plugins/ChurchBuilder/Controllers/ChurchPageController.php`, `app/Plugins/ChurchBuilder/Routes/api.php`, `app/Plugins/ChurchBuilder/Database/Seeders/ChurchBuilderPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/ChurchBuilder/Controllers/ChurchProfileController.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Services\ChurchLoader;
use App\Plugins\ChurchBuilder\Services\PaginateChurches;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchProfileController extends Controller
{
    public function __construct(
        private ChurchLoader $loader,
        private PaginateChurches $paginator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Church::class);
        $churches = $this->paginator->execute($request);
        return response()->json($churches);
    }

    public function show(Church $church): JsonResponse
    {
        Gate::authorize('view', $church);
        return response()->json(['church' => $this->loader->loadForDetail($church)]);
    }

    public function verify(Church $church): JsonResponse
    {
        Gate::authorize('verify', Church::class);

        $church->update([
            'is_verified' => !$church->is_verified,
            'verified_at' => $church->is_verified ? null : now(),
            'verified_by' => $church->is_verified ? null : auth()->id(),
        ]);

        return response()->json([
            'is_verified' => $church->is_verified,
            'verified_at' => $church->verified_at,
        ]);
    }

    public function feature(Church $church): JsonResponse
    {
        Gate::authorize('feature', Church::class);

        $church->update(['is_featured' => !$church->is_featured]);

        return response()->json([
            'is_featured' => $church->is_featured,
        ]);
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/ChurchBuilder/Controllers/ChurchMemberController.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Services\ChurchMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchMemberController extends Controller
{
    public function __construct(
        private ChurchMemberService $memberService,
    ) {}

    public function join(Church $church): JsonResponse
    {
        $membership = $this->memberService->join($church, auth()->id());

        return response()->json([
            'membership' => $membership,
            'message' => 'Joined church successfully.',
        ], 201);
    }

    public function leave(Church $church): JsonResponse
    {
        $this->memberService->leave($church, auth()->id());

        return response()->json(null, 204);
    }

    public function members(Request $request, Church $church): JsonResponse
    {
        $query = $church->approvedMembers()
            ->with('user:id,name,avatar,email')
            ->latest('joined_at');

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $members = $query->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json($members);
    }

    public function removeMember(Church $church, int $userId): JsonResponse
    {
        Gate::authorize('manageMembers', $church);

        $this->memberService->removeMember($church, $userId);

        return response()->json(null, 204);
    }

    public function updateRole(Request $request, Church $church, int $userId): JsonResponse
    {
        Gate::authorize('manageMembers', $church);

        $validated = $request->validate([
            'role' => 'required|string|in:member,admin',
        ]);

        $member = $this->memberService->updateRole($church, $userId, $validated['role']);

        return response()->json(['member' => $member]);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/ChurchBuilder/Controllers/ChurchPageController.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use App\Plugins\ChurchBuilder\Requests\ModifyChurchPage;
use App\Plugins\ChurchBuilder\Services\CrupdateChurchPage;
use App\Plugins\ChurchBuilder\Services\DeleteChurchPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchPageController extends Controller
{
    public function __construct(
        private CrupdateChurchPage $crupdate,
        private DeleteChurchPages $deleter,
    ) {}

    public function index(Church $church): JsonResponse
    {
        $pages = $church->publishedPages()
            ->select(['id', 'church_id', 'title', 'slug', 'sort_order', 'is_published', 'created_at'])
            ->get();

        return response()->json(['pages' => $pages]);
    }

    public function show(Church $church, ChurchPage $churchPage): JsonResponse
    {
        Gate::authorize('view', $churchPage);

        $churchPage->load('creator:id,name');

        return response()->json(['page' => $churchPage]);
    }

    public function store(ModifyChurchPage $request, Church $church): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $data = $request->validated();
        $data['church_id'] = $church->id;
        $data['created_by'] = auth()->id();

        $page = $this->crupdate->execute($data);

        return response()->json(['page' => $page], 201);
    }

    public function update(ModifyChurchPage $request, Church $church, ChurchPage $churchPage): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $page = $this->crupdate->execute($request->validated(), $churchPage);

        return response()->json(['page' => $page]);
    }

    public function destroy(Church $church, ChurchPage $churchPage): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $this->deleter->execute([$churchPage->id]);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/ChurchBuilder/Routes/api.php`

```php
<?php

use App\Plugins\ChurchBuilder\Controllers\ChurchProfileController;
use App\Plugins\ChurchBuilder\Controllers\ChurchMemberController;
use App\Plugins\ChurchBuilder\Controllers\ChurchPageController;
use Illuminate\Support\Facades\Route;

// Church directory & profile
Route::get('churches', [ChurchProfileController::class, 'index']);
Route::get('churches/{church}', [ChurchProfileController::class, 'show']);

// Church admin actions
Route::patch('churches/{church}/verify', [ChurchProfileController::class, 'verify']);
Route::patch('churches/{church}/feature', [ChurchProfileController::class, 'feature']);

// Membership
Route::post('churches/{church}/join', [ChurchMemberController::class, 'join']);
Route::delete('churches/{church}/leave', [ChurchMemberController::class, 'leave']);
Route::get('churches/{church}/members', [ChurchMemberController::class, 'members']);
Route::delete('churches/{church}/members/{userId}', [ChurchMemberController::class, 'removeMember']);
Route::patch('churches/{church}/members/{userId}/role', [ChurchMemberController::class, 'updateRole']);

// Church pages
Route::get('churches/{church}/pages', [ChurchPageController::class, 'index']);
Route::get('churches/{church}/pages/{churchPage}', [ChurchPageController::class, 'show']);
Route::post('churches/{church}/pages', [ChurchPageController::class, 'store']);
Route::put('churches/{church}/pages/{churchPage}', [ChurchPageController::class, 'update']);
Route::delete('churches/{church}/pages/{churchPage}', [ChurchPageController::class, 'destroy']);
```

- [ ] **Step 5:** Create `app/Plugins/ChurchBuilder/Database/Seeders/ChurchBuilderPermissionSeeder.php`

```php
<?php

namespace App\Plugins\ChurchBuilder\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class ChurchBuilderPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'churches' => [
                'churches.view' => 'View Church Directory',
                'churches.create' => 'Register a Church',
                'churches.update' => 'Edit Own Church',
                'churches.update_any' => 'Edit Any Church',
                'churches.delete' => 'Delete Own Church',
                'churches.delete_any' => 'Delete Any Church',
                'churches.verify' => 'Verify/Unverify Churches',
                'churches.feature' => 'Feature Churches',
                'churches.manage_members' => 'Manage Church Members',
                'churches.manage_pages' => 'Manage Church Pages',
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

        $memberPerms = Permission::whereIn('name', [
            'churches.view', 'churches.create',
        ])->pluck('id');

        $churchAdminPerms = Permission::whereIn('name', [
            'churches.view', 'churches.create', 'churches.update',
            'churches.delete', 'churches.manage_members', 'churches.manage_pages',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'churches.view', 'churches.create', 'churches.update', 'churches.update_any',
            'churches.delete', 'churches.delete_any', 'churches.manage_members', 'churches.manage_pages',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'churches')->pluck('id');

        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($churchAdminPerms);
        }

        foreach (['pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($churchAdminPerms);
        }

        foreach (['moderator'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
        }

        foreach (['ministry-leader', 'member'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
```

- [ ] **Step 6:** Verify: `php -l app/Plugins/ChurchBuilder/Controllers/*.php && php -l app/Plugins/ChurchBuilder/Routes/api.php && php -l app/Plugins/ChurchBuilder/Database/Seeders/ChurchBuilderPermissionSeeder.php`

---

### Task 6: AppServiceProvider + morph map + plugin routes
**Files:** `app/Providers/AppServiceProvider.php`, `routes/api.php`, `common/foundation/src/Reactions/Controllers/ReactionController.php`

- [ ] **Step 1:** In `app/Providers/AppServiceProvider.php`:

Add imports:
```php
use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Policies\ChurchPolicy;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use App\Plugins\ChurchBuilder\Policies\ChurchPagePolicy;
```

> **Note:** Since the import `use App\Plugins\ChurchBuilder\Models\Church;` conflicts with any existing `Church` import (there shouldn't be one in AppServiceProvider currently — the legacy `App\Models\Church` is NOT imported there), add it directly. If there IS a conflict, use an alias: `use App\Plugins\ChurchBuilder\Models\Church as PluginChurch;` and adjust accordingly.

Add policy registration (after existing policies):
```php
Gate::policy(Church::class, ChurchPolicy::class);
Gate::policy(ChurchPage::class, ChurchPagePolicy::class);
```

Expand morph map (add `church`):
```php
Relation::enforceMorphMap([
    'post' => Post::class,
    'comment' => Comment::class,
    'group' => Group::class,
    'event' => Event::class,
    'sermon' => Sermon::class,
    'prayer_request' => PrayerRequest::class,
    'church' => Church::class,
]);
```

> **Warning:** The `Church` class in the morph map MUST be the plugin model (`App\Plugins\ChurchBuilder\Models\Church`), not the legacy `App\Models\Church`. This is ensured by the import at the top.

- [ ] **Step 2:** In `routes/api.php`, add after the Prayer plugin block inside the `auth:sanctum` group:

```php
        // Church Builder Plugin routes (authenticated)
        if (app(\Common\Core\PluginManager::class)->isEnabled('church_builder')) {
            require app_path('Plugins/ChurchBuilder/Routes/api.php');
        }
```

- [ ] **Step 3:** Update reaction allowlist to include `church`:

In `ReactionController.php`, change:
```php
'reactable_type' => 'required|string|in:post,comment,event,sermon,prayer_request',
```
To:
```php
'reactable_type' => 'required|string|in:post,comment,event,sermon,prayer_request,church',
```

- [ ] **Step 4:** Verify: `php -l app/Providers/AppServiceProvider.php && php -l routes/api.php && php -l common/foundation/src/Reactions/Controllers/ReactionController.php`

---

### Task 7: Factory + Tests (16 tests across 3 files)
**Files:** Factory and test files

- [ ] **Step 1:** Create `database/factories/ChurchFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\ChurchBuilder\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChurchFactory extends Factory
{
    protected $model = Church::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Church';
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'status' => 'approved',
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip_code' => fake()->postcode(),
            'country' => 'US',
            'latitude' => fake()->latitude(25, 48),
            'longitude' => fake()->longitude(-125, -70),
            'denomination' => fake()->randomElement(['Baptist', 'Methodist', 'Non-denominational', 'Catholic', 'Pentecostal']),
            'short_description' => fake()->sentence(),
            'primary_color' => '#4F46E5',
            'is_featured' => false,
            'is_verified' => false,
            'admin_user_id' => \App\Models\User::factory(),
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true, 'verified_at' => now()]);
    }

    public function withLocation(float $lat, float $lng): static
    {
        return $this->state(fn () => ['latitude' => $lat, 'longitude' => $lng]);
    }
}
```

- [ ] **Step 2:** Create `database/factories/ChurchPageFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChurchPageFactory extends Factory
{
    protected $model = ChurchPage::class;

    public function definition(): array
    {
        return [
            'church_id' => \App\Plugins\ChurchBuilder\Models\Church::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'body' => fake()->paragraphs(3, true),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_published' => true,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
```

- [ ] **Step 3:** Create `tests/Feature/ChurchBuilder/ChurchProfileTest.php` — 5 tests

```php
<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_member_can_list_churches(): void
    {
        $user = $this->memberUser();
        Church::factory()->count(3)->create(['status' => 'approved']);
        Church::factory()->create(['status' => 'pending']);

        $this->actingAs($user)->getJson('/api/v1/churches')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_member_can_view_approved_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}")
            ->assertOk()
            ->assertJsonPath('church.name', $church->name);
    }

    public function test_directory_filters_by_city(): void
    {
        $user = $this->memberUser();
        Church::factory()->create(['status' => 'approved', 'city' => 'Dallas']);
        Church::factory()->create(['status' => 'approved', 'city' => 'Houston']);

        $this->actingAs($user)->getJson('/api/v1/churches?city=Dallas')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_verify_church(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/verify")
            ->assertOk()
            ->assertJsonPath('is_verified', true);

        $this->assertTrue($church->fresh()->is_verified);
    }

    public function test_admin_can_toggle_featured(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }
}
```

- [ ] **Step 4:** Create `tests/Feature/ChurchBuilder/ChurchMembershipTest.php` — 6 tests

```php
<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchMember;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_user_can_join_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated()
            ->assertJsonPath('membership.role', 'member');

        $this->assertDatabaseHas('church_user', [
            'church_id' => $church->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    public function test_user_can_leave_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/churches/{$church->id}/leave")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_user', [
            'church_id' => $church->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_joining_is_idempotent(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated();
        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated();

        $this->assertEquals(1, ChurchMember::where([
            'church_id' => $church->id,
            'user_id' => $user->id,
        ])->count());
    }

    public function test_can_list_church_members(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        foreach (range(1, 3) as $_) {
            ChurchMember::create([
                'church_id' => $church->id,
                'user_id' => User::factory()->create()->id,
                'role' => 'member',
                'status' => 'approved',
                'joined_at' => now(),
            ]);
        }

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}/members")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_remove_member(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $member = User::factory()->create();
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/v1/churches/{$church->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_user', [
            'church_id' => $church->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_admin_can_update_member_role(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $member = User::factory()->create();
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/members/{$member->id}/role", [
            'role' => 'admin',
        ])->assertOk()
            ->assertJsonPath('member.role', 'admin');
    }
}
```

- [ ] **Step 5:** Create `tests/Feature/ChurchBuilder/ChurchPageTest.php` — 5 tests

```php
<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    public function test_can_list_published_church_pages(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);
        ChurchPage::factory()->count(2)->create(['church_id' => $church->id, 'is_published' => true]);
        ChurchPage::factory()->unpublished()->create(['church_id' => $church->id]);

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}/pages")
            ->assertOk()
            ->assertJsonCount(2, 'pages');
    }

    public function test_admin_can_create_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);

        $this->actingAs($admin)->postJson("/api/v1/churches/{$church->id}/pages", [
            'title' => 'Our Beliefs',
            'body' => 'We believe in the power of prayer.',
        ])->assertCreated()
            ->assertJsonPath('page.title', 'Our Beliefs');
    }

    public function test_admin_can_update_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $page = ChurchPage::factory()->create(['church_id' => $church->id]);

        $this->actingAs($admin)->putJson("/api/v1/churches/{$church->id}/pages/{$page->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('page.title', 'Updated Title');
    }

    public function test_admin_can_delete_church_page(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $page = ChurchPage::factory()->create(['church_id' => $church->id]);

        $this->actingAs($admin)->deleteJson("/api/v1/churches/{$church->id}/pages/{$page->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_pages', ['id' => $page->id]);
    }

    public function test_member_cannot_create_page_on_other_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/pages", [
            'title' => 'Unauthorized Page',
            'body' => 'Should not work.',
        ])->assertForbidden();
    }
}
```

- [ ] **Step 6:** Verify: `php -l database/factories/ChurchFactory.php && php -l database/factories/ChurchPageFactory.php && php -l tests/Feature/ChurchBuilder/*.php`

---

### Task 8: React — Church Builder frontend
**Files:** `resources/client/plugins/church-builder/queries.ts`, church-builder pages and components

- [ ] **Step 1:** Create `resources/client/plugins/church-builder/queries.ts`

Hooks: `useChurchDirectory` (paginated, filterable), `useChurch` (detail), `useJoinChurch`, `useLeaveChurch`, `useChurchMembers`, `useRemoveMember`, `useUpdateMemberRole`, `useChurchPages`, `useChurchPage`, `useVerifyChurch`, `useFeatureChurch`.

Types: `Church`, `ChurchMember`, `ChurchPage`.

- [ ] **Step 2:** Create `resources/client/plugins/church-builder/components/ChurchCard.tsx`

Displays: church name, city, denomination, short_description (truncated), logo, verified badge, featured badge, member count. Clicking navigates to profile.

- [ ] **Step 3:** Create `resources/client/plugins/church-builder/components/ChurchAboutTab.tsx`

Shows: short_description, mission_statement, vision_statement, contact info (email, phone, website, address), service_hours formatted list, social links, denomination, year_founded.

- [ ] **Step 4:** Create `resources/client/plugins/church-builder/components/ChurchMembersTab.tsx`

Lists members with avatar + name + role badge. If current user is church admin, shows "Remove" button per member. Join/Leave button at top based on current membership status.

- [ ] **Step 5:** Create `resources/client/plugins/church-builder/components/ChurchPagesTab.tsx`

Lists published pages as sidebar links. Clicking a page shows its body content. If no pages, shows empty state.

- [ ] **Step 6:** Create `resources/client/plugins/church-builder/pages/ChurchDirectoryPage.tsx`

Search bar + city/denomination filter dropdowns. List of ChurchCards with infinite scroll. "Register Church" button (if user has permission).

- [ ] **Step 7:** Create `resources/client/plugins/church-builder/pages/ChurchProfilePage.tsx`

Facebook Page-style layout: cover photo, logo (overlapping), church name + verification badge, Join/Leave button, member count. Tabs: About, Members, Pages. Uses tab state to switch content. Cover photo uses `primary_color` as fallback background.

---

### Task 9: Router + sidebar updates
**Files:** `resources/client/app-router.tsx`, `resources/client/admin/AdminLayout.tsx`

- [ ] **Step 1:** In `app-router.tsx`:

Add lazy imports:
```tsx
const ChurchDirectoryPage = lazy(() => import('./plugins/church-builder/pages/ChurchDirectoryPage').then(m => ({default: m.ChurchDirectoryPage})));
const ChurchProfilePage = lazy(() => import('./plugins/church-builder/pages/ChurchProfilePage').then(m => ({default: m.ChurchProfilePage})));
```

Add routes inside `<RequireAuth />`:
```tsx
<Route path="/churches" element={<ChurchDirectoryPage />} />
<Route path="/churches/:churchId" element={<ChurchProfilePage />} />
```

- [ ] **Step 2:** In `AdminLayout.tsx`, add sidebar item after Prayers:

```tsx
{ label: 'Churches', path: '/churches', icon: 'Church', permission: 'churches.view' },
```

---

## Verification Checklist

After all tasks complete:
1. `php -l` passes on all new PHP files
2. All 16 tests pass: `php artisan test --filter=ChurchBuilder`
3. New files committed
4. React components follow established patterns (TanStack Query hooks, Tailwind, dark mode classes)
5. Plugin routes load conditionally via `PluginManager::isEnabled('church_builder')`
6. Morph map includes `church` → plugin Church model
7. Reaction allowlist includes `church`
8. Permission seeder creates 10 permissions assigned to 7 role tiers
