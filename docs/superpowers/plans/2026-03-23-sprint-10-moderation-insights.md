# Sprint 10 — Moderation & Insights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add community post moderation (pin/approve), page verification requests, and a page insights endpoint to the church platform.

**Architecture:** Two independent subsystems: (1) Post moderation — community admins can toggle `is_pinned` and set `is_approved`/`approved_by` on posts in their communities, via a new `PostModerationController` in the Post plugin; (2) Page management — page admins can view aggregated insights and request platform verification, via two new controllers in the Entity plugin. Both subsystems require one `ALTER TABLE` migration each (using `boolean`/`timestamp` types — never `enum` — for SQLite compatibility). Frontend adds a pinned badge to `PostCard` and a moderator admin panel on `PageDetailPage`.

**Tech Stack:** Laravel 11, PestPHP (SQLite in-memory), React 18, TypeScript, TanStack Query, Tailwind CSS, Axios

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `plugins/Post/database/migrations/2026_05_10_000001_add_moderation_to_social_posts.php` | Create | `is_pinned`, `is_approved`, `approved_by` columns |
| `plugins/Entity/database/migrations/2026_05_10_000001_add_verification_to_church_entities.php` | Create | `verification_requested_at` timestamp column |
| `plugins/Post/Models/Post.php` | Modify | Add moderation fields to `$fillable` + `$casts` |
| `plugins/Entity/Models/ChurchEntity.php` | Modify | Add `verification_requested_at` to `$fillable` + `$casts` |
| `database/factories/ChurchEntityFactory.php` | Modify | Add `verification_requested_at => null` to definition |
| `plugins/Post/Controllers/PostModerationController.php` | Create | `pin()` and `approve()` — community-admin-gated |
| `plugins/Entity/Controllers/PageInsightsController.php` | Create | `show()` — returns page stats, admin-gated |
| `plugins/Entity/Controllers/PageVerificationController.php` | Create | `store()` — requests verification, admin-gated |
| `plugins/Post/routes/api.php` | Modify | Add 2 moderation routes |
| `plugins/Entity/routes/api.php` | Modify | Add 2 page management routes |
| `resources/js/plugins/feed/PostCard.tsx` | Modify | Show pinned badge when `is_pinned` is true |
| `resources/js/plugins/pages/PageDetailPage.tsx` | Modify | Add admin insights panel + verification button |
| `tests/Feature/PostModerationTest.php` | Create | 4 tests: pin toggle, unpin, non-admin 403, approve |
| `tests/Feature/PageInsightsTest.php` | Create | 5 tests: insights admin ok, non-admin 403, verify ok, verify twice 422, non-admin verify 403 |

---

## Task 1: Migrations + model updates

**Files:**
- Create: `plugins/Post/database/migrations/2026_05_10_000001_add_moderation_to_social_posts.php`
- Create: `plugins/Entity/database/migrations/2026_05_10_000001_add_verification_to_church_entities.php`
- Modify: `plugins/Post/Models/Post.php`
- Modify: `plugins/Entity/Models/ChurchEntity.php`
- Modify: `database/factories/ChurchEntityFactory.php`

- [ ] **Step 1: Create social_posts moderation migration**

Create `plugins/Post/database/migrations/2026_05_10_000001_add_moderation_to_social_posts.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('status');
            $table->boolean('is_approved')->nullable()->after('is_pinned');
            $table->unsignedBigInteger('approved_by')->nullable()->after('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_approved', 'approved_by']);
        });
    }
};
```

> `approved_by` is `unsignedBigInteger` (not `foreignId()->constrained()`) because SQLite does not enforce FK constraints and we want `nullable` without cascades complicating teardown in tests.

- [ ] **Step 2: Create church_entities verification migration**

Create `plugins/Entity/database/migrations/2026_05_10_000001_add_verification_to_church_entities.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_entities', function (Blueprint $table) {
            $table->timestamp('verification_requested_at')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('church_entities', function (Blueprint $table) {
            $table->dropColumn('verification_requested_at');
        });
    }
};
```

- [ ] **Step 3: Update Post model**

In `plugins/Post/Models/Post.php`:

1. Add to `$fillable`: `'is_pinned'`, `'is_approved'`, `'approved_by'`
2. Add to `$casts`:
```php
'is_pinned'   => 'boolean',
// NOTE: is_approved is intentionally NOT cast to boolean — the column is nullable,
// and casting to boolean would coerce null (not-yet-reviewed) to false (rejected),
// losing the tri-state distinction. Leave as default so null stays null.
'approved_by' => 'integer',
```

- [ ] **Step 4: Update ChurchEntity model**

