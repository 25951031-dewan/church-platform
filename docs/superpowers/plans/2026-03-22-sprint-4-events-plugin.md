# Church Events Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full-featured church events plugin with RSVP, recurring events (RRULE), online/hybrid support, event discussion threads using `social_posts`, and 24-hour reminder notifications.

**Architecture:** A new `plugins/Event/` plugin owns its own `events` + `event_attendees` tables. Discussion threads reuse `social_posts` with `type='event_post'` — a MySQL generated column on `social_posts` extracts `meta->>'$.event_id'` as an indexed column for fast queries. Recurring events are expanded on-the-fly by `RecurrenceExpander`; the first RSVP/comment/edit materialises a concrete row. All RSVP counter mutations use `DB::transaction` + `lockForUpdate()`.

**Tech Stack:** Laravel 11, `recurr/recurr` RRULE library, Eloquent SoftDeletes, Pest, React, TypeScript.

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `plugins/Event/database/migrations/2026_04_20_000001_create_events_table.php` | Create | events table |
| `plugins/Event/database/migrations/2026_04_20_000002_create_event_attendees_table.php` | Create | event_attendees table |
| `plugins/Event/database/migrations/2026_04_20_000003_add_event_id_generated_column_to_social_posts.php` | Create | Generated column + index on social_posts |
| `plugins/Event/Models/Event.php` | Create | SoftDeletes, is_multi_day, recurring helpers |
| `plugins/Event/Models/EventAttendee.php` | Create | RSVP model |
| `plugins/Event/Services/RecurrenceExpander.php` | Create | RRULE expansion + materialisation |
| `plugins/Event/Controllers/EventController.php` | Create | CRUD + listing with filters |
| `plugins/Event/Controllers/EventRsvpController.php` | Create | RSVP with lockForUpdate |
| `plugins/Event/Controllers/EventPostController.php` | Create | Event discussion thread |
| `plugins/Event/Jobs/SendEventRemindersJob.php` | Create | 24-hour reminder dispatch |
| `plugins/Event/Notifications/EventReminderNotification.php` | Create | Database notification |
| `plugins/Event/Policies/EventPolicy.php` | Create | Authorization for view/create/update/delete/post |
| `plugins/Event/routes/api.php` | Create | All event routes |
| `plugins/Event/EventServiceProvider.php` | Create | Boot routes, migrations, policies |
| `plugins/Event/plugin.json` | Create | Plugin manifest |
| `bootstrap/providers.php` | Modify | Register EventServiceProvider |
| `tests/Feature/EventTest.php` | Create | CRUD, auth, listing tests |
| `tests/Feature/EventRsvpTest.php` | Create | RSVP counter tests |
| `tests/Feature/EventReminderTest.php` | Create | Reminder job tests |
| `resources/js/plugins/events/EventCard.tsx` | Create | Compact event card |
| `resources/js/plugins/events/EventCalendar.tsx` | Create | CSS Grid month/week calendar |
| `resources/js/plugins/events/EventsPage.tsx` | Create | Calendar+list toggle, filter bar |
| `resources/js/plugins/events/EventDetailPage.tsx` | Create | Full event detail with RSVP + thread |
| `resources/js/plugins/events/CreateEventForm.tsx` | Create | 4-step event creation form |

---

### Task 1: Migrations — events, event_attendees, social_posts generated column

**Files:**
- Create: `plugins/Event/database/migrations/2026_04_20_000001_create_events_table.php`
- Create: `plugins/Event/database/migrations/2026_04_20_000002_create_event_attendees_table.php`
- Create: `plugins/Event/database/migrations/2026_04_20_000003_add_event_id_generated_column_to_social_posts.php`

- [ ] **Step 1: Create events migration**

```php
<?php
// plugins/Event/database/migrations/2026_04_20_000001_create_events_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('location', 300)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('meeting_url')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('events')->nullOnDelete();
            $table->enum('category', ['worship', 'youth', 'outreach', 'study', 'fellowship', 'other'])->default('other');
            $table->unsignedInteger('max_attendees')->nullable();
            $table->unsignedInteger('going_count')->default(0);
            $table->unsignedInteger('maybe_count')->default(0);
            $table->enum('status', ['published', 'draft', 'cancelled'])->default('published');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['church_id', 'start_at']);
            $table->index(['community_id', 'start_at']);
            $table->index(['start_at', 'status']);
            $table->index(['reminder_sent_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
```

- [ ] **Step 2: Create event_attendees migration**

```php
<?php
// plugins/Event/database/migrations/2026_04_20_000002_create_event_attendees_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['going', 'maybe', 'not_going']);
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};
```

- [ ] **Step 3: Create generated column migration**

> This adds an indexed generated column to `social_posts` so event thread queries avoid a slow JSON scan. Uses `DB::statement` since Laravel Blueprint doesn't support stored generated columns.
> **MySQL 5.7.6+ / MySQL 8 required.** On cPanel shared hosting confirm MySQL version before deploying.
> For SQLite (test env) the generated column syntax differs — wrap in a driver check.

```php
<?php
// plugins/Event/database/migrations/2026_04_20_000003_add_event_id_generated_column_to_social_posts.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE social_posts
                ADD COLUMN event_id BIGINT UNSIGNED NULL
                    GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.event_id')) AS UNSIGNED)) STORED
            ");
            DB::statement('ALTER TABLE social_posts ADD INDEX idx_social_posts_event_id (event_id)');
        }
        // SQLite: add a plain nullable column and populate manually (test-only fallback)
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('social_posts', function ($table) {
                $table->unsignedBigInteger('event_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE social_posts DROP INDEX idx_social_posts_event_id');
            DB::statement('ALTER TABLE social_posts DROP COLUMN event_id');
        }
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('social_posts', function ($table) {
                $table->dropColumn('event_id');
            });
        }
    }
};
```

> **SQLite limitation:** In tests, `event_id` is a plain column, not generated. Tests that create event posts must manually set `event_id` on the `social_posts` row. See `EventPostController@store` — it sets the plain column when on SQLite.

