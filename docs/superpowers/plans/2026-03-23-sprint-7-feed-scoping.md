# Sprint 7 — Feed Scoping + Post-as-Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scope the social feed to church entity pages and allow page admins to post *as the page* (showing the page as author instead of the individual user).

**Architecture:** Add `entity_id`, `posted_as`, `actor_entity_id`, `is_approved`, `is_pinned` columns to `social_posts`. FeedController gains a `page()` method and home feed folds in followed-entity posts. PostController validates entity-admin when `posted_as=entity`. Frontend PostCard shows entity avatar/name as author; CreatePostModal adds a "Post as" dropdown; PageDetailPage renders the live entity feed.

**Tech Stack:** Laravel 11 · PestPHP · React 18 · TypeScript · Tailwind CSS · Axios

---

## File Map

### New files
| File | Responsibility |
|---|---|
| `plugins/Post/database/migrations/2026_05_01_000001_add_entity_fields_to_social_posts.php` | Add entity_id, posted_as, actor_entity_id, is_approved, is_pinned to social_posts |
| `tests/Feature/EntityFeedTest.php` | Page feed endpoint + home feed entity scoping |
| `tests/Feature/PostAsPageTest.php` | Post-as-entity creation, auth guard, validation |

### Modified files
| File | Change |
|---|---|
| `plugins/Post/Models/Post.php` | Add entity fields to `$fillable`, `$casts`, add `entity()` + `entityActor()` relations |
| `plugins/Post/Controllers/PostController.php` | Accept `entity_id`, `posted_as`, `actor_entity_id`; validate entity admin |
| `plugins/Feed/Controllers/FeedController.php` | Add `page()` method; include entity posts in home feed |
| `plugins/Feed/routes/api.php` | Add `GET /feed/page/{entityId}` route |
| `resources/js/plugins/feed/PostCard.tsx` | Show entity name/avatar when `posted_as=entity` |
| `resources/js/plugins/feed/CreatePostModal.tsx` | Add "Post as" selector for admins |
| `resources/js/plugins/pages/PageDetailPage.tsx` | Replace placeholder with live `/feed/page/{id}` feed |

---

## Task T1: Worktree + migration

**Files:**
- Create: `.worktrees/sprint-7/`
- Create: `plugins/Post/database/migrations/2026_05_01_000001_add_entity_fields_to_social_posts.php`

- [ ] **Create worktree**
```bash
git worktree add .worktrees/sprint-7 -b sprint/7-feed-scoping
cd .worktrees/sprint-7
ln -s ../../vendor vendor
cp ../../.env .env
```

- [ ] **Create migration**

`plugins/Post/database/migrations/2026_05_01_000001_add_entity_fields_to_social_posts.php`:
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
            $table->unsignedBigInteger('entity_id')->nullable()->after('community_id');
            $table->enum('posted_as', ['user', 'entity'])->default('user')->after('entity_id');
            $table->unsignedBigInteger('actor_entity_id')->nullable()->after('posted_as');
            $table->boolean('is_approved')->default(true)->after('actor_entity_id');
            $table->boolean('is_pinned')->default(false)->after('is_approved');

            $table->index(['entity_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['entity_id', 'status', 'published_at']);
            $table->dropColumn(['entity_id', 'posted_as', 'actor_entity_id', 'is_approved', 'is_pinned']);
        });
    }
};
```

> **SQLite note:** `ALTER TABLE ADD COLUMN` with defaults works in SQLite. The `dropIndex` in `down()` uses array syntax which is SQLite-safe.

- [ ] **Commit T1**
```bash
git add plugins/Post/database/migrations/
git commit -m "feat(feed): add entity_id, posted_as, actor_entity_id, is_approved, is_pinned to social_posts"
```

---

## Task T2: Post model + PostController entity support

**Files:**
- Modify: `plugins/Post/Models/Post.php`
- Modify: `plugins/Post/Controllers/PostController.php`

- [ ] **Write failing tests (PostAsPageTest)**

`tests/Feature/PostAsPageTest.php`:
```php
<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Post\Models\Post;

