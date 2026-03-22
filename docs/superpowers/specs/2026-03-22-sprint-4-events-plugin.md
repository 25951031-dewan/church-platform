# Sprint 4 — Church Events Plugin Design Spec

## Goal

Full-featured church events: create and discover events, RSVP (going/maybe/not-going), recurring events (weekly service), online/hybrid support, event discussion threads, and 24-hour reminder notifications.

## Architecture

### New Plugin: `plugins/Event/`

**Database tables:**

`events`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| church_id | FK → churches nullOnDelete nullable | |
| community_id | FK → communities nullOnDelete nullable | |
| created_by | FK → users restrictOnDelete | |
| title | string(200) | |
| description | text nullable | |
| cover_image | string nullable | URL |
| start_at | datetime | |
| end_at | datetime | |
| location | string(300) nullable | Address text |
| latitude | decimal(10,7) nullable | |
| longitude | decimal(10,7) nullable | |
| is_online | bool default false | |
| meeting_url | string nullable | Zoom / Meet / Teams link |
| is_recurring | bool default false | |
| recurrence_rule | string nullable | iCal RRULE string, e.g. `FREQ=WEEKLY;BYDAY=SU` |
| recurrence_parent_id | FK → events nullOnDelete nullable | Child occurrences point to parent. `nullOnDelete` so parent soft-delete does not cascade-delete children. |
| category | enum | `worship`, `youth`, `outreach`, `study`, `fellowship`, `other` |
| max_attendees | int nullable | null = unlimited. Caps `going` count only. `maybe` RSVPs are always allowed. |
| going_count | int default 0 | Denormalised |
| maybe_count | int default 0 | Denormalised |
| status | enum | `published`, `draft`, `cancelled` |
| reminder_sent_at | timestamp nullable | Set when 24h reminders dispatched |
| created_at/updated_at | timestamps | |
| deleted_at | timestamp nullable | Soft deletes |

Indexes: `(church_id, start_at)`, `(community_id, start_at)`, `(start_at, status)`, `(reminder_sent_at, status)`.

`event_attendees`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| event_id | FK → events cascadeOnDelete | |
| user_id | FK → users cascadeOnDelete | |
| status | enum | `going`, `maybe`, `not_going` |
| created_at/updated_at | timestamps | |
| UNIQUE | (event_id, user_id) | One RSVP per user per event |

**`is_multi_day` — not a column.** Computed as a virtual model attribute: `$event->is_multi_day` returns `Carbon::parse($this->start_at)->toDateString() !== Carbon::parse($this->end_at)->toDateString()`. No storage, no staleness risk.

### Discussion Thread

Each event has a scoped discussion using `social_posts` with `type = 'event_post'`. To avoid a slow JSON path scan on `meta->event_id`, a **migration adds a generated column** to `social_posts`:

```sql
ALTER TABLE social_posts
  ADD COLUMN event_id BIGINT UNSIGNED NULL GENERATED ALWAYS AS (CAST(meta->>'$.event_id' AS UNSIGNED)) STORED,
  ADD INDEX idx_social_posts_event_id (event_id);
```