- [ ] **Step 4: Create plugin directory structure**

```bash
mkdir -p plugins/Event/{Models,Services,Controllers,Jobs,Notifications,Policies,database/migrations,routes}
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Event/database/
git commit -m "feat(events): add events, event_attendees migrations and social_posts event_id generated column"
```

---

### Task 2: Event and EventAttendee Models

**Files:**
- Create: `plugins/Event/Models/Event.php`
- Create: `plugins/Event/Models/EventAttendee.php`

- [ ] **Step 1: Write failing model test**

```php
<?php
// tests/Feature/EventTest.php (partial — model only)
use Plugins\Event\Models\Event;
use App\Models\User;

test('is_multi_day returns true when start and end are different dates', function () {
    $event = new Event([
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-02 17:00:00',
    ]);
    expect($event->is_multi_day)->toBeTrue();
});

test('is_multi_day returns false for same-day events', function () {
    $event = new Event([
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-01 17:00:00',
    ]);
    expect($event->is_multi_day)->toBeFalse();
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/EventTest.php --filter="is_multi_day" 2>&1 | tail -5
```
Expected: FAIL (class not found)

- [ ] **Step 3: Create Event model**

```php
<?php
// plugins/Event/Models/Event.php
namespace Plugins\Event\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plugins\Community\Models\Community;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'church_id', 'community_id', 'created_by', 'title', 'description', 'cover_image',
        'start_at', 'end_at', 'location', 'latitude', 'longitude', 'is_online', 'meeting_url',
        'is_recurring', 'recurrence_rule', 'recurrence_parent_id', 'category',
        'max_attendees', 'going_count', 'maybe_count', 'status', 'reminder_sent_at',
    ];

    protected $casts = [
        'start_at'         => 'datetime',
        'end_at'           => 'datetime',
        'reminder_sent_at' => 'datetime',
        'is_online'        => 'boolean',
        'is_recurring'     => 'boolean',
    ];

    /**
     * Virtual attribute — not stored. True when start and end are on different calendar dates.
     */
    public function getIsMultiDayAttribute(): bool
    {
        return Carbon::parse($this->start_at)->toDateString()
            !== Carbon::parse($this->end_at)->toDateString();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_at', '<', now());
    }
}
```

- [ ] **Step 4: Create EventAttendee model**

```php
<?php
// plugins/Event/Models/EventAttendee.php
namespace Plugins\Event\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    protected $fillable = ['event_id', 'user_id', 'status'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Run test — expect PASS**

```bash
./vendor/bin/pest tests/Feature/EventTest.php --filter="is_multi_day" 2>&1 | tail -5
```

- [ ] **Step 6: Commit**

```bash
git add plugins/Event/Models/
git commit -m "feat(events): add Event and EventAttendee models"
```

---

### Task 3: EventPolicy

**Files:**
- Create: `plugins/Event/Policies/EventPolicy.php`

- [ ] **Step 1: Create EventPolicy**

```php
<?php
// plugins/Event/Policies/EventPolicy.php
namespace Plugins\Event\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;

class EventPolicy
{
    public function view(?User $user, Event $event): bool
    {
        return $event->status === 'published';
    }

    /** Any authenticated user can create a platform-wide event. Scoped events require admin/mod role. */
    public function create(User $user, ?int $communityId = null, ?int $churchId = null): bool
    {
        if ($communityId) {
            return DB::table('community_members')
                ->where('user_id', $user->id)
                ->where('community_id', $communityId)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'approved')
                ->exists();
        }
        if ($churchId) {
            return DB::table('church_members')
                ->where('user_id', $user->id)
                ->where('church_id', $churchId)
                ->where('role', 'admin')
                ->exists();
        }
        return true; // platform-wide
    }

    public function update(User $user, Event $event): bool
    {
        if ($event->created_by === $user->id) return true;
        if ($user->is_admin) return true;

        // Community admin/mod for events scoped to a community
        if ($event->community_id) {
            $isCommunityAdmin = DB::table('community_members')
                ->where('user_id', $user->id)
                ->where('community_id', $event->community_id)
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'approved')
                ->exists();
            if ($isCommunityAdmin) return true;
        }

        // Church admin for events scoped to a church
        if ($event->church_id) {
            return DB::table('church_members')
                ->where('user_id', $user->id)
                ->where('church_id', $event->church_id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    public function delete(User $user, Event $event): bool
    {
        return $event->created_by === $user->id || $user->is_admin;
    }

    /** Any authenticated user can post to an event thread. */
    public function post(User $user, Event $event): bool
    {
        return true;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/Event/Policies/
git commit -m "feat(events): add EventPolicy"
```

---

### Task 4: RecurrenceExpander service

**Files:**
- Create: `plugins/Event/Services/RecurrenceExpander.php`

> Requires `composer require rlanvin/php-rrule` (or `recurr/recurr`). Use `rlanvin/php-rrule` — it's lighter, well-maintained, and has no external dependencies.

- [ ] **Step 1: Install the RRULE library**

```bash
composer require rlanvin/php-rrule
```

- [ ] **Step 2: Create RecurrenceExpander**

```php
<?php
// plugins/Event/Services/RecurrenceExpander.php
namespace Plugins\Event\Services;

use Carbon\Carbon;
use Plugins\Event\Models\Event;
use RRule\RRule;

class RecurrenceExpander
{
    /**
     * Expand a recurring event's RRULE and return virtual occurrences within the date window.
     * Occurrences are NOT persisted — they are stdClass-like arrays with start_at/end_at overrides.
     *
     * @return array<array{start_at: Carbon, end_at: Carbon, parent: Event}>
     */
    public function expand(Event $parent, Carbon $from, Carbon $to): array
    {
        if (! $parent->is_recurring || ! $parent->recurrence_rule) {
            return [];
        }

        $rrule = new RRule($parent->recurrence_rule, $parent->start_at->toDateTimeString());
        $duration = $parent->start_at->diffInSeconds($parent->end_at);
        $occurrences = [];

        foreach ($rrule as $occurrence) {
            $start = Carbon::instance($occurrence);
            if ($start->gt($to)) break;
            if ($start->lt($from)) continue;

            $end = $start->copy()->addSeconds($duration);
            $occurrences[] = ['start_at' => $start, 'end_at' => $end, 'parent' => $parent];
        }

        return $occurrences;
    }

    /**
     * Materialise a virtual occurrence into a concrete events row.
     * Idempotent: returns existing row if already materialised.
     */
    public function materialise(Event $parent, Carbon $occurrenceDate): Event
    {
        $duration = $parent->start_at->diffInSeconds($parent->end_at);

        $existing = Event::where('recurrence_parent_id', $parent->id)
            ->whereDate('start_at', $occurrenceDate->toDateString())
            ->first();

        if ($existing) return $existing;

        $endAt = $occurrenceDate->copy()->addSeconds($duration);

        return Event::create(array_merge(
            $parent->only(['church_id', 'community_id', 'created_by', 'title', 'description', 'cover_image',
                'location', 'latitude', 'longitude', 'is_online', 'meeting_url', 'category', 'max_attendees', 'status']),
            [
                'start_at'             => $occurrenceDate,
                'end_at'               => $endAt,
                'is_recurring'         => false,
                'recurrence_parent_id' => $parent->id,
            ]
        ));
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add plugins/Event/Services/
git commit -m "feat(events): add RecurrenceExpander with RRULE expansion and materialisation"
```

---

### Task 5: EventController (CRUD + listing)

**Files:**
- Create: `plugins/Event/Controllers/EventController.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/EventTest.php (expand — add controller tests)
use App\Models\User;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

test('authenticated user can create a platform-wide event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/events', [
        'title'    => 'Sunday Worship',
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-01 11:00:00',
        'category' => 'worship',
    ])->assertStatus(201)->assertJsonFragment(['title' => 'Sunday Worship']);
});

test('GET /events/{id} hides meeting_url for unauthenticated users', function () {
    $event = Event::factory()->create(['meeting_url' => 'https://zoom.us/j/abc', 'status' => 'published']);

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertStatus(200)
        ->assertJsonPath('meeting_url', null);
});

test('GET /events/{id} shows meeting_url to going attendee', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['meeting_url' => 'https://zoom.us/j/abc', 'status' => 'published']);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);

    $this->actingAs($user)->getJson("/api/v1/events/{$event->id}")
        ->assertStatus(200)
        ->assertJsonPath('meeting_url', 'https://zoom.us/j/abc');
});