test('page admin can post as page', function () {
    $admin = User::factory()->create();
    $page  = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'approved']);

    $this->actingAs($admin)->postJson('/api/v1/posts', [
        'body'            => 'Sunday service starts at 9am',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ])->assertStatus(201)->assertJsonFragment(['posted_as' => 'entity']);
});

test('non-admin cannot post as page', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);

    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body'            => 'Trying to hijack',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ])->assertStatus(403);
});

test('post as page sets entity_id and actor_entity_id on the record', function () {
    $admin = User::factory()->create();
    $page  = ChurchEntity::factory()->create(['type' => 'page', 'owner_id' => $admin->id]);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $admin->id, 'role' => 'admin', 'status' => 'approved']);

    $response = $this->actingAs($admin)->postJson('/api/v1/posts', [
        'body'            => 'Entity post',
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ]);

    $this->assertDatabaseHas('social_posts', [
        'id'              => $response->json('id'),
        'entity_id'       => $page->id,
        'posted_as'       => 'entity',
        'actor_entity_id' => $page->id,
    ]);
});

test('regular post still works without entity fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'body' => 'Normal post',
    ])->assertStatus(201)->assertJsonFragment(['posted_as' => 'user']);
});
```

- [ ] **Run tests — expect FAIL** (column not in fillable yet)
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PostAsPageTest.php 2>&1 | tail -10
```

- [ ] **Update Post model** — add entity fields to `$fillable` and `$casts`, add relations:

In `plugins/Post/Models/Post.php`, update `$fillable`:
```php
protected $fillable = [
    'user_id', 'church_id', 'community_id', 'parent_id',
    'entity_id', 'posted_as', 'actor_entity_id', 'is_approved', 'is_pinned',
    'type', 'body', 'media', 'meta',
    'is_anonymous', 'status', 'published_at',
];
```

Update `$casts`:
```php
protected $casts = [
    'media'           => 'array',
    'meta'            => 'array',
    'is_anonymous'    => 'boolean',
    'is_approved'     => 'boolean',
    'is_pinned'       => 'boolean',
    'published_at'    => 'datetime',
    'shares_count'    => 'integer',
];
```

Add relations after existing ones:
```php
public function entity(): BelongsTo
{
    return $this->belongsTo(\Plugins\Entity\Models\ChurchEntity::class, 'entity_id');
}

public function entityActor(): BelongsTo
{
    return $this->belongsTo(\Plugins\Entity\Models\ChurchEntity::class, 'actor_entity_id');
}
```

- [ ] **Update PostController** — add entity validation and admin check:

In `plugins/Post/Controllers/PostController.php`, add to the `store()` validation array:
```php
'entity_id'       => ['nullable', 'integer', 'exists:church_entities,id'],
'posted_as'       => ['nullable', 'in:user,entity'],
'actor_entity_id' => ['nullable', 'integer', 'exists:church_entities,id'],
```

After validation and before `Post::create(...)`, add entity admin guard:
```php
if (($data['posted_as'] ?? 'user') === 'entity') {
    $entityId = $data['actor_entity_id'] ?? $data['entity_id'] ?? null;
    abort_unless($entityId, 422, 'actor_entity_id required when posted_as=entity');
    $entity = \Plugins\Entity\Models\ChurchEntity::findOrFail($entityId);
    abort_unless($entity->isAdmin($request->user()->id), 403, 'Not an admin of this page');
    $data['entity_id']       = $entityId;
    $data['actor_entity_id'] = $entityId;
}
$data['posted_as'] = $data['posted_as'] ?? 'user';
```

Also add `'entity_id', 'posted_as', 'actor_entity_id'` to the `Post::create(array_merge($data, [...]))` by including them in `$data`.

