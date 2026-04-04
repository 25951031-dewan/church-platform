# Plan 4: Events + Sermons — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Events and Sermons plugins — completing the core Phase 1 features. Events adds RSVP (attending/interested/not going), Zoom/Meet meeting links, and a calendar data endpoint on top of the existing `events` table. Sermons normalizes the speaker/series strings into proper tables, adds `HasReactions` + `HasComments` for engagement, and introduces a persistent audio player bar that plays across page navigation (BeMusic-inspired).

**Architecture:** Both plugins follow the established pattern: `app/Plugins/{Name}/` with Loader, Crupdate, Paginate, Delete services, a Policy, form requests, and a PermissionSeeder. Both plugins reuse the existing legacy tables (`events`, `event_registrations`, `sermons`) — enhanced via new migrations rather than recreated — and replace the legacy controllers in `App\Http\Controllers\Api\`. The existing tables already have `church_id` (added by `2026_03_06_000001`). Events gets a new `event_rsvps` table for authenticated member RSVPs (separate from guest registrations). Sermons gets `sermon_series` and `speakers` tables, with nullable FKs added to `sermons`.

**Tech Stack:** Laravel 12 plugins, Eloquent on existing + enhanced tables, TanStack React Query, HTML5 Audio element with Zustand store for persistent player state, `react-router` for navigation.

**Spec:** `docs/superpowers/specs/2026-03-28-church-community-platform-design.md` (sections 7 — Events, 10 — Sermons)

**Depends on:** Plan 3 (Groups) — all tasks must be complete. Specifically requires: `HasReactions`/`HasComments` traits (Plan 2), morph map pattern (Plan 2), plugin loading via `PluginManager` (Plan 2), permission seeder pattern (Plan 2).

---

## File Structure Overview

```
app/Plugins/Events/
├── Models/
│   ├── Event.php                          # Plugin Event model (replaces App\Models\Event)
│   └── EventRsvp.php                      # Authenticated RSVP model
├── Services/
│   ├── EventLoader.php                    # API response formatting
│   ├── CrupdateEvent.php                 # Create/update with slug generation
│   ├── PaginateEvents.php                # Browse with date/category filters
│   ├── DeleteEvents.php                  # Delete with cascade
│   └── EventRsvpService.php              # RSVP toggle (attending/interested/not_going/remove)
├── Controllers/
│   ├── EventController.php               # CRUD + upcoming + calendar
│   └── EventRsvpController.php           # RSVP actions
├── Policies/
│   └── EventPolicy.php                   # own/any permission pattern
├── Requests/
│   ├── ModifyEvent.php                   # Validation for create/update
│   └── ModifyEventRsvp.php              # Validation for RSVP status
├── Routes/
│   └── api.php                           # Plugin routes
├── Database/
│   └── Seeders/
│       └── EventPermissionSeeder.php     # 10 permissions across 7 roles

app/Plugins/Sermons/
├── Models/
│   ├── Sermon.php                         # Plugin Sermon model (replaces App\Models\Sermon)
│   ├── SermonSeries.php                   # Series collection
│   └── Speaker.php                        # Speaker profile
├── Services/
│   ├── SermonLoader.php                   # API response formatting
│   ├── CrupdateSermon.php                # Create/update with slug + file handling
│   ├── PaginateSermons.php               # Browse with search, speaker, series, scripture filters
│   ├── DeleteSermons.php                 # Delete with file cleanup
│   ├── CrupdateSermonSeries.php          # Series CRUD
│   └── CrupdateSpeaker.php              # Speaker CRUD
├── Controllers/
│   ├── SermonController.php              # CRUD + featured
│   ├── SermonSeriesController.php        # Series CRUD
│   └── SpeakerController.php            # Speaker CRUD
├── Policies/
│   └── SermonPolicy.php                  # own/any + manage_series + manage_speakers
├── Requests/
│   ├── ModifySermon.php                  # Validation for create/update
│   ├── ModifySermonSeries.php            # Series validation
│   └── ModifySpeaker.php                # Speaker validation
├── Routes/
│   └── api.php                           # Plugin routes
├── Database/
│   └── Seeders/
│       └── SermonPermissionSeeder.php    # 10 permissions across 7 roles

database/
├── migrations/
│   ├── 0004_01_01_000001_enhance_events_and_add_rsvps.php
│   └── 0004_01_01_000002_create_sermon_series_speakers_tables.php
├── factories/
│   ├── EventFactory.php
│   └── SermonFactory.php

tests/Feature/
├── Events/
│   ├── EventCrudTest.php                  # 6 tests
│   ├── EventRsvpTest.php                 # 5 tests
│   └── EventPolicyTest.php              # 4 tests
├── Sermons/
│   ├── SermonCrudTest.php                # 6 tests
│   └── SermonPolicyTest.php             # 4 tests

resources/client/
├── plugins/events/
│   ├── queries.ts                        # TanStack Query hooks
│   ├── pages/
│   │   ├── EventsPage.tsx               # List with date filters
│   │   └── EventDetailPage.tsx          # Detail with RSVP, map, meeting link
│   └── components/
│       ├── EventCard.tsx                 # Card for list/grid
│       └── RsvpButton.tsx               # Three-state RSVP toggle
├── plugins/sermons/
│   ├── queries.ts                        # TanStack Query hooks
│   ├── pages/
│   │   ├── SermonsPage.tsx              # List with search/filters
│   │   ├── SermonDetailPage.tsx         # Detail with inline player
│   │   └── SermonSeriesPage.tsx         # Series detail with sermon list
│   └── components/
│       ├── SermonCard.tsx               # Card for list
│       └── SermonPlayer.tsx             # Inline player (triggers global)
├── common/
│   └── audio-player/
│       ├── audio-player-store.ts        # Zustand store for global player state
│       └── AudioPlayerBar.tsx           # Persistent bottom bar (app shell)
```

---

## Tasks

### Task 1: Events migration — enhance events + create event_rsvps
**Files:** `database/migrations/0004_01_01_000001_enhance_events_and_add_rsvps.php`

The existing `events` table has everything except `meeting_url` (for Zoom/Meet links). The existing `event_registrations` table handles guest registration. We add a separate `event_rsvps` table for authenticated member RSVPs — the two concepts are distinct (guest registration captures contact info; RSVP is a simple status toggle for logged-in members).

- [ ] **Step 1:** Create migration `0004_01_01_000001_enhance_events_and_add_rsvps.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add meeting_url to events for Zoom/Meet links
        Schema::table('events', function (Blueprint $table) {
            $table->string('meeting_url')->nullable()->after('registration_link');
        });

        // Authenticated member RSVPs (separate from guest registrations)
        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['attending', 'interested', 'not_going'])->default('attending');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rsvps');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('meeting_url');
        });
    }
};
```

- [ ] **Step 2:** Verify syntax: `php -l database/migrations/0004_01_01_000001_enhance_events_and_add_rsvps.php`

---

### Task 2: Sermons migration — create sermon_series + speakers tables
**Files:** `database/migrations/0004_01_01_000002_create_sermon_series_speakers_tables.php`

The existing `sermons` table stores `speaker` and `series` as plain strings. We normalize these into proper tables so speakers have profiles (bio, image) and series can be browsed. The original string columns remain for backward compatibility; the new FKs are nullable.

- [ ] **Step 1:** Create migration `0004_01_01_000002_create_sermon_series_speakers_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sermon_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('church_id');
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('church_id');
        });

        // Add normalized FKs to sermons (alongside existing string columns)
        Schema::table('sermons', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('series')
                ->constrained('sermon_series')->nullOnDelete();
            $table->foreignId('speaker_id')->nullable()->after('speaker')
                ->constrained('speakers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sermons', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropForeign(['speaker_id']);
            $table->dropColumn(['series_id', 'speaker_id']);
        });

        Schema::dropIfExists('speakers');
        Schema::dropIfExists('sermon_series');
    }
};
```

- [ ] **Step 2:** Verify syntax: `php -l database/migrations/0004_01_01_000002_create_sermon_series_speakers_tables.php`

---

### Task 3: Events models — Event, EventRsvp
**Files:** `app/Plugins/Events/Models/Event.php`, `app/Plugins/Events/Models/EventRsvp.php`

The plugin Event model replaces `App\Models\Event`. It uses the same `events` table but adds `HasReactions`, `HasComments`, and proper relationships.

- [ ] **Step 1:** Create `app/Plugins/Events/Models/Event.php`

```php
<?php

