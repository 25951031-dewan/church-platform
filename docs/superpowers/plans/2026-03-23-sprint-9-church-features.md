# Sprint 9 — Church-Specific Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add ministry sub-pages (parent→child page hierarchy) and community type badges to the church platform.

**Architecture:** Two independent enhancements to existing plugins. Sub-pages extend the Entity plugin — `parent_entity_id` already exists in the `church_entities` migration as a Sprint 9 placeholder; this sprint wires up model relationships, a dedicated `SubPageController`, two API routes, and a frontend sub-pages grid on `PageDetailPage`. Community types add a single `community_type` nullable string column to `communities` (string not enum, SQLite-safe — same pattern as `privacy_closed` from Sprint 8), surface it through the existing API, and render color-coded badges on the frontend.

**Tech Stack:** Laravel 11, PestPHP (SQLite in-memory), React 18, TypeScript, TanStack Query, Tailwind CSS, Axios

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `plugins/Entity/Models/ChurchEntity.php` | Modify | Add `parent()` + `subPages()` relationships |
| `plugins/Entity/Controllers/SubPageController.php` | Create | `index()` and `store()` for sub-pages |
| `plugins/Entity/routes/api.php` | Modify | Add 2 sub-page routes |
| `plugins/Community/database/migrations/2026_05_05_000002_add_community_type_to_communities.php` | Create | Add `community_type` nullable string column |
| `plugins/Community/Models/Community.php` | Modify | Add `community_type` to `$fillable`; add `communityTypeLabel()` helper |
| `plugins/Community/Controllers/CommunityController.php` | Modify | Accept `community_type` in `store()` validation |
| `database/factories/CommunityFactory.php` | Modify | Include `community_type` in `definition()` |
| `resources/js/plugins/pages/PageDetailPage.tsx` | Modify | Add sub-pages grid section |
| `resources/js/plugins/community/CommunityPage.tsx` | Modify | Add community type badge on cards |
| `resources/js/plugins/community/CommunityDetailPage.tsx` | Modify | Add community type badge in header |
| `tests/Feature/SubPageTest.php` | Create | 4 sub-page API tests |
| `tests/Feature/CommunityTypeTest.php` | Create | 3 community type tests |

---

## Task 1: Sub-page model relationships

**Files:**
- Modify: `plugins/Entity/Models/ChurchEntity.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SubPageTest.php`:

```php
<?php

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('can list sub-pages of a parent page', function () {
    $parent = ChurchEntity::factory()->create(['type' => 'page']);
    ChurchEntity::factory()->count(3)->create([
        'type' => 'page',
        'parent_entity_id' => $parent->id,
    ]);

    $this->getJson("/api/v1/pages/{$parent->id}/sub-pages")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('creating a sub-page requires admin of parent', function () {
    $owner = User::factory()->create();
    $parent = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $owner->id]);
    EntityMember::create([
        'entity_id' => $parent->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $nonAdmin = User::factory()->create();

    $this->actingAs($nonAdmin)
        ->postJson("/api/v1/pages/{$parent->id}/sub-pages", ['name' => 'Youth Choir'])
        ->assertForbidden();
});

test('admin can create a sub-page under their parent page', function () {
    $owner = User::factory()->create();
    $parent = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $owner->id]);
    EntityMember::create([
        'entity_id' => $parent->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$parent->id}/sub-pages", [
            'name'        => 'Youth Ministry',
            'description' => 'For young adults',
        ])
        ->assertCreated()
        ->assertJsonPath('parent_entity_id', $parent->id)
        ->assertJsonPath('type', 'page');
});

test('listing sub-pages of unknown parent returns 404', function () {
    $this->getJson('/api/v1/pages/9999/sub-pages')
        ->assertNotFound();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/SubPageTest.php
```

