# Sprint 2 — Feeds, Comments, Reactions & Communities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete social feed system — threaded comments, emoji reactions, a paginated home/community feed, general community groups, and basic user profiles — on top of the Sprint 1 foundation.

**Architecture:** Five self-contained plugins/extensions added to the existing plugin architecture. `Comment` and `Reaction` are new plugins with polymorphic relationships to posts and each other. `Feed` is a new API controller that aggregates published posts. `Community` general groups extend the existing `CounselGroupController` pattern. `UserProfile` extends the `User` model with a new controller and frontend profile page.

**Tech Stack:** Laravel 11, Pest (PHP tests), React 18, TypeScript, Tailwind CSS, React Query (`@tanstack/react-query`), DOMPurify (via `SafeHtml` wrapper)

---

## File Map

### New Files
| File | Responsibility |
|------|---------------|
| `plugins/Comment/CommentServiceProvider.php` | Register routes + migrations |
| `plugins/Comment/plugin.json` | Plugin manifest |
| `plugins/Comment/Models/Comment.php` | Polymorphic comment model with threading |
| `plugins/Comment/Controllers/CommentController.php` | CRUD for comments |
| `plugins/Comment/routes/api.php` | Comment API routes |
| `plugins/Comment/database/migrations/…_create_comments_table.php` | comments table |
| `plugins/Reaction/ReactionServiceProvider.php` | Register routes + migrations |
| `plugins/Reaction/plugin.json` | Plugin manifest |
| `plugins/Reaction/Models/Reaction.php` | Polymorphic reaction (post/comment) |
| `plugins/Reaction/Controllers/ReactionController.php` | Toggle reaction |
| `plugins/Reaction/routes/api.php` | Reaction API routes |
| `plugins/Reaction/database/migrations/…_create_reactions_table.php` | reactions table |
| `plugins/Feed/FeedServiceProvider.php` | Register routes |
| `plugins/Feed/plugin.json` | Plugin manifest |
| `plugins/Feed/Controllers/FeedController.php` | Paginated home + community feed |
| `plugins/Feed/routes/api.php` | Feed API routes |
| `plugins/Community/Controllers/CommunityController.php` | General community CRUD |
| `app/Http/Controllers/Api/UserProfileController.php` | Public profile endpoint |
| `resources/js/components/shared/SafeHtml.tsx` | DOMPurify wrapper — use instead of raw dangerouslySetInnerHTML |
| `resources/js/plugins/feed/FeedPage.tsx` | Home feed UI |
| `resources/js/plugins/feed/PostCard.tsx` | Post card with reactions + comment count |
| `resources/js/plugins/feed/CommentThread.tsx` | Threaded comments UI |
| `resources/js/plugins/community/CommunityPage.tsx` | General groups browser |
| `resources/js/plugins/profile/ProfilePage.tsx` | Public user profile |
| `tests/Feature/CommentTest.php` | Comment API tests |
| `tests/Feature/ReactionTest.php` | Reaction API tests |
| `tests/Feature/FeedTest.php` | Feed API tests |

### Modified Files
| File | Change |
|------|--------|
| `plugins/Community/routes/api.php` | Add general community routes |
| `plugins/Post/Models/Post.php` | Add `comments()` (Task 2), `reactions()` (Task 3), update `scopePublished` (Task 4) |
| `app/Models/User.php` | Add `churchMemberships()`, `communities()`, profile fillable fields |
| `routes/api.php` | Register user profile routes |
| `database/migrations/2026_04_10_000003_add_profile_fields_to_users.php` | avatar, bio, cover_image, location, website columns |
| `bootstrap/providers.php` | Register new service providers |

---

## Task 1: Comment Plugin — Migration & Model

**Files:**
- Create: `plugins/Comment/database/migrations/2026_04_10_000001_create_comments_table.php`
- Create: `plugins/Comment/Models/Comment.php`
- Create: `plugins/Comment/plugin.json`
- Create: `plugins/Comment/CommentServiceProvider.php`

- [ ] **Step 1: Create plugin.json**

```json
{
    "name": "Comment",
    "slug": "comment",
    "version": "1.0.0",
    "description": "Threaded comments on posts",
    "requires": ["post"]
}
```

- [ ] **Step 2: Create migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id', 'created_at']);
            $table->index(['parent_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('comments'); }
};
```

- [ ] **Step 3: Create Comment model**

```php
<?php
namespace Plugins\Comment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, MorphTo};

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = ['commentable_type', 'commentable_id', 'user_id', 'parent_id', 'body'];

    public function commentable(): MorphTo  { return $this->morphTo(); }
    public function author(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }
    public function parent(): BelongsTo    { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies(): HasMany     { return $this->hasMany(Comment::class, 'parent_id')->latest(); }
    // NOTE: reactions() relationship is added in Task 3 after Reaction plugin exists.
    public function isTopLevel(): bool { return is_null($this->parent_id); }
}
```

- [ ] **Step 4: Create CommentServiceProvider**

```php
<?php
namespace Plugins\Comment;
use Illuminate\Support\ServiceProvider;

class CommentServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
```

- [ ] **Step 5: Register provider**

Add `Plugins\Comment\CommentServiceProvider::class` to `bootstrap/providers.php`.

- [ ] **Step 6: Commit**
```bash
git add plugins/Comment/ bootstrap/providers.php
git commit -m "feat: Comment plugin — migration, model, service provider"
```

---

## Task 2: Comment Controller & Routes

**Files:**
- Create: `plugins/Comment/Controllers/CommentController.php`
- Create: `plugins/Comment/routes/api.php`
- Modify: `plugins/Post/Models/Post.php`

- [ ] **Step 1: Add `comments()` relationship to Post model**

In `plugins/Post/Models/Post.php` add:
```php
public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(\Plugins\Comment\Models\Comment::class, 'commentable')
                ->whereNull('parent_id')->latest();
}
```

- [ ] **Step 2: Create CommentController**

```php
<?php
namespace Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Comment\Models\Comment;
use Plugins\Post\Models\Post;

class CommentController extends Controller
{
    /** @group Comments */
    public function index(int $postId): JsonResponse
    {
        $comments = Comment::where('commentable_type', Post::class)
            ->where('commentable_id', $postId)
            ->whereNull('parent_id')
            ->with(['author:id,name,avatar', 'replies.author:id,name,avatar'])
            ->latest()->paginate(20);

        return response()->json($comments);
    }

    /**
     * @group Comments
     * @bodyParam post_id   integer required Example: 1
     * @bodyParam body      string  required Example: "Great post!"
     * @bodyParam parent_id integer optional Example: 5
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id'   => ['required', 'integer', 'exists:social_posts,id'],
            'body'      => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = DB::transaction(function () use ($validated, $request) {
            $comment = Comment::create([
                'commentable_type' => Post::class,
                'commentable_id'   => $validated['post_id'],
                'user_id'          => $request->user()->id,
                'parent_id'        => $validated['parent_id'] ?? null,
                'body'             => $validated['body'],
            ]);

            if ($comment->parent_id) {
                Comment::where('id', $comment->parent_id)->increment('replies_count');
            }
            DB::table('social_posts')->where('id', $validated['post_id'])->increment('comments_count');

            return $comment;
        });

        return response()->json($comment->load('author:id,name,avatar'), 201);
    }

    /** @group Comments */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        abort_if($comment->user_id !== $request->user()->id, 403);
        $comment->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
```

- [ ] **Step 3: Create routes**

```php
<?php
use Illuminate\Support\Facades\Route;
use Plugins\Comment\Controllers\CommentController;

Route::prefix('v1')->group(function () {
    Route::get('posts/{postId}/comments',  [CommentController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('comments',            [CommentController::class, 'store']);
        Route::delete('comments/{id}',     [CommentController::class, 'destroy']);
    });
});
```

- [ ] **Step 4: Write Feature test**

```php
<?php
// tests/Feature/CommentTest.php
use App\Models\User;
use Plugins\Post\Models\Post;
use Plugins\Comment\Models\Comment;

test('user can comment on a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/comments', [
        'post_id' => $post->id,
        'body'    => 'Great post!',
    ])->assertStatus(201)->assertJsonFragment(['body' => 'Great post!']);

    expect(Comment::count())->toBe(1);
});

test('user can reply to a comment', function () {
    $user    = User::factory()->create();
    $post    = Post::factory()->create(['status' => 'published']);
    $comment = Comment::factory()->create(['commentable_id' => $post->id, 'commentable_type' => Post::class]);

    $this->actingAs($user)->postJson('/api/v1/comments', [
        'post_id'   => $post->id,
        'body'      => 'Nice reply!',
        'parent_id' => $comment->id,
    ])->assertStatus(201);

    expect($comment->fresh()->replies_count)->toBe(1);
});

test('user cannot delete another users comment', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/comments/{$comment->id}")
        ->assertStatus(403);
});
```

- [ ] **Step 5: Commit**
```bash
git add plugins/Comment/ plugins/Post/Models/Post.php tests/Feature/CommentTest.php
git commit -m "feat: Comment controller, routes, and feature tests"
```

---

## Task 3: Reaction Plugin

**Files:**
- Create: `plugins/Reaction/database/migrations/2026_04_10_000002_create_reactions_table.php`
- Create: `plugins/Reaction/Models/Reaction.php`
- Create: `plugins/Reaction/Controllers/ReactionController.php`
- Create: `plugins/Reaction/routes/api.php`
- Create: `plugins/Reaction/ReactionServiceProvider.php`
- Create: `plugins/Reaction/plugin.json`

- [ ] **Step 1: Create plugin.json**
```json
{ "name": "Reaction", "slug": "reaction", "version": "1.0.0", "description": "Emoji reactions on posts and comments" }
```

- [ ] **Step 2: Create migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('reactable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 10)->default('👍');
            $table->timestamps();

            $table->unique(['reactable_type', 'reactable_id', 'user_id']);
            $table->index(['reactable_type', 'reactable_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('reactions'); }
};
```