test('non-owner cannot update event', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($other)->patchJson("/api/v1/events/{$event->id}", ['title' => 'Changed'])
        ->assertStatus(403);
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/EventTest.php --filter="authenticated user can create" 2>&1 | tail -5
```

- [ ] **Step 3: Create EventController**

```php
<?php
// plugins/Event/Controllers/EventController.php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;
use Plugins\Event\Policies\EventPolicy;

class EventController extends Controller
{
    /** GET /api/v1/events */
    public function index(Request $request): JsonResponse
    {
        $query = Event::published()->with(['creator:id,name,avatar'])->withCount('attendees');

        if ($request->church_id)    $query->where('church_id', $request->church_id);
        if ($request->community_id) $query->where('community_id', $request->community_id);
        if ($request->category)     $query->where('category', $request->category);
        if ($request->from)         $query->where('start_at', '>=', $request->from);
        if ($request->to)           $query->where('start_at', '<=', $request->to);

        match ($request->scope) {
            'upcoming' => $query->upcoming()->orderBy('start_at'),
            'past'     => $query->past()->orderByDesc('start_at'),
            default    => $query->orderBy('start_at'),
        };

        return response()->json($query->paginate(15));
    }

    /** GET /api/v1/events/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::with(['creator:id,name,avatar'])->findOrFail($id);
        $data  = $event->toArray();

        // Redact meeting_url unless authenticated and has a 'going' RSVP
        $user = $request->user();
        $hasGoingRsvp = $user && EventAttendee::where('event_id', $id)
            ->where('user_id', $user->id)->where('status', 'going')->exists();

        if (! $hasGoingRsvp) {
            $data['meeting_url'] = null;
        }

        return response()->json($data);
    }

    /** POST /api/v1/events */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string'],
            'cover_image'      => ['nullable', 'url'],
            'start_at'         => ['required', 'date'],
            'end_at'           => ['required', 'date', 'after:start_at'],
            'location'         => ['nullable', 'string', 'max:300'],
            'latitude'         => ['nullable', 'numeric'],
            'longitude'        => ['nullable', 'numeric'],
            'is_online'        => ['boolean'],
            'meeting_url'      => ['nullable', 'url'],
            'is_recurring'     => ['boolean'],
            'recurrence_rule'  => ['nullable', 'string'],
            'category'         => ['nullable', 'in:worship,youth,outreach,study,fellowship,other'],
            'max_attendees'    => ['nullable', 'integer', 'min:1'],
            'church_id'        => ['nullable', 'integer', 'exists:churches,id'],
            'community_id'     => ['nullable', 'integer', 'exists:communities,id'],
        ]);

        $policy = new EventPolicy();
        abort_unless(
            $policy->create($request->user(), $data['community_id'] ?? null, $data['church_id'] ?? null),
            403
        );

        $event = Event::create(array_merge($data, [
            'created_by' => $request->user()->id,
            'status'     => 'published',
        ]));

        return response()->json($event, 201);
    }

    /** PATCH /api/v1/events/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $policy = new EventPolicy();
        abort_unless($policy->update($request->user(), $event), 403);

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:200'],
            'description'  => ['nullable', 'string'],
            'start_at'     => ['sometimes', 'date'],
            'end_at'       => ['sometimes', 'date'],
            'location'     => ['nullable', 'string', 'max:300'],
            'is_online'    => ['sometimes', 'boolean'],
            'meeting_url'  => ['nullable', 'url'],
            'category'     => ['sometimes', 'in:worship,youth,outreach,study,fellowship,other'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
            'status'       => ['sometimes', 'in:published,draft,cancelled'],
        ]);

        $event->update($data);
        return response()->json($event);
    }

    /** DELETE /api/v1/events/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $policy = new EventPolicy();
        abort_unless($policy->delete($request->user(), $event), 403);

        $event->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    /** GET /api/v1/events/{id}/attendees */
    public function attendees(int $id): JsonResponse
    {
        $attendees = \Plugins\Event\Models\EventAttendee::with('user:id,name,avatar')
            ->where('event_id', $id)->where('status', 'going')->paginate(20);
        return response()->json($attendees);
    }
}
```

> Also create `database/factories/EventFactory.php`:
```php
<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plugins\Event\Models\Event;
class EventFactory extends Factory {
    protected $model = Event::class;
    public function definition(): array {
        return [
            'created_by' => \App\Models\User::factory(),
            'title'      => $this->faker->sentence(3),
            'start_at'   => now()->addDays(7),
            'end_at'     => now()->addDays(7)->addHours(2),
            'category'   => 'worship',
            'status'     => 'published',
        ];
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/EventTest.php 2>&1 | tail -10
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Event/Controllers/EventController.php database/factories/EventFactory.php
git commit -m "feat(events): add EventController with CRUD, listing, meeting_url redaction"
```

---

### Task 6: EventRsvpController

**Files:**
- Create: `plugins/Event/Controllers/EventRsvpController.php`

- [ ] **Step 1: Write failing RSVP tests**

```php
<?php
// tests/Feature/EventRsvpTest.php
use App\Models\User;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

test('RSVP going increments going_count', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => null]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'going'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(1);
});

