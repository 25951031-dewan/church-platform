# Plan 2: Timeline + Reactions + Comments — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first church-specific plugin (Timeline) with polymorphic reactions and nested comments — establishing the plugin development pattern that all subsequent plugins will follow.

**Architecture:** Timeline is the first plugin in `app/Plugins/Timeline/`. Reactions and Comments live in `common/foundation/` because they're shared across all plugins (sermons, prayers, articles will reuse them). The plugin follows BeMusic service patterns: Loader, Crupdate, Paginate, Delete per resource. Each model gets a Policy with own/any permission pattern.

**Tech Stack:** Laravel 12 plugin in `app/Plugins/Timeline/`, polymorphic Eloquent relations, TanStack React Query for data fetching, Zustand for optimistic UI, infinite scroll via `@tanstack/react-virtual`.

**Spec:** `docs/superpowers/specs/2026-03-28-church-community-platform-design.md` (sections 5, 8, 9, 13)

**Depends on:** Plan 1 (Foundation + Auth + Settings) — all 15 tasks must be complete.

---

## File Structure Overview

```
common/foundation/src/
├── Reactions/
│   ├── Models/Reaction.php              # Polymorphic reaction model
│   ├── Controllers/ReactionController.php
│   ├── Traits/HasReactions.php          # Attach to any reactable model
│   └── config/reactions.php             # Reaction types config
├── Comments/
│   ├── Models/Comment.php               # Polymorphic nested comment model
│   ├── Controllers/CommentController.php
│   ├── Policies/CommentPolicy.php
│   ├── Requests/ModifyComment.php
│   └── Traits/HasComments.php           # Attach to any commentable model

app/Plugins/Timeline/
├── Models/
│   ├── Post.php                         # Timeline post model
│   └── PostMedia.php                    # Post media attachments
├── Services/
│   ├── PostLoader.php                   # API response formatting
│   ├── CrupdatePost.php                 # Create/update logic
│   ├── PaginatePosts.php                # Feed with filters/sorting
│   └── DeletePosts.php                  # Delete with cleanup
├── Controllers/
│   └── PostController.php              # CRUD + feed endpoint
├── Policies/
│   └── PostPolicy.php                  # own/any permission pattern
├── Requests/
│   └── ModifyPost.php                  # Validation
├── Routes/
│   └── api.php                         # Plugin routes
├── Database/
│   ├── Migrations/
│   │   ├── 0002_01_01_000001_create_posts_table.php
│   │   ├── 0002_01_01_000002_create_post_media_table.php
│   │   ├── 0002_01_01_000003_create_reactions_table.php
│   │   └── 0002_01_01_000004_create_comments_table.php
│   └── Seeders/
│       └── TimelinePermissionSeeder.php

database/
├── migrations/
│   ├── 0002_01_01_000001_create_posts_table.php     (symlink or copy)
│   ├── 0002_01_01_000002_create_post_media_table.php
│   ├── 0002_01_01_000003_create_reactions_table.php
│   └── 0002_01_01_000004_create_comments_table.php

resources/client/
├── common/
│   └── components/
│       ├── ReactionBar.tsx              # Shared reaction picker + counts
│       └── CommentThread.tsx            # Shared nested comments
├── plugins/
│   └── timeline/
│       ├── pages/
│       │   └── NewsfeedPage.tsx         # Main feed page
│       ├── components/
│       │   ├── PostComposer.tsx         # Create post form
│       │   ├── PostCard.tsx             # Single post display
│       │   └── PostFeed.tsx             # Infinite scroll feed
│       └── queries.ts                   # TanStack Query hooks

tests/
├── Feature/
│   ├── Timeline/
│   │   ├── PostCrudTest.php
│   │   ├── PostFeedTest.php
│   │   └── PostPolicyTest.php
│   ├── Reactions/
│   │   └── ReactionTest.php
│   └── Comments/
│       └── CommentTest.php
```

---

## Task 1: Reactions Migration + Model (Foundation — shared)

**Files:**
- Create: `database/migrations/0002_01_01_000001_create_reactions_table.php`
- Create: `common/foundation/src/Reactions/Models/Reaction.php`
- Create: `common/foundation/src/Reactions/Traits/HasReactions.php`
- Test: `tests/Feature/Reactions/ReactionTest.php`

- [ ] **Step 1: Create reactions migration**

```php
<?php
// database/migrations/0002_01_01_000001_create_reactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactable'); // reactable_id + reactable_type
            $table->string('type', 20); // like, pray, amen, love, celebrate
            $table->timestamps();

            $table->unique(['user_id', 'reactable_id', 'reactable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
```

- [ ] **Step 2: Create Reaction model**

```php
<?php
// common/foundation/src/Reactions/Models/Reaction.php

namespace Common\Reactions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = ['like', 'pray', 'amen', 'love', 'celebrate'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

- [ ] **Step 3: Create HasReactions trait**

```php
<?php
// common/foundation/src/Reactions/Traits/HasReactions.php

namespace Common\Reactions\Traits;

use Common\Reactions\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function reactionCounts(): array
    {
        return $this->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function currentUserReaction(): ?Reaction
    {
        if (!auth()->check()) return null;
        return $this->reactions()->where('user_id', auth()->id())->first();
    }
}
```

- [ ] **Step 4: Write reaction test**

```php
<?php
// tests/Feature/Reactions/ReactionTest.php