- [ ] **Step 3: Create Reaction model**

```php
<?php
namespace Plugins\Reaction\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};

class Reaction extends Model
{
    protected $fillable = ['reactable_type', 'reactable_id', 'user_id', 'emoji'];

    public function reactable(): MorphTo { return $this->morphTo(); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}
```

- [ ] **Step 4: Create ReactionController**

```php
<?php
namespace Plugins\Reaction\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Reaction\Models\Reaction;

class ReactionController extends Controller
{
    private const ALLOWED = ['👍','❤️','🙏','😂','😮','😢'];

    /**
     * Toggle a reaction (creates or removes).
     * @group Reactions
     * @bodyParam reactable_type string  required "post" or "comment". Example: post
     * @bodyParam reactable_id   integer required Example: 1
     * @bodyParam emoji          string  optional Default: 👍
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactable_type' => ['required', 'in:post,comment'],
            'reactable_id'   => ['required', 'integer'],
            'emoji'          => ['sometimes', 'string', 'in:' . implode(',', self::ALLOWED)],
        ]);

        $map   = ['post' => \Plugins\Post\Models\Post::class, 'comment' => \Plugins\Comment\Models\Comment::class];
        $type  = $map[$validated['reactable_type']];
        $id    = $validated['reactable_id'];
        $table = $validated['reactable_type'] === 'post' ? 'social_posts' : 'comments';

        $existing = Reaction::where(['reactable_type' => $type, 'reactable_id' => $id, 'user_id' => $request->user()->id])->first();

        if ($existing) {
            $existing->delete();
            DB::table($table)->where('id', $id)->decrement('reactions_count');
            return response()->json(['reacted' => false]);
        }

        Reaction::create(['reactable_type' => $type, 'reactable_id' => $id, 'user_id' => $request->user()->id, 'emoji' => $validated['emoji'] ?? '👍']);
        DB::table($table)->where('id', $id)->increment('reactions_count');

        return response()->json(['reacted' => true], 201);
    }

    /** @group Reactions */
    public function summary(Request $request, string $type, int $id): JsonResponse
    {
        $map = ['post' => \Plugins\Post\Models\Post::class, 'comment' => \Plugins\Comment\Models\Comment::class];
        abort_unless(isset($map[$type]), 422, 'Invalid type.');

        $counts = Reaction::where(['reactable_type' => $map[$type], 'reactable_id' => $id])
            ->selectRaw('emoji, count(*) as count')->groupBy('emoji')->pluck('count', 'emoji');

        $userReaction = $request->user()
            ? Reaction::where(['reactable_type' => $map[$type], 'reactable_id' => $id, 'user_id' => $request->user()->id])->value('emoji')
            : null;

        return response()->json(['counts' => $counts, 'user_reaction' => $userReaction]);
    }
}
```

- [ ] **Step 5: Create routes**

```php
<?php
use Illuminate\Support\Facades\Route;
use Plugins\Reaction\Controllers\ReactionController;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->post('reactions', [ReactionController::class, 'toggle']);
    Route::get('reactions/{type}/{id}', [ReactionController::class, 'summary']);
});
```

- [ ] **Step 6: Create ReactionServiceProvider + register**

```php
<?php
namespace Plugins\Reaction;
use Illuminate\Support\ServiceProvider;
class ReactionServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
```
Add `Plugins\Reaction\ReactionServiceProvider::class` to `bootstrap/providers.php`.

- [ ] **Step 7: Write feature test**

```php
<?php
// tests/Feature/ReactionTest.php
use App\Models\User;
use Plugins\Post\Models\Post;

test('user can react to a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/reactions', [
        'reactable_type' => 'post', 'reactable_id' => $post->id, 'emoji' => '👍',
    ])->assertStatus(201)->assertJson(['reacted' => true]);

    expect($post->fresh()->reactions_count)->toBe(1);
});

test('reacting twice removes the reaction (toggle)', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['status' => 'published']);

    $this->actingAs($user)->postJson('/api/v1/reactions', ['reactable_type' => 'post', 'reactable_id' => $post->id]);
    $this->actingAs($user)->postJson('/api/v1/reactions', ['reactable_type' => 'post', 'reactable_id' => $post->id])
        ->assertJson(['reacted' => false]);

    expect($post->fresh()->reactions_count)->toBe(0);
});
```

- [ ] **Step 8: Add `reactions()` to both Comment and Post models now that Reaction class exists**

In `plugins/Comment/Models/Comment.php`, add:
```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function reactions(): MorphMany
{
    return $this->morphMany(\Plugins\Reaction\Models\Reaction::class, 'reactable');
}
```

In `plugins/Post/Models/Post.php`, add alongside the existing `comments()` relationship:
```php
public function reactions(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(\Plugins\Reaction\Models\Reaction::class, 'reactable');
}
```

This is required for `withCount(['reactions'])` in FeedController to work.