test('change from going to maybe decrements going_count and increments maybe_count atomically', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create();
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);
    $event->update(['going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'maybe'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
    expect($event->fresh()->maybe_count)->toBe(1);
});

test('RSVP going when event is full returns 422', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => 1, 'going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'going'])
        ->assertStatus(422)->assertJsonFragment(['Event is full']);
});

test('RSVP maybe when event is full is allowed', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => 1, 'going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'maybe'])
        ->assertStatus(200);
});

test('not_going RSVP does not change any counter', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['going_count' => 0, 'maybe_count' => 0]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'not_going'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
    expect($event->fresh()->maybe_count)->toBe(0);
});

test('remove RSVP decrements going_count', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['going_count' => 1]);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);

    $this->actingAs($user)->deleteJson("/api/v1/events/{$event->id}/rsvp")
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/EventRsvpTest.php 2>&1 | tail -5
```

- [ ] **Step 3: Create EventRsvpController**

```php
<?php
// plugins/Event/Controllers/EventRsvpController.php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

class EventRsvpController extends Controller
{
    /** POST /api/v1/events/{id}/rsvp */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:going,maybe,not_going'],
        ]);

        $result = DB::transaction(function () use ($id, $data, $request) {
            $event    = Event::lockForUpdate()->findOrFail($id);
            $newStatus = $data['status'];

            // Capacity check for 'going' only
            if ($newStatus === 'going' && $event->max_attendees !== null) {
                if ($event->going_count >= $event->max_attendees) {
                    abort(422, 'Event is full');
                }
            }

            $existing = EventAttendee::where('event_id', $id)
                ->where('user_id', $request->user()->id)->first();

            $oldStatus = $existing?->status;

            // Decrement old counter
            if ($oldStatus === 'going')       $event->decrement('going_count');
            elseif ($oldStatus === 'maybe')   $event->decrement('maybe_count');

            // Upsert RSVP row
            EventAttendee::updateOrCreate(
                ['event_id' => $id, 'user_id' => $request->user()->id],
                ['status' => $newStatus]
            );

            // Increment new counter (not_going has no counter column)
            if ($newStatus === 'going')       $event->increment('going_count');
            elseif ($newStatus === 'maybe')   $event->increment('maybe_count');

            return $event->fresh(['attendees']);
        });

        return response()->json(['status' => $data['status'], 'event' => $result]);
    }

    /** DELETE /api/v1/events/{id}/rsvp */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $attendee = EventAttendee::where('event_id', $id)
            ->where('user_id', $request->user()->id)->firstOrFail();

        DB::transaction(function () use ($id, $attendee) {
            $event = Event::lockForUpdate()->findOrFail($id);
            $attendee->delete();
            if ($attendee->status === 'going')      $event->decrement('going_count');
            elseif ($attendee->status === 'maybe')  $event->decrement('maybe_count');
        });

        return response()->json(['message' => 'RSVP removed.']);
    }
}
```

- [ ] **Step 4: Run RSVP tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/EventRsvpTest.php 2>&1 | tail -10
```

- [ ] **Step 5: Commit**

```bash
git add plugins/Event/Controllers/EventRsvpController.php
git commit -m "feat(events): add EventRsvpController with atomic counter updates and capacity check"
```

---

### Task 7: EventPostController (discussion thread)

**Files:**
- Create: `plugins/Event/Controllers/EventPostController.php`

- [ ] **Step 1: Create EventPostController**

```php
<?php
// plugins/Event/Controllers/EventPostController.php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;
use Plugins\Post\Models\Post;

class EventPostController extends Controller
{
    /** GET /api/v1/events/{id}/posts */
    public function index(int $id): JsonResponse
    {
        Event::findOrFail($id); // ensure event exists

        $posts = Post::where('type', 'event_post')
            ->where('event_id', $id)        // uses the generated/indexed column
            ->with(['author:id,name,avatar'])
            ->withCount(['comments', 'reactions'])
            ->latest('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    /** POST /api/v1/events/{id}/posts */
    public function store(Request $request, int $id): JsonResponse
    {
        Event::findOrFail($id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $post = Post::create([
            'user_id'      => $request->user()->id,
            'type'         => 'event_post',
            'body'         => $data['body'],
            'meta'         => ['event_id' => $id],
            'status'       => 'published',
            'published_at' => now(),
        ]);

        // SQLite (test env): manually set the plain event_id column since generated columns aren't supported
        if (DB::getDriverName() === 'sqlite') {
            DB::table('social_posts')->where('id', $post->id)->update(['event_id' => $id]);
        }

        return response()->json($post->load('author:id,name,avatar'), 201);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/Event/Controllers/EventPostController.php
git commit -m "feat(events): add EventPostController for event discussion thread"
```

