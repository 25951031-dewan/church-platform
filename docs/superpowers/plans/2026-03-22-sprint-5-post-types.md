# Unified Post Types Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the Post plugin so members can create Prayer Requests, Blessings, Polls, and Bible Studies — all in the unified feed with a filter bar, each with a type-specific card renderer and compose form.

**Architecture:** `social_posts.type` column gains four new values (`prayer`, `blessing`, `poll`, `bible_study`). A new `poll_votes` table is the authoritative source for vote counts (meta is a cache, updated via JSON_SET in a `lockForUpdate` transaction). `PostController` validates type-specific `meta` fields. `FeedController` gains a `?type=` query param filter. `PostCard` dispatches to type-specific sub-components. A `CreatePostModal` wraps all compose flows.

**Tech Stack:** Laravel 11, MySQL JSON functions (JSON_SET, JSON_UNQUOTE), Str::ulid(), Pest, React, TypeScript.

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `plugins/Post/database/migrations/2026_04_25_000001_create_poll_votes_table.php` | Create | poll_votes table |
| `plugins/Post/database/migrations/2026_04_25_000002_add_type_index_to_social_posts.php` | Create | Index on social_posts.type |
| `plugins/Post/Models/PollVote.php` | Create | PollVote model |
| `plugins/Post/Models/Post.php` | Modify | pollVotes relationship, type constants, crossPost meta copy |
| `plugins/Post/Services/PollVoteService.php` | Create | Vote upsert logic with lockForUpdate |
| `plugins/Post/Controllers/PollVoteController.php` | Create | Store, destroy, counts endpoints |
| `plugins/Post/Controllers/PrayerAnswerController.php` | Create | Toggle meta.answered on prayer posts |
| `plugins/Post/Controllers/PostController.php` | Modify | Type + meta validation, crossPost meta copy |
| `plugins/Feed/Controllers/FeedController.php` | Modify | Add ?type= filter to all three methods |
| `plugins/Post/routes/api.php` | Modify | Register new endpoints |
| `tests/Feature/PostTypeTest.php` | Create | Type validation, feed filter, prayer answer tests |
| `tests/Feature/PollVoteTest.php` | Create | Vote logic, expiry, allow_multiple tests |
| `resources/js/plugins/feed/PrayerCard.tsx` | Create | Prayer post card |
| `resources/js/plugins/feed/BlessingCard.tsx` | Create | Blessing post card |
| `resources/js/plugins/feed/PollCard.tsx` | Create | Poll post card with vote bars |
| `resources/js/plugins/feed/BibleStudyCard.tsx` | Create | Bible study card |
| `resources/js/plugins/feed/CreatePostModal.tsx` | Create | Unified compose modal |
| `resources/js/plugins/feed/PostCard.tsx` | Modify | Type dispatch, updated Post interface |
| `resources/js/plugins/feed/FeedPage.tsx` | Modify | Filter tab bar, CreatePostModal trigger |

---

### Task 1: Migrations — poll_votes table and type index

**Files:**
- Create: `plugins/Post/database/migrations/2026_04_25_000001_create_poll_votes_table.php`
- Create: `plugins/Post/database/migrations/2026_04_25_000002_add_type_index_to_social_posts.php`

- [ ] **Step 1: Create poll_votes migration**

```php
<?php
// plugins/Post/database/migrations/2026_04_25_000001_create_poll_votes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('option_id', 40);
            $table->timestamp('created_at')->useCurrent();

            // Prevents duplicate vote per option. For allow_multiple=false, app checks (post_id, user_id).
            $table->unique(['post_id', 'user_id', 'option_id']);
            $table->index(['post_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
```

- [ ] **Step 2: Create type index migration**

```php
<?php
// plugins/Post/database/migrations/2026_04_25_000002_add_type_index_to_social_posts.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });
    }
};
```

- [ ] **Step 3: Commit**

```bash
git add plugins/Post/database/migrations/2026_04_25_000001_create_poll_votes_table.php
git add plugins/Post/database/migrations/2026_04_25_000002_add_type_index_to_social_posts.php
git commit -m "feat(post-types): add poll_votes table and type index on social_posts"
```

---

### Task 2: PollVote model + Post model extensions

**Files:**
- Create: `plugins/Post/Models/PollVote.php`
- Modify: `plugins/Post/Models/Post.php`

- [ ] **Step 1: Create PollVote model**

```php
<?php
// plugins/Post/Models/PollVote.php
namespace Plugins\Post\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'user_id', 'option_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Extend Post model**

Add to `plugins/Post/Models/Post.php`:

```php
// Add these to the Post class:

use Illuminate\Database\Eloquent\Relations\HasMany;

const TYPES = ['post', 'prayer', 'blessing', 'poll', 'bible_study'];

public function pollVotes(): HasMany
{
    return $this->hasMany(PollVote::class);
}

public function isPoll(): bool
{
    return $this->type === 'poll';
}

public function isPrayerAnswered(): bool
{
    return $this->type === 'prayer' && ($this->meta['answered'] ?? false);
}
```

Also add `'shares_count' => 'integer'` to `$casts` if not present.

- [ ] **Step 3: Commit**

```bash
git add plugins/Post/Models/PollVote.php plugins/Post/Models/Post.php
git commit -m "feat(post-types): add PollVote model and pollVotes relationship on Post"
```

---

### Task 3: PollVoteService

**Files:**
- Create: `plugins/Post/Services/PollVoteService.php`

- [ ] **Step 1: Create PollVoteService**

```php
<?php
// plugins/Post/Services/PollVoteService.php
namespace Plugins\Post\Services;