- [ ] **Step 9: Commit**
```bash
git add plugins/Reaction/ bootstrap/providers.php tests/Feature/ReactionTest.php plugins/Comment/Models/Comment.php plugins/Post/Models/Post.php
git commit -m "feat: Reaction plugin — polymorphic toggle reactions on posts and comments"
```

---

## Task 4: Feed Plugin

**Files:**
- Create: `plugins/Feed/FeedServiceProvider.php`
- Create: `plugins/Feed/plugin.json`
- Create: `plugins/Feed/Controllers/FeedController.php`
- Create: `plugins/Feed/routes/api.php`

- [ ] **Step 1: Update existing `scopePublished` in Post model**

`Post.php` already has `scopePublished` that checks `status = 'published'`. Update it to also respect `published_at` scheduling. Find the existing method and replace its body:

```php
// MODIFY the existing scopePublished — do NOT add a second one
public function scopePublished($query)
{
    return $query->where('status', 'published')
        ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
}
```

- [ ] **Step 2: Create FeedController**

```php
<?php
namespace Plugins\Feed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\Post;

class FeedController extends Controller
{
    /**
     * Home feed — posts from communities/churches the authenticated user follows.
     * Falls back to all published posts when the user has no memberships.
     * @group Feed
     */
    public function home(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Post::published()
            ->with(['author:id,name,avatar', 'church:id,name,logo'])
            ->withCount(['comments', 'reactions'])
            ->latest('published_at');

        if ($user) {
            $communityIds = DB::table('community_members')->where('user_id', $user->id)->where('status', 'approved')->pluck('community_id');
            $churchIds    = DB::table('church_members')->where('user_id', $user->id)->pluck('church_id');

            if ($communityIds->isNotEmpty() || $churchIds->isNotEmpty()) {
                $query->where(fn ($q) => $q->whereIn('community_id', $communityIds)->orWhereIn('church_id', $churchIds));
            }
        }

        return response()->json($query->paginate(15));
    }

    /** @group Feed @urlParam communityId integer required Example: 1 */
    public function community(int $communityId): JsonResponse
    {
        return response()->json(
            Post::published()->where('community_id', $communityId)
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    /** @group Feed @urlParam churchId integer required Example: 1 */
    public function church(int $churchId): JsonResponse
    {
        return response()->json(
            Post::published()->where('church_id', $churchId)
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }
}
```

- [ ] **Step 3: Create routes**

```php
<?php
use Illuminate\Support\Facades\Route;
use Plugins\Feed\Controllers\FeedController;

Route::prefix('v1')->group(function () {
    Route::get('feed',                         [FeedController::class, 'home'])->middleware('auth:sanctum');
    Route::get('feed/community/{communityId}', [FeedController::class, 'community']);
    Route::get('feed/church/{churchId}',       [FeedController::class, 'church']);
});
```

- [ ] **Step 4: Create FeedServiceProvider + plugin.json**

```php
<?php
namespace Plugins\Feed;
use Illuminate\Support\ServiceProvider;
class FeedServiceProvider extends ServiceProvider {
    public function boot(): void { $this->loadRoutesFrom(__DIR__ . '/routes/api.php'); }
}
```

```json
{ "name": "Feed", "slug": "feed", "version": "1.0.0", "description": "Paginated post feed for home, community and church contexts", "requires": ["post"] }
```

Add `Plugins\Feed\FeedServiceProvider::class` to `bootstrap/providers.php`.

- [ ] **Step 5: Write feature test**

```php
<?php
// tests/Feature/FeedTest.php
use App\Models\User;
use Plugins\Post\Models\Post;

test('authenticated user sees home feed', function () {
    $user = User::factory()->create();
    Post::factory(5)->create(['status' => 'published']);

    $this->actingAs($user)->getJson('/api/v1/feed')
        ->assertOk()->assertJsonStructure(['data', 'meta']);
});

test('community feed returns only that communitys posts', function () {
    Post::factory(3)->create(['status' => 'published', 'community_id' => 1]);
    Post::factory(2)->create(['status' => 'published', 'community_id' => 2]);

    $this->getJson('/api/v1/feed/community/1')
        ->assertOk()->assertJsonCount(3, 'data');
});
```

- [ ] **Step 6: Commit**
```bash
git add plugins/Feed/ bootstrap/providers.php tests/Feature/FeedTest.php plugins/Post/Models/Post.php
git commit -m "feat: Feed plugin — home feed, community feed, church feed endpoints"
```

---

## Task 6: General Community Controller

**Files:**
- Create: `plugins/Community/Controllers/CommunityController.php`
- Modify: `plugins/Community/routes/api.php`

- [ ] **Step 1: Create CommunityController**