---

### Task 8: SendEventRemindersJob + EventReminderNotification

**Files:**
- Create: `plugins/Event/Jobs/SendEventRemindersJob.php`
- Create: `plugins/Event/Notifications/EventReminderNotification.php`

- [ ] **Step 1: Write failing reminder tests**

```php
<?php
// tests/Feature/EventReminderTest.php
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Plugins\Event\Jobs\SendEventRemindersJob;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;
use Plugins\Event\Notifications\EventReminderNotification;

test('reminder job sends to going attendees only', function () {
    Notification::fake();
    $event = Event::factory()->create([
        'start_at'         => now()->addHours(24),
        'end_at'           => now()->addHours(26),
        'status'           => 'published',
        'reminder_sent_at' => null,
    ]);
    $going = User::factory()->create();
    $maybe = User::factory()->create();
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $going->id, 'status' => 'going']);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $maybe->id, 'status' => 'maybe']);

    (new SendEventRemindersJob())->handle();

    Notification::assertSentTo($going, EventReminderNotification::class);
    Notification::assertNotSentTo($maybe, EventReminderNotification::class);
    expect($event->fresh()->reminder_sent_at)->not->toBeNull();
});

test('reminder job does not re-send', function () {
    Notification::fake();
    $event = Event::factory()->create([
        'start_at'         => now()->addHours(24),
        'status'           => 'published',
        'reminder_sent_at' => now(), // already sent
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});

test('reminder job does not fire for cancelled events', function () {
    Notification::fake();
    Event::factory()->create([
        'start_at' => now()->addHours(24),
        'status'   => 'cancelled',
        'reminder_sent_at' => null,
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});
```

- [ ] **Step 2: Run to verify fail**

```bash
./vendor/bin/pest tests/Feature/EventReminderTest.php 2>&1 | tail -5
```

- [ ] **Step 3: Create EventReminderNotification**

```php
<?php
// plugins/Event/Notifications/EventReminderNotification.php
namespace Plugins\Event\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Plugins\Event\Models\Event;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        return [
            'event_id'   => $this->event->id,
            'event_title' => $this->event->title,
            'start_at'   => $this->event->start_at->toIso8601String(),
            'message'    => "Reminder: \"{$this->event->title}\" starts in 24 hours.",
        ];
    }
}
```

- [ ] **Step 4: Create SendEventRemindersJob**

```php
<?php
// plugins/Event/Jobs/SendEventRemindersJob.php
namespace Plugins\Event\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Plugins\Event\Models\Event;
use Plugins\Event\Notifications\EventReminderNotification;

class SendEventRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Event::published()
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_at', [now()->addHours(23), now()->addHours(25)])
            ->each(function (Event $event) {
                $event->attendees()
                    ->where('status', 'going')
                    ->with('user')
                    ->chunk(100, function ($attendees) use ($event) {
                        foreach ($attendees as $attendee) {
                            $attendee->user->notify(new EventReminderNotification($event));
                        }
                    });

                $event->update(['reminder_sent_at' => now()]);
            });
    }
}
```

- [ ] **Step 5: Run reminder tests — expect PASS**

```bash
./vendor/bin/pest tests/Feature/EventReminderTest.php 2>&1 | tail -10
```

- [ ] **Step 6: Register job in Laravel scheduler**

Laravel 11 uses `routes/console.php` for schedules (not `Kernel.php`). Add:

```php
// routes/console.php — add:
use Illuminate\Support\Facades\Schedule;
use Plugins\Event\Jobs\SendEventRemindersJob;

Schedule::job(new SendEventRemindersJob())->everyFifteenMinutes();
```

If `routes/console.php` does not exist, create it with the above content.

- [ ] **Step 7: Add missing reminder test (past event)**

```php
// Append to tests/Feature/EventReminderTest.php:
test('reminder job does not fire for past events', function () {
    Notification::fake();
    Event::factory()->create([
        'start_at'         => now()->subDay(), // already started
        'status'           => 'published',
        'reminder_sent_at' => null,
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});
```

- [ ] **Step 8: Commit**

```bash
git add plugins/Event/Jobs/ plugins/Event/Notifications/ routes/console.php
git commit -m "feat(events): add SendEventRemindersJob, EventReminderNotification, schedule every 15 minutes"
```

---

### Task 9: EventServiceProvider, plugin.json, routes, bootstrap registration

**Files:**
- Create: `plugins/Event/EventServiceProvider.php`
- Create: `plugins/Event/plugin.json`
- Create: `plugins/Event/routes/api.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Create routes**

```php
<?php
// plugins/Event/routes/api.php
use Illuminate\Support\Facades\Route;
use Plugins\Event\Controllers\EventController;
use Plugins\Event\Controllers\EventPostController;
use Plugins\Event\Controllers\EventRsvpController;

Route::prefix('v1')->name('api.v1.events.')->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('index');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('show');
    Route::get('/events/{id}/attendees', [EventController::class, 'attendees'])->name('attendees');
    Route::get('/events/{id}/posts', [EventPostController::class, 'index'])->name('posts.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/events', [EventController::class, 'store'])->name('store');
        Route::patch('/events/{id}', [EventController::class, 'update'])->name('update');
        Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('destroy');
        Route::post('/events/{id}/rsvp', [EventRsvpController::class, 'update'])->name('rsvp.update');
        Route::delete('/events/{id}/rsvp', [EventRsvpController::class, 'destroy'])->name('rsvp.destroy');
        Route::post('/events/{id}/posts', [EventPostController::class, 'store'])->name('posts.store');
    });
});
```

- [ ] **Step 2: Create EventServiceProvider**

```php
<?php
// plugins/Event/EventServiceProvider.php
namespace Plugins\Event;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
```

- [ ] **Step 3: Create plugin.json**

```json
{
  "name": "Event",
  "slug": "event",
  "version": "1.0.0",
  "description": "Church events with RSVP, recurring support, online/hybrid, and discussion threads.",
  "author": "Church Platform",
  "icon": "calendar",
  "category": "Feature",
  "requires": [],
  "settings_page": false,
  "can_disable": true,
  "can_remove": true,
  "enabled_by_default": true
}
```

- [ ] **Step 4: Register in bootstrap/providers.php**

```php
// Add to bootstrap/providers.php:
Plugins\Event\EventServiceProvider::class,
```

- [ ] **Step 5: Run full test suite**

```bash
./vendor/bin/pest --stop-on-failure 2>&1 | tail -15
```
Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add plugins/Event/EventServiceProvider.php plugins/Event/plugin.json plugins/Event/routes/ bootstrap/providers.php
git commit -m "feat(events): register EventServiceProvider, routes, and plugin manifest"
```