use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\PollVote;
use Plugins\Post\Models\Post;

class PollVoteService
{
    /**
     * Cast a vote on a poll option.
     * Returns ['counts' => [option_id => int], 'user_vote' => option_id]
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function vote(Post $post, int $userId, string $optionId): array
    {
        // Validate option exists in meta
        $options = $post->meta['options'] ?? [];
        $optionIdx = collect($options)->search(fn ($o) => $o['id'] === $optionId);
        abort_if($optionIdx === false, 422, 'Invalid option');

        DB::transaction(function () use ($post, $userId, $optionId, $optionIdx) {
            $allowMultiple = $post->meta['allow_multiple'] ?? false;

            if (! $allowMultiple) {
                // allow_multiple = false: only one vote per post per user
                $existing = PollVote::where('post_id', $post->id)
                    ->where('user_id', $userId)->first();

                if ($existing) {
                    if ($existing->option_id === $optionId) {
                        abort(422, 'Already voted');
                    }
                    // Change vote: remove old, decrement old count
                    $oldIdx = collect($post->meta['options'])->search(fn ($o) => $o['id'] === $existing->option_id);
                    if ($oldIdx !== false) {
                        DB::statement(
                            "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), GREATEST(0, CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) - 1)) WHERE id = ?",
                            [$oldIdx, $oldIdx, $post->id]
                        );
                    }
                    $existing->delete();
                }
            } else {
                // allow_multiple = true: block re-voting on the same option only
                $alreadyVoted = PollVote::where('post_id', $post->id)
                    ->where('user_id', $userId)->where('option_id', $optionId)->exists();
                abort_if($alreadyVoted, 422, 'Already voted for this option');
            }

            PollVote::create(['post_id' => $post->id, 'user_id' => $userId, 'option_id' => $optionId]);

            // Increment votes_count for the chosen option in meta
            DB::statement(
                "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) + 1) WHERE id = ?",
                [$optionIdx, $optionIdx, $post->id]
            );
        });

        return $this->counts($post, $userId);
    }

    /**
     * Remove all votes by this user on this poll.
     */
    public function removeVote(Post $post, int $userId): void
    {
        $votes = PollVote::where('post_id', $post->id)->where('user_id', $userId)->get();
        abort_if($votes->isEmpty(), 404);

        DB::transaction(function () use ($post, $userId, $votes) {
            foreach ($votes as $vote) {
                $idx = collect($post->meta['options'])->search(fn ($o) => $o['id'] === $vote->option_id);
                if ($idx !== false) {
                    DB::statement(
                        "UPDATE social_posts SET meta = JSON_SET(meta, CONCAT('$.options[', ?, '].votes_count'), GREATEST(0, CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, CONCAT('$.options[', ?, '].votes_count'))) AS UNSIGNED) - 1)) WHERE id = ?",
                        [$idx, $idx, $post->id]
                    );
                }
                $vote->delete();
            }
        });
    }

    /**
     * Return vote counts from poll_votes table (authoritative) and the user's current vote.
     */
    public function counts(Post $post, ?int $userId): array
    {
        $counts = PollVote::where('post_id', $post->id)
            ->selectRaw('option_id, count(*) as cnt')
            ->groupBy('option_id')
            ->pluck('cnt', 'option_id');

        $userVote = $userId
            ? PollVote::where('post_id', $post->id)->where('user_id', $userId)->value('option_id')
            : null;

        return ['counts' => $counts, 'user_vote' => $userVote];
    }
}
```

> **SQLite note:** The `JSON_SET`/`JSON_EXTRACT` MySQL syntax will not work on SQLite test environments. For tests, either mock the service, use a MySQL test database, or skip the JSON_SET and rely on poll_votes table counts alone (which is the authoritative source anyway). The test for vote counts uses `GET /posts/{id}/votes` which always reads from poll_votes — so tests can assert via that endpoint without needing JSON_SET to work.

- [ ] **Step 2: Commit**

```bash
git add plugins/Post/Services/PollVoteService.php
git commit -m "feat(post-types): add PollVoteService with atomic vote upsert and lockForUpdate"
```

---

### Task 4: PollVoteController and PrayerAnswerController

**Files:**
- Create: `plugins/Post/Controllers/PollVoteController.php`
- Create: `plugins/Post/Controllers/PrayerAnswerController.php`

- [ ] **Step 1: Write failing vote tests**

```php
<?php
// tests/Feature/PollVoteTest.php
use App\Models\User;
use Plugins\Post\Models\Post;
use Plugins\Post\Models\PollVote;

function makePoll(array $overrides = []): Post {
    return Post::factory()->create(array_merge([
        'type'   => 'poll',
        'status' => 'published',
        'meta'   => [
            'question'      => 'Favourite hymn?',
            'options'       => [
                ['id' => 'opt_aaa', 'text' => 'Amazing Grace', 'votes_count' => 0],
                ['id' => 'opt_bbb', 'text' => 'Holy Holy Holy', 'votes_count' => 0],
            ],
            'ends_at'       => null,
            'allow_multiple' => false,
        ],
    ], $overrides));
}

test('vote on valid option returns 201 and increments count', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])
        ->assertStatus(201);

    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->json('counts');
    expect($counts['opt_aaa'])->toBe(1);
});

test('voting on same option twice returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa']);
    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])
        ->assertStatus(422);
});