```php
<?php
namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

class CommunityController extends Controller
{
    /** @group Communities */
    public function index(Request $request): JsonResponse
    {
        $communities = Community::regularGroups()->active()
            ->with('creator:id,name,avatar')
            ->withCount('approvedMembers')
            ->when($request->search,    fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->church_id, fn ($q) => $q->where('church_id', $request->church_id))
            ->latest()->paginate(20);

        return response()->json($communities);
    }

    /**
     * @group Communities
     * @bodyParam name        string  required Example: "Sunday Youth"
     * @bodyParam description string  optional
     * @bodyParam privacy     string  optional public|private
     * @bodyParam church_id   integer optional
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'privacy'     => ['sometimes', 'in:public,private'],
            'church_id'   => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $community = Community::create(array_merge($validated, [
            'created_by' => $request->user()->id, 'status' => 'active', 'is_counsel_group' => false,
        ]));

        CommunityMember::create(['community_id' => $community->id, 'user_id' => $request->user()->id, 'role' => 'admin', 'status' => 'approved']);

        return response()->json($community->load('creator:id,name,avatar'), 201);
    }

    /** @group Communities */
    public function show(int $id): JsonResponse
    {
        return response()->json(
            Community::regularGroups()->with('creator:id,name,avatar')->withCount('approvedMembers')->findOrFail($id)
        );
    }

    /** @group Communities */
    public function join(Request $request, int $id): JsonResponse
    {
        $community = Community::regularGroups()->active()->findOrFail($id);
        abort_if($community->isFull(), 422, 'Community is full.');

        $existing = CommunityMember::where(['community_id' => $id, 'user_id' => $request->user()->id])->first();
        abort_if($existing, 422, 'Already a member.');

        $status = $community->requires_approval ? 'pending' : 'approved';
        CommunityMember::create(['community_id' => $id, 'user_id' => $request->user()->id, 'role' => 'member', 'status' => $status]);

        if ($status === 'approved') { $community->increment('members_count'); }

        return response()->json(['status' => $status], 201);
    }

    /** @group Communities */
    public function leave(Request $request, int $id): JsonResponse
    {
        $member = CommunityMember::where(['community_id' => $id, 'user_id' => $request->user()->id])->firstOrFail();
        abort_if($member->role === 'admin', 422, 'Admin cannot leave. Transfer ownership first.');
        $member->delete();
        Community::where('id', $id)->decrement('members_count');
        return response()->json(['message' => 'Left community.']);
    }
}
```

- [ ] **Step 2: Add community routes inside the existing `prefix('v1')` group**

The existing `plugins/Community/routes/api.php` already has a `Route::prefix('v1')` group for counsel routes.
Add the new routes **inside** the existing group — do NOT create a second `Route::prefix('v1')` block (that would double-prefix routes as `/v1/v1/communities`).

Add the following routes inside the existing `Route::prefix('v1')->group(...)` closure:
```php
use Plugins\Community\Controllers\CommunityController;

// General communities (add inside the existing prefix('v1') group)
Route::get('communities',                    [CommunityController::class, 'index']);
Route::get('communities/{id}',               [CommunityController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('communities',               [CommunityController::class, 'store']);
    Route::post('communities/{id}/join',     [CommunityController::class, 'join']);
    Route::delete('communities/{id}/leave',  [CommunityController::class, 'leave']);
});
```

- [ ] **Step 3: Commit**
```bash
git add plugins/Community/
git commit -m "feat: CommunityController — general community CRUD, join/leave"
```

---

## Task 7: User Profile

**Files:**
- Create: `database/migrations/2026_04_10_000004_add_profile_fields_to_users.php`
- Modify: `app/Models/User.php`
- Create: `app/Http/Controllers/Api/UserProfileController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Migration**

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('cover_image')->nullable()->after('avatar');
            $table->string('bio', 500)->nullable()->after('cover_image');
            $table->string('location', 100)->nullable()->after('bio');
            $table->string('website')->nullable()->after('location');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar','cover_image','bio','location','website']);
        });
    }
};
```

- [ ] **Step 2: Update User model**

Add to `$fillable` and add relationships:
```php
protected $fillable = ['name', 'email', 'password', 'avatar', 'cover_image', 'bio', 'location', 'website'];

public function churchMemberships()
{
    return $this->hasMany(\App\Models\ChurchMember::class);
}

public function communities()
{
    return $this->hasMany(\Plugins\Community\Models\CommunityMember::class);
}
```

- [ ] **Step 3: Create UserProfileController**

```php
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Get a public profile.
     * @group Profiles
     * @urlParam id integer required Example: 1
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(
            User::select('id','name','avatar','cover_image','bio','location','website','created_at')->findOrFail($id)
        );
    }

    /**
     * Update authenticated user's profile.
     * @group Profiles
     * @bodyParam name        string optional
     * @bodyParam bio         string optional Max 500 chars.
     * @bodyParam location    string optional
     * @bodyParam website     string optional
     * @bodyParam avatar      string optional URL.
     * @bodyParam cover_image string optional URL.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'bio'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'location'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'website'     => ['sometimes', 'nullable', 'url', 'max:255'],
            'avatar'      => ['sometimes', 'nullable', 'string', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $request->user()->update($validated);
        return response()->json($request->user()->fresh());
    }
}
```