namespace App\Plugins\Events\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_recurring' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'registration_required' => 'boolean',
        'max_attendees' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $slug = Str::slug($event->title);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $event->slug = $slug;
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\EventFactory::new();
    }

    // --- Relationships ---

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function attendingRsvps(): HasMany
    {
        return $this->rsvps()->where('status', 'attending');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(\App\Models\EventRegistration::class);
    }

    // --- RSVP helpers ---

    public function getUserRsvp(int $userId): ?EventRsvp
    {
        return $this->rsvps()->where('user_id', $userId)->first();
    }

    public function rsvpCounts(): array
    {
        return $this->rsvps()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    // --- Scopes ---

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->where('is_active', true)
            ->orderBy('start_date');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->created_by === $userId;
    }

    public function isFull(): bool
    {
        if (!$this->max_attendees) return false;
        return $this->attendingRsvps()->count() >= $this->max_attendees;
    }

    public function isPast(): bool
    {
        $endOrStart = $this->end_date ?? $this->start_date;
        return $endOrStart->isPast();
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Events/Models/EventRsvp.php`

```php
<?php

namespace App\Plugins\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    protected $guarded = ['id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
```

- [ ] **Step 3:** Verify: `php -l app/Plugins/Events/Models/Event.php && php -l app/Plugins/Events/Models/EventRsvp.php`

---

### Task 4: Sermons models — Sermon, SermonSeries, Speaker
**Files:** `app/Plugins/Sermons/Models/Sermon.php`, `app/Plugins/Sermons/Models/SermonSeries.php`, `app/Plugins/Sermons/Models/Speaker.php`

- [ ] **Step 1:** Create `app/Plugins/Sermons/Models/Sermon.php`

```php
<?php

namespace App\Plugins\Sermons\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Sermon extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sermon_date' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sermon $sermon) {
            if (empty($sermon->slug)) {
                $slug = Str::slug($sermon->title);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $sermon->slug = $slug;
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\SermonFactory::new();
    }

    // --- Relationships ---

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'author_id');
    }

    public function sermonSeries(): BelongsTo
    {
        return $this->belongsTo(SermonSeries::class, 'series_id');
    }

    public function speakerProfile(): BelongsTo
    {
        return $this->belongsTo(Speaker::class, 'speaker_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->active();
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->author_id === $userId;
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    public function hasAudio(): bool
    {
        return !empty($this->audio_url);
    }

    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Sermons/Models/SermonSeries.php`

```php
<?php

namespace App\Plugins\Sermons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SermonSeries extends Model
{
    protected $table = 'sermon_series';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (SermonSeries $series) {
            if (empty($series->slug)) {
                $series->slug = Str::slug($series->name) . '-' . Str::random(6);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class, 'series_id');
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Sermons/Models/Speaker.php`

```php
<?php

namespace App\Plugins\Sermons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Speaker extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Speaker $speaker) {
            if (empty($speaker->slug)) {
                $speaker->slug = Str::slug($speaker->name) . '-' . Str::random(6);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class, 'speaker_id');
    }
}
```

- [ ] **Step 4:** Verify: `php -l app/Plugins/Sermons/Models/*.php`

---

### Task 5: Events services + EventRsvpService
**Files:** `app/Plugins/Events/Services/EventLoader.php`, `app/Plugins/Events/Services/CrupdateEvent.php`, `app/Plugins/Events/Services/PaginateEvents.php`, `app/Plugins/Events/Services/DeleteEvents.php`, `app/Plugins/Events/Services/EventRsvpService.php`

- [ ] **Step 1:** Create `EventLoader.php`

```php
<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class EventLoader
{
    public function load(Event $event): Event
    {
        return $event->load([
            'creator:id,name,avatar',
        ])->loadCount(['rsvps', 'attendingRsvps', 'registrations', 'comments', 'reactions']);
    }

    public function loadForDetail(Event $event): array
    {
        $this->load($event);

        $data = $event->toArray();
        $data['rsvp_counts'] = $event->rsvpCounts();
        $data['reaction_counts'] = $event->reactionCounts();

        $userId = auth()->id();
        if ($userId) {
            $rsvp = $event->getUserRsvp($userId);
            $data['current_user_rsvp'] = $rsvp?->status;
            $data['current_user_reaction'] = $event->currentUserReaction()?->type;
        }

        return $data;
    }
}
```

- [ ] **Step 2:** Create `CrupdateEvent.php`

```php
<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class CrupdateEvent
{
    public function execute(array $data, ?Event $event = null): Event
    {
        $fields = [
            'title', 'description', 'content', 'image', 'location', 'location_url',
            'start_date', 'end_date', 'is_recurring', 'recurrence_pattern',
            'is_featured', 'is_active', 'max_attendees', 'registration_required',
            'registration_link', 'meeting_url', 'meta_title', 'meta_description',
        ];

        if ($event) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $event->update($updateData);
        } else {
            $createData = ['created_by' => $data['created_by']];
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $event = Event::create($createData);
        }

        return $event;
    }
}
```

- [ ] **Step 3:** Create `PaginateEvents.php`

```php
<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateEvents
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Event::query()
            ->with(['creator:id,name,avatar'])
            ->withCount(['attendingRsvps', 'registrations']);

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        } elseif ($request->boolean('featured')) {
            $query->featured();
        } else {
            $query->active()->orderByDesc('start_date');
        }

        if ($request->has('from')) {
            $query->where('start_date', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->where('start_date', '<=', $request->input('to'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(min((int) $request->input('per_page', 15), 50));
    }
}
```

- [ ] **Step 4:** Create `DeleteEvents.php`

```php
<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class DeleteEvents
{
    public function execute(array $eventIds): void
    {
        $events = Event::whereIn('id', $eventIds)->get();

        foreach ($events as $event) {
            $event->reactions()->delete();
            $event->comments()->delete();
            $event->rsvps()->delete();
            $event->delete();
        }
    }
}
```

- [ ] **Step 5:** Create `EventRsvpService.php`

```php
<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Models\EventRsvp;

class EventRsvpService
{
    /**
     * Set or update RSVP status. Returns the RSVP record.
     */
    public function rsvp(Event $event, int $userId, string $status): EventRsvp
    {
        return EventRsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $userId],
            ['status' => $status]
        );
    }

    /**
     * Remove RSVP entirely.
     */
    public function cancel(Event $event, int $userId): void
    {
        EventRsvp::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->delete();
    }
}
```

- [ ] **Step 6:** Verify: `php -l app/Plugins/Events/Services/*.php`

---

### Task 6: Sermons services
**Files:** `app/Plugins/Sermons/Services/SermonLoader.php`, `app/Plugins/Sermons/Services/CrupdateSermon.php`, `app/Plugins/Sermons/Services/PaginateSermons.php`, `app/Plugins/Sermons/Services/DeleteSermons.php`, `app/Plugins/Sermons/Services/CrupdateSermonSeries.php`, `app/Plugins/Sermons/Services/CrupdateSpeaker.php`

- [ ] **Step 1:** Create `SermonLoader.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class SermonLoader
{
    public function load(Sermon $sermon): Sermon
    {
        return $sermon->load([
            'author:id,name,avatar',
            'sermonSeries:id,name,slug',
            'speakerProfile:id,name,slug,image',
            'reactions',
        ])->loadCount(['comments', 'reactions']);
    }

    public function loadForDetail(Sermon $sermon): array
    {
        $this->load($sermon);

        $data = $sermon->toArray();
        $data['reaction_counts'] = $sermon->reactionCounts();

        $userId = auth()->id();
        if ($userId) {
            $data['current_user_reaction'] = $sermon->currentUserReaction()?->type;
        }

        return $data;
    }
}
```

- [ ] **Step 2:** Create `CrupdateSermon.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class CrupdateSermon
{
    public function execute(array $data, ?Sermon $sermon = null): Sermon
    {
        $fields = [
            'title', 'description', 'content', 'speaker', 'speaker_id',
            'image', 'thumbnail', 'video_url', 'audio_url', 'pdf_notes',
            'scripture_reference', 'series', 'series_id', 'category',
            'sermon_date', 'duration_minutes', 'is_featured', 'is_active',
            'is_published', 'tags', 'meta_title', 'meta_description',
        ];

        if ($sermon) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $sermon->update($updateData);
        } else {
            $createData = ['author_id' => $data['author_id']];
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $sermon = Sermon::create($createData);
        }

        return $sermon;
    }
}
```

- [ ] **Step 3:** Create `PaginateSermons.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateSermons
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Sermon::query()
            ->with(['author:id,name,avatar', 'sermonSeries:id,name,slug', 'speakerProfile:id,name,slug,image'])
            ->withCount(['comments', 'reactions']);

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        } elseif ($request->boolean('published')) {
            $query->published();
        } else {
            $query->active();
        }

        if ($request->has('speaker_id')) {
            $query->where('speaker_id', $request->input('speaker_id'));
        }

        if ($request->has('series_id')) {
            $query->where('series_id', $request->input('series_id'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('speaker', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('scripture_reference', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('sermon_date');

        return $query->paginate(min((int) $request->input('per_page', 15), 50));
    }
}
```

- [ ] **Step 4:** Create `DeleteSermons.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class DeleteSermons
{
    public function execute(array $sermonIds): void
    {
        $sermons = Sermon::whereIn('id', $sermonIds)->get();

        foreach ($sermons as $sermon) {
            $sermon->reactions()->delete();
            $sermon->comments()->delete();
            $sermon->delete();
        }
    }
}
```

- [ ] **Step 5:** Create `CrupdateSermonSeries.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\SermonSeries;

class CrupdateSermonSeries
{
    public function execute(array $data, ?SermonSeries $series = null): SermonSeries
    {
        if ($series) {
            $series->update([
                'name' => $data['name'] ?? $series->name,
                'description' => $data['description'] ?? $series->description,
                'image' => $data['image'] ?? $series->image,
            ]);
        } else {
            $series = SermonSeries::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image' => $data['image'] ?? null,
                'church_id' => $data['church_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);
        }

        return $series;
    }
}
```

- [ ] **Step 6:** Create `CrupdateSpeaker.php`

```php
<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Speaker;

class CrupdateSpeaker
{
    public function execute(array $data, ?Speaker $speaker = null): Speaker
    {
        if ($speaker) {
            $speaker->update([
                'name' => $data['name'] ?? $speaker->name,
                'bio' => $data['bio'] ?? $speaker->bio,
                'image' => $data['image'] ?? $speaker->image,
            ]);
        } else {
            $speaker = Speaker::create([
                'name' => $data['name'],
                'bio' => $data['bio'] ?? null,
                'image' => $data['image'] ?? null,
                'church_id' => $data['church_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);
        }

        return $speaker;
    }
}
```

- [ ] **Step 7:** Verify: `php -l app/Plugins/Sermons/Services/*.php`

---

### Task 7: Policies and form requests (both plugins)
**Files:** Event policy + requests, Sermon policy + requests

- [ ] **Step 1:** Create `app/Plugins/Events/Policies/EventPolicy.php`

```php
<?php

namespace App\Plugins\Events\Policies;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Core\BasePolicy;

class EventPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return true; // All active events are visible to authenticated users
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('events.create');
    }

    public function update(User $user, Event $event): bool
    {
        if ($event->isOwnedBy($user->id)) {
            return $user->hasPermission('events.update');
        }
        return $user->hasPermission('events.update_any');
    }

    public function delete(User $user, Event $event): bool
    {
        if ($event->isOwnedBy($user->id)) {
            return $user->hasPermission('events.delete');
        }
        return $user->hasPermission('events.delete_any');
    }

    public function rsvp(User $user, Event $event): bool
    {
        return $user->hasPermission('events.rsvp');
    }

    public function feature(User $user): bool
    {
        return $user->hasPermission('events.feature');
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Events/Requests/ModifyEvent.php`

```php
<?php

namespace App\Plugins\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyEvent extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'location_url' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|max:100',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1',
            'registration_required' => 'nullable|boolean',
            'registration_link' => 'nullable|string|max:500',
            'meeting_url' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Events/Requests/ModifyEventRsvp.php`

```php
<?php

namespace App\Plugins\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyEventRsvp extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:attending,interested,not_going',
        ];
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/Sermons/Policies/SermonPolicy.php`

```php
<?php

namespace App\Plugins\Sermons\Policies;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use Common\Core\BasePolicy;

class SermonPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sermons.view');
    }

    public function view(User $user, Sermon $sermon): bool
    {
        return true; // All active sermons are visible to authenticated users
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sermons.create');
    }

    public function update(User $user, Sermon $sermon): bool
    {
        if ($sermon->isOwnedBy($user->id)) {
            return $user->hasPermission('sermons.update');
        }
        return $user->hasPermission('sermons.update_any');
    }

    public function delete(User $user, Sermon $sermon): bool
    {
        if ($sermon->isOwnedBy($user->id)) {
            return $user->hasPermission('sermons.delete');
        }
        return $user->hasPermission('sermons.delete_any');
    }

    public function manageSeries(User $user): bool
    {
        return $user->hasPermission('sermons.manage_series');
    }

    public function manageSpeakers(User $user): bool
    {
        return $user->hasPermission('sermons.manage_speakers');
    }
}
```

- [ ] **Step 5:** Create `app/Plugins/Sermons/Requests/ModifySermon.php`

```php
<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySermon extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'content' => 'nullable|string',
            'speaker' => 'required|string|max:255',
            'speaker_id' => 'nullable|integer|exists:speakers,id',
            'scripture_reference' => 'nullable|string|max:500',
            'series' => 'nullable|string|max:255',
            'series_id' => 'nullable|integer|exists:sermon_series,id',
            'category' => 'nullable|string|max:255',
            'sermon_date' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'video_url' => 'nullable|string|max:500',
            'audio_url' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string|max:500',
            'pdf_notes' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'tags' => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
```

- [ ] **Step 6:** Create `app/Plugins/Sermons/Requests/ModifySermonSeries.php`

```php
<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySermonSeries extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
        ];
    }
}
```

- [ ] **Step 7:** Create `app/Plugins/Sermons/Requests/ModifySpeaker.php`

```php
<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySpeaker extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'image' => 'nullable|string|max:500',
        ];
    }
}
```

- [ ] **Step 8:** Verify: `php -l app/Plugins/Events/Policies/*.php && php -l app/Plugins/Events/Requests/*.php && php -l app/Plugins/Sermons/Policies/*.php && php -l app/Plugins/Sermons/Requests/*.php`

---

### Task 8: Events controllers + routes + seeder
**Files:** `app/Plugins/Events/Controllers/EventController.php`, `app/Plugins/Events/Controllers/EventRsvpController.php`, `app/Plugins/Events/Routes/api.php`, `app/Plugins/Events/Database/Seeders/EventPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/Events/Controllers/EventController.php`

```php
<?php

namespace App\Plugins\Events\Controllers;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Requests\ModifyEvent;
use App\Plugins\Events\Services\CrupdateEvent;
use App\Plugins\Events\Services\DeleteEvents;
use App\Plugins\Events\Services\EventLoader;
use App\Plugins\Events\Services\PaginateEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct(
        private EventLoader $loader,
        private CrupdateEvent $crupdate,
        private PaginateEvents $paginator,
        private DeleteEvents $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);
        $events = $this->paginator->execute($request);
        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);
        $event->incrementView();
        return response()->json(['event' => $this->loader->loadForDetail($event)]);
    }

    public function store(ModifyEvent $request): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'event' => $this->loader->loadForDetail($event),
        ], 201);
    }

    public function update(ModifyEvent $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event = $this->crupdate->execute($request->validated(), $event);

        return response()->json([
            'event' => $this->loader->loadForDetail($event),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $this->deleter->execute([$event->id]);

        return response()->noContent();
    }

    public function feature(Event $event): JsonResponse
    {
        Gate::authorize('feature', Event::class);

        $event->update(['is_featured' => !$event->is_featured]);

        return response()->json(['is_featured' => $event->is_featured]);
    }
}
```

> **Note:** `incrementView()` is called in `show()` — but the Event model from Task 3 doesn't define it. Add `public function incrementView(): void { /* no-op for now */ }` or remove the call. The method exists on Sermon but not Event. **Remove the `$event->incrementView()` line from `show()` when implementing** — events don't track view counts.

- [ ] **Step 2:** Create `app/Plugins/Events/Controllers/EventRsvpController.php`

```php
<?php