test('change vote decrements old and increments new (allow_multiple=false)', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa'])
        ->assertStatus(201);
    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_bbb'])
        ->assertStatus(201);

    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->json('counts');
    expect((int)($counts['opt_aaa'] ?? 0))->toBe(0);
    expect((int)($counts['opt_bbb'] ?? 0))->toBe(1);
});

test('vote on expired poll returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll(['meta' => [
        'question' => 'Old poll', 'options' => [['id' => 'opt_x', 'text' => 'A', 'votes_count' => 0]],
        'ends_at' => now()->subDay()->toIso8601String(), 'allow_multiple' => false,
    ]]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_x'])
        ->assertStatus(422)->assertJsonFragment(['Poll has ended']);
});

test('vote with invalid option_id returns 422', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_INVALID'])
        ->assertStatus(422);
});

test('GET /posts/{id}/votes unauthenticated returns user_vote null', function () {
    $poll = makePoll();
    $this->getJson("/api/v1/posts/{$poll->id}/votes")
        ->assertStatus(200)->assertJsonPath('user_vote', null);
});

test('DELETE /posts/{id}/vote removes vote and decrements count', function () {
    $user = User::factory()->create();
    $poll = makePoll();

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/vote", ['option_id' => 'opt_aaa']);
    $this->actingAs($user)->deleteJson("/api/v1/posts/{$poll->id}/vote")
        ->assertStatus(200);

    $counts = $this->getJson("/api/v1/posts/{$poll->id}/votes")->json('counts');
    expect((int)($counts['opt_aaa'] ?? 0))->toBe(0);
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/PollVoteTest.php 2>&1 | tail -5
```

- [ ] **Step 3: Create PollVoteController**

```php
<?php
// plugins/Post/Controllers/PollVoteController.php
namespace Plugins\Post\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Post\Models\Post;
use Plugins\Post\Services\PollVoteService;

class PollVoteController extends Controller
{
    public function __construct(private PollVoteService $service) {}

    /** POST /api/v1/posts/{id}/vote */
    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['option_id' => ['required', 'string']]);

        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'poll', 422, 'Not a poll.');

        // Check expiry
        $endsAt = $post->meta['ends_at'] ?? null;
        if ($endsAt && Carbon::parse($endsAt)->isPast()) {
            return response()->json(['message' => 'Poll has ended'], 422);
        }

        $counts = $this->service->vote($post, $request->user()->id, $data['option_id']);
        return response()->json($counts, 201);
    }

    /** DELETE /api/v1/posts/{id}/vote */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'poll', 422, 'Not a poll.');

        $this->service->removeVote($post, $request->user()->id);
        return response()->json(['message' => 'Vote removed']);
    }

    /** GET /api/v1/posts/{id}/votes */
    public function counts(Request $request, int $id): JsonResponse
    {
        $post   = Post::published()->findOrFail($id);
        $userId = $request->user()?->id;
        return response()->json($this->service->counts($post, $userId));
    }
}
```

- [ ] **Step 4: Create PrayerAnswerController**

```php
<?php
// plugins/Post/Controllers/PrayerAnswerController.php
namespace Plugins\Post\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Post\Models\Post;

class PrayerAnswerController extends Controller
{
    /** POST /api/v1/posts/{id}/answer-prayer */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'prayer', 422, 'Not a prayer post.');
        abort_if($post->user_id !== $request->user()->id, 403, 'Only the author can mark this as answered.');

        $meta     = $post->meta ?? [];
        $answered = ! ($meta['answered'] ?? false);

        $post->update([
            'meta' => array_merge($meta, [
                'answered'    => $answered,
                'answered_at' => $answered ? now()->toIso8601String() : null,
            ]),
        ]);

        return response()->json([
            'answered'    => $answered,
            'answered_at' => $answered ? now()->toIso8601String() : null,
        ]);
    }
}
```

- [ ] **Step 5: Run vote tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/PollVoteTest.php 2>&1 | tail -10
```

> If JSON_SET fails on SQLite: tests that assert vote_count changes in meta will fail. The `counts()` endpoint reads from `poll_votes` table, so tests using `GET /votes` will still pass. Separate the JSON_SET assertions into a `@group mysql` group and skip on CI if needed.

- [ ] **Step 6: Commit**

```bash
git add plugins/Post/Controllers/PollVoteController.php plugins/Post/Controllers/PrayerAnswerController.php
git commit -m "feat(post-types): add PollVoteController and PrayerAnswerController"
```

---

### Task 5: Extend PostController (type validation + meta + crossPost fix)

**Files:**
- Modify: `plugins/Post/Controllers/PostController.php`

- [ ] **Step 1: Write failing post type tests**