- [ ] **Run tests — expect PASS**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/PostAsPageTest.php 2>&1 | tail -8
```
Expected: 4 passed.

- [ ] **Commit T2**
```bash
git add plugins/Post/Models/Post.php plugins/Post/Controllers/PostController.php tests/Feature/PostAsPageTest.php
git commit -m "feat(feed): update Post model and PostController to support posted_as=entity — 4 tests passing"
```

---

## Task T3: FeedController page feed + home feed entity scoping

**Files:**
- Modify: `plugins/Feed/Controllers/FeedController.php`
- Modify: `plugins/Feed/routes/api.php`

- [ ] **Write failing tests (EntityFeedTest)**

`tests/Feature/EntityFeedTest.php`:
```php
<?php
use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Post\Models\Post;

test('GET /feed/page/{id} returns posts scoped to that entity', function () {
    $page      = ChurchEntity::factory()->create(['type' => 'page']);
    $pagePost  = Post::factory()->create(['entity_id' => $page->id, 'status' => 'published', 'published_at' => now()]);
    $otherPost = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

    $this->getJson("/api/v1/feed/page/{$page->id}")
         ->assertStatus(200)
         ->assertJsonFragment(['id' => $pagePost->id])
         ->assertJsonMissing(['id' => $otherPost->id]);
});

test('GET /feed/page/{id} returns 404 for non-existent entity', function () {
    $this->getJson('/api/v1/feed/page/99999')->assertStatus(404);
});

test('home feed includes posts from followed pages', function () {
    $user = User::factory()->create();
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    EntityMember::create(['entity_id' => $page->id, 'user_id' => $user->id, 'role' => 'member', 'status' => 'approved']);

    $pagePost  = Post::factory()->create(['entity_id' => $page->id, 'status' => 'published', 'published_at' => now()]);
    $otherPost = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/feed');
    $ids = collect($response->json('data'))->pluck('id')->toArray();

    expect($ids)->toContain($pagePost->id);
});