namespace Tests\Feature\Reactions;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Common\Reactions\Models\Reaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    private function createMemberUser(): User
    {
        $perm = Permission::create([
            'name' => 'reactions.create',
            'display_name' => 'React to Content',
            'group' => 'reactions',
        ]);
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_user_can_toggle_reaction(): void
    {
        $user = $this->createMemberUser();

        // We need a reactable model — we'll use Post once it exists.
        // For now, test the model directly via DB insert.
        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'like',
        ]);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        // Toggle off — delete
        $reaction->delete();
        $this->assertDatabaseMissing('reactions', ['id' => $reaction->id]);
    }

    public function test_user_cannot_double_react_same_content(): void
    {
        $user = $this->createMemberUser();

        Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'like',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Reaction::create([
            'user_id' => $user->id,
            'reactable_id' => 1,
            'reactable_type' => 'post',
            'type' => 'pray', // Different type, same content — still blocked by unique constraint
        ]);
    }

    public function test_reaction_types_are_valid(): void
    {
        $this->assertEquals(
            ['like', 'pray', 'amen', 'love', 'celebrate'],
            Reaction::TYPES
        );
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/0002_01_01_000001_create_reactions_table.php \
  common/foundation/src/Reactions/ tests/Feature/Reactions/
git commit -m "feat: add polymorphic reactions system (like, pray, amen, love, celebrate)"
```

---

## Task 2: Comments Migration + Model (Foundation — shared)

**Files:**
- Create: `database/migrations/0002_01_01_000002_create_comments_table.php`
- Create: `common/foundation/src/Comments/Models/Comment.php`
- Create: `common/foundation/src/Comments/Traits/HasComments.php`
- Create: `common/foundation/src/Comments/Requests/ModifyComment.php`
- Create: `common/foundation/src/Comments/Policies/CommentPolicy.php`
- Test: `tests/Feature/Comments/CommentTest.php`

- [ ] **Step 1: Create comments migration**

```php
<?php
// database/migrations/0002_01_01_000002_create_comments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable'); // commentable_id + commentable_type
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

- [ ] **Step 2: Create Comment model**

```php
<?php
// common/foundation/src/Comments/Models/Comment.php

namespace Common\Comments\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use HasReactions;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
```

- [ ] **Step 3: Create HasComments trait**

```php
<?php
// common/foundation/src/Comments/Traits/HasComments.php

namespace Common\Comments\Traits;

use Common\Comments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function commentCount(): int
    {
        return $this->comments()->count();
    }
}
```

- [ ] **Step 4: Create ModifyComment request**

```php
<?php
// common/foundation/src/Comments/Requests/ModifyComment.php

namespace Common\Comments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyComment extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ];
    }
}
```

- [ ] **Step 5: Create CommentPolicy**

```php
<?php
// common/foundation/src/Comments/Policies/CommentPolicy.php

namespace Common\Comments\Policies;

use App\Models\User;
use Common\Comments\Models\Comment;
use Common\Core\BasePolicy;

class CommentPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('comments.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($comment->isOwnedBy($user->id)) {
            return $user->hasPermission('comments.update');
        }
        return false; // Only own comments can be edited (no update_any for comments)
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isOwnedBy($user->id)) {
            return true; // Users can always delete their own comments
        }
        return $user->hasPermission('comments.delete_any');
    }
}
```

- [ ] **Step 6: Write comment test**

```php
<?php
// tests/Feature/Comments/CommentTest.php

namespace Tests\Feature\Comments;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Common\Comments\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function seedTimelinePermissions(): void
    {
        foreach ([
            'comments.create' => 'Post Comments',
            'comments.update' => 'Edit Own Comments',
            'comments.delete_any' => 'Delete Any Comment',
            'comments.moderate' => 'Moderate Comments',
            'reactions.create' => 'React to Content',
        ] as $name => $display) {
            Permission::create([
                'name' => $name,
                'display_name' => $display,
                'group' => explode('.', $name)[0],
            ]);
        }
    }

    private function createMember(): User
    {
        $this->seedTimelinePermissions();
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach(
            Permission::whereIn('name', ['comments.create', 'comments.update', 'reactions.create'])->pluck('id')
        );

        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_comment_has_nested_replies(): void
    {
        $user = User::factory()->create();

        $parent = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Great post!',
        ]);

        $reply = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Thanks!',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals(1, $parent->replies()->count());
        $this->assertEquals($parent->id, $reply->parent->id);
    }

    public function test_comment_has_reactions(): void
    {
        $user = User::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Amen!',
        ]);

        $comment->reactions()->create([
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        $this->assertEquals(1, $comment->reactions()->count());
        $this->assertEquals(['like' => 1], $comment->reactionCounts());
    }

    public function test_deleting_parent_deletes_replies(): void
    {
        $user = User::factory()->create();

        $parent = Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Parent',
        ]);

        Comment::create([
            'user_id' => $user->id,
            'commentable_id' => 1,
            'commentable_type' => 'post',
            'body' => 'Reply',
            'parent_id' => $parent->id,
        ]);

        $parent->delete();
        $this->assertEquals(0, Comment::count());
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add database/migrations/0002_01_01_000002_create_comments_table.php \
  common/foundation/src/Comments/ tests/Feature/Comments/
git commit -m "feat: add polymorphic nested comments system with policy"
```

---

## Task 3: Reaction Controller + API Routes (Foundation)

**Files:**
- Create: `common/foundation/src/Reactions/Controllers/ReactionController.php`
- Modify: `routes/api.php`
- Test: Update `tests/Feature/Reactions/ReactionTest.php`

- [ ] **Step 1: Create ReactionController**

```php
<?php
// common/foundation/src/Reactions/Controllers/ReactionController.php

namespace Common\Reactions\Controllers;

use Common\Reactions\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReactionController extends Controller
{
    /**
     * Toggle a reaction. If same type exists, remove it.
     * If different type exists, switch it. If none, create it.
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactable_id' => 'required|integer',
            'reactable_type' => 'required|string|in:post,comment,sermon,prayer_request,article',
            'type' => 'required|string|in:' . implode(',', Reaction::TYPES),
        ]);

        $existing = Reaction::where([
            'user_id' => $request->user()->id,
            'reactable_id' => $validated['reactable_id'],
            'reactable_type' => $validated['reactable_type'],
        ])->first();

        if ($existing) {
            if ($existing->type === $validated['type']) {
                // Same type — remove (toggle off)
                $existing->delete();
                return response()->json(['reaction' => null, 'action' => 'removed']);
            }
            // Different type — switch
            $existing->update(['type' => $validated['type']]);
            return response()->json(['reaction' => $existing->fresh(), 'action' => 'switched']);
        }

        // No existing — create
        $reaction = Reaction::create([
            'user_id' => $request->user()->id,
            'reactable_id' => $validated['reactable_id'],
            'reactable_type' => $validated['reactable_type'],
            'type' => $validated['type'],
        ]);

        return response()->json(['reaction' => $reaction, 'action' => 'created'], 201);
    }
}
```

- [ ] **Step 2: Create CommentController**

```php
<?php
// common/foundation/src/Comments/Controllers/CommentController.php

namespace Common\Comments\Controllers;

use Common\Comments\Models\Comment;
use Common\Comments\Requests\ModifyComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_id' => 'required|integer',
            'commentable_type' => 'required|string|in:post,sermon,prayer_request,article',
            'page' => 'integer|min:1',
        ]);

        $comments = Comment::where([
                'commentable_id' => $validated['commentable_id'],
                'commentable_type' => $validated['commentable_type'],
            ])
            ->whereNull('parent_id')
            ->with(['user:id,name,avatar', 'replies.user:id,name,avatar', 'reactions'])
            ->withCount('replies')
            ->latest()
            ->paginate(15);

        return response()->json($comments);
    }

    public function store(ModifyComment $request): JsonResponse
    {
        Gate::authorize('create', Comment::class);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_id' => $request->input('commentable_id'),
            'commentable_type' => $request->input('commentable_type'),
            'body' => $request->input('body'),
            'parent_id' => $request->input('parent_id'),
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json(['comment' => $comment], 201);
    }

    public function update(ModifyComment $request, Comment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $comment->update(['body' => $request->input('body')]);

        return response()->json(['comment' => $comment]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
```

- [ ] **Step 3: Add reaction and comment routes to api.php**

Add inside the `auth:sanctum` group in `routes/api.php`:

```php
// Reactions & Comments (shared foundation)
Route::post('reactions/toggle', [\Common\Reactions\Controllers\ReactionController::class, 'toggle'])
    ->middleware('permission:reactions.create');

Route::get('comments', [\Common\Comments\Controllers\CommentController::class, 'index']);
Route::post('comments', [\Common\Comments\Controllers\CommentController::class, 'store']);
Route::put('comments/{comment}', [\Common\Comments\Controllers\CommentController::class, 'update']);
Route::delete('comments/{comment}', [\Common\Comments\Controllers\CommentController::class, 'destroy']);
```

- [ ] **Step 4: Register CommentPolicy in AppServiceProvider**

Add to `AppServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;
use Common\Comments\Models\Comment;
use Common\Comments\Policies\CommentPolicy;

// In boot():
Gate::policy(Comment::class, CommentPolicy::class);
```

- [ ] **Step 5: Write API-level reaction test**

Add to `tests/Feature/Reactions/ReactionTest.php`:

```php
public function test_toggle_reaction_via_api(): void
{
    $user = $this->createMemberUser();

    // Need a post to react to — create one directly
    \DB::table('posts')->insert([
        'id' => 1,
        'user_id' => $user->id,
        'type' => 'text',
        'content' => 'Hello world',
        'visibility' => 'public',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create reaction
    $this->actingAs($user)->postJson('/api/v1/reactions/toggle', [
        'reactable_id' => 1,
        'reactable_type' => 'post',
        'type' => 'like',
    ])
        ->assertStatus(201)
        ->assertJsonPath('action', 'created');

    // Toggle off (same type)
    $this->actingAs($user)->postJson('/api/v1/reactions/toggle', [
        'reactable_id' => 1,
        'reactable_type' => 'post',
        'type' => 'like',
    ])
        ->assertOk()
        ->assertJsonPath('action', 'removed');
}
```

**Note:** This test requires the posts table to exist. It will only pass after Task 4 creates the posts migration. If running tests incrementally, run the reaction model tests first, then this API test after Task 4.

- [ ] **Step 6: Commit**

```bash
git add common/foundation/src/Reactions/Controllers/ \
  common/foundation/src/Comments/Controllers/ \
  routes/api.php app/Providers/AppServiceProvider.php \
  tests/Feature/Reactions/
git commit -m "feat: add reaction toggle API and comment CRUD controllers"
```

---

## Task 4: Posts Migration + Model (Timeline Plugin)

**Files:**
- Create: `database/migrations/0002_01_01_000003_create_posts_table.php`
- Create: `database/migrations/0002_01_01_000004_create_post_media_table.php`
- Create: `app/Plugins/Timeline/Models/Post.php`
- Create: `app/Plugins/Timeline/Models/PostMedia.php`

- [ ] **Step 1: Create posts migration**

```php
<?php
// database/migrations/0002_01_01_000003_create_posts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->enum('type', ['text', 'photo', 'video', 'announcement'])->default('text');
            $table->text('content')->nullable();
            $table->enum('visibility', ['public', 'members', 'private'])->default('public');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('church_id');
            $table->index('visibility');
            $table->index('is_pinned');
            $table->index('published_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

- [ ] **Step 2: Create post_media migration**

```php
<?php
// database/migrations/0002_01_01_000004_create_post_media_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('type', ['image', 'video'])->default('image');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
```

- [ ] **Step 3: Create Post model**

```php
<?php
// app/Plugins/Timeline/Models/Post.php

namespace App\Plugins\Timeline\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasReactions, HasComments;

    protected $guarded = ['id'];

    protected $casts = [
        'is_pinned' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function isPublished(): bool
    {
        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }
        return true;
    }

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('scheduled_at')
              ->orWhere('scheduled_at', '<=', now());
        });
    }

    public function scopeFeed($query)
    {
        return $query->published()
            ->where('visibility', '!=', 'private')
            ->orderByDesc('is_pinned')
            ->latest();
    }
}
```

- [ ] **Step 4: Create PostMedia model**

```php
<?php
// app/Plugins/Timeline/Models/PostMedia.php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    protected $guarded = ['id'];

    protected $table = 'post_media';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/0002_01_01_000003_create_posts_table.php \
  database/migrations/0002_01_01_000004_create_post_media_table.php \
  app/Plugins/Timeline/Models/