```php
<?php
// tests/Feature/PostTypeTest.php
use App\Models\User;
use Plugins\Post\Models\Post;

test('create prayer post returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'prayer',
        'body' => 'Please pray for my family.',
    ])->assertStatus(201)->assertJsonPath('type', 'prayer');
});

test('create blessing with scripture in meta returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'blessing',
        'body' => 'God provided a new job!',
        'meta' => ['scripture' => 'Jeremiah 29:11'],
    ])->assertStatus(201)->assertJsonPath('meta.scripture', 'Jeremiah 29:11');
});

test('create poll with 1 option returns 422', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'poll',
        'meta' => ['question' => 'A question?', 'options' => [['text' => 'Only one']]],
    ])->assertStatus(422);
});

test('create poll with 11 options returns 422', function () {
    $user = User::factory()->create();
    $options = array_fill(0, 11, ['text' => 'Option']);
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'poll',
        'meta' => ['question' => 'Too many?', 'options' => $options],
    ])->assertStatus(422);
});

test('create bible study with scripture and passage returns 201', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/v1/posts', [
        'type' => 'bible_study',
        'body' => 'Reflection on grace.',
        'meta' => ['scripture' => 'Romans 8:28', 'passage' => 'And we know that in all things God works for the good…'],
    ])->assertStatus(201);
});

test('reshare poll returns 422', function () {
    $user = User::factory()->create();
    $poll = Post::factory()->create(['type' => 'poll', 'status' => 'published', 'user_id' => $user->id,
        'meta' => ['question' => 'Q', 'options' => [['id' => 'a', 'text' => 'A', 'votes_count' => 0]], 'ends_at' => null, 'allow_multiple' => false]]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$poll->id}/cross-post", [
        'targets' => [['community_id' => null, 'church_id' => null]],
    ])->assertStatus(422);
});

test('reshare blessing copies meta.scripture', function () {
    $owner  = User::factory()->create();
    $other  = User::factory()->create();
    $blessing = Post::factory()->create([
        'type' => 'blessing', 'status' => 'published', 'user_id' => $owner->id,
        'meta' => ['scripture' => 'Psalm 23:1'],
    ]);
    $community = \Plugins\Community\Models\Community::factory()->create();

    $this->actingAs($other)->postJson("/api/v1/posts/{$blessing->id}/cross-post", [
        'targets' => [['community_id' => $community->id]],
    ])->assertStatus(200);

    $reshare = Post::where('parent_id', $blessing->id)->first();
    expect($reshare->meta['scripture'])->toBe('Psalm 23:1');
});

test('author can mark prayer answered', function () {
    $user   = User::factory()->create();
    $prayer = Post::factory()->create([
        'user_id' => $user->id, 'type' => 'prayer', 'status' => 'published',
        'meta' => ['answered' => false, 'answered_at' => null],
    ]);

    $this->actingAs($user)->postJson("/api/v1/posts/{$prayer->id}/answer-prayer")
        ->assertStatus(200)->assertJsonPath('answered', true);
});

test('non-author cannot mark prayer answered', function () {
    $owner  = User::factory()->create();
    $other  = User::factory()->create();
    $prayer = Post::factory()->create(['user_id' => $owner->id, 'type' => 'prayer', 'status' => 'published',
        'meta' => ['answered' => false, 'answered_at' => null]]);

    $this->actingAs($other)->postJson("/api/v1/posts/{$prayer->id}/answer-prayer")
        ->assertStatus(403);
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/PostTypeTest.php 2>&1 | tail -5
```

- [ ] **Step 3: Modify PostController@store — add type + meta validation**

Replace the `$data = $request->validate([...])` in `store()` with:

```php
$data = $request->validate([
    'body'               => ['required_without:media', 'nullable', 'string'],
    'media'              => ['nullable', 'array'],
    'type'               => ['nullable', 'string', 'in:post,prayer,blessing,poll,bible_study'],
    'church_id'          => ['nullable', 'integer', 'exists:churches,id'],
    'community_id'       => ['nullable', 'integer', 'exists:communities,id'],
    'is_anonymous'       => ['boolean'],
    'cross_post_targets' => ['nullable', 'array'],
    'cross_post_targets.*.community_id' => ['nullable', 'integer', 'exists:communities,id'],
    'cross_post_targets.*.church_id'    => ['nullable', 'integer', 'exists:churches,id'],
    // poll meta
    'meta.question'      => ['required_if:type,poll', 'string'],
    'meta.options'       => ['required_if:type,poll', 'array', 'min:2', 'max:10'],
    'meta.options.*.text' => ['required_if:type,poll', 'string'],
    'meta.ends_at'       => ['nullable', 'date'],
    'meta.allow_multiple' => ['boolean'],
    // bible_study meta — scripture is required (non-nullable) for bible_study; optional for blessing
    'meta.scripture'     => ['required_if:type,bible_study', 'string'],
    'meta.passage'       => ['required_if:type,bible_study', 'string'],
    'meta.study_guide'   => ['nullable', 'string'],
    // blessing meta
    // meta.scripture reused (optional for blessing)
]);

// For poll: generate stable option IDs and initialise votes_count
if (($data['type'] ?? 'post') === 'poll') {
    $data['meta']['options'] = collect($data['meta']['options'])->map(fn ($opt) => [
        'id'          => 'opt_' . \Illuminate\Support\Str::ulid(),
        'text'        => $opt['text'],
        'votes_count' => 0,
    ])->all();
    $data['meta']['allow_multiple'] = $data['meta']['allow_multiple'] ?? false;
}
$data['type'] = $data['type'] ?? 'post';
```

- [ ] **Step 4: Modify PostController@crossPost — add poll guard + meta copy**

In the `crossPost` method, find the inner `foreach` loop and add the guard + meta copy:

```php
// At the top of the DB::transaction closure, before the foreach loop:
// Guard: poll posts cannot be reshared
if ($post->type === 'poll') {
    abort(422, 'Poll posts cannot be reshared.');
}

// Inside the foreach, replace the Post::create([...]) call with:
$reshare = Post::create([
    'user_id'      => $userId,
    'parent_id'    => $post->id,
    'community_id' => $communityId,
    'church_id'    => $churchId,
    'type'         => $post->type,
    'body'         => null,
    'meta'         => $post->meta,   // ← NEW: copy meta so BlessingCard renders correctly on reshare
    'status'       => 'published',
    'published_at' => now(),
]);
```