test('GET /feed/page/{id} supports type filter', function () {
    $page = ChurchEntity::factory()->create(['type' => 'page']);
    Post::factory()->create(['entity_id' => $page->id, 'type' => 'prayer', 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['entity_id' => $page->id, 'type' => 'post',   'status' => 'published', 'published_at' => now()]);

    $this->getJson("/api/v1/feed/page/{$page->id}?type=prayer")
         ->assertStatus(200)
         ->assertJsonCount(1, 'data');
});
```

- [ ] **Run tests — expect FAIL**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/EntityFeedTest.php 2>&1 | tail -8
```

- [ ] **Update FeedController** — add `page()` method and entity scoping to `home()`:

Full updated `plugins/Feed/Controllers/FeedController.php`:
```php
<?php
namespace Plugins\Feed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Post\Models\Post;

class FeedController extends Controller
{
    public function home(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Post::published()
            ->with(['author:id,name,avatar', 'church:id,name,logo', 'entityActor:id,name,profile_image'])
            ->withCount(['comments', 'reactions'])
            ->latest('published_at');

        if ($user) {
            $communityIds = DB::table('community_members')->where('user_id', $user->id)->where('status', 'approved')->pluck('community_id');
            $churchIds    = DB::table('church_members')->where('user_id', $user->id)->where('type', 'member')->pluck('church_id');
            $entityIds    = DB::table('entity_members')->where('user_id', $user->id)->where('status', 'approved')->pluck('entity_id');

            if ($communityIds->isNotEmpty() || $churchIds->isNotEmpty() || $entityIds->isNotEmpty()) {
                $query->where(fn ($q) => $q
                    ->whereIn('community_id', $communityIds)
                    ->orWhereIn('church_id', $churchIds)
                    ->orWhereIn('entity_id', $entityIds)
                );
            }
        }

        $query->when($request->type, fn ($q) => $q->where('type', $request->type));

        return response()->json($query->paginate(15));
    }

    public function community(Request $request, int $communityId): JsonResponse
    {
        return response()->json(
            Post::published()->where('community_id', $communityId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar'])->withCount(['comments', 'reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    public function church(Request $request, int $churchId): JsonResponse
    {
        return response()->json(
            Post::published()->where('church_id', $churchId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar'])->withCount(['comments', 'reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    public function page(Request $request, int $entityId): JsonResponse
    {
        ChurchEntity::pages()->findOrFail($entityId);

        return response()->json(
            Post::published()->where('entity_id', $entityId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar', 'entityActor:id,name,profile_image'])
                ->withCount(['comments', 'reactions'])
                ->latest('published_at')->paginate(15)
        );
    }
}
```

- [ ] **Add route** in `plugins/Feed/routes/api.php`:
```php
Route::get('feed/page/{entityId}', [FeedController::class, 'page']);
```

- [ ] **Run tests — expect PASS**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/EntityFeedTest.php 2>&1 | tail -8
```
Expected: 4 passed.

- [ ] **Confirm existing feed tests still pass**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/ 2>&1 | tail -5
```

- [ ] **Commit T3**
```bash
git add plugins/Feed/ tests/Feature/EntityFeedTest.php
git commit -m "feat(feed): add page feed endpoint + entity scoping in home feed — 4 new tests passing"
```

---

## Task T4: Frontend — PostCard entity author + CreatePostModal "Post as" + PageDetailPage feed

**Files:**
- Modify: `resources/js/plugins/feed/PostCard.tsx`
- Modify: `resources/js/plugins/feed/CreatePostModal.tsx`
- Modify: `resources/js/plugins/pages/PageDetailPage.tsx`

- [ ] **Update PostCard** — show entity avatar/name when `posted_as=entity`

In `resources/js/plugins/feed/PostCard.tsx`, update the `Post` interface:
```tsx
interface Post {
    id: number;
    body: string | null;
    type: 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
    meta?: Record<string, any>;
    author: Author | null;
    entity_actor?: { id: number; name: string; profile_image?: string } | null;
    posted_as: 'user' | 'entity';
    church?: { name: string };
    reactions_count: number;
    comments_count: number;
    created_at: string;
}
```

Replace the author avatar/name block with an entity-aware version:
```tsx
// Replace the existing author row div with:
const displayName  = post.posted_as === 'entity' && post.entity_actor
    ? post.entity_actor.name
    : (post.author?.name ?? 'Anonymous');
const displayAvatar = post.posted_as === 'entity' && post.entity_actor
    ? (post.entity_actor.profile_image ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.entity_actor.name)}`)
    : (post.author?.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.author?.name ?? 'Anonymous')}`);

// Then in JSX:
<img src={displayAvatar} style={{ width: 40, height: 40, borderRadius: post.posted_as === 'entity' ? 8 : '50%', objectFit: 'cover' }} alt="" />
<div>
    <div style={{ fontWeight: 600, fontSize: '0.9rem' }}>
        {displayName}
        {post.posted_as === 'entity' && <span style={{ fontSize: '0.7rem', color: '#3b82f6', marginLeft: 6, fontWeight: 500 }}>Page</span>}
    </div>
    <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
        {new Date(post.created_at).toLocaleDateString()}{post.church && ` · ${post.church.name}`}
    </div>
</div>
```

- [ ] **Update CreatePostModal** — add "Post as" selector

Add `entityId` and `postedAs` state + a page selector dropdown. Fetch the user's admin pages on mount:

Add state:
```tsx
const [postedAs, setPostedAs]   = useState<'user' | 'entity'>('user');
const [entityId, setEntityId]   = useState<number | null>(null);
const [adminPages, setAdminPages] = useState<Array<{id: number; name: string}>>([]);

// Fetch admin pages on mount
React.useEffect(() => {
    fetch('/api/v1/pages?mine=1')
        .then(r => r.json())
        .then(d => setAdminPages(d.data ?? []))
        .catch(() => {});
}, []);
```