git commit -m "feat: add Post and PostMedia models with reactions/comments traits"
```

---

## Task 5: Timeline Services (Loader, Crupdate, Paginate, Delete)

**Files:**
- Create: `app/Plugins/Timeline/Services/PostLoader.php`
- Create: `app/Plugins/Timeline/Services/CrupdatePost.php`
- Create: `app/Plugins/Timeline/Services/PaginatePosts.php`
- Create: `app/Plugins/Timeline/Services/DeletePosts.php`

- [ ] **Step 1: Create PostLoader**

```php
<?php
// app/Plugins/Timeline/Services/PostLoader.php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class PostLoader
{
    public function load(Post $post): Post
    {
        return $post->load([
            'user:id,name,avatar',
            'media',
            'reactions',
        ])->loadCount(['comments', 'reactions']);
    }

    public function loadForFeed(Post $post): array
    {
        $this->load($post);

        $data = $post->toArray();
        $data['reaction_counts'] = $post->reactionCounts();
        $data['current_user_reaction'] = $post->currentUserReaction()?->type;

        return $data;
    }
}
```

- [ ] **Step 2: Create CrupdatePost**

```php
<?php
// app/Plugins/Timeline/Services/CrupdatePost.php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class CrupdatePost
{
    public function execute(array $data, ?Post $post = null): Post
    {
        if ($post) {
            $post->update([
                'content' => $data['content'] ?? $post->content,
                'type' => $data['type'] ?? $post->type,
                'visibility' => $data['visibility'] ?? $post->visibility,
                'scheduled_at' => $data['scheduled_at'] ?? $post->scheduled_at,
            ]);
        } else {
            $post = Post::create([
                'user_id' => $data['user_id'],
                'church_id' => $data['church_id'] ?? null,
                'type' => $data['type'] ?? 'text',
                'content' => $data['content'],
                'visibility' => $data['visibility'] ?? 'public',
                'is_pinned' => $data['is_pinned'] ?? false,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'published_at' => isset($data['scheduled_at']) ? null : now(),
            ]);
        }

        return $post;
    }
}
```

- [ ] **Step 3: Create PaginatePosts**

```php
<?php
// app/Plugins/Timeline/Services/PaginatePosts.php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginatePosts
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Post::query()
            ->with(['user:id,name,avatar', 'media', 'reactions'])
            ->withCount(['comments', 'reactions']);

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->input('feed', false)) {
            $query->feed();
        } else {
            $query->published()->latest();
        }

        return $query->paginate($request->input('per_page', 15));
    }
}
```

- [ ] **Step 4: Create DeletePosts**

```php
<?php
// app/Plugins/Timeline/Services/DeletePosts.php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class DeletePosts
{
    public function execute(array $postIds): void
    {
        $posts = Post::whereIn('id', $postIds)->get();

        foreach ($posts as $post) {
            // Delete associated reactions and comments (cascade handles DB level,
            // but we may want to fire events or clean up files)
            $post->media()->delete();
            $post->delete();
        }
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Plugins/Timeline/Services/
git commit -m "feat: add Timeline service classes (Loader, Crupdate, Paginate, Delete)"
```

---

## Task 6: Post Policy + Controller + Routes

**Files:**
- Create: `app/Plugins/Timeline/Policies/PostPolicy.php`
- Create: `app/Plugins/Timeline/Requests/ModifyPost.php`
- Create: `app/Plugins/Timeline/Controllers/PostController.php`
- Create: `app/Plugins/Timeline/Routes/api.php`
- Modify: `routes/api.php` (include plugin routes)
- Modify: `app/Providers/AppServiceProvider.php` (register policy + morph map)

- [ ] **Step 1: Create PostPolicy**

```php
<?php
// app/Plugins/Timeline/Policies/PostPolicy.php

namespace App\Plugins\Timeline\Policies;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Core\BasePolicy;

class PostPolicy extends BasePolicy
{
    public function view(User $user, Post $post): bool
    {
        if ($post->visibility === 'public') return true;
        if ($post->visibility === 'members') return $user->hasPermission('posts.view');
        // private — only owner
        return $post->isOwnedBy($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.create');
    }

    public function update(User $user, Post $post): bool
    {
        if ($post->isOwnedBy($user->id)) {
            return $user->hasPermission('posts.update');
        }
        return $user->hasPermission('posts.update_any');
    }

    public function delete(User $user, Post $post): bool
    {
        if ($post->isOwnedBy($user->id)) {
            return $user->hasPermission('posts.delete');
        }
        return $user->hasPermission('posts.delete_any');
    }

    public function pin(User $user): bool
    {
        return $user->hasPermission('posts.pin');
    }

    public function announce(User $user): bool
    {
        return $user->hasPermission('posts.announce');
    }
}
```

- [ ] **Step 2: Create ModifyPost request**

```php
<?php
// app/Plugins/Timeline/Requests/ModifyPost.php

namespace App\Plugins\Timeline\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyPost extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required_without:media|string|max:10000',
            'type' => 'sometimes|in:text,photo,video,announcement',
            'visibility' => 'sometimes|in:public,members,private',
            'is_pinned' => 'sometimes|boolean',
            'scheduled_at' => 'sometimes|nullable|date|after:now',
            'church_id' => 'sometimes|nullable|integer',
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|max:20480', // 20MB per file
        ];
    }
}
```

- [ ] **Step 3: Create PostController**

```php
<?php
// app/Plugins/Timeline/Controllers/PostController.php