---

### Task 10: Frontend — EventCard, EventCalendar, EventsPage, EventDetailPage, CreateEventForm

**Files:**
- Create: `resources/js/plugins/events/EventCard.tsx`
- Create: `resources/js/plugins/events/EventCalendar.tsx`
- Create: `resources/js/plugins/events/EventsPage.tsx`
- Create: `resources/js/plugins/events/EventDetailPage.tsx`
- Create: `resources/js/plugins/events/CreateEventForm.tsx`

- [ ] **Step 1: Create EventCard**

```tsx
// resources/js/plugins/events/EventCard.tsx
import React from 'react';

interface EventItem {
    id: number; title: string; start_at: string; end_at: string; is_multi_day: boolean;
    location?: string; is_online: boolean; category: string;
    going_count: number; cover_image?: string;
    user_rsvp?: 'going' | 'maybe' | 'not_going' | null;
}

interface Props { event: EventItem; onRsvp?: (id: number, status: string) => void }

export default function EventCard({ event, onRsvp }: Props) {
    const start = new Date(event.start_at);
    const dateStr = start.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    const timeStr = start.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

    return (
        <div style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)', marginBottom: '1rem' }}>
            {event.cover_image && (
                <img src={event.cover_image} alt={event.title}
                    style={{ width: '100%', height: 140, objectFit: 'cover' }} />
            )}
            <div style={{ padding: '0.75rem 1rem' }}>
                <div style={{ fontSize: '0.75rem', color: '#64748b', marginBottom: 4 }}>
                    {dateStr} · {timeStr}
                    {event.is_online && <span style={{ marginLeft: 8, background: '#dbeafe', color: '#1d4ed8', borderRadius: 4, padding: '1px 6px' }}>Online</span>}
                </div>
                <div style={{ fontWeight: 700, fontSize: '1rem', marginBottom: 4 }}>{event.title}</div>
                {event.location && <div style={{ fontSize: '0.8rem', color: '#94a3b8' }}>📍 {event.location}</div>}
                <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                    {(['going', 'maybe'] as const).map(s => (
                        <button key={s} onClick={() => onRsvp?.(event.id, s)}
                            style={{
                                fontSize: '0.8rem', borderRadius: 20, padding: '4px 14px', cursor: 'pointer', border: 'none',
                                background: event.user_rsvp === s ? '#2563eb' : '#f1f5f9',
                                color: event.user_rsvp === s ? '#fff' : '#475569',
                            }}>
                            {s === 'going' ? `✓ Going (${event.going_count})` : 'Maybe'}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Create EventCalendar (CSS Grid month view)**

```tsx
// resources/js/plugins/events/EventCalendar.tsx
import React, { useState } from 'react';

interface CalEvent { id: number; title: string; start_at: string; category: string }
interface Props { events: CalEvent[]; onEventClick: (id: number) => void }

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const CATEGORY_COLORS: Record<string, string> = {
    worship: '#7c3aed', youth: '#059669', outreach: '#d97706',
    study: '#2563eb', fellowship: '#db2777', other: '#64748b',
};

export default function EventCalendar({ events, onEventClick }: Props) {
    const [current, setCurrent] = useState(new Date());
    const year = current.getFullYear();
    const month = current.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = Array.from({ length: firstDay + daysInMonth }, (_, i) =>
        i < firstDay ? null : i - firstDay + 1
    );

    const byDate = events.reduce<Record<string, CalEvent[]>>((acc, e) => {
        const d = new Date(e.start_at).getDate();
        (acc[d] = acc[d] ?? []).push(e);
        return acc;
    }, {});

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                <button onClick={() => setCurrent(new Date(year, month - 1, 1))} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>‹</button>
                <strong>{current.toLocaleString('default', { month: 'long', year: 'numeric' })}</strong>
                <button onClick={() => setCurrent(new Date(year, month + 1, 1))} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>›</button>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: 2, fontSize: '0.75rem' }}>
                {DAYS.map(d => <div key={d} style={{ textAlign: 'center', fontWeight: 600, color: '#94a3b8', padding: '4px 0' }}>{d}</div>)}
                {cells.map((day, i) => (
                    <div key={i} style={{ minHeight: 64, background: day ? '#fff' : 'transparent', borderRadius: 6, padding: 4, border: '1px solid #f1f5f9' }}>
                        {day && <>
                            <div style={{ color: '#64748b', marginBottom: 2 }}>{day}</div>
                            {(byDate[day] ?? []).slice(0, 2).map(e => (
                                <div key={e.id} onClick={() => onEventClick(e.id)}
                                    style={{ background: CATEGORY_COLORS[e.category] ?? '#64748b', color: '#fff', borderRadius: 3, padding: '1px 4px', marginBottom: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', cursor: 'pointer', fontSize: '0.7rem' }}>
                                    {e.title}
                                </div>
                            ))}
                        </>}
                    </div>
                ))}
            </div>
        </div>
    );
}
```

- [ ] **Step 3: Create EventsPage (calendar/list toggle + filter bar)**

```tsx
// resources/js/plugins/events/EventsPage.tsx
import React, { useEffect, useState } from 'react';
import EventCard from './EventCard';
import EventCalendar from './EventCalendar';