- [ ] **Step 4: Add to routes/api.php**

Inside the `v1` prefix group:
```php
use App\Http\Controllers\Api\UserProfileController;

Route::get('users/{id}',  [UserProfileController::class, 'show']);
Route::patch('profile',   [UserProfileController::class, 'update'])->middleware('auth:sanctum');
```

- [ ] **Step 5: Commit**
```bash
git add database/migrations/2026_04_10_000004_* app/Models/User.php app/Http/Controllers/Api/UserProfileController.php routes/api.php
git commit -m "feat: user profile — avatar, bio, location; GET /users/{id}, PATCH /profile"
```

---

## Task 8: SafeHtml Component (REQUIRED before any HTML rendering)

**Files:**
- Create: `resources/js/components/shared/SafeHtml.tsx`

All components that render user-generated HTML MUST use this wrapper
instead of using `dangerouslySetInnerHTML` directly.

- [ ] **Step 1: Create SafeHtml**

```tsx
import React from 'react';
import DOMPurify from 'dompurify';

interface Props {
    html: string;
    className?: string;
    style?: React.CSSProperties;
}

/**
 * Renders sanitized HTML content.
 * All user-generated HTML must go through this component.
 * Uses DOMPurify to strip XSS vectors before rendering.
 */
export default function SafeHtml({ html, className, style }: Props) {
    return (
        <div
            className={className}
            style={style}
            ref={(el) => {
                if (el) el.innerHTML = DOMPurify.sanitize(html);
            }}
        />
    );
}
```

- [ ] **Step 2: Commit**
```bash
git add resources/js/components/shared/SafeHtml.tsx
git commit -m "feat: SafeHtml component — DOMPurify wrapper for all user-generated HTML"
```

---

## Task 9: Frontend — PostCard + CommentThread + FeedPage

**Files:**
- Create: `resources/js/plugins/feed/PostCard.tsx`
- Create: `resources/js/plugins/feed/CommentThread.tsx`
- Create: `resources/js/plugins/feed/FeedPage.tsx`

Note: Use `<SafeHtml html={...} />` for any user-generated content. Never use `dangerouslySetInnerHTML` directly.

- [ ] **Step 1: Create PostCard.tsx**

```tsx
import React, { useState, Suspense, lazy } from 'react';
import SafeHtml from '../../components/shared/SafeHtml';

const CommentThread = lazy(() => import('./CommentThread'));

const EMOJIS = ['👍', '❤️', '🙏', '😂', '😮', '😢'];

interface Author { id: number; name: string; avatar?: string }
interface Post {
    id: number; body: string; author: Author;
    church?: { name: string }; reactions_count: number;
    comments_count: number; created_at: string;
}

export default function PostCard({ post, onReact }: { post: Post; onReact: (id: number, emoji: string) => void }) {
    const [showComments, setShowComments] = useState(false);

    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '1rem', marginBottom: '1rem', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
            <div style={{ display: 'flex', gap: '0.75rem', marginBottom: '0.75rem', alignItems: 'center' }}>
                <img src={post.author.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.author.name)}`}
                    style={{ width: 40, height: 40, borderRadius: '50%', objectFit: 'cover' }} alt="" />
                <div>
                    <div style={{ fontWeight: 600, fontSize: '0.9rem' }}>{post.author.name}</div>
                    <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                        {new Date(post.created_at).toLocaleDateString()}{post.church && ` · ${post.church.name}`}
                    </div>
                </div>
            </div>

            <SafeHtml html={post.body} style={{ fontSize: '0.95rem', lineHeight: 1.6, marginBottom: '0.75rem' }} />

            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', flexWrap: 'wrap' }}>
                {EMOJIS.map(e => (
                    <button key={e} onClick={() => onReact(post.id, e)}
                        style={{ background: 'none', border: '1px solid #e2e8f0', borderRadius: 20, padding: '0.2rem 0.6rem', cursor: 'pointer' }}>
                        {e}
                    </button>
                ))}
                <span style={{ marginLeft: 'auto', fontSize: '0.8rem', color: '#64748b' }}>{post.reactions_count} reactions</span>
                <button onClick={() => setShowComments(v => !v)}
                    style={{ fontSize: '0.8rem', color: '#2563eb', background: 'none', border: 'none', cursor: 'pointer' }}>
                    {post.comments_count} comments
                </button>
            </div>

            {showComments && (
                <div style={{ marginTop: '0.75rem', borderTop: '1px solid #f1f5f9', paddingTop: '0.75rem' }}>
                    <Suspense fallback={<span style={{ fontSize: '0.8rem', color: '#94a3b8' }}>Loading…</span>}>
                        <CommentThread postId={post.id} />
                    </Suspense>
                </div>
            )}
        </div>
    );
}
```

- [ ] **Step 2: Create CommentThread.tsx**

```tsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import SafeHtml from '../../components/shared/SafeHtml';