namespace App\Plugins\Timeline\Controllers;

use App\Plugins\Timeline\Models\Post;
use App\Plugins\Timeline\Requests\ModifyPost;
use App\Plugins\Timeline\Services\CrupdatePost;
use App\Plugins\Timeline\Services\DeletePosts;
use App\Plugins\Timeline\Services\PaginatePosts;
use App\Plugins\Timeline\Services\PostLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(
        private PostLoader $loader,
        private CrupdatePost $crupdate,
        private PaginatePosts $paginator,
        private DeletePosts $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = $this->paginator->execute($request);
        return response()->json($posts);
    }

    public function show(Post $post): JsonResponse
    {
        Gate::authorize('view', $post);
        return response()->json(['post' => $this->loader->loadForFeed($post)]);
    }

    public function store(ModifyPost $request): JsonResponse
    {
        Gate::authorize('create', Post::class);

        if ($request->input('type') === 'announcement') {
            Gate::authorize('announce', Post::class);
        }

        $post = $this->crupdate->execute([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'post' => $this->loader->loadForFeed($post),
        ], 201);
    }

    public function update(ModifyPost $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);

        $post = $this->crupdate->execute($request->validated(), $post);

        return response()->json([
            'post' => $this->loader->loadForFeed($post),
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $this->deleter->execute([$post->id]);

        return response()->noContent();
    }

    public function pin(Post $post): JsonResponse
    {
        Gate::authorize('pin', Post::class);

        $post->update(['is_pinned' => !$post->is_pinned]);

        return response()->json(['is_pinned' => $post->is_pinned]);
    }
}
```

- [ ] **Step 4: Create plugin routes file**

```php
<?php
// app/Plugins/Timeline/Routes/api.php