In `plugins/Entity/Models/ChurchEntity.php`:

1. Add to `$fillable`: `'verification_requested_at'`
2. Add to `$casts`:
```php
'verification_requested_at' => 'datetime',
```

- [ ] **Step 5: Update ChurchEntityFactory**

In `database/factories/ChurchEntityFactory.php`, add to `definition()`:
```php
'verification_requested_at' => null,
```

- [ ] **Step 6: Quick smoke test — migrations run without error**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest --filter="SubPageTest" 2>&1 | tail -5
```

Expected: 4 passing (existing Sprint 9 tests). If they still pass, migrations are SQLite-safe.

- [ ] **Step 7: Commit**

```bash
git add plugins/Post/database/migrations/2026_05_10_000001_add_moderation_to_social_posts.php \
        plugins/Entity/database/migrations/2026_05_10_000001_add_verification_to_church_entities.php \
        plugins/Post/Models/Post.php \
        plugins/Entity/Models/ChurchEntity.php \
        database/factories/ChurchEntityFactory.php
git commit -m "feat(moderation): add is_pinned/is_approved/approved_by to posts; verification_requested_at to entities"
```

---

## Task 2: PostModerationController + routes + tests

**Files:**
- Create: `plugins/Post/Controllers/PostModerationController.php`
- Modify: `plugins/Post/routes/api.php`
- Create: `tests/Feature/PostModerationTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PostModerationTest.php`:

```php
<?php

use App\Models\User;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;
use Plugins\Post\Models\Post;

test('community admin can pin a community post', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertOk()
        ->assertJsonPath('is_pinned', true);

    expect($post->fresh()->is_pinned)->toBeTrue();
});

test('pinning an already-pinned post toggles it off', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
        'is_pinned'    => true,
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertOk()
        ->assertJsonPath('is_pinned', false);

    expect($post->fresh()->is_pinned)->toBeFalse();
});

test('non-admin cannot pin a post', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/posts/{$post->id}/pin")
        ->assertForbidden();
});

test('community admin can approve a post', function () {
    $admin = User::factory()->create();
    $community = Community::factory()->create(['status' => 'active']);
    CommunityMember::create([
        'community_id' => $community->id,
        'user_id'      => $admin->id,
        'role'         => 'admin',
        'status'       => 'approved',
    ]);
    $post = Post::factory()->create([
        'community_id' => $community->id,
        'status'       => 'published',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/v1/posts/{$post->id}/approve")
        ->assertOk()
        ->assertJsonPath('is_approved', true);

    expect($post->fresh()->is_approved)->toBeTrue();
    expect($post->fresh()->approved_by)->toBe($admin->id);
});
```

- [ ] **Step 2: Run tests to confirm they FAIL**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PostModerationTest.php
```

Expected: FAIL (routes not yet defined)

- [ ] **Step 3: Create PostModerationController**

Create `plugins/Post/Controllers/PostModerationController.php`:

```php
<?php

namespace Plugins\Post\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Community\Models\CommunityMember;
use Plugins\Post\Models\Post;

class PostModerationController extends Controller
{
    /**
     * Toggle pin on a community post. Community admin only.
     * POST /api/v1/posts/{id}/pin
     */
    public function pin(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->whereNotNull('community_id')->findOrFail($id);

        abort_unless($this->isCommunityAdmin($request->user()->id, $post->community_id), 403);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        return response()->json(['is_pinned' => $post->is_pinned]);
    }

    /**
     * Approve a community post. Community admin only.
     * POST /api/v1/posts/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $post = Post::whereNotNull('community_id')->findOrFail($id);

        abort_unless($this->isCommunityAdmin($request->user()->id, $post->community_id), 403);

        $post->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
        ]);
    }

    private function isCommunityAdmin(int $userId, int $communityId): bool
    {
        return CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->where('status', 'approved')
            ->exists();
    }
}
```

- [ ] **Step 4: Add routes to Post plugin**

In `plugins/Post/routes/api.php`, add `use Plugins\Post\Controllers\PostModerationController;` at the top, then inside the `Route::middleware('auth:sanctum')` group add:

```php
Route::post('/posts/{id}/pin',     [PostModerationController::class, 'pin'])->name('api.v1.posts.pin');
Route::post('/posts/{id}/approve', [PostModerationController::class, 'approve'])->name('api.v1.posts.approve');
```

- [ ] **Step 5: Run tests — expect 4 passing**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PostModerationTest.php
```

Expected: 4 passing

- [ ] **Step 6: Commit**

```bash
git add plugins/Post/Controllers/PostModerationController.php \
        plugins/Post/routes/api.php \
        tests/Feature/PostModerationTest.php
git commit -m "feat(moderation): PostModerationController — pin/approve, 4 tests passing"
```

---

## Task 3: PageInsightsController + PageVerificationController + routes + tests

**Files:**
- Create: `plugins/Entity/Controllers/PageInsightsController.php`
- Create: `plugins/Entity/Controllers/PageVerificationController.php`
- Modify: `plugins/Entity/routes/api.php`
- Create: `tests/Feature/PageInsightsTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PageInsightsTest.php`:

```php
<?php

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

test('page admin can view insights', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'       => 'page',
        'owner_id'   => $owner->id,
        'is_active'  => true,
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->getJson("/api/v1/pages/{$page->id}/insights")
        ->assertOk()
        ->assertJsonStructure([
            'members_count', 'sub_pages_count', 'posts_count',
            'is_verified', 'verification_requested', 'created_at',
        ]);
});

test('non-admin cannot view insights', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'is_active' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/pages/{$page->id}/insights")
        ->assertForbidden();
});