Expected: FAIL (routes don't exist yet → 404 responses)

- [ ] **Step 3: Add relationships to ChurchEntity model**

In `plugins/Entity/Models/ChurchEntity.php`, add inside the class after the existing `admins()` relation:

```php
public function parent(): BelongsTo
{
    return $this->belongsTo(ChurchEntity::class, 'parent_entity_id');
}

public function subPages(): HasMany
{
    return $this->hasMany(ChurchEntity::class, 'parent_entity_id');
}
```

Also add the import at the top:
```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

(Both may already be imported — check before adding.)

- [ ] **Step 4: Commit model changes**

```bash
git add plugins/Entity/Models/ChurchEntity.php
git commit -m "feat(entity): add parent/subPages relationships on ChurchEntity"
```

---

## Task 2: SubPageController + routes

**Files:**
- Create: `plugins/Entity/Controllers/SubPageController.php`
- Modify: `plugins/Entity/routes/api.php`

- [ ] **Step 1: Create SubPageController**

```php
<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class SubPageController extends Controller
{
    /** List sub-pages of a parent page (public). */
    public function index(int $id): JsonResponse
    {
        $parent = ChurchEntity::pages()->active()->findOrFail($id);

        $subPages = $parent->subPages()
            ->where('type', 'page')
            ->where('is_active', true)
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->latest()
            ->paginate(20);

        return response()->json($subPages);
    }

    /** Create a sub-page under a parent (admin of parent only). */
    public function store(Request $request, int $id): JsonResponse
    {
        $parent = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($parent->isAdmin($request->user()->id), 403, 'Only page admins may create sub-pages.');

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $slug = $this->uniqueSlug(Str::slug($data['name']));

        $subPage = ChurchEntity::create(array_merge($data, [
            'type'             => 'page',
            'owner_id'         => $request->user()->id,
            'slug'             => $slug,
            'parent_entity_id' => $parent->id,
        ]));

        // Auto-add creator as admin member of the sub-page
        EntityMember::create([
            'entity_id' => $subPage->id,
            'user_id'   => $request->user()->id,
            'role'      => 'admin',
            'status'    => 'approved',
        ]);

        $subPage->increment('members_count');

        return response()->json($subPage->load('owner:id,name,avatar'), 201);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i    = 1;
        while (ChurchEntity::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug ?: 'page-' . uniqid();
    }
}
```

- [ ] **Step 2: Add routes to Entity plugin**

In `plugins/Entity/routes/api.php`, add `use Plugins\Entity\Controllers\SubPageController;` at the top, then add two routes **inside** the `Route::prefix('v1')` block:

Public route (before the auth:sanctum group):
```php
Route::get('/pages/{id}/sub-pages', [SubPageController::class, 'index'])->name('sub-pages.index');
```

Authenticated route (inside the `auth:sanctum` group):
```php
Route::post('/pages/{id}/sub-pages', [SubPageController::class, 'store'])->name('sub-pages.store');
```

- [ ] **Step 3: Run tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/SubPageTest.php
```

Expected: 4 passing

- [ ] **Step 4: Commit**

```bash
git add plugins/Entity/Controllers/SubPageController.php plugins/Entity/routes/api.php tests/Feature/SubPageTest.php
git commit -m "feat(entity): SubPageController — GET/POST /pages/{id}/sub-pages, 4 tests passing"
```

---

## Task 3: Community type column + model

**Files:**
- Create: `plugins/Community/database/migrations/2026_05_05_000002_add_community_type_to_communities.php`
- Modify: `plugins/Community/Models/Community.php`
- Modify: `plugins/Community/Controllers/CommunityController.php`
- Modify: `database/factories/CommunityFactory.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CommunityTypeTest.php`:

```php
<?php

use App\Models\User;
use Plugins\Community\Models\Community;

test('creating a community accepts a community_type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/communities', [
            'name'           => 'Sunday Bible Study',
            'community_type' => 'bible_study',
        ])
        ->assertCreated()
        ->assertJsonPath('community_type', 'bible_study');
});

test('community_type appears in the community list', function () {
    Community::factory()->create([
        'community_type' => 'prayer_circle',
        'status'         => 'active',
        'is_counsel_group' => false,
    ]);

    $this->getJson('/api/v1/communities')
        ->assertOk()
        ->assertJsonFragment(['community_type' => 'prayer_circle']);
});

test('community_type defaults to null when not provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/communities', ['name' => 'General Group'])
        ->assertCreated();

    expect($response->json('community_type'))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityTypeTest.php
```

Expected: FAIL — `community_type` column does not exist yet.

- [ ] **Step 3: Create migration**

Create `plugins/Community/database/migrations/2026_05_05_000002_add_community_type_to_communities.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->string('community_type')->nullable()->after('privacy_closed');
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn('community_type');
        });
    }
};
```

> **Why string not enum?** SQLite (used in tests) enforces enum columns as CHECK constraints and cannot ALTER them. A nullable string gives full flexibility with zero migration pain. Validation in the controller enforces the allowed values.

- [ ] **Step 4: Update Community model**

In `plugins/Community/Models/Community.php`:

1. Add `'community_type'` to the `$fillable` array.
2. Add a helper method after `isClosed()`:

```php
/** Human-readable label for the community type. */
public function communityTypeLabel(): ?string
{
    return match ($this->community_type) {
        'small_group'    => 'Small Group',
        'prayer_circle'  => 'Prayer Circle',
        'bible_study'    => 'Bible Study',
        'ministry_team'  => 'Ministry Team',
        'choir'          => 'Choir',
        default          => null,
    };
}
```

- [ ] **Step 5: Update CommunityController store() validation**

In `plugins/Community/Controllers/CommunityController.php`, add to the `validate()` array in `store()`:

```php
'community_type' => ['nullable', 'in:small_group,prayer_circle,bible_study,ministry_team,choir'],
```

And include it in the `Community::create()` call by ensuring `$validated` carries it through (it already will since `array_merge($validated, [...])` passes all validated fields).

- [ ] **Step 6: Update CommunityFactory**

In `database/factories/CommunityFactory.php`, add `'community_type' => null` to the `definition()` array return value.

- [ ] **Step 7: Run tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/CommunityTypeTest.php
```

Expected: 3 passing

- [ ] **Step 8: Commit**

```bash
git add plugins/Community/database/migrations/2026_05_05_000002_add_community_type_to_communities.php \
        plugins/Community/Models/Community.php \
        plugins/Community/Controllers/CommunityController.php \
        database/factories/CommunityFactory.php \
        tests/Feature/CommunityTypeTest.php
git commit -m "feat(community): community_type column — 3 tests passing"
```

---

## Task 4: Frontend — sub-pages grid on PageDetailPage

**Files:**
- Modify: `resources/js/plugins/pages/PageDetailPage.tsx`

- [ ] **Step 1: Add sub-pages query and grid section**

In `PageDetailPage.tsx`, add:

1. After the existing `followMutation`, add a TanStack Query for sub-pages:

```tsx
const { data: subPages } = useQuery({
    queryKey: ['page-sub-pages', page?.id],
    queryFn:  () => axios.get(`/api/v1/pages/${page!.id}/sub-pages`).then(r => r.data.data ?? []),
    enabled: !!page,
})
```

2. Replace the `{/* Posts placeholder */}` block at the bottom of the JSX return with a sub-pages grid section (keep it as the last section before closing `</div>`):

```tsx
{/* Sub-pages (Ministry Hub) */}
{subPages && subPages.length > 0 && (
    <div className="px-6 py-6 border-t border-gray-100">
        <h2 className="text-base font-semibold text-gray-900 mb-4">Ministries</h2>
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {subPages.map((sp: { id: number; name: string; slug: string; profile_image?: string; approved_members_count?: number }) => (
                <button
                    key={sp.id}
                    onClick={() => navigate(`/pages/${sp.slug}`)}
                    className="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-colors text-left"
                >
                    <div className="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center overflow-hidden">
                        {sp.profile_image ? (
                            <img src={sp.profile_image} alt={sp.name} className="w-full h-full object-cover" />
                        ) : (
                            <span className="text-xl font-bold text-blue-600">{sp.name[0]}</span>
                        )}
                    </div>
                    <span className="text-xs font-medium text-gray-800 text-center leading-tight">{sp.name}</span>
                    {sp.approved_members_count !== undefined && (
                        <span className="text-xs text-gray-400">{sp.approved_members_count} members</span>
                    )}
                </button>
            ))}
        </div>
    </div>
)}

{/* Posts placeholder — Sprint 7 will add entity-scoped feed */}
<div className="px-6 py-8 border-t border-gray-100 text-center text-gray-400 text-sm">
    Posts from this page will appear here in Sprint 7.
</div>
```

> **Note:** The `subPages` variable is typed inline in the `map` to avoid a separate interface declaration for a transient sub-component. The `page?.id` in `queryKey` means the query only fires after the page loads (`enabled: !!page`).

- [ ] **Step 2: Verify Vite build**

```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -20
```

Expected: Build succeeds with no TypeScript errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/plugins/pages/PageDetailPage.tsx
git commit -m "feat(pages): ministry sub-pages grid on PageDetailPage"
```

---

## Task 5: Frontend — community type badges

**Files:**
- Modify: `resources/js/plugins/community/CommunityPage.tsx`
- Modify: `resources/js/plugins/community/CommunityDetailPage.tsx`

- [ ] **Step 1: Add type badge helper and render on CommunityPage cards**

In `CommunityPage.tsx`:

1. Add a type badge helper constant near the top of the file, after the `Community` interface:

```tsx
const TYPE_BADGES: Record<string, { label: string; color: string }> = {
    small_group:   { label: 'Small Group',   color: '#6366f1' },
    prayer_circle: { label: 'Prayer Circle', color: '#8b5cf6' },
    bible_study:   { label: 'Bible Study',   color: '#0ea5e9' },
    ministry_team: { label: 'Ministry Team', color: '#10b981' },
    choir:         { label: 'Choir',         color: '#f59e0b' },
};
```

2. Add `community_type?: string` to the `Community` interface.

3. Inside the community card JSX, **inside the clickable content div** (`<div style={{ padding: '0.75rem 0.75rem 0' }}>`) — place the badge immediately after the `{c.description && <p ...>}` line but **before** the `</div>` that closes the clickable content area (i.e., the badge stays inside the `onClick` div, not below it). Add:

```tsx
{c.community_type && TYPE_BADGES[c.community_type] && (
    <span style={{
        display: 'inline-block',
        fontSize: '0.7rem',
        fontWeight: 600,
        padding: '0.1rem 0.5rem',
        borderRadius: 99,
        background: TYPE_BADGES[c.community_type].color + '1a',
        color: TYPE_BADGES[c.community_type].color,
        marginBottom: '0.4rem',
    }}>
        {TYPE_BADGES[c.community_type].label}
    </span>
)}
```

- [ ] **Step 2: Add type badge to CommunityDetailPage header**

In `CommunityDetailPage.tsx`:

1. Add the same `TYPE_BADGES` constant after the `Member` interface.

2. Note: `community` in this file is untyped (returned as `r.data` without a cast), so TypeScript will not error on `community.community_type` — no interface update needed here.

3. In the header `<p className="text-sm text-gray-500 mt-0.5">` line, add the badge **after** the closing `</p>` (not inside it), still within the `<div className="px-6 py-4">` header section:

```tsx
{community.community_type && TYPE_BADGES[community.community_type] && (
    <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1" style={{
        background: TYPE_BADGES[community.community_type].color + '1a',
        color: TYPE_BADGES[community.community_type].color,
    }}>
        {TYPE_BADGES[community.community_type].label}
    </span>
)}
```

- [ ] **Step 3: Verify Vite build**

```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -20
```

Expected: No errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/plugins/community/CommunityPage.tsx \
        resources/js/plugins/community/CommunityDetailPage.tsx
git commit -m "feat(community): community type color badges on list and detail pages"
```

---

## Task 6: Run full test suite + apply Pint

- [ ] **Step 1: Run all tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest 2>&1 | tail -15
```

Expected: 7 pre-existing failures (CommentTest, FeedTest, ReactionTest from main). All Sprint 9 tests (SubPageTest × 4 + CommunityTypeTest × 3) pass. Zero new failures.

- [ ] **Step 2: Apply Pint formatting**

```bash
vendor/bin/pint plugins/Entity plugins/Community 2>&1
```

- [ ] **Step 3: Commit Pint changes (if any)**

```bash
git add plugins/Entity plugins/Community
git commit -m "style: apply Pint formatting to Entity and Community plugins"
```

---

## Task 7: Finish branch

- [ ] **Step 1: Use `superpowers:finishing-a-development-branch` skill**

This will present options for merge, PR, or cleanup.