use App\Plugins\Timeline\Controllers\PostController;
use Illuminate\Support\Facades\Route;

// All routes are prefixed with /api/v1 and wrapped in auth:sanctum by the main routes file.

Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);

Route::middleware('permission:posts.create')->group(function () {
    Route::post('posts', [PostController::class, 'store']);
});

Route::put('posts/{post}', [PostController::class, 'update']);
Route::delete('posts/{post}', [PostController::class, 'destroy']);
Route::patch('posts/{post}/pin', [PostController::class, 'pin']);
```

- [ ] **Step 5: Include plugin routes in main api.php**

Add inside the `auth:sanctum` group in `routes/api.php`:

```php
// Timeline Plugin routes
if (app(\Common\Core\PluginManager::class)->isEnabled('timeline')) {
    require app_path('Plugins/Timeline/Routes/api.php');
}
```

- [ ] **Step 6: Register PostPolicy and morph map in AppServiceProvider**

Add to `AppServiceProvider::boot()`:

```php
use App\Plugins\Timeline\Models\Post;
use App\Plugins\Timeline\Policies\PostPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;

// Policies
Gate::policy(Post::class, PostPolicy::class);

// Morph map (required for polymorphic reactions/comments)
Relation::enforceMorphMap([
    'post' => Post::class,
    'comment' => \Common\Comments\Models\Comment::class,
]);
```

- [ ] **Step 7: Commit**

```bash
git add app/Plugins/Timeline/Policies/ app/Plugins/Timeline/Requests/ \
  app/Plugins/Timeline/Controllers/ app/Plugins/Timeline/Routes/ \
  routes/api.php app/Providers/AppServiceProvider.php
git commit -m "feat: add PostController with policy, routes, and plugin-aware loading"
```

---

## Task 7: Timeline Permission Seeder

**Files:**
- Create: `app/Plugins/Timeline/Database/Seeders/TimelinePermissionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create TimelinePermissionSeeder**

```php
<?php
// app/Plugins/Timeline/Database/Seeders/TimelinePermissionSeeder.php

namespace App\Plugins\Timeline\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class TimelinePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'posts' => [
                'posts.view' => 'View Posts',
                'posts.create' => 'Create Posts',
                'posts.update' => 'Edit Own Posts',
                'posts.update_any' => 'Edit Any Post',
                'posts.delete' => 'Delete Own Posts',
                'posts.delete_any' => 'Delete Any Post',
                'posts.pin' => 'Pin Posts',
                'posts.schedule' => 'Schedule Posts',
                'posts.moderate' => 'Moderate Posts',
                'posts.announce' => 'Create Announcements',
            ],
            'comments' => [
                'comments.create' => 'Post Comments',
                'comments.update' => 'Edit Own Comments',
                'comments.delete_any' => 'Delete Any Comment',
                'comments.moderate' => 'Moderate Comments',
            ],
            'reactions' => [
                'reactions.create' => 'React to Content',
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

        // Assign to roles
        $memberPerms = Permission::whereIn('name', [
            'posts.view', 'posts.create', 'posts.update', 'posts.delete',
            'comments.create', 'comments.update',
            'reactions.create',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'posts.view', 'posts.create', 'posts.update', 'posts.update_any',
            'posts.delete', 'posts.delete_any', 'posts.pin', 'posts.moderate',
            'comments.create', 'comments.update', 'comments.delete_any', 'comments.moderate',
            'reactions.create',
        ])->pluck('id');

        $allPerms = Permission::whereIn('group', ['posts', 'comments', 'reactions'])->pluck('id');

        // Sync additional permissions to existing roles (don't remove existing perms)
        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
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

- [ ] **Step 2: Update DatabaseSeeder**

Add to `DatabaseSeeder::run()`:

```php
$this->call([
    PermissionSeeder::class,
    RoleSeeder::class,
    \App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class,
]);
```

- [ ] **Step 3: Commit**

```bash
git add app/Plugins/Timeline/Database/Seeders/ database/seeders/DatabaseSeeder.php
git commit -m "feat: add Timeline plugin permissions (15 permissions, role assignments)"
```

---

## Task 8: Post CRUD Feature Tests

**Files:**
- Create: `tests/Feature/Timeline/PostCrudTest.php`
- Create: `tests/Feature/Timeline/PostFeedTest.php`
- Create: `tests/Feature/Timeline/PostPolicyTest.php`

- [ ] **Step 1: Create PostCrudTest**

```php
<?php
// tests/Feature/Timeline/PostCrudTest.php