test('page admin can request verification', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'        => 'page',
        'owner_id'    => $owner->id,
        'is_active'   => true,
        'is_verified' => false,
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertCreated()
        ->assertJsonPath('verification_requested', true);

    expect($page->fresh()->verification_requested_at)->not->toBeNull();
});

test('requesting verification twice returns 422', function () {
    $owner = User::factory()->create();
    $page  = ChurchEntity::factory()->create([
        'type'                       => 'page',
        'owner_id'                   => $owner->id,
        'is_active'                  => true,
        'is_verified'                => false,
        'verification_requested_at'  => now(),
    ]);
    EntityMember::create([
        'entity_id' => $page->id,
        'user_id'   => $owner->id,
        'role'      => 'admin',
        'status'    => 'approved',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertStatus(422);
});

test('non-admin cannot request verification', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page', 'is_active' => true]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/pages/{$page->id}/verify")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to confirm they FAIL**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageInsightsTest.php
```

Expected: FAIL (routes not yet defined)

- [ ] **Step 3: Create PageInsightsController**

Create `plugins/Entity/Controllers/PageInsightsController.php`:

```php
<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;

class PageInsightsController extends Controller
{
    /**
     * GET /api/v1/pages/{id}/insights
     * Returns page analytics. Admin only.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $page = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($page->isAdmin($request->user()->id), 403);

        return response()->json([
            'members_count'          => $page->approvedMembers()->count(),
            'sub_pages_count'        => $page->subPages()->count(),
            'posts_count'            => (int) $page->posts_count,
            'is_verified'            => $page->is_verified,
            'verification_requested' => $page->verification_requested_at !== null,
            'created_at'             => $page->created_at,
        ]);
    }
}
```

- [ ] **Step 4: Create PageVerificationController**

Create `plugins/Entity/Controllers/PageVerificationController.php`:

```php
<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;

class PageVerificationController extends Controller
{
    /**
     * POST /api/v1/pages/{id}/verify
     * Request page verification. Page admin only.
     * - 422 if already verified
     * - 422 if verification already requested
     * - 201 on first successful request
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $page = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($page->isAdmin($request->user()->id), 403);

        if ($page->is_verified) {
            return response()->json(['message' => 'Page is already verified.'], 422);
        }

        if ($page->verification_requested_at !== null) {
            return response()->json(['message' => 'Verification already requested.'], 422);
        }

        $page->update(['verification_requested_at' => now()]);

        return response()->json(['verification_requested' => true], 201);
    }
}
```

- [ ] **Step 5: Add routes to Entity plugin**

In `plugins/Entity/routes/api.php`:

1. Add imports at top:
```php
use Plugins\Entity\Controllers\PageInsightsController;
use Plugins\Entity\Controllers\PageVerificationController;
```

2. Inside the `auth:sanctum` group, add:
```php
Route::get('/pages/{id}/insights', [PageInsightsController::class, 'show'])->name('insights.show');
Route::post('/pages/{id}/verify',  [PageVerificationController::class, 'store'])->name('verify.store');
```

- [ ] **Step 6: Run tests — expect 5 passing**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PageInsightsTest.php
```

Expected: 5 passing

- [ ] **Step 7: Commit**

```bash
git add plugins/Entity/Controllers/PageInsightsController.php \
        plugins/Entity/Controllers/PageVerificationController.php \
        plugins/Entity/routes/api.php \
        tests/Feature/PageInsightsTest.php
git commit -m "feat(entity): PageInsightsController + PageVerificationController — 5 tests passing"
```

---

## Task 4: Frontend — pinned badge on PostCard

**Files:**
- Modify: `resources/js/plugins/feed/PostCard.tsx`

- [ ] **Step 1: Add `is_pinned` to Post interface and render badge**

In `resources/js/plugins/feed/PostCard.tsx`:

1. Add `is_pinned?: boolean` to the `Post` interface.

2. In the author/date row (just above the `<img>` avatar line, inside the outer `<div>`), add a pinned banner **before** the author row — as the very first element inside the outer card `<div>`:

```tsx
{post.is_pinned && (
    <div style={{ fontSize: '0.72rem', fontWeight: 600, color: '#6366f1', marginBottom: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.25rem' }}>
        📌 Pinned
    </div>
)}
```

- [ ] **Step 2: Verify Vite build**

```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -10
```

Expected: Zero TypeScript errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/plugins/feed/PostCard.tsx
git commit -m "feat(feed): pinned badge on PostCard when is_pinned is true"
```

---

## Task 5: Frontend — admin panel on PageDetailPage

**Files:**
- Modify: `resources/js/plugins/pages/PageDetailPage.tsx`

- [ ] **Step 1: Add insights query + admin panel section**

In `resources/js/plugins/pages/PageDetailPage.tsx`:

1. Add a TanStack Query for insights. Because non-admins get 403, use `retry: false` so it silently fails for regular visitors:

```tsx
const { data: insights } = useQuery({
    queryKey: ['page-insights', page?.id],
    queryFn:  () => axios.get(`/api/v1/pages/${page!.id}/insights`).then(r => r.data),
    enabled:  !!page,
    retry:    false,
})
```

2. Add a `verifyMutation` using `useMutation`:

```tsx
const verifyMutation = useMutation({
    mutationFn: () => axios.post(`/api/v1/pages/${page!.id}/verify`),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['page-insights', page!.id] }),
})
```

3. Add the admin panel section **after** the sub-pages grid and **before** the posts placeholder:

```tsx
{/* Admin Insights Panel */}
{insights && (
    <div className="px-6 py-5 border-t border-gray-100 bg-indigo-50 rounded-b-xl">
        <h2 className="text-sm font-semibold text-indigo-800 mb-3">Page Insights</h2>
        <div className="grid grid-cols-3 gap-3 text-center mb-4">
            <div>
                <p className="text-xl font-bold text-indigo-700">{insights.members_count}</p>
                <p className="text-xs text-indigo-500">Members</p>
            </div>
            <div>
                <p className="text-xl font-bold text-indigo-700">{insights.sub_pages_count}</p>
                <p className="text-xs text-indigo-500">Ministries</p>
            </div>
            <div>
                <p className="text-xl font-bold text-indigo-700">{insights.posts_count}</p>
                <p className="text-xs text-indigo-500">Posts</p>
            </div>
        </div>

        {/* Verification status */}
        {insights.is_verified ? (
            <p className="text-xs text-indigo-600 font-medium">✅ Page is verified</p>
        ) : insights.verification_requested ? (
            <p className="text-xs text-amber-600 font-medium">⏳ Verification request pending review</p>
        ) : (
            <button
                onClick={() => verifyMutation.mutate()}
                disabled={verifyMutation.isPending}
                className="text-xs font-medium bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
            >
                {verifyMutation.isPending ? 'Requesting…' : 'Request Verification'}
            </button>
        )}
    </div>
)}
```

- [ ] **Step 2: Verify Vite build**

```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -10
```

Expected: Zero TypeScript errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/plugins/pages/PageDetailPage.tsx
git commit -m "feat(pages): admin insights panel + verification request button on PageDetailPage"
```

---

## Task 6: Full test suite + Pint

- [ ] **Step 1: Run all tests**

```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest 2>&1 | tail -10
```

Expected:
- Sprint 10 new tests: `PostModerationTest` (4) + `PageInsightsTest` (5) = 9 new passing
- Sprint 9 tests still pass (SubPageTest 4, CommunityTypeTest 3, CommunityJoinTest 7)
- Pre-existing failures: exactly 7 (CommentTest, FeedTest, ReactionTest — unchanged from main)
- Zero new failures

- [ ] **Step 2: Apply Pint**

```bash
vendor/bin/pint plugins/Post plugins/Entity 2>&1
```

- [ ] **Step 3: Commit Pint changes (if any)**

```bash
git add plugins/Post plugins/Entity
git commit -m "style: apply Pint formatting to Post and Entity plugins"
```

---

## Task 7: Finish branch

- [ ] **Step 1: Use `superpowers:finishing-a-development-branch` skill**