const CATEGORIES = ['All', 'Worship', 'Youth', 'Outreach', 'Study', 'Fellowship'];

interface EventItem { id: number; title: string; start_at: string; end_at: string; is_multi_day: boolean; location?: string; is_online: boolean; category: string; going_count: number; cover_image?: string; user_rsvp?: string | null }

export default function EventsPage() {
    const [events, setEvents] = useState<EventItem[]>([]);
    const [view, setView] = useState<'list' | 'calendar'>('list');
    const [category, setCategory] = useState('All');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const params = new URLSearchParams({ scope: 'upcoming' });
        if (category !== 'All') params.set('category', category.toLowerCase());
        fetch(`/api/v1/events?${params}`)
            .then(r => r.json())
            .then(d => { setEvents(d.data ?? d); setLoading(false); });
    }, [category]);

    async function rsvp(eventId: number, status: string) {
        await fetch(`/api/v1/events/${eventId}/rsvp`, {
            method: 'POST', body: JSON.stringify({ status }),
            headers: { 'Content-Type': 'application/json' },
        });
        setEvents(prev => prev.map(e => e.id === eventId ? { ...e, user_rsvp: status, going_count: status === 'going' ? e.going_count + 1 : e.going_count } : e));
    }

    return (
        <div style={{ maxWidth: 680, margin: '0 auto', padding: '1rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h1 style={{ fontSize: '1.25rem', fontWeight: 700, margin: 0 }}>Events</h1>
                <div style={{ display: 'flex', gap: 8 }}>
                    {(['list', 'calendar'] as const).map(v => (
                        <button key={v} onClick={() => setView(v)}
                            style={{ fontSize: '0.8rem', padding: '4px 12px', borderRadius: 20, border: 'none', cursor: 'pointer', background: view === v ? '#2563eb' : '#f1f5f9', color: view === v ? '#fff' : '#475569' }}>
                            {v === 'list' ? '☰ List' : '◫ Calendar'}
                        </button>
                    ))}
                </div>
            </div>
            <div style={{ display: 'flex', gap: 8, overflowX: 'auto', marginBottom: '1rem', paddingBottom: 4 }}>
                {CATEGORIES.map(c => (
                    <button key={c} onClick={() => setCategory(c)}
                        style={{ fontSize: '0.8rem', padding: '4px 14px', borderRadius: 20, whiteSpace: 'nowrap', border: 'none', cursor: 'pointer', background: category === c ? '#2563eb' : '#f1f5f9', color: category === c ? '#fff' : '#475569' }}>
                        {c}
                    </button>
                ))}
            </div>
            {loading ? <div style={{ color: '#94a3b8' }}>Loading events…</div> : (
                view === 'calendar'
                    ? <EventCalendar events={events} onEventClick={id => window.location.href = `/events/${id}`} />
                    : events.map(e => <EventCard key={e.id} event={e} onRsvp={rsvp} />)
            )}
        </div>
    );
}
```

- [ ] **Step 4: Create EventDetailPage**

```tsx
// resources/js/plugins/events/EventDetailPage.tsx
import React, { useEffect, useState, Suspense, lazy } from 'react';
import SafeHtml from '../../components/shared/SafeHtml';

const CommentThread = lazy(() => import('../feed/CommentThread'));

interface EventDetail { id: number; title: string; description: string; start_at: string; end_at: string; is_online: boolean; meeting_url?: string; location?: string; cover_image?: string; going_count: number; maybe_count: number; user_rsvp?: string | null; created_by: { name: string } }

export default function EventDetailPage({ eventId }: { eventId: number }) {
    const [event, setEvent] = useState<EventDetail | null>(null);

    useEffect(() => {
        fetch(`/api/v1/events/${eventId}`).then(r => r.json()).then(setEvent);
    }, [eventId]);

    async function rsvp(status: string) {
        const res = await fetch(`/api/v1/events/${eventId}/rsvp`, {
            method: 'POST', body: JSON.stringify({ status }),
            headers: { 'Content-Type': 'application/json' },
        });
        const d = await res.json();
        setEvent(prev => prev ? { ...prev, user_rsvp: status } : prev);
    }

    if (!event) return <div style={{ padding: '2rem', color: '#94a3b8' }}>Loading…</div>;

    return (
        <div style={{ maxWidth: 680, margin: '0 auto', padding: '1rem' }}>
            {event.cover_image && <img src={event.cover_image} alt={event.title} style={{ width: '100%', borderRadius: 12, marginBottom: '1rem', maxHeight: 240, objectFit: 'cover' }} />}
            <h1 style={{ fontSize: '1.4rem', fontWeight: 700, marginBottom: 4 }}>{event.title}</h1>
            <div style={{ color: '#64748b', fontSize: '0.875rem', marginBottom: 12 }}>
                {new Date(event.start_at).toLocaleString()} {event.is_online ? '· Online' : event.location ? `· ${event.location}` : ''}
            </div>

            <div style={{ display: 'flex', gap: 8, marginBottom: '1rem' }}>
                {(['going', 'maybe', 'not_going'] as const).map(s => (
                    <button key={s} onClick={() => rsvp(s)}
                        style={{ fontSize: '0.875rem', padding: '6px 16px', borderRadius: 20, border: 'none', cursor: 'pointer', background: event.user_rsvp === s ? '#2563eb' : '#f1f5f9', color: event.user_rsvp === s ? '#fff' : '#475569' }}>
                        {s === 'going' ? `Going (${event.going_count})` : s === 'maybe' ? `Maybe (${event.maybe_count})` : 'Not Going'}
                    </button>
                ))}
            </div>

            {event.meeting_url && (
                <a href={event.meeting_url} target="_blank" rel="noopener noreferrer"
                    style={{ display: 'inline-block', background: '#2563eb', color: '#fff', borderRadius: 8, padding: '8px 20px', textDecoration: 'none', marginBottom: '1rem', fontSize: '0.875rem' }}>
                    🎥 Join Meeting
                </a>
            )}

            <SafeHtml html={event.description} style={{ marginBottom: '1.5rem', lineHeight: 1.7 }} />

            <h3 style={{ fontSize: '1rem', fontWeight: 600, marginBottom: 8 }}>Discussion</h3>
            {/*
              The existing CommentThread component fetches from /api/v1/posts/{id}/comments.
              Event threads use GET /api/v1/events/{id}/posts (same paginated post shape).
              Before using CommentThread here, check whether it accepts an `apiUrl` prop.
              If not, add `apiUrl?: string` to CommentThread's props and use it as the fetch URL
              instead of the hardcoded comments endpoint. Then pass:
              apiUrl={`/api/v1/events/${eventId}/posts`}
            */}
            <Suspense fallback={<div style={{ color: '#94a3b8' }}>Loading…</div>}>
                <CommentThread postId={eventId} apiUrl={`/api/v1/events/${eventId}/posts`} />
            </Suspense>
        </div>
    );
}
```

- [ ] **Step 5: Create CreateEventForm (4-step)**

```tsx
// resources/js/plugins/events/CreateEventForm.tsx
import React, { useState } from 'react';

interface FormData { title: string; description: string; category: string; start_at: string; end_at: string; is_recurring: boolean; is_online: boolean; meeting_url: string; location: string; max_attendees: string }
const EMPTY: FormData = { title: '', description: '', category: 'worship', start_at: '', end_at: '', is_recurring: false, is_online: false, meeting_url: '', location: '', max_attendees: '' };

export default function CreateEventForm({ onCreated }: { onCreated: () => void }) {
    const [step, setStep] = useState(1);
    const [form, setForm] = useState<FormData>(EMPTY);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const update = (k: keyof FormData, v: any) => setForm(f => ({ ...f, [k]: v }));

    async function submit() {
        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/events', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...form, max_attendees: form.max_attendees ? Number(form.max_attendees) : null }),
            });
            if (!res.ok) throw new Error((await res.json()).message ?? 'Failed');
            onCreated();
        } catch (e: any) {
            setError(e.message);
        } finally {
            setSubmitting(false);
        }
    }

    const stepLabel = ['Details', 'Schedule', 'Location', 'Publish'];

    return (
        <div style={{ maxWidth: 500, margin: '0 auto', padding: '1rem' }}>
            <div style={{ display: 'flex', gap: 8, marginBottom: '1.5rem' }}>
                {stepLabel.map((l, i) => (
                    <div key={i} style={{ flex: 1, textAlign: 'center', fontSize: '0.75rem', fontWeight: step === i + 1 ? 700 : 400, color: step === i + 1 ? '#2563eb' : '#94a3b8', borderBottom: step === i + 1 ? '2px solid #2563eb' : '2px solid #e2e8f0', paddingBottom: 4 }}>
                        {l}
                    </div>
                ))}
            </div>

            {step === 1 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <input placeholder="Event title *" value={form.title} onChange={e => update('title', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem' }} />
                    <textarea placeholder="Description" rows={4} value={form.description} onChange={e => update('description', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem', resize: 'vertical' }} />
                    <select value={form.category} onChange={e => update('category', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem' }}>
                        {['worship', 'youth', 'outreach', 'study', 'fellowship', 'other'].map(c => <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>)}
                    </select>
                </div>
            )}

            {step === 2 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <label style={{ fontSize: '0.875rem', color: '#64748b' }}>Start *</label>
                    <input type="datetime-local" value={form.start_at} onChange={e => update('start_at', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <label style={{ fontSize: '0.875rem', color: '#64748b' }}>End *</label>
                    <input type="datetime-local" value={form.end_at} onChange={e => update('end_at', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: '0.875rem', cursor: 'pointer' }}>
                        <input type="checkbox" checked={form.is_recurring} onChange={e => update('is_recurring', e.target.checked)} />
                        Recurring event
                    </label>
                </div>
            )}

            {step === 3 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer' }}>
                        <input type="checkbox" checked={form.is_online} onChange={e => update('is_online', e.target.checked)} />
                        Online event
                    </label>
                    {form.is_online
                        ? <input placeholder="Meeting URL (Zoom, Meet…)" value={form.meeting_url} onChange={e => update('meeting_url', e.target.value)}
                            style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                        : <input placeholder="Location address" value={form.location} onChange={e => update('location', e.target.value)}
                            style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    }
                </div>
            )}

            {step === 4 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <input type="number" placeholder="Max attendees (optional)" value={form.max_attendees} onChange={e => update('max_attendees', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <div style={{ background: '#f8fafc', borderRadius: 8, padding: '0.75rem', fontSize: '0.875rem' }}>
                        <strong>{form.title || '(no title)'}</strong><br />
                        <span style={{ color: '#64748b' }}>{form.start_at || 'No date set'} — {form.category}</span>
                    </div>
                    {error && <div style={{ color: '#dc2626', fontSize: '0.875rem' }}>{error}</div>}
                </div>
            )}

            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '1.5rem' }}>
                {step > 1 && <button onClick={() => setStep(s => s - 1)} style={{ padding: '8px 20px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', cursor: 'pointer' }}>Back</button>}
                {step < 4
                    ? <button onClick={() => setStep(s => s + 1)} style={{ marginLeft: 'auto', padding: '8px 20px', borderRadius: 8, border: 'none', background: '#2563eb', color: '#fff', cursor: 'pointer' }}>Next</button>
                    : <button onClick={submit} disabled={submitting} style={{ marginLeft: 'auto', padding: '8px 20px', borderRadius: 8, border: 'none', background: '#2563eb', color: '#fff', cursor: 'pointer' }}>
                        {submitting ? 'Creating…' : 'Publish Event'}
                    </button>
                }
            </div>
        </div>
    );
}
```

- [ ] **Step 6: Verify Vite builds cleanly**

```bash
npm run build 2>&1 | tail -10
```

- [ ] **Step 7: Run full test suite**

```bash
./vendor/bin/pest --stop-on-failure 2>&1 | tail -15
```
Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add resources/js/plugins/events/
git commit -m "feat(events): add EventCard, EventCalendar, EventsPage, EventDetailPage, CreateEventForm"
```