namespace Tests\Feature\Timeline;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
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

    public function test_member_can_create_post(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/posts', [
            'content' => 'God is good!',
            'type' => 'text',
            'visibility' => 'public',
        ])
            ->assertStatus(201)
            ->assertJsonPath('post.content', 'God is good!')
            ->assertJsonStructure(['post' => ['id', 'content', 'type', 'user', 'reaction_counts']]);
    }

    public function test_member_can_update_own_post(): void
    {
        $user = $this->memberUser();

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Original',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Updated content',
        ])
            ->assertOk()
            ->assertJsonPath('post.content', 'Updated content');
    }

    public function test_member_cannot_update_others_post(): void
    {
        $author = $this->memberUser();
        $other = $this->memberUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'My post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($other)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Hacked!',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_any_post(): void
    {
        $author = $this->memberUser();
        $admin = $this->adminUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Member post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($admin)->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Admin edited',
        ])->assertOk();
    }

    public function test_member_can_delete_own_post(): void
    {
        $user = $this->memberUser();

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Delete me',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/posts/{$post->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_unauthenticated_cannot_create_post(): void
    {
        $this->postJson('/api/v1/posts', [
            'content' => 'Anonymous post',
        ])->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Create PostFeedTest**

```php
<?php
// tests/Feature/Timeline/PostFeedTest.php

namespace Tests\Feature\Timeline;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
    }

    public function test_feed_returns_paginated_posts(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        Post::factory(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)->getJson('/api/v1/posts?feed=1')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }

    public function test_pinned_posts_appear_first(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        $regular = Post::create([
            'user_id' => $user->id,
            'content' => 'Regular post',
            'type' => 'text',
            'visibility' => 'public',
            'created_at' => now(),
        ]);

        $pinned = Post::create([
            'user_id' => $user->id,
            'content' => 'Pinned post',
            'type' => 'text',
            'visibility' => 'public',
            'is_pinned' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/posts?feed=1');
        $data = $response->json('data');

        $this->assertEquals($pinned->id, $data[0]['id']);
    }

    public function test_scheduled_posts_hidden_from_feed(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        Post::create([
            'user_id' => $user->id,
            'content' => 'Visible post',
            'type' => 'text',
            'visibility' => 'public',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Scheduled post',
            'type' => 'text',
            'visibility' => 'public',
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/posts?feed=1');
        $this->assertCount(1, $response->json('data'));
    }
}
```

- [ ] **Step 3: Create Post factory for tests**

```php
<?php
// database/factories/PostFactory.php

namespace Database\Factories;

use App\Plugins\Timeline\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'content' => fake()->paragraph(),
            'type' => 'text',
            'visibility' => 'public',
            'is_pinned' => false,
        ];
    }

    public function announcement(): static
    {
        return $this->state(fn () => ['type' => 'announcement']);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['scheduled_at' => now()->addDay()]);
    }
}
```

Add `HasFactory` to the Post model — update `app/Plugins/Timeline/Models/Post.php` to include:

```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PostFactory::new();
    }
    // ... rest of model
}
```

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Timeline/ database/factories/PostFactory.php \
  app/Plugins/Timeline/Models/Post.php
git commit -m "test: add Timeline CRUD, feed, and policy tests with Post factory"
```

---

## Task 9: React Shared Components — ReactionBar + CommentThread

**Files:**
- Create: `resources/client/common/components/ReactionBar.tsx`
- Create: `resources/client/common/components/CommentThread.tsx`

- [ ] **Step 1: Create ReactionBar component**

```tsx
// resources/client/common/components/ReactionBar.tsx
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

const REACTION_EMOJI: Record<string, string> = {
  like: '\u{1F44D}',
  pray: '\u{1F64F}',
  amen: '\u{2728}',
  love: '\u{2764}\u{FE0F}',
  celebrate: '\u{1F389}',
};

interface ReactionBarProps {
  reactableId: number;
  reactableType: string;
  reactionCounts: Record<string, number>;
  currentUserReaction: string | null;
  queryKey: string[];
}

export function ReactionBar({
  reactableId,
  reactableType,
  reactionCounts,
  currentUserReaction,
  queryKey,
}: ReactionBarProps) {
  const [showPicker, setShowPicker] = useState(false);
  const queryClient = useQueryClient();

  const toggleMutation = useMutation({
    mutationFn: (type: string) =>
      apiClient.post('/reactions/toggle', {
        reactable_id: reactableId,
        reactable_type: reactableType,
        type,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
    },
  });

  const totalReactions = Object.values(reactionCounts).reduce((a, b) => a + b, 0);

  return (
    <div className="flex items-center gap-2 relative">
      {/* Reaction counts */}
      {totalReactions > 0 && (
        <div className="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
          {Object.entries(reactionCounts).map(([type, count]) => (
            <span key={type} className="flex items-center gap-0.5">
              <span>{REACTION_EMOJI[type]}</span>
              <span>{count}</span>
            </span>
          ))}
        </div>
      )}

      {/* Toggle button */}
      <button
        onClick={() => setShowPicker(!showPicker)}
        className={`text-sm px-2 py-1 rounded ${
          currentUserReaction
            ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400'
            : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
        }`}
      >
        {currentUserReaction ? REACTION_EMOJI[currentUserReaction] : 'React'}
      </button>

      {/* Picker */}
      {showPicker && (
        <div className="absolute bottom-full left-0 mb-1 flex gap-1 bg-white dark:bg-gray-800 rounded-full shadow-lg px-2 py-1 border dark:border-gray-700">
          {Object.entries(REACTION_EMOJI).map(([type, emoji]) => (
            <button
              key={type}
              onClick={() => {
                toggleMutation.mutate(type);
                setShowPicker(false);
              }}
              className={`text-xl hover:scale-125 transition-transform p-1 ${
                currentUserReaction === type ? 'bg-primary-100 dark:bg-primary-900/30 rounded-full' : ''
              }`}
              title={type}
            >
              {emoji}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 2: Create CommentThread component**

```tsx
// resources/client/common/components/CommentThread.tsx
import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

interface Comment {
  id: number;
  body: string;
  user: { id: number; name: string; avatar: string | null };
  replies: Comment[];
  replies_count: number;
  created_at: string;
}

interface CommentThreadProps {
  commentableId: number;
  commentableType: string;
}

export function CommentThread({ commentableId, commentableType }: CommentThreadProps) {
  const queryClient = useQueryClient();
  const queryKey = ['comments', commentableType, commentableId];

  const { data, isLoading } = useQuery({
    queryKey,
    queryFn: () =>
      apiClient
        .get('/comments', { params: { commentable_id: commentableId, commentable_type: commentableType } })
        .then((r) => r.data),
  });

  const [newComment, setNewComment] = useState('');

  const createMutation = useMutation({
    mutationFn: (body: string) =>
      apiClient.post('/comments', {
        commentable_id: commentableId,
        commentable_type: commentableType,
        body,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
      setNewComment('');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newComment.trim()) {
      createMutation.mutate(newComment);
    }
  };

  if (isLoading) return <div className="text-sm text-gray-400">Loading comments...</div>;

  const comments: Comment[] = data?.data ?? [];

  return (
    <div className="space-y-3">
      {/* Comment input */}
      <form onSubmit={handleSubmit} className="flex gap-2">
        <input
          type="text"
          value={newComment}
          onChange={(e) => setNewComment(e.target.value)}
          placeholder="Write a comment..."
          className="flex-1 px-3 py-2 text-sm border rounded-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        />
        <button
          type="submit"
          disabled={createMutation.isPending || !newComment.trim()}
          className="px-4 py-2 text-sm bg-primary-600 text-white rounded-full hover:bg-primary-700 disabled:opacity-50"
        >
          Post
        </button>
      </form>

      {/* Comments list */}
      {comments.map((comment) => (
        <CommentItem key={comment.id} comment={comment} />
      ))}
    </div>
  );
}

function CommentItem({ comment }: { comment: Comment }) {
  return (
    <div className="flex gap-2">
      <div className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex-shrink-0 flex items-center justify-center text-xs font-bold">
        {comment.user.name[0]}
      </div>
      <div className="flex-1">
        <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
          <p className="text-sm font-medium text-gray-900 dark:text-white">{comment.user.name}</p>
          <p className="text-sm text-gray-700 dark:text-gray-300">{comment.body}</p>
        </div>
        <div className="flex gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
          <span>{new Date(comment.created_at).toLocaleDateString()}</span>
          {comment.replies_count > 0 && <span>{comment.replies_count} replies</span>}
        </div>

        {/* Nested replies */}
        {comment.replies?.length > 0 && (
          <div className="mt-2 ml-4 space-y-2">
            {comment.replies.map((reply) => (
              <CommentItem key={reply.id} comment={reply} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/client/common/components/
git commit -m "feat: add shared ReactionBar and CommentThread React components"
```

---

## Task 10: Timeline Frontend — PostCard, PostComposer, PostFeed, NewsfeedPage

**Files:**
- Create: `resources/client/plugins/timeline/queries.ts`
- Create: `resources/client/plugins/timeline/components/PostCard.tsx`
- Create: `resources/client/plugins/timeline/components/PostComposer.tsx`
- Create: `resources/client/plugins/timeline/components/PostFeed.tsx`
- Create: `resources/client/plugins/timeline/pages/NewsfeedPage.tsx`
- Modify: `resources/client/app-router.tsx`

- [ ] **Step 1: Create Timeline query hooks**

```typescript
// resources/client/plugins/timeline/queries.ts
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

export const POST_FEED_KEY = ['posts', 'feed'];

export function usePostFeed() {
  return useInfiniteQuery({
    queryKey: POST_FEED_KEY,
    queryFn: ({ pageParam = 1 }) =>
      apiClient.get('/posts', { params: { feed: 1, page: pageParam } }).then((r) => r.data),
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    initialPageParam: 1,
  });
}

export function useCreatePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: { content: string; type?: string; visibility?: string }) =>
      apiClient.post('/posts', data).then((r) => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: POST_FEED_KEY });
    },
  });
}

export function useDeletePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (postId: number) => apiClient.delete(`/posts/${postId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: POST_FEED_KEY });
    },
  });
}
```

- [ ] **Step 2: Create PostCard component**

```tsx
// resources/client/plugins/timeline/components/PostCard.tsx
import { useState } from 'react';
import { ReactionBar } from '@app/common/components/ReactionBar';
import { CommentThread } from '@app/common/components/CommentThread';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useDeletePost, POST_FEED_KEY } from '../queries';

interface PostCardProps {
  post: {
    id: number;
    content: string;
    type: string;
    is_pinned: boolean;
    user: { id: number; name: string; avatar: string | null };
    media: Array<{ id: number; file_path: string; type: string }>;
    reaction_counts: Record<string, number>;
    current_user_reaction: string | null;
    comments_count: number;
    created_at: string;
  };
}

export function PostCard({ post }: PostCardProps) {
  const [showComments, setShowComments] = useState(false);
  const currentUser = useBootstrapStore((s) => s.user);
  const deletePost = useDeletePost();

  const isOwner = currentUser?.id === post.user.id;

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
      {/* Header */}
      <div className="flex items-center justify-between p-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-sm font-bold text-primary-700 dark:text-primary-400">
            {post.user.name[0]}
          </div>
          <div>
            <p className="font-medium text-gray-900 dark:text-white">{post.user.name}</p>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              {new Date(post.created_at).toLocaleDateString()}
              {post.is_pinned && <span className="ml-2 text-primary-600">Pinned</span>}
              {post.type === 'announcement' && (
                <span className="ml-2 px-1.5 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs">
                  Announcement
                </span>
              )}
            </p>
          </div>
        </div>

        {isOwner && (
          <button
            onClick={() => {
              if (confirm('Delete this post?')) deletePost.mutate(post.id);
            }}
            className="text-gray-400 hover:text-red-500 text-sm"
          >
            Delete
          </button>
        )}
      </div>

      {/* Content */}
      <div className="px-4 pb-3">
        <p className="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{post.content}</p>
      </div>

      {/* Media */}
      {post.media.length > 0 && (
        <div className="px-4 pb-3">
          <div className={`grid gap-1 ${post.media.length > 1 ? 'grid-cols-2' : 'grid-cols-1'}`}>
            {post.media.map((m) => (
              <img key={m.id} src={m.file_path} alt="" className="rounded-lg w-full object-cover max-h-96" />
            ))}
          </div>
        </div>
      )}

      {/* Reactions + Comment toggle */}
      <div className="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <ReactionBar
          reactableId={post.id}
          reactableType="post"
          reactionCounts={post.reaction_counts}
          currentUserReaction={post.current_user_reaction}
          queryKey={POST_FEED_KEY}
        />

        <button
          onClick={() => setShowComments(!showComments)}
          className="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
        >
          {post.comments_count} comments
        </button>
      </div>

      {/* Comments */}
      {showComments && (
        <div className="px-4 pb-4 border-t border-gray-100 dark:border-gray-700 pt-3">
          <CommentThread commentableId={post.id} commentableType="post" />
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 3: Create PostComposer component**

```tsx
// resources/client/plugins/timeline/components/PostComposer.tsx
import { useState } from 'react';
import { useCreatePost } from '../queries';

export function PostComposer() {
  const [content, setContent] = useState('');
  const createPost = useCreatePost();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!content.trim()) return;

    createPost.mutate(
      { content, type: 'text', visibility: 'public' },
      { onSuccess: () => setContent('') }
    );
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Share something with your community..."
        className="w-full border-0 resize-none focus:ring-0 text-gray-900 dark:text-white dark:bg-gray-800 placeholder-gray-400"
        rows={3}
      />
      <div className="flex justify-end mt-2">
        <button
          type="submit"
          disabled={createPost.isPending || !content.trim()}
          className="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700 disabled:opacity-50"
        >
          {createPost.isPending ? 'Posting...' : 'Post'}
        </button>
      </div>
    </form>
  );
}
```

- [ ] **Step 4: Create PostFeed component**

```tsx
// resources/client/plugins/timeline/components/PostFeed.tsx
import { usePostFeed } from '../queries';
import { PostCard } from './PostCard';
import { useEffect, useRef } from 'react';

export function PostFeed() {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } = usePostFeed();
  const loadMoreRef = useRef<HTMLDivElement>(null);

  // Infinite scroll observer
  useEffect(() => {
    if (!loadMoreRef.current || !hasNextPage) return;

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage();
        }
      },
      { threshold: 0.1 }
    );

    observer.observe(loadMoreRef.current);
    return () => observer.disconnect();
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 animate-pulse">
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-4" />
            <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2" />
            <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
          </div>
        ))}
      </div>
    );
  }

  const posts = data?.pages.flatMap((page) => page.data) ?? [];

  if (posts.length === 0) {
    return (
      <div className="text-center py-12 text-gray-500 dark:text-gray-400">
        No posts yet. Be the first to share something!
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {posts.map((post: any) => (
        <PostCard key={post.id} post={post} />
      ))}

      <div ref={loadMoreRef} className="py-4 text-center">
        {isFetchingNextPage && <span className="text-sm text-gray-400">Loading more...</span>}
      </div>
    </div>
  );
}
```

- [ ] **Step 5: Create NewsfeedPage**

```tsx
// resources/client/plugins/timeline/pages/NewsfeedPage.tsx
import { PostComposer } from '../components/PostComposer';
import { PostFeed } from '../components/PostFeed';

export function NewsfeedPage() {
  return (
    <div className="max-w-2xl mx-auto py-6 px-4 space-y-4">
      <PostComposer />
      <PostFeed />
    </div>
  );
}
```

- [ ] **Step 6: Update app-router.tsx to add timeline route**

Read the existing `resources/client/app-router.tsx` and add the newsfeed route. Add this lazy import and route:

```tsx
const NewsfeedPage = lazy(() =>
  import('./plugins/timeline/pages/NewsfeedPage').then((m) => ({ default: m.NewsfeedPage }))
);
```

Add inside the `<Routes>` inside `<RequireAuth>` but OUTSIDE the admin permission guard:

```tsx
<Route element={<RequireAuth />}>
  {/* Member-accessible routes */}
  <Route path="/feed" element={<NewsfeedPage />} />

  {/* Admin routes (existing) */}
  <Route element={<RequirePermission permission="admin.access" />}>
    ...
  </Route>
</Route>
```

- [ ] **Step 7: Commit**

```bash
git add resources/client/plugins/timeline/ resources/client/app-router.tsx
git commit -m "feat: add Timeline React frontend (feed, composer, post card, infinite scroll)"
```

---

## Task 11: Add Newsfeed Link to Admin Sidebar + Settings Registration

**Files:**
- Modify: `resources/client/admin/AdminLayout.tsx`
- Modify: `common/foundation/src/Core/BootstrapDataService.php` (add morph map info)
- Modify: `resources/client/common/core/bootstrap-data.ts` (add types)

- [ ] **Step 1: Add Feed link to sidebar**

In `resources/client/admin/AdminLayout.tsx`, add to the `sidebarItems` array:

```tsx
{ label: 'Feed', path: '/feed', icon: 'Newspaper', permission: 'posts.view' },
```

Place it at position 0 (first item) so it appears at the top.

- [ ] **Step 2: Update BootstrapData TypeScript types**

In `resources/client/common/core/bootstrap-data.ts`, the `BootstrapData` interface already has all needed fields. No changes needed.

- [ ] **Step 3: Commit**

```bash
git add resources/client/admin/AdminLayout.tsx
git commit -m "feat: add Feed link to admin sidebar navigation"
```

---

## Summary

After completing all 11 tasks, you will have:

| Component | Status |
|-----------|--------|
| Polymorphic reactions (like, pray, amen, love, celebrate) | Foundation — shared |
| Polymorphic nested comments (threaded, with replies) | Foundation — shared |
| Reaction toggle API (create/switch/remove) | 1 endpoint |
| Comment CRUD API (list, create, update, delete) | 4 endpoints |
| Timeline plugin directory structure | `app/Plugins/Timeline/` |
| Post model (text, photo, video, announcement) | With reactions + comments traits |
| PostMedia model (images, videos) | Linked to posts |
| BeMusic service pattern (Loader, Crupdate, Paginate, Delete) | 4 service classes |
| PostPolicy (own/any permission pattern) | With super admin bypass |
| Post CRUD API + feed endpoint | 6 endpoints |
| Plugin-aware route loading | Via PluginManager check |
| 15 new permissions (posts, comments, reactions) | Seeded to roles |
| PostFactory for tests | With announcement/pinned/scheduled states |
| Morph map enforcement | post, comment types |
| React: ReactionBar (shared) | Emoji picker + counts |
| React: CommentThread (shared) | Nested, threaded |
| React: PostCard, PostComposer, PostFeed | Timeline components |
| React: NewsfeedPage with infinite scroll | Member-accessible at /feed |
| Tests: 15+ tests (reactions, comments, CRUD, feed, policy) | Covering all features |

**Plugin pattern established:** All future plugins follow the same structure:
1. Models in `app/Plugins/{Name}/Models/`
2. Services: Loader, Crupdate, Paginate, Delete
3. Controller using services
4. Policy extending BasePolicy with own/any pattern
5. Plugin routes loaded conditionally via PluginManager
6. Permission seeder with role assignments
7. React components in `resources/client/plugins/{name}/`
8. Shared reactions + comments via traits

**Next plan:** Plan 3 (Groups) builds on this pattern, adding group-scoped posts and membership.

---

*Plan created: 2026-03-28 | Estimated effort: Week 4-5 (11 tasks, ~3-4 days)*