namespace App\Plugins\Events\Controllers;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Requests\ModifyEventRsvp;
use App\Plugins\Events\Services\EventRsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EventRsvpController extends Controller
{
    public function __construct(
        private EventRsvpService $rsvpService,
    ) {}

    public function rsvp(ModifyEventRsvp $request, Event $event): JsonResponse
    {
        Gate::authorize('rsvp', $event);

        $rsvp = $this->rsvpService->rsvp($event, auth()->id(), $request->input('status'));

        return response()->json([
            'rsvp' => $rsvp,
            'rsvp_counts' => $event->rsvpCounts(),
        ]);
    }

    public function cancel(Event $event): JsonResponse
    {
        $this->rsvpService->cancel($event, auth()->id());

        return response()->json([
            'rsvp_counts' => $event->rsvpCounts(),
        ]);
    }

    public function attendees(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $attendees = $event->rsvps()
            ->with('user:id,name,avatar')
            ->orderByRaw("FIELD(status, 'attending', 'interested', 'not_going')")
            ->paginate(50);

        return response()->json($attendees);
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Events/Routes/api.php`

```php
<?php

use App\Plugins\Events\Controllers\EventController;
use App\Plugins\Events\Controllers\EventRsvpController;
use Illuminate\Support\Facades\Route;

// Event CRUD
Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);

Route::middleware('permission:events.create')->group(function () {
    Route::post('events', [EventController::class, 'store']);
});

Route::put('events/{event}', [EventController::class, 'update']);
Route::delete('events/{event}', [EventController::class, 'destroy']);
Route::patch('events/{event}/feature', [EventController::class, 'feature']);

// RSVP
Route::post('events/{event}/rsvp', [EventRsvpController::class, 'rsvp']);
Route::delete('events/{event}/rsvp', [EventRsvpController::class, 'cancel']);
Route::get('events/{event}/attendees', [EventRsvpController::class, 'attendees']);
```

- [ ] **Step 4:** Create `app/Plugins/Events/Database/Seeders/EventPermissionSeeder.php`

```php
<?php

namespace App\Plugins\Events\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class EventPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'events' => [
                'events.view' => 'View Events',
                'events.create' => 'Create Events',
                'events.update' => 'Edit Own Events',
                'events.update_any' => 'Edit Any Event',
                'events.delete' => 'Delete Own Events',
                'events.delete_any' => 'Delete Any Event',
                'events.rsvp' => 'RSVP to Events',
                'events.manage_rsvp' => 'View/Export RSVP Lists',
                'events.feature' => 'Feature Events',
                'events.manage_registrations' => 'Manage Event Registrations',
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
            'events.view', 'events.rsvp',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'events.view', 'events.create', 'events.update', 'events.update_any',
            'events.delete', 'events.delete_any', 'events.rsvp',
            'events.manage_rsvp', 'events.manage_registrations',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'events')->pluck('id');

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

- [ ] **Step 5:** Verify: `php -l app/Plugins/Events/Controllers/*.php && php -l app/Plugins/Events/Routes/api.php && php -l app/Plugins/Events/Database/Seeders/EventPermissionSeeder.php`

---

### Task 9: Sermons controllers + routes + seeder
**Files:** `app/Plugins/Sermons/Controllers/SermonController.php`, `app/Plugins/Sermons/Controllers/SermonSeriesController.php`, `app/Plugins/Sermons/Controllers/SpeakerController.php`, `app/Plugins/Sermons/Routes/api.php`, `app/Plugins/Sermons/Database/Seeders/SermonPermissionSeeder.php`

- [ ] **Step 1:** Create `app/Plugins/Sermons/Controllers/SermonController.php`

```php
<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Requests\ModifySermon;
use App\Plugins\Sermons\Services\CrupdateSermon;
use App\Plugins\Sermons\Services\DeleteSermons;
use App\Plugins\Sermons\Services\PaginateSermons;
use App\Plugins\Sermons\Services\SermonLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SermonController extends Controller
{
    public function __construct(
        private SermonLoader $loader,
        private CrupdateSermon $crupdate,
        private PaginateSermons $paginator,
        private DeleteSermons $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Sermon::class);
        $sermons = $this->paginator->execute($request);
        return response()->json($sermons);
    }

    public function show(Sermon $sermon): JsonResponse
    {
        Gate::authorize('view', $sermon);
        $sermon->incrementView();
        return response()->json(['sermon' => $this->loader->loadForDetail($sermon)]);
    }

    public function store(ModifySermon $request): JsonResponse
    {
        Gate::authorize('create', Sermon::class);

        $sermon = $this->crupdate->execute([
            ...$request->validated(),
            'author_id' => $request->user()->id,
        ]);

        return response()->json([
            'sermon' => $this->loader->loadForDetail($sermon),
        ], 201);
    }

    public function update(ModifySermon $request, Sermon $sermon): JsonResponse
    {
        Gate::authorize('update', $sermon);

        $sermon = $this->crupdate->execute($request->validated(), $sermon);

        return response()->json([
            'sermon' => $this->loader->loadForDetail($sermon),
        ]);
    }

    public function destroy(Sermon $sermon): JsonResponse
    {
        Gate::authorize('delete', $sermon);

        $this->deleter->execute([$sermon->id]);

        return response()->noContent();
    }
}
```

- [ ] **Step 2:** Create `app/Plugins/Sermons/Controllers/SermonSeriesController.php`

```php
<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\SermonSeries;
use App\Plugins\Sermons\Requests\ModifySermonSeries;
use App\Plugins\Sermons\Services\CrupdateSermonSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SermonSeriesController extends Controller
{
    public function __construct(
        private CrupdateSermonSeries $crupdate,
    ) {}

    public function index(): JsonResponse
    {
        $series = SermonSeries::withCount('sermons')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($series);
    }

    public function show(SermonSeries $sermonSeries): JsonResponse
    {
        $sermonSeries->loadCount('sermons');

        return response()->json(['series' => $sermonSeries]);
    }

    public function store(ModifySermonSeries $request): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        $series = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['series' => $series], 201);
    }

    public function update(ModifySermonSeries $request, SermonSeries $sermonSeries): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        $series = $this->crupdate->execute($request->validated(), $sermonSeries);

        return response()->json(['series' => $series]);
    }

    public function destroy(SermonSeries $sermonSeries): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        // Nullify FK on sermons, then delete series
        $sermonSeries->sermons()->update(['series_id' => null]);
        $sermonSeries->delete();

        return response()->noContent();
    }
}
```

- [ ] **Step 3:** Create `app/Plugins/Sermons/Controllers/SpeakerController.php`

```php
<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\Speaker;
use App\Plugins\Sermons\Requests\ModifySpeaker;
use App\Plugins\Sermons\Services\CrupdateSpeaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SpeakerController extends Controller
{
    public function __construct(
        private CrupdateSpeaker $crupdate,
    ) {}

    public function index(): JsonResponse
    {
        $speakers = Speaker::withCount('sermons')
            ->orderBy('name')
            ->paginate(50);

        return response()->json($speakers);
    }

    public function show(Speaker $speaker): JsonResponse
    {
        $speaker->loadCount('sermons');

        return response()->json(['speaker' => $speaker]);
    }

    public function store(ModifySpeaker $request): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['speaker' => $speaker], 201);
    }

    public function update(ModifySpeaker $request, Speaker $speaker): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker = $this->crupdate->execute($request->validated(), $speaker);

        return response()->json(['speaker' => $speaker]);
    }

    public function destroy(Speaker $speaker): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker->sermons()->update(['speaker_id' => null]);
        $speaker->delete();

        return response()->noContent();
    }
}
```

- [ ] **Step 4:** Create `app/Plugins/Sermons/Routes/api.php`

```php
<?php

use App\Plugins\Sermons\Controllers\SermonController;
use App\Plugins\Sermons\Controllers\SermonSeriesController;
use App\Plugins\Sermons\Controllers\SpeakerController;
use Illuminate\Support\Facades\Route;

// Sermon CRUD
Route::get('sermons', [SermonController::class, 'index']);
Route::get('sermons/{sermon}', [SermonController::class, 'show']);

Route::middleware('permission:sermons.create')->group(function () {
    Route::post('sermons', [SermonController::class, 'store']);
});

Route::put('sermons/{sermon}', [SermonController::class, 'update']);
Route::delete('sermons/{sermon}', [SermonController::class, 'destroy']);

// Sermon Series
Route::get('sermon-series', [SermonSeriesController::class, 'index']);
Route::get('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'show']);
Route::post('sermon-series', [SermonSeriesController::class, 'store']);
Route::put('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'update']);
Route::delete('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'destroy']);

// Speakers
Route::get('speakers', [SpeakerController::class, 'index']);
Route::get('speakers/{speaker}', [SpeakerController::class, 'show']);
Route::post('speakers', [SpeakerController::class, 'store']);
Route::put('speakers/{speaker}', [SpeakerController::class, 'update']);
Route::delete('speakers/{speaker}', [SpeakerController::class, 'destroy']);
```

- [ ] **Step 5:** Create `app/Plugins/Sermons/Database/Seeders/SermonPermissionSeeder.php`

```php
<?php

namespace App\Plugins\Sermons\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class SermonPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'sermons' => [
                'sermons.view' => 'View Sermons',
                'sermons.create' => 'Upload Sermons',
                'sermons.update' => 'Edit Own Sermons',
                'sermons.update_any' => 'Edit Any Sermon',
                'sermons.delete' => 'Delete Own Sermons',
                'sermons.delete_any' => 'Delete Any Sermon',
                'sermons.manage_series' => 'Manage Sermon Series',
                'sermons.manage_speakers' => 'Manage Speakers',
                'sermons.feature' => 'Feature Sermons',
                'sermons.download' => 'Download Sermon Audio',
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
            'sermons.view', 'sermons.download',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'sermons.view', 'sermons.create', 'sermons.update', 'sermons.update_any',
            'sermons.delete', 'sermons.delete_any', 'sermons.download',
            'sermons.manage_series', 'sermons.manage_speakers',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'sermons')->pluck('id');

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

- [ ] **Step 6:** Verify: `php -l app/Plugins/Sermons/Controllers/*.php && php -l app/Plugins/Sermons/Routes/api.php && php -l app/Plugins/Sermons/Database/Seeders/SermonPermissionSeeder.php`

---

### Task 10: AppServiceProvider + morph map + plugin routes
**Files:** `app/Providers/AppServiceProvider.php`, `routes/api.php`, `common/foundation/src/Reactions/Controllers/ReactionController.php`, `common/foundation/src/Comments/Controllers/CommentController.php`, `common/foundation/src/Comments/Requests/ModifyComment.php`

- [ ] **Step 1:** In `app/Providers/AppServiceProvider.php`:

Add imports:
```php
use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Policies\EventPolicy;
use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Policies\SermonPolicy;
```

Add policy registrations:
```php
Gate::policy(Event::class, EventPolicy::class);
Gate::policy(Sermon::class, SermonPolicy::class);
```

Expand morph map:
```php
Relation::enforceMorphMap([
    'post' => Post::class,
    'comment' => Comment::class,
    'group' => Group::class,
    'event' => Event::class,
    'sermon' => Sermon::class,
]);
```

- [ ] **Step 2:** In `routes/api.php`, add after the Groups plugin block:

```php
        // Events Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('events')) {
            require app_path('Plugins/Events/Routes/api.php');
        }

        // Sermons Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('sermons')) {
            require app_path('Plugins/Sermons/Routes/api.php');
        }
```

- [ ] **Step 3:** Update reaction/comment allowlists to include `event` and `sermon`:

In `ReactionController.php`, change:
```php
'reactable_type' => 'required|string|in:post,comment',
```
To:
```php
'reactable_type' => 'required|string|in:post,comment,event,sermon',
```

In `CommentController.php` (`index` method), change:
```php
'commentable_type' => 'required|string|in:post',
```
To:
```php
'commentable_type' => 'required|string|in:post,event,sermon',
```

In `ModifyComment.php`, change:
```php
'commentable_type' => 'required_without:parent_id|string|in:post',
```
To:
```php
'commentable_type' => 'required_without:parent_id|string|in:post,event,sermon',
```

- [ ] **Step 4:** Verify: `php -l app/Providers/AppServiceProvider.php && php -l routes/api.php && php -l common/foundation/src/Reactions/Controllers/ReactionController.php && php -l common/foundation/src/Comments/Controllers/CommentController.php && php -l common/foundation/src/Comments/Requests/ModifyComment.php`

---

### Task 11: Factories + Tests
**Files:** Factories and 5 test files

- [ ] **Step 1:** Create `database/factories/EventFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'is_active' => true,
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['start_date' => fake()->dateTimeBetween('-30 days', '-1 day')]);
    }

    public function withMeetingUrl(): static
    {
        return $this->state(fn () => ['meeting_url' => 'https://zoom.us/j/' . fake()->randomNumber(9)]);
    }
}
```

- [ ] **Step 2:** Create `database/factories/SermonFactory.php`

```php
<?php

namespace Database\Factories;

use App\Plugins\Sermons\Models\Sermon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SermonFactory extends Factory
{
    protected $model = Sermon::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'speaker' => fake()->name(),
            'sermon_date' => fake()->date(),
            'is_active' => true,
            'is_published' => true,
            'author_id' => \App\Models\User::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function withAudio(): static
    {
        return $this->state(fn () => [
            'audio_url' => 'https://example.com/sermons/' . fake()->uuid() . '.mp3',
            'duration_minutes' => fake()->numberBetween(15, 60),
        ]);
    }

    public function withVideo(): static
    {
        return $this->state(fn () => [
            'video_url' => 'https://youtube.com/watch?v=' . fake()->lexify('???????????'),
        ]);
    }
}
```

- [ ] **Step 3:** Create `tests/Feature/Events/EventCrudTest.php` — 6 tests

```php
<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Events\Database\Seeders\EventPermissionSeeder::class);
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

    public function test_member_can_list_events(): void
    {
        $user = $this->memberUser();
        Event::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_event(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/events', [
            'title' => 'Sunday Worship',
            'description' => 'Weekly worship service',
            'start_date' => now()->addWeek()->toDateTimeString(),
            'location' => 'Main Sanctuary',
            'meeting_url' => 'https://zoom.us/j/123456789',
        ])->assertCreated()
            ->assertJsonPath('event.title', 'Sunday Worship')
            ->assertJsonPath('event.meeting_url', 'https://zoom.us/j/123456789');
    }

    public function test_member_cannot_create_event(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/events', [
            'title' => 'Test',
            'description' => 'Test',
            'start_date' => now()->addWeek()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_admin_can_update_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('event.title', 'Updated Title');
    }

    public function test_admin_can_delete_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/events/{$event->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_admin_can_feature_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create(['is_featured' => false]);

        $this->actingAs($admin)->patchJson("/api/v1/events/{$event->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }
}
```

- [ ] **Step 4:** Create `tests/Feature/Events/EventRsvpTest.php` — 5 tests

```php
<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Models\EventRsvp;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRsvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Events\Database\Seeders\EventPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    public function test_member_can_rsvp_attending(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'attending',
        ])->assertOk()
            ->assertJsonPath('rsvp.status', 'attending');
    }

    public function test_member_can_change_rsvp_status(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();
        EventRsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'interested',
        ])->assertOk()
            ->assertJsonPath('rsvp.status', 'interested');

        $this->assertDatabaseHas('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'interested',
        ]);
    }

    public function test_member_can_cancel_rsvp(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();
        EventRsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/events/{$event->id}/rsvp")
            ->assertOk();

        $this->assertDatabaseMissing('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_rsvp_counts_returned(): void
    {
        $event = Event::factory()->create();
        $users = collect(range(1, 3))->map(fn () => $this->memberUser());

        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[0]->id, 'status' => 'attending']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[1]->id, 'status' => 'attending']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[2]->id, 'status' => 'interested']);

        $this->actingAs($users[0])->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.rsvp_counts.attending', 2)
            ->assertJsonPath('event.rsvp_counts.interested', 1);
    }

    public function test_attendees_list_ordered_by_status(): void
    {
        $event = Event::factory()->create();
        $user1 = $this->memberUser();
        $user2 = $this->memberUser();

        EventRsvp::create(['event_id' => $event->id, 'user_id' => $user1->id, 'status' => 'interested']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $user2->id, 'status' => 'attending']);

        $response = $this->actingAs($user1)->getJson("/api/v1/events/{$event->id}/attendees")
            ->assertOk();

        // Attending should come first
        $data = $response->json('data');
        $this->assertEquals('attending', $data[0]['status']);
        $this->assertEquals('interested', $data[1]['status']);
    }
}
```

- [ ] **Step 5:** Create `tests/Feature/Events/EventPolicyTest.php` — 4 tests

```php
<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Events\Database\Seeders\EventPermissionSeeder::class);
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

    public function test_member_can_view_event(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->getJson("/api/v1/events/{$event->id}")
            ->assertOk();
    }

    public function test_member_cannot_update_others_event(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_member_can_rsvp(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'attending',
        ])->assertOk();
    }

    public function test_super_admin_can_manage_any_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Admin Updated',
        ])->assertOk();

        $this->actingAs($admin)->deleteJson("/api/v1/events/{$event->id}")
            ->assertNoContent();
    }
}
```

- [ ] **Step 6:** Create `tests/Feature/Sermons/SermonCrudTest.php` — 6 tests

```php
<?php

namespace Tests\Feature\Sermons;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SermonCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Sermons\Database\Seeders\SermonPermissionSeeder::class);
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

    public function test_member_can_list_sermons(): void
    {
        $user = $this->memberUser();
        Sermon::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/sermons')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_sermon(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/sermons', [
            'title' => 'The Good Shepherd',
            'speaker' => 'Pastor John',
            'sermon_date' => '2026-03-28',
            'scripture_reference' => 'John 10:1-18',
            'audio_url' => 'https://example.com/sermon.mp3',
        ])->assertCreated()
            ->assertJsonPath('sermon.title', 'The Good Shepherd')
            ->assertJsonPath('sermon.speaker', 'Pastor John');
    }

    public function test_member_cannot_create_sermon(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/sermons', [
            'title' => 'Test',
            'speaker' => 'Test',
        ])->assertForbidden();
    }

    public function test_admin_can_update_sermon(): void
    {
        $admin = $this->adminUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/sermons/{$sermon->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('sermon.title', 'Updated Title');
    }

    public function test_admin_can_delete_sermon(): void
    {
        $admin = $this->adminUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/sermons/{$sermon->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sermons', ['id' => $sermon->id]);
    }

    public function test_show_sermon_increments_view_count(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create(['view_count' => 0]);

        $this->actingAs($user)->getJson("/api/v1/sermons/{$sermon->id}")
            ->assertOk();

        $this->assertEquals(1, $sermon->fresh()->view_count);
    }
}
```

- [ ] **Step 7:** Create `tests/Feature/Sermons/SermonPolicyTest.php` — 4 tests

```php
<?php

namespace Tests\Feature\Sermons;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\SermonSeries;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SermonPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Sermons\Database\Seeders\SermonPermissionSeeder::class);
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

    public function test_member_can_view_sermon(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($user)->getJson("/api/v1/sermons/{$sermon->id}")
            ->assertOk();
    }

    public function test_member_cannot_update_sermon(): void
    {
        $user = $this->memberUser();
        $sermon = Sermon::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/sermons/{$sermon->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_member_cannot_manage_series(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/sermon-series', [
            'name' => 'Test Series',
        ])->assertForbidden();
    }

    public function test_admin_can_manage_series_and_speakers(): void
    {
        $admin = $this->adminUser();

        // Create series
        $this->actingAs($admin)->postJson('/api/v1/sermon-series', [
            'name' => 'Gospel of John',
        ])->assertCreated();

        // Create speaker
        $this->actingAs($admin)->postJson('/api/v1/speakers', [
            'name' => 'Pastor Sarah',
            'bio' => 'Lead pastor and teacher',
        ])->assertCreated();
    }
}
```

- [ ] **Step 8:** Verify: `php -l database/factories/EventFactory.php && php -l database/factories/SermonFactory.php && php -l tests/Feature/Events/*.php && php -l tests/Feature/Sermons/*.php`

---

### Task 12: React — Events frontend
**Files:** `resources/client/plugins/events/queries.ts`, event pages and components

- [ ] **Step 1:** Create `resources/client/plugins/events/queries.ts`

Hooks: `useEvents` (paginated, filterable), `useEvent` (detail), `useCreateEvent`, `useUpdateEvent`, `useDeleteEvent`, `useRsvp`, `useCancelRsvp`, `useEventAttendees`.

- [ ] **Step 2:** Create `resources/client/plugins/events/components/EventCard.tsx`

Displays: title, date/time, location, attendee count, meeting link badge, image. Links to detail page.

- [ ] **Step 3:** Create `resources/client/plugins/events/components/RsvpButton.tsx`

Three-state toggle: Attending (green), Interested (blue), Not Going (gray). Clicking a state either sets it or cancels if already selected. Uses `useRsvp` and `useCancelRsvp` mutations.

- [ ] **Step 4:** Create `resources/client/plugins/events/pages/EventsPage.tsx`

List view with tabs: Upcoming / All / Featured. Date range filter. Infinite scroll. Uses `useEvents` with tab-derived params.

- [ ] **Step 5:** Create `resources/client/plugins/events/pages/EventDetailPage.tsx`

Shows: title, date range, location (with `location_url` link), meeting URL (with "Join Meeting" button), description, RSVP button, attendee count badges, comment thread, reaction bar.

---

### Task 13: React — Sermons frontend + global audio player
**Files:** Sermons components + audio player

- [ ] **Step 1:** Create `resources/client/plugins/sermons/queries.ts`

Hooks: `useSermons` (paginated, filterable), `useSermon` (detail), `useCreateSermon`, `useUpdateSermon`, `useDeleteSermon`, `useSermonSeries` (list), `useSpeakers` (list).

- [ ] **Step 2:** Create `resources/client/common/audio-player/audio-player-store.ts`

Zustand store for persistent player state:
```typescript
import {create} from 'zustand';

interface AudioPlayerState {
  currentSermon: {id: number; title: string; speaker: string; audioUrl: string} | null;
  isPlaying: boolean;
  play: (sermon: {id: number; title: string; speaker: string; audioUrl: string}) => void;
  pause: () => void;
  resume: () => void;
  stop: () => void;
}

export const useAudioPlayerStore = create<AudioPlayerState>((set) => ({
  currentSermon: null,
  isPlaying: false,
  play: (sermon) => set({currentSermon: sermon, isPlaying: true}),
  pause: () => set({isPlaying: false}),
  resume: () => set({isPlaying: true}),
  stop: () => set({currentSermon: null, isPlaying: false}),
}));
```

- [ ] **Step 3:** Create `resources/client/common/audio-player/AudioPlayerBar.tsx`

Sticky bottom bar rendered in the app shell (outside routes). Only visible when `currentSermon` is set. Features: play/pause toggle, sermon title + speaker, progress bar (HTML5 Audio `timeupdate`), close button.

> **Implementation note:** The HTML5 `<audio>` element is rendered by this component with a `ref`. `useEffect` listens to `isPlaying` state changes and calls `audio.play()` / `audio.pause()`. The `src` is `currentSermon.audioUrl`. When a different sermon is selected, the `<audio>` element re-renders with a new `src`.

- [ ] **Step 4:** Create `resources/client/plugins/sermons/components/SermonCard.tsx`

Displays: title, speaker, date, duration, scripture reference, series badge. Play button (triggers `useAudioPlayerStore.play()`).

- [ ] **Step 5:** Create `resources/client/plugins/sermons/components/SermonPlayer.tsx`

Inline player for detail page. Shows video embed (if `video_url`) or audio waveform placeholder. Play button integrates with global audio player store.

- [ ] **Step 6:** Create `resources/client/plugins/sermons/pages/SermonsPage.tsx`

List view with search bar, speaker filter, series filter. Infinite scroll. Uses `useSermons`.

- [ ] **Step 7:** Create `resources/client/plugins/sermons/pages/SermonDetailPage.tsx`

Shows: title, speaker (links to speaker page if `speaker_id`), date, scripture, series badge (links to series page), description/content, inline player (SermonPlayer), reaction bar, comment thread. Download button (if `pdf_notes`).

- [ ] **Step 8:** Create `resources/client/plugins/sermons/pages/SermonSeriesPage.tsx`

Shows series info (name, description, image) + paginated list of sermons in that series.

---

### Task 14: Router + sidebar updates
**Files:** `resources/client/app-router.tsx`, `resources/client/admin/AdminLayout.tsx`, `resources/client/main.tsx` (or app shell)

- [ ] **Step 1:** In `app-router.tsx`:

Add lazy imports for all new pages:
```tsx
const EventsPage = lazy(() => import('./plugins/events/pages/EventsPage').then(m => ({default: m.EventsPage})));
const EventDetailPage = lazy(() => import('./plugins/events/pages/EventDetailPage').then(m => ({default: m.EventDetailPage})));
const SermonsPage = lazy(() => import('./plugins/sermons/pages/SermonsPage').then(m => ({default: m.SermonsPage})));
const SermonDetailPage = lazy(() => import('./plugins/sermons/pages/SermonDetailPage').then(m => ({default: m.SermonDetailPage})));
const SermonSeriesPage = lazy(() => import('./plugins/sermons/pages/SermonSeriesPage').then(m => ({default: m.SermonSeriesPage})));
```

Add routes inside `<RequireAuth />`:
```tsx
<Route path="/events" element={<EventsPage />} />
<Route path="/events/:eventId" element={<EventDetailPage />} />
<Route path="/sermons" element={<SermonsPage />} />
<Route path="/sermons/:sermonId" element={<SermonDetailPage />} />
<Route path="/sermon-series/:seriesId" element={<SermonSeriesPage />} />
```

- [ ] **Step 2:** In `AdminLayout.tsx`, add sidebar items after Groups:

```tsx
{ label: 'Events', path: '/events', icon: 'Calendar', permission: 'events.view' },
{ label: 'Sermons', path: '/sermons', icon: 'Mic', permission: 'sermons.view' },
```

- [ ] **Step 3:** Add `<AudioPlayerBar />` to the app shell so it persists across navigation. In `main.tsx` (or wherever `<BrowserRouter>` is rendered), add the component after `<AppRouter />`:

```tsx
import {AudioPlayerBar} from './common/audio-player/AudioPlayerBar';

// Inside the JSX:
<AppRouter />
<AudioPlayerBar />
```

---

## Execution Notes

### Task dependency order
- Tasks 1-2 (migrations) are independent — can run in parallel
- Tasks 3-4 (models) depend on respective migrations
- Tasks 5-6 (services) depend on respective models
- Task 7 (policies + requests) depends on models
- Tasks 8-9 (controllers + routes + seeders) depend on services + policies
- Task 10 (AppServiceProvider) depends on models + policies
- Task 11 (tests) depends on all backend tasks
- Tasks 12-13 (React frontend) depend on backend + are independent of each other
- Task 14 (router) depends on frontend pages

**Suggested execution order:**
1. Tasks 1, 2 in parallel (migrations)
2. Tasks 3, 4 in parallel (models)
3. Tasks 5, 6, 7 in parallel (services + policies/requests)
4. Tasks 8, 9 in parallel (controllers + routes + seeders)
5. Task 10 (AppServiceProvider + morph map + route registration)
6. Task 11 (factories + tests)
7. Tasks 12, 13 in parallel (React frontend)
8. Task 14 (router + sidebar)

### Key design decisions
- **Event RSVPs vs Registrations:** Two separate systems. `event_registrations` (legacy) handles guest/public registration with contact info. `event_rsvps` (new) handles authenticated member RSVP with a simple status toggle. Both can coexist on the same event.
- **Sermon speaker/series normalization:** The existing string columns (`speaker`, `series`) remain for backward compatibility and free-text entry. The new FK columns (`speaker_id`, `series_id`) are optional — they link to the normalized tables when a proper profile exists. The API returns both the string and the related model.
- **Persistent audio player:** Global Zustand store + `<AudioPlayerBar />` in the app shell. The store tracks `{currentSermon, isPlaying}`. Any component can call `useAudioPlayerStore.getState().play(sermon)` to start playback. The bar renders only when `currentSermon` is non-null.
- **No file uploads in this plan:** Sermon thumbnails, PDF notes, and event images are stored as URL strings (not uploaded files). File upload integration comes with the Files plugin in Phase 2.

### What this plan does NOT cover
- `.ics` calendar export — deferred to a future enhancement (requires `spatie/icalendar-generator` package)
- Calendar month/week views — the API supports date range filtering (`from`/`to` params) but the React calendar component (month grid) is deferred
- Sermon download tracking / analytics — deferred
- Video embedding (YouTube/Vimeo) — the `video_url` is stored but no embed player is built; the detail page shows a link
- Recurring event expansion (generating future instances from recurrence_pattern) — deferred
- Sermon search via Meilisearch — uses SQL LIKE for now; Meilisearch integration comes in Phase 2