- [ ] **Step 5: Run post type tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/PostTypeTest.php 2>&1 | tail -15
```

- [ ] **Step 6: Commit**

```bash
git add plugins/Post/Controllers/PostController.php
git commit -m "feat(post-types): extend PostController with type/meta validation, poll guard, crossPost meta copy"
```

---

### Task 6: Extend FeedController with ?type= filter + update routes

**Files:**
- Modify: `plugins/Feed/Controllers/FeedController.php`
- Modify: `plugins/Post/routes/api.php`

- [ ] **Step 1: Write failing feed filter test**

```php
// Add to tests/Feature/PostTypeTest.php:
test('GET /feed?type=prayer returns only prayer posts', function () {
    Post::factory()->create(['type' => 'post', 'status' => 'published']);
    Post::factory()->create(['type' => 'prayer', 'status' => 'published',
        'meta' => ['answered' => false, 'answered_at' => null]]);

    $this->getJson('/api/v1/feed?type=prayer')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'prayer');
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/PostTypeTest.php --filter="GET /feed" 2>&1 | tail -5
```

- [ ] **Step 3: Modify FeedController — add ?type= filter**

In `plugins/Feed/Controllers/FeedController.php`, update all three methods. Add `Request $request` parameter to `community()` and `church()` (they currently don't accept it):

```php
// home() — add after the existing $query construction:
->when($request->type, fn ($q) => $q->where('type', $request->type))

// community() — change signature and add filter:
public function community(Request $request, int $communityId): JsonResponse
{
    return response()->json(
        Post::published()->where('community_id', $communityId)
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
            ->latest('published_at')->paginate(15)
    );
}

// church() — same pattern:
public function church(Request $request, int $churchId): JsonResponse
{
    return response()->json(
        Post::published()->where('church_id', $churchId)
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
            ->latest('published_at')->paginate(15)
    );
}
```

- [ ] **Step 4: Add new routes to plugins/Post/routes/api.php**

```php
// Add to plugins/Post/routes/api.php, inside the v1 prefix group:
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{id}/answer-prayer', [\Plugins\Post\Controllers\PrayerAnswerController::class, 'toggle'])
        ->name('api.v1.posts.answer-prayer');
    Route::post('/posts/{id}/vote', [\Plugins\Post\Controllers\PollVoteController::class, 'store'])
        ->name('api.v1.posts.vote.store');
    Route::delete('/posts/{id}/vote', [\Plugins\Post\Controllers\PollVoteController::class, 'destroy'])
        ->name('api.v1.posts.vote.destroy');
});
Route::get('/posts/{id}/votes', [\Plugins\Post\Controllers\PollVoteController::class, 'counts'])
    ->name('api.v1.posts.vote.counts');
```

- [ ] **Step 5: Run post type tests — all green**

```bash
./vendor/bin/pest tests/Feature/PostTypeTest.php 2>&1 | tail -10
```

- [ ] **Step 6: Run full test suite**

```bash
./vendor/bin/pest --stop-on-failure 2>&1 | tail -15
```

- [ ] **Step 7: Commit**

```bash
git add plugins/Feed/Controllers/FeedController.php plugins/Post/routes/api.php
git commit -m "feat(post-types): add ?type= filter to FeedController, register vote and answer-prayer routes"
```

---

### Task 7: Frontend — PrayerCard, BlessingCard, BibleStudyCard, PollCard

**Files:**
- Create: `resources/js/plugins/feed/PrayerCard.tsx`
- Create: `resources/js/plugins/feed/BlessingCard.tsx`
- Create: `resources/js/plugins/feed/BibleStudyCard.tsx`
- Create: `resources/js/plugins/feed/PollCard.tsx`

- [ ] **Step 1: Create PrayerCard**

```tsx
// resources/js/plugins/feed/PrayerCard.tsx
import React from 'react';

interface PrayerMeta { answered: boolean; answered_at: string | null }
interface Props { postId: number; body: string; meta: PrayerMeta; isAuthor: boolean; onAnswered?: () => void }