Add selector UI above the submit button (only if `adminPages.length > 0`):
```tsx
{adminPages.length > 0 && (
    <div style={{ marginBottom: '0.75rem' }}>
        <label style={{ fontSize: '0.8rem', fontWeight: 600, color: '#374151', display: 'block', marginBottom: 4 }}>Post as</label>
        <select
            value={postedAs === 'entity' ? String(entityId) : 'user'}
            onChange={e => {
                if (e.target.value === 'user') { setPostedAs('user'); setEntityId(null); }
                else { setPostedAs('entity'); setEntityId(Number(e.target.value)); }
            }}
            style={{ width: '100%', padding: '0.5rem', border: '1px solid #e5e7eb', borderRadius: 8, fontSize: '0.9rem' }}
        >
            <option value="user">Myself</option>
            {adminPages.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
        </select>
    </div>
)}
```

Update `submit()` to include entity fields:
```tsx
body: JSON.stringify({
    type, body: body || null, meta,
    ...(postedAs === 'entity' && entityId ? {
        posted_as: 'entity',
        entity_id: entityId,
        actor_entity_id: entityId,
    } : {}),
}),
```

- [ ] **Update PageDetailPage** — replace placeholder with live feed

Replace the placeholder `<div>` at the bottom with:
```tsx
// Add to imports:
import { useQuery } from '@tanstack/react-query'
import axios from 'axios'

// Add inside component after page loads:
const { data: feedData, isLoading: feedLoading } = useQuery({
    queryKey: ['page-feed', page?.id],
    queryFn: () => axios.get(`/api/v1/feed/page/${page.id}`).then(r => r.data),
    enabled: !!page?.id,
})

// Replace placeholder div with:
<div className="px-6 pt-2 pb-6 border-t border-gray-100">
    <h2 className="text-base font-semibold text-gray-900 mb-4">Posts</h2>
    {feedLoading && <p className="text-sm text-gray-400 text-center py-8">Loading posts…</p>}
    {feedData?.data?.length === 0 && <p className="text-sm text-gray-400 text-center py-8">No posts yet.</p>}
    {feedData?.data?.map((post: any) => (
        <div key={post.id} className="mb-4 p-4 bg-gray-50 rounded-xl">
            <div className="flex items-center gap-2 mb-2">
                <img
                    src={post.entity_actor?.profile_image ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(page.name)}`}
                    className="w-8 h-8 rounded-lg object-cover"
                    alt=""
                />
                <div>
                    <p className="text-sm font-semibold text-gray-900">{page.name}</p>
                    <p className="text-xs text-gray-400">{new Date(post.created_at).toLocaleDateString()}</p>
                </div>
            </div>
            {post.body && <p className="text-sm text-gray-700 leading-relaxed">{post.body}</p>}
        </div>
    ))}
</div>
```

- [ ] **Check TypeScript**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" node node_modules/.bin/tsc --noEmit 2>&1 | grep "plugins/" | head -10
```

- [ ] **Vite build**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -5
```

- [ ] **Commit T4**
```bash
git add resources/js/plugins/feed/PostCard.tsx resources/js/plugins/feed/CreatePostModal.tsx resources/js/plugins/pages/PageDetailPage.tsx
git commit -m "feat(feed): PostCard entity author display, CreatePostModal post-as-page, PageDetailPage live feed"
```

---

## Task T5: Final verification + finish branch

- [ ] **Run all new tests**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/EntityFeedTest.php tests/Feature/PostAsPageTest.php 2>&1 | tail -12
```
Expected: 8 tests, all green.

- [ ] **Run full suite (confirm no regressions)**
```bash
"/Users/siku/Library/Application Support/Herd/bin/php" vendor/bin/pest tests/Feature/ 2>&1 | tail -8
```

- [ ] **Format PHP**
```bash
vendor/bin/pint plugins/Feed/ plugins/Post/
```

- [ ] **Final Vite build**
```bash
PATH="/Users/siku/.nvm/versions/node/v24.14.0/bin:$PATH" npm run build 2>&1 | tail -5
```

- [ ] **Finish branch** — use `superpowers:finishing-a-development-branch` skill