This generated column is added by a migration in the **Event plugin** (no changes to the Post plugin's own migration). Posts in the event thread are standard `Post` model instances queryable via `Post::where('type', 'event_post')->where('event_id', $id)`. The `CommentThread` component on the frontend is reused — it fetches from `GET /events/{id}/posts` which returns the same paginated post shape.

### API Endpoints

All under `/api/v1`:

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/events` | optional | List; query: `church_id`, `community_id`, `category`, `from` (date), `to` (date), `scope=upcoming\|past`. "Upcoming" = `start_at > NOW()`. "Past" = `end_at < NOW()`. Events in progress appear in both. |
| GET | `/events/{id}` | optional | Single event. `meeting_url` **only returned** when user is authenticated AND has a `going` RSVP. Redacted (null) otherwise. |
| POST | `/events` | sanctum | Create event. **Authorization:** any authenticated user may create a platform-wide event. To scope to a `community_id`, user must be a `community_members` record with `role IN (admin, moderator)` for that community. To scope to a `church_id`, user must be a `church_members` record with `role = admin`. |
| PATCH | `/events/{id}` | sanctum | Owner (`created_by`) or church/community admin. |
| DELETE | `/events/{id}` | sanctum | Owner (`created_by`) or **platform admin** (User with `is_admin = true` on the `users` table). |
| POST | `/events/{id}/rsvp` | sanctum | Body: `{status: going\|maybe\|not_going}`. Updates existing RSVP or creates. Adjusts counters atomically. `going` status checks `max_attendees`. |
| DELETE | `/events/{id}/rsvp` | sanctum | Remove own RSVP. Adjusts counters. |
| GET | `/events/{id}/attendees` | optional | Paginated `going` attendees (name + avatar). |
| GET | `/events/{id}/posts` | optional | Event discussion thread, paginated 20/page. |
| POST | `/events/{id}/posts` | sanctum | Post to event thread. Open to **any authenticated user** — no RSVP required. Creates `social_posts` row with `type=event_post`, `meta.event_id`. |

### Authorization — "Owner/Admin" Defined

For `PATCH` and `DELETE`:
- **Owner**: `events.created_by = auth()->id()`
- **Community admin**: `community_members` row for the event's `community_id` with `role IN (admin, moderator)` and `status = approved`
- **Church admin**: `church_members` row for the event's `church_id` with `role = admin`. A church admin cannot edit/delete an event that has `church_id = null` (i.e., a community-only or platform-wide event) — the church admin check is scoped to the specific `church_id` on the event row.
- **Platform admin**: `users.is_admin = true`

**Asymmetry note:** Only Owner and Platform admin can `DELETE`. Community admin and Church admin can only `PATCH`. This is intentional — deletion is a destructive action reserved for owners and platform staff.

### Recurring Events

When `is_recurring = true`, the parent event stores the RRULE. Occurrences within a requested date window are **generated on-the-fly** by `RecurrenceExpander` (using `recurr/recurr` library) — not persisted.

**On first interaction with a virtual occurrence (RSVP, comment, edit), it is materialised:**
```
RecurrenceExpander::materialise(Event $parent, Carbon $occurrenceDate): Event
```
Creates a concrete `events` row with `recurrence_parent_id = $parent->id` and `start_at`/`end_at` from the occurrence. RSVPs, comments, and reminders all operate on this materialised row.

**Editing an occurrence:** If the organiser edits only "this occurrence", a materialised row is created (if not already) and updated. Editing "all future occurrences" updates the parent RRULE **and deletes all materialised child rows whose `start_at > NOW()`** — they will be re-generated on-the-fly from the updated RRULE. Already-passed materialised occurrences are preserved for historical record.

### RSVP Counter Consistency

`EventRsvpController@update` wraps all changes in `DB::transaction` with `lockForUpdate()` on the event row:

1. Load event `lockForUpdate()`
2. Check `max_attendees` only when new status is `going`: if `going_count >= max_attendees` → 422 "Event is full"
3. Find existing RSVP row (if any)
4. Decrement old status counter (if changing from an existing status)
5. Insert/update RSVP row
6. Increment new status counter (unless `not_going` — that counter is not tracked)
7. Commit

`not_going` status: stored in `event_attendees` for the user's own reference, but no counter column exists for it (intentional).

### Reminders

`SendEventRemindersJob` scheduled every 15 minutes. Queries:
```sql
SELECT * FROM events
WHERE start_at BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
  AND reminder_sent_at IS NULL
  AND status = 'published'
  AND deleted_at IS NULL
```
For each event, dispatches `EventReminderNotification` in **chunks of 100** (`going` attendees loaded via `chunk(100)`) to avoid memory issues on large events. Sets `reminder_sent_at` after dispatching all chunks.

`EventReminderNotification` uses Laravel's database notification channel (stored in `notifications` table) and optionally push (future sprint).

## File Structure

```
plugins/Event/
  EventServiceProvider.php
  plugin.json                   { "name": "Event", "slug": "event", "version": "1.0.0", "requires": [] }
  Models/
    Event.php                   (SoftDeletes, is_multi_day virtual attribute, recurring helpers)
    EventAttendee.php
  Services/
    RecurrenceExpander.php
  Controllers/
    EventController.php
    EventRsvpController.php
    EventPostController.php
  Jobs/
    SendEventRemindersJob.php
  Notifications/
    EventReminderNotification.php
  Policies/
    EventPolicy.php             (can: view, create, update, delete, post)
  database/migrations/
    2026_04_20_000001_create_events_table.php
    2026_04_20_000002_create_event_attendees_table.php
    2026_04_20_000003_add_event_id_generated_column_to_social_posts.php
  routes/
    api.php
resources/js/plugins/events/
  EventsPage.tsx          (calendar + list toggle, filter bar by category)
  EventCard.tsx           (compact card: cover, title, date, location/online badge, RSVP buttons)
  EventDetailPage.tsx     (full detail: SafeHtml description, RSVP section, meeting URL if going, map, thread)
  CreateEventForm.tsx     (4-step: details → schedule/recurring → location/online → publish)
  EventCalendar.tsx       (month/week grid using CSS grid, no external calendar library)
tests/Feature/
  EventTest.php
  EventRsvpTest.php
  EventReminderTest.php
```

## Frontend

**EventsPage:** Toggle: calendar grid / list. Filter bar: All | Worship | Youth | Outreach | Study | Fellowship. `GET /api/v1/events` with params.

**EventCard:** Cover image, title, date range (single day vs multi-day), location/online badge, going count, three RSVP quick-action buttons. "Going" shown as filled/active when user has going RSVP.

**EventDetailPage:** Cover banner, `<SafeHtml>` description, RSVP buttons with counts, "Join meeting" button (only rendered if `meeting_url` present in response), attendee avatar strip (max 10 + count), map placeholder (if lat/lng), discussion thread using `CommentThread` component fetching from `GET /events/{id}/posts`.

**CreateEventForm:** Step 1: title, description, cover image, category. Step 2: start/end datetime, multi-day toggle (auto-detected), recurring toggle → RRULE builder (frequency: daily/weekly/monthly, day-of-week picker for weekly). Step 3: location text OR online toggle + meeting URL. Step 4: max attendees (optional), review, publish.

## Testing

**EventTest:**
- Create event (authenticated) → 201
- List with `church_id` filter → only those events
- `GET /events/{id}` unauthenticated → `meeting_url` is null
- `GET /events/{id}` as going attendee → `meeting_url` present
- Community member (non-admin) cannot create event scoped to that community → 403
- Community admin can create event scoped to their community → 201
- Non-owner cannot PATCH → 403

**EventRsvpTest:**
- RSVP going increments `going_count`
- Change from going to maybe: `going_count` decrements, `maybe_count` increments (atomic)
- RSVP going when `going_count >= max_attendees` → 422
- RSVP maybe when event full → 201 (maybe is always allowed)
- Remove RSVP decrements correct counter
- `not_going` RSVP does not change any counter

**EventReminderTest:**
- Job sends only to `going` attendees
- Sets `reminder_sent_at`, does not re-send
- Does not fire for cancelled events
- Does not fire for past events (start_at already passed)