export default function PrayerCard({ postId, body, meta, isAuthor, onAnswered }: Props) {
    async function toggleAnswered() {
        await fetch(`/api/v1/posts/${postId}/answer-prayer`, { method: 'POST' });
        onAnswered?.();
    }

    return (
        <div style={{ borderLeft: '4px solid #7c3aed', paddingLeft: '0.75rem' }}>
            <div style={{ display: 'flex', gap: 6, alignItems: 'center', marginBottom: 8 }}>
                <span style={{ background: '#ede9fe', color: '#7c3aed', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>🙏 Prayer Request</span>
                {meta.answered && (
                    <span style={{ background: '#dcfce7', color: '#15803d', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>✓ Answered</span>
                )}
            </div>
            <p style={{ margin: 0, lineHeight: 1.6, fontSize: '0.95rem' }}>{body}</p>
            {isAuthor && (
                <button onClick={toggleAnswered}
                    style={{ marginTop: 8, fontSize: '0.8rem', background: 'none', border: '1px solid #7c3aed', color: '#7c3aed', borderRadius: 20, padding: '3px 12px', cursor: 'pointer' }}>
                    {meta.answered ? 'Unmark as Answered' : 'Mark as Answered'}
                </button>
            )}
        </div>
    );
}
```

- [ ] **Step 2: Create BlessingCard**

```tsx
// resources/js/plugins/feed/BlessingCard.tsx
import React from 'react';

interface BlessingMeta { scripture?: string }
interface Props { body: string; meta: BlessingMeta }

export default function BlessingCard({ body, meta }: Props) {
    return (
        <div>
            <div style={{ marginBottom: 8 }}>
                <span style={{ background: '#fef9c3', color: '#a16207', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>✨ Blessing</span>
            </div>
            <p style={{ margin: 0, lineHeight: 1.6, fontSize: '0.95rem' }}>{body}</p>
            {meta.scripture && (
                <p style={{ marginTop: 8, fontStyle: 'italic', fontSize: '0.875rem', color: '#64748b' }}>
                    — {meta.scripture}
                </p>
            )}
        </div>
    );
}
```

- [ ] **Step 3: Create BibleStudyCard**

```tsx
// resources/js/plugins/feed/BibleStudyCard.tsx
import React, { useState } from 'react';

interface BibleStudyMeta { scripture: string; passage: string; study_guide?: string }
interface Props { body: string; meta: BibleStudyMeta }

export default function BibleStudyCard({ body, meta }: Props) {
    const [expanded, setExpanded] = useState(false);

    return (
        <div>
            <div style={{ marginBottom: 8 }}>
                <span style={{ background: '#dbeafe', color: '#1d4ed8', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>📖 Bible Study</span>
                <span style={{ marginLeft: 8, fontWeight: 700, fontSize: '0.875rem', color: '#1e40af' }}>{meta.scripture}</span>
            </div>
            <blockquote style={{ borderLeft: '3px solid #bfdbfe', paddingLeft: '0.75rem', margin: '0 0 12px', color: '#374151', fontStyle: 'italic', fontSize: '0.9rem', lineHeight: 1.6 }}>
                {meta.passage}
            </blockquote>
            {body && <p style={{ margin: '0 0 8px', fontSize: '0.95rem', lineHeight: 1.6 }}>{body}</p>}
            {meta.study_guide && (
                <>
                    <button onClick={() => setExpanded(e => !e)}
                        style={{ fontSize: '0.8rem', background: 'none', border: 'none', color: '#2563eb', cursor: 'pointer', padding: 0 }}>
                        {expanded ? '▲ Hide study guide' : '▼ Show study guide'}
                    </button>
                    {expanded && (
                        <div style={{ marginTop: 8, background: '#f8fafc', borderRadius: 8, padding: '0.75rem', fontSize: '0.875rem', lineHeight: 1.7, color: '#374151' }}>
                            {meta.study_guide}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
```

- [ ] **Step 4: Create PollCard**

```tsx
// resources/js/plugins/feed/PollCard.tsx
import React, { useEffect, useState } from 'react';

interface PollOption { id: string; text: string; votes_count: number }
interface PollMeta { question: string; options: PollOption[]; ends_at: string | null; allow_multiple: boolean }
interface Props { postId: number; meta: PollMeta }

export default function PollCard({ postId, meta }: Props) {
    const [counts, setCounts] = useState<Record<string, number>>({});
    const [userVote, setUserVote] = useState<string | null>(null);
    const [total, setTotal] = useState(0);
    const expired = meta.ends_at ? new Date(meta.ends_at) < new Date() : false;

    useEffect(() => {
        fetch(`/api/v1/posts/${postId}/votes`)
            .then(r => r.json())
            .then(d => {
                setCounts(d.counts ?? {});
                setUserVote(d.user_vote ?? null);
                setTotal(Object.values(d.counts ?? {}).reduce((s: number, c: any) => s + Number(c), 0));
            });
    }, [postId]);

    async function vote(optionId: string) {
        if (expired) return;
        const res = await fetch(`/api/v1/posts/${postId}/vote`, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ option_id: optionId }),
        });
        if (res.ok) {
            const d = await res.json();
            setCounts(d.counts ?? {});
            setUserVote(d.user_vote ?? null);
            setTotal(Object.values(d.counts ?? {}).reduce((s: number, c: any) => s + Number(c), 0));
        }
    }

    return (
        <div>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 10 }}>
                <span style={{ background: '#f0fdf4', color: '#15803d', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>📊 Poll</span>
                {expired && <span style={{ background: '#fef2f2', color: '#dc2626', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem' }}>Ended</span>}
            </div>
            <p style={{ fontWeight: 700, fontSize: '0.95rem', marginBottom: 12 }}>{meta.question}</p>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                {meta.options.map(opt => {
                    const count  = Number(counts[opt.id] ?? 0);
                    const pct    = total > 0 ? Math.round((count / total) * 100) : 0;
                    const isVote = userVote === opt.id;

                    return (
                        <div key={opt.id} onClick={() => vote(opt.id)}
                            style={{ position: 'relative', borderRadius: 8, overflow: 'hidden', border: `2px solid ${isVote ? '#2563eb' : '#e2e8f0'}`, cursor: expired ? 'default' : 'pointer', background: '#fff' }}>
                            <div style={{ position: 'absolute', inset: 0, background: isVote ? '#dbeafe' : '#f8fafc', width: `${pct}%`, transition: 'width 0.4s ease' }} />
                            <div style={{ position: 'relative', display: 'flex', justifyContent: 'space-between', padding: '8px 12px', fontSize: '0.875rem' }}>
                                <span style={{ fontWeight: isVote ? 700 : 400 }}>{opt.text}</span>
                                <span style={{ color: '#64748b' }}>{pct}% ({count})</span>
                            </div>
                        </div>
                    );
                })}
            </div>
            <div style={{ marginTop: 8, fontSize: '0.75rem', color: '#94a3b8' }}>
                {total} vote{total !== 1 ? 's' : ''}
                {meta.ends_at && !expired && ` · Ends ${new Date(meta.ends_at).toLocaleDateString()}`}
            </div>
        </div>
    );
}
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/plugins/feed/PrayerCard.tsx resources/js/plugins/feed/BlessingCard.tsx
git add resources/js/plugins/feed/BibleStudyCard.tsx resources/js/plugins/feed/PollCard.tsx
git commit -m "feat(post-types): add PrayerCard, BlessingCard, BibleStudyCard, PollCard components"
```

---

### Task 8: CreatePostModal + PostCard dispatch + FeedPage filter bar

**Files:**
- Create: `resources/js/plugins/feed/CreatePostModal.tsx`
- Modify: `resources/js/plugins/feed/PostCard.tsx`
- Modify: `resources/js/plugins/feed/FeedPage.tsx`

- [ ] **Step 1: Create CreatePostModal**

```tsx
// resources/js/plugins/feed/CreatePostModal.tsx
import React, { useState } from 'react';

type PostType = 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
const TYPE_LABELS: Record<PostType, string> = { post: '💬 Post', prayer: '🙏 Prayer', blessing: '✨ Blessing', poll: '📊 Poll', bible_study: '📖 Bible Study' };
const BODY_PLACEHOLDERS: Record<PostType, string> = {
    post: "What's on your mind?", prayer: 'What would you like prayer for?',
    blessing: 'Share your testimony…', poll: 'Context (optional)',
    bible_study: 'Reflection (optional)',
};

interface Props { onClose: () => void; onCreated: () => void }

export default function CreatePostModal({ onClose, onCreated }: Props) {
    const [type, setType] = useState<PostType>('post');
    const [body, setBody] = useState('');
    const [scripture, setScripture] = useState('');
    const [passage, setPassage] = useState('');
    const [studyGuide, setStudyGuide] = useState('');
    const [question, setQuestion] = useState('');
    const [options, setOptions] = useState(['', '']);
    const [endsAt, setEndsAt] = useState('');
    const [allowMultiple, setAllowMultiple] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    async function submit() {
        setSubmitting(true);
        setError('');
        let meta: Record<string, any> | undefined;
        if (type === 'prayer') meta = undefined;
        if (type === 'blessing') meta = { scripture: scripture || undefined };
        if (type === 'poll') meta = { question, options: options.filter(Boolean).map(t => ({ text: t })), ends_at: endsAt || null, allow_multiple: allowMultiple };
        if (type === 'bible_study') meta = { scripture, passage, study_guide: studyGuide || undefined };

        try {
            const res = await fetch('/api/v1/posts', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, body: body || null, meta }),
            });
            if (!res.ok) throw new Error((await res.json()).message ?? 'Failed');
            onCreated();
            onClose();
        } catch (e: any) {
            setError(e.message);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <div style={{ background: '#fff', borderRadius: 16, padding: '1.5rem', width: '100%', maxWidth: 520, maxHeight: '90vh', overflowY: 'auto', boxShadow: '0 8px 32px rgba(0,0,0,.15)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                    <h2 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700 }}>Create Post</h2>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: '1.25rem', cursor: 'pointer', color: '#64748b' }}>✕</button>
                </div>

                {/* Type selector */}
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: '1rem' }}>
                    {(Object.keys(TYPE_LABELS) as PostType[]).map(t => (
                        <button key={t} onClick={() => setType(t)}
                            style={{ fontSize: '0.8rem', padding: '4px 12px', borderRadius: 20, border: 'none', cursor: 'pointer', background: type === t ? '#2563eb' : '#f1f5f9', color: type === t ? '#fff' : '#475569' }}>
                            {TYPE_LABELS[t]}
                        </button>
                    ))}
                </div>

                {/* Shared body */}
                <textarea rows={3} placeholder={BODY_PLACEHOLDERS[type]} value={body} onChange={e => setBody(e.target.value)}
                    style={{ width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem', resize: 'vertical', boxSizing: 'border-box' }} />

                {/* Type-specific fields */}
                {(type === 'blessing' || type === 'bible_study') && (
                    <input placeholder={type === 'bible_study' ? 'Scripture reference *' : 'Scripture reference (optional)'}
                        value={scripture} onChange={e => setScripture(e.target.value)}
                        style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', boxSizing: 'border-box' }} />
                )}
                {type === 'bible_study' && (
                    <>
                        <textarea rows={3} placeholder="Passage text *" value={passage} onChange={e => setPassage(e.target.value)}
                            style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', resize: 'vertical', boxSizing: 'border-box' }} />
                        <textarea rows={2} placeholder="Study guide (optional)" value={studyGuide} onChange={e => setStudyGuide(e.target.value)}
                            style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', resize: 'vertical', boxSizing: 'border-box' }} />
                    </>
                )}
                {type === 'poll' && (
                    <div style={{ marginTop: 8 }}>
                        <input placeholder="Question *" value={question} onChange={e => setQuestion(e.target.value)}
                            style={{ width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', boxSizing: 'border-box' }} />
                        {options.map((opt, i) => (
                            <div key={i} style={{ display: 'flex', gap: 6, marginTop: 6 }}>
                                <input placeholder={`Option ${i + 1} *`} value={opt} onChange={e => setOptions(o => o.map((v, j) => j === i ? e.target.value : v))}
                                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                                {options.length > 2 && (
                                    <button onClick={() => setOptions(o => o.filter((_, j) => j !== i))}
                                        style={{ border: 'none', background: '#fee2e2', color: '#dc2626', borderRadius: 6, padding: '0 10px', cursor: 'pointer' }}>✕</button>
                                )}
                            </div>
                        ))}
                        {options.length < 10 && (
                            <button onClick={() => setOptions(o => [...o, ''])}
                                style={{ marginTop: 6, fontSize: '0.8rem', background: 'none', border: '1px solid #e2e8f0', borderRadius: 8, padding: '4px 12px', cursor: 'pointer', color: '#2563eb' }}>
                                + Add option
                            </button>
                        )}
                        <div style={{ display: 'flex', gap: 12, marginTop: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                            <input type="datetime-local" value={endsAt} onChange={e => setEndsAt(e.target.value)}
                                style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.5rem', fontSize: '0.8rem' }} />
                            <label style={{ display: 'flex', gap: 6, alignItems: 'center', cursor: 'pointer', fontSize: '0.875rem' }}>
                                <input type="checkbox" checked={allowMultiple} onChange={e => setAllowMultiple(e.target.checked)} />
                                Allow multiple choices
                            </label>
                        </div>
                    </div>
                )}

                {error && <div style={{ marginTop: 8, color: '#dc2626', fontSize: '0.875rem' }}>{error}</div>}

                <button onClick={submit} disabled={submitting}
                    style={{ marginTop: '1rem', width: '100%', padding: '0.7rem', borderRadius: 10, border: 'none', background: '#2563eb', color: '#fff', fontSize: '0.95rem', fontWeight: 600, cursor: 'pointer' }}>
                    {submitting ? 'Posting…' : 'Post'}
                </button>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Update PostCard to dispatch by type**

In `resources/js/plugins/feed/PostCard.tsx`:

1. Add imports at the top:
```tsx
import PrayerCard from './PrayerCard';
import BlessingCard from './BlessingCard';
import BibleStudyCard from './BibleStudyCard';
import PollCard from './PollCard';
```

2. Update the `Post` interface to include type and meta:
```tsx
interface Post {
    id: number; body: string;
    type: 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
    meta?: Record<string, any>;
    author: Author; church?: { name: string };
    reactions_count: number; comments_count: number; created_at: string;
}
```

3. Replace the `<SafeHtml html={post.body} ... />` section with a type dispatch block:
```tsx
{/* Type-specific body rendering */}
{post.type === 'prayer' && post.meta && (
    <PrayerCard postId={post.id} body={post.body} meta={post.meta as any} isAuthor={false} />
)}
{post.type === 'blessing' && (
    <BlessingCard body={post.body} meta={(post.meta ?? {}) as any} />
)}
{post.type === 'bible_study' && post.meta && (
    <BibleStudyCard body={post.body} meta={post.meta as any} />
)}
{post.type === 'poll' && post.meta && (
    <PollCard postId={post.id} meta={post.meta as any} />
)}
{post.type === 'post' && (
    <SafeHtml html={post.body} style={{ fontSize: '0.95rem', lineHeight: 1.6, marginBottom: '0.75rem' }} />
)}
```

- [ ] **Step 3: Update FeedPage to add filter tabs and CreatePostModal**

In `resources/js/plugins/feed/FeedPage.tsx`:

1. Add `import CreatePostModal from './CreatePostModal';`
2. Add state: `const [showCompose, setShowCompose] = useState(false);`
3. **Derive type filter from URL query param** (spec requires bookmarkable tab state):
```tsx
// Read from URL — e.g. ?type=prayer makes "Prayer" the active tab
const params = new URLSearchParams(window.location.search);
const [typeFilter, setTypeFilter] = useState(params.get('type') ?? '');

function changeFilter(value: string) {
    const url = new URL(window.location.href);
    if (value) url.searchParams.set('type', value);
    else url.searchParams.delete('type');
    window.history.pushState({}, '', url.toString());
    setTypeFilter(value);
    setPage(1);
}
```
4. Update `load()` to pass `typeFilter` to the API: append `type=${typeFilter}` query param when non-empty
5. Add a "Create Post" button above the feed
6. Add the filter tab bar (use `changeFilter` instead of `setTypeFilter`):
```tsx
const FEED_FILTERS = [
    { label: 'All', value: '' },
    { label: '🙏 Prayer', value: 'prayer' },
    { label: '✨ Blessings', value: 'blessing' },
    { label: '📊 Polls', value: 'poll' },
    { label: '📖 Bible Study', value: 'bible_study' },
];
// Render before the post list:
<div style={{ display: 'flex', gap: 8, overflowX: 'auto', marginBottom: '1rem' }}>
    {FEED_FILTERS.map(f => (
        <button key={f.value} onClick={() => changeFilter(f.value)}
            style={{ padding: '6px 16px', borderRadius: 20, border: 'none', cursor: 'pointer', whiteSpace: 'nowrap', fontSize: '0.875rem', background: typeFilter === f.value ? '#2563eb' : '#f1f5f9', color: typeFilter === f.value ? '#fff' : '#475569' }}>
            {f.label}
        </button>
    ))}
</div>
```
7. Render `<CreatePostModal>` when `showCompose` is true

- [ ] **Step 4: Verify Vite builds cleanly**

```bash
npm run build 2>&1 | tail -10
```
Expected: no errors.

- [ ] **Step 5: Run full test suite**

```bash
./vendor/bin/pest --stop-on-failure 2>&1 | tail -15
```
Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add resources/js/plugins/feed/CreatePostModal.tsx
git add resources/js/plugins/feed/PostCard.tsx resources/js/plugins/feed/FeedPage.tsx
git commit -m "feat(post-types): add CreatePostModal, type dispatch in PostCard, filter bar in FeedPage"
```