interface Comment {
    id: number; body: string;
    author: { name: string; avatar?: string };
    replies: Comment[]; replies_count: number; created_at: string;
}

export default function CommentThread({ postId }: { postId: number }) {
    const [comments, setComments] = useState<Comment[]>([]);
    const [body, setBody]         = useState('');
    const [replyTo, setReplyTo]   = useState<number | null>(null);
    const [loading, setLoading]   = useState(true);

    useEffect(() => {
        axios.get(`/api/v1/posts/${postId}/comments`).then(r => { setComments(r.data.data ?? []); setLoading(false); });
    }, [postId]);

    const submit = async (parentId?: number) => {
        if (!body.trim()) return;
        const { data } = await axios.post('/api/v1/comments', { post_id: postId, body, parent_id: parentId ?? null });
        if (parentId) {
            setComments(cs => cs.map(c => c.id === parentId ? { ...c, replies: [data, ...c.replies] } : c));
        } else {
            setComments(cs => [data, ...cs]);
        }
        setBody(''); setReplyTo(null);
    };

    const Item = ({ c, depth = 0 }: { c: Comment; depth?: number }) => (
        <div style={{ marginLeft: depth * 20, marginBottom: '0.75rem' }}>
            <div style={{ display: 'flex', gap: '0.5rem' }}>
                <img src={c.author.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(c.author.name)}`}
                    style={{ width: 28, height: 28, borderRadius: '50%', flexShrink: 0 }} alt="" />
                <div style={{ background: '#f8fafc', borderRadius: 8, padding: '0.4rem 0.75rem', flex: 1 }}>
                    <strong style={{ fontSize: '0.8rem' }}>{c.author.name}</strong>
                    <SafeHtml html={c.body} style={{ fontSize: '0.875rem' }} />
                </div>
            </div>
            <button onClick={() => setReplyTo(c.id)}
                style={{ fontSize: '0.75rem', color: '#2563eb', background: 'none', border: 'none', cursor: 'pointer', marginLeft: 36 }}>
                Reply
            </button>
            {replyTo === c.id && (
                <div style={{ marginLeft: 36, marginTop: '0.4rem', display: 'flex', gap: '0.5rem' }}>
                    <input value={body} onChange={e => setBody(e.target.value)} placeholder="Write a reply…"
                        style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.4rem 0.75rem', fontSize: '0.875rem' }} />
                    <button onClick={() => submit(c.id)}
                        style={{ background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.4rem 0.75rem', cursor: 'pointer' }}>
                        Post
                    </button>
                </div>
            )}
            {c.replies?.map(r => <Item key={r.id} c={r} depth={depth + 1} />)}
        </div>
    );

    return (
        <div>
            <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '0.75rem' }}>
                <input value={body} onChange={e => setBody(e.target.value)} placeholder="Write a comment…"
                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.5rem 0.75rem', fontSize: '0.875rem' }} />
                <button onClick={() => submit()}
                    style={{ background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.5rem 1rem', cursor: 'pointer' }}>
                    Post
                </button>
            </div>
            {loading ? <p style={{ color: '#94a3b8', fontSize: '0.875rem' }}>Loading…</p> : comments.map(c => <Item key={c.id} c={c} />)}
        </div>
    );
}
```

- [ ] **Step 3: Create FeedPage.tsx**

```tsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import PostCard from './PostCard';

export default function FeedPage() {
    const [posts, setPosts]     = useState<any[]>([]);
    const [page, setPage]       = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [loading, setLoading] = useState(false);

    const load = async (p = 1) => {
        setLoading(true);
        const { data } = await axios.get(`/api/v1/feed?page=${p}`);
        setPosts(prev => p === 1 ? data.data : [...prev, ...data.data]);
        setHasMore(!!data.next_page_url);
        setLoading(false);
    };

    useEffect(() => { load(1); }, []);

    const react = async (postId: number, emoji: string) => {
        await axios.post('/api/v1/reactions', { reactable_type: 'post', reactable_id: postId, emoji });
        setPosts(ps => ps.map(p => p.id === postId ? { ...p, reactions_count: p.reactions_count + 1 } : p));
    };

    return (
        <div style={{ maxWidth: 640, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Home Feed</h1>
            {posts.map(post => <PostCard key={post.id} post={post} onReact={react} />)}
            {hasMore && (
                <button onClick={() => { const next = page + 1; setPage(next); load(next); }}
                    disabled={loading}
                    style={{ width: '100%', padding: '0.75rem', background: '#f1f5f9', border: 'none', borderRadius: 8, cursor: 'pointer', marginTop: '0.5rem' }}>
                    {loading ? 'Loading…' : 'Load more'}
                </button>
            )}
        </div>
    );
}
```

- [ ] **Step 4: Commit**
```bash
git add resources/js/plugins/feed/
git commit -m "feat: PostCard, CommentThread, FeedPage React components"
```

---

## Task 10: CommunityPage + ProfilePage

**Files:**
- Create: `resources/js/plugins/community/CommunityPage.tsx`
- Create: `resources/js/plugins/profile/ProfilePage.tsx`

- [ ] **Step 1: Create CommunityPage.tsx**

```tsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface Community { id: number; name: string; description?: string; members_count: number; privacy: string; cover_image?: string }

export default function CommunityPage() {
    const [items, setItems]   = useState<Community[]>([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        axios.get('/api/v1/communities', { params: { search } }).then(r => setItems(r.data.data ?? []));
    }, [search]);

    const join = async (id: number) => {
        await axios.post(`/api/v1/communities/${id}/join`);
        setItems(cs => cs.map(c => c.id === id ? { ...c, members_count: c.members_count + 1 } : c));
    };

    return (
        <div style={{ maxWidth: 800, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Communities</h1>
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search…"
                style={{ width: '100%', padding: '0.6rem 1rem', border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: '1rem', boxSizing: 'border-box' }} />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(240px,1fr))', gap: '1rem' }}>
                {items.map(c => (
                    <div key={c.id} style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
                        <div style={{ height: 80, background: c.cover_image ? `url(${c.cover_image}) center/cover` : '#2563eb' }} />
                        <div style={{ padding: '0.75rem' }}>
                            <div style={{ fontWeight: 600 }}>{c.name}</div>
                            <div style={{ fontSize: '0.8rem', color: '#64748b', marginBottom: '0.4rem' }}>{c.members_count} members · {c.privacy}</div>
                            {c.description && <p style={{ fontSize: '0.8rem', color: '#475569', marginBottom: '0.5rem' }}>{c.description}</p>}
                            <button onClick={() => join(c.id)}
                                style={{ width: '100%', background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.4rem', cursor: 'pointer' }}>
                                Join
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Create ProfilePage.tsx**

```tsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface Profile { id: number; name: string; avatar?: string; cover_image?: string; bio?: string; location?: string; website?: string }

export default function ProfilePage({ userId }: { userId: number }) {
    const [profile, setProfile] = useState<Profile | null>(null);

    useEffect(() => {
        axios.get(`/api/v1/users/${userId}`).then(r => setProfile(r.data));
    }, [userId]);

    if (!profile) return <p style={{ textAlign: 'center', padding: '2rem', color: '#94a3b8' }}>Loading…</p>;

    return (
        <div style={{ maxWidth: 640, margin: '0 auto' }}>
            <div style={{ height: 160, borderRadius: '0 0 12px 12px', background: profile.cover_image ? `url(${profile.cover_image}) center/cover` : '#2563eb' }} />
            <div style={{ padding: '0 1rem' }}>
                <img src={profile.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.name)}&size=80`}
                    style={{ width: 80, height: 80, borderRadius: '50%', border: '3px solid #fff', marginTop: -40, objectFit: 'cover' }} alt="" />
                <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginTop: '0.5rem' }}>{profile.name}</h1>
                {profile.bio      && <p style={{ color: '#475569', margin: '0.25rem 0' }}>{profile.bio}</p>}
                {profile.location && <p style={{ fontSize: '0.875rem', color: '#64748b' }}>📍 {profile.location}</p>}
                {profile.website  && <a href={profile.website} style={{ color: '#2563eb', fontSize: '0.875rem' }}>{profile.website}</a>}
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Commit**
```bash
git add resources/js/plugins/community/CommunityPage.tsx resources/js/plugins/profile/ProfilePage.tsx
git commit -m "feat: CommunityPage and ProfilePage React components"
```

---

## Verification Checklist

- [ ] `POST /api/v1/comments` creates a comment on a post
- [ ] `GET /api/v1/posts/{id}/comments` returns paginated comments with replies
- [ ] Replying increments `replies_count` on parent comment
- [ ] `POST /api/v1/reactions` toggles reaction; second call returns `reacted: false`
- [ ] `GET /api/v1/feed` returns paginated posts (auth required)
- [ ] `GET /api/v1/feed/community/{id}` returns only that community's posts (public)
- [ ] `GET /api/v1/communities` lists general communities
- [ ] `POST /api/v1/communities/{id}/join` creates member row
- [ ] `GET /api/v1/users/{id}` returns public profile fields only
- [ ] `PATCH /api/v1/profile` updates bio/avatar
- [ ] FeedPage renders PostCard list; emoji click calls reaction API
- [ ] CommentThread loads inline; reply creates nested comment
- [ ] SafeHtml wrapper used for all user-generated body content

---

## Expected Commit Log
```
feat: Comment plugin — migration, model, service provider
feat: Comment controller, routes, and feature tests
feat: Reaction plugin — polymorphic toggle reactions, reactions() added to Comment
feat: Feed plugin — home feed, community feed, church feed endpoints
feat: CommunityController — general community CRUD, join/leave
feat: user profile — avatar, bio, location; GET /users/{id}, PATCH /profile
feat: SafeHtml component — DOMPurify wrapper for all user-generated HTML
feat: PostCard, CommentThread, FeedPage React components
feat: CommunityPage and ProfilePage React components
```
