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
| recurrence_parent_id | FK → events nullable | Child occurrences point to parent. FK has NO ON DELETE rule (RESTRICT default) — materialised occurrences must be explicitly deleted before parent. Soft-delete on parent does not trigger FK. |
| category | enum | `worship`, `youth`, `outreach`, `study`, `fellowship`, `other` |
| max_attendees | int nullable | null = unlimited. Caps `going` count only. `maybe` RSVPs are always allowed. |
| going_count | int default 0 | Denormalised |
| maybe_count | int default 0 | Denormalised |
| status | enum | `published`, `draft`, `cancelled` |
| reminder_sent_at | timestamp nullable | Set atomically **before** dispatching reminder chunks to prevent duplicate sends. |
| created_at/updated_at | timestamps | |
| deleted_at | timestamp nullable | Soft deletes |

Indexes: `(church_id, start_at)`, `(community_id, start_at)`, `(start_at, status)`, `(reminder_sent_at, status)`.

> **MySQL version requirement:** The generated column migration (see Discussion Thread below) uses `meta->>'$.event_id'` (JSON unquoting operator), which requires MySQL 5.7.13+. Document this in `README.md`. MariaDB is not supported for this feature.

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

This generated column is added by a migration in the **Event plugin** (no changes to the Post plugin's own migration). Posts in the event thread are standard `Post` model instances queryable via `Post::where('type', 'event_post')->where('event_id', $id)`.

**Migration `down()` method:** `ALTER TABLE social_posts DROP INDEX idx_social_posts_event_id, DROP COLUMN event_id;` — this must be run before uninstalling the Event plugin.

**Frontend thread component:** The existing `CommentThread` component has hardcoded API paths (`/api/v1/posts/{id}/comments`, `/api/v1/comments`). It **must be extended** with an optional `postsEndpoint` prop in this sprint:
```tsx
// CommentThread.tsx — new prop
interface Props {
  postId: number;
  postsEndpoint?: string;  // defaults to `/api/v1/posts/${postId}/comments` if omitted
  storeEndpoint?: string;  // defaults to `/api/v1/comments`
}
```
`EventDetailPage` passes `postsEndpoint="/api/v1/events/{id}/posts"` and `storeEndpoint="/api/v1/events/{id}/posts"`. The event thread uses `social_posts` rows (each a top-level post), not `comments` rows. The `CommentThread` component's submit path for events calls `POST /events/{id}/posts` which creates a `social_posts` row, not a `comments` row.

### API Endpoints

All under `/api/v1`:

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/events` | optional | List; query: `church_id`, `community_id`, `category`, `from` (date), `to` (date), `scope=upcoming\|past`. "Upcoming" = `start_at > NOW()`. "Past" = `end_at < NOW()`. Events in progress appear in both. Virtual recurring occurrences are included in the list with `id` formatted as `"parent:{parent_id}:{date}"` (see Recurring Events). |
| GET | `/events/{id}` | optional | Single event. Accepts both numeric DB id and virtual occurrence identifier `parent:{id}:{date}` — the latter auto-materialises on access. `meeting_url` **only returned** when user is authenticated AND has a `going` RSVP. Redacted (null) otherwise. |
| POST | `/events` | sanctum | Create event. **Authorization:** any authenticated user may create a platform-wide event. To scope to a `community_id`, user must be a `community_members` record with `role IN (admin, moderator)` and `status = approved`. To scope to a `church_id`, user must be a `church_members` record with `type = 'member'` AND `role = admin`. |
| PATCH | `/events/{id}` | sanctum | Owner (`created_by`) or church/community admin (see Authorization below). |
| DELETE | `/events/{id}` | sanctum | Owner (`created_by`) or **platform admin** (`users.is_admin = true`). **Note:** `is_admin` column added to `users` table in Sprint 3 migration. |
| POST | `/events/{id}/rsvp` | sanctum | Body: `{status: going\|maybe\|not_going}`. Updates existing RSVP or creates. Adjusts counters atomically. `going` status checks `max_attendees`. |
| DELETE | `/events/{id}/rsvp` | sanctum | Remove own RSVP. Adjusts counters (no-op if the removed RSVP was `not_going` — that status has no counter). |
| GET | `/events/{id}/attendees` | conditional | Paginated `going` attendees (name + avatar). **If the event belongs to a community with `privacy IN (closed, secret)`, requires `auth:sanctum` AND an approved `community_members` row** for that community. Otherwise public. |
| GET | `/events/{id}/posts` | optional | Event discussion thread, paginated 20/page. |
| POST | `/events/{id}/posts` | sanctum | Post to event thread. Open to **any authenticated user** — no RSVP required. Creates `social_posts` row with `type=event_post`, `meta.event_id`. |

### Authorization — "Owner/Admin" Defined

For `PATCH` and `DELETE`:
- **Owner**: `events.created_by = auth()->id()`
- **Community admin**: `community_members` row for the event's `community_id` with `role IN (admin, moderator)` and `status = approved`
- **Church admin**: `church_members` row for the event's `church_id` with `type = 'member'` AND `role = admin`. A church admin cannot edit/delete an event that has `church_id = null`.
- **Platform admin**: `users.is_admin = true` (column added in Sprint 3)

**Asymmetry:** Only Owner and Platform admin can `DELETE`. Community admin and Church admin can only `PATCH`. This is intentional.

### Recurring Events

When `is_recurring = true`, the parent event stores the RRULE. Occurrences within a requested date window are **generated on-the-fly** by `RecurrenceExpander` (using `recurr/recurr` library) — not persisted.

**Virtual occurrence identifier:** A virtual (non-materialised) occurrence is identified by a composite string `"parent:{parent_id}:{date}"` (date in `Y-m-d` format). This string is returned as the `id` field of the occurrence in list responses. The frontend uses this as the route parameter for `EventDetailPage` and RSVP calls. The `EventController` and `EventRsvpController` detect this format (regex `/^parent:\d+:\d{4}-\d{2}-\d{2}$/`) and call `RecurrenceExpander::materialise()` before processing.

**On first interaction with a virtual occurrence (RSVP, comment, edit), it is materialised:**
```
RecurrenceExpander::materialise(Event $parent, Carbon $occurrenceDate): Event
```
Creates a concrete `events` row with `recurrence_parent_id = $parent->id` and `start_at`/`end_at` from the occurrence. RSVPs, comments, and reminders all operate on this materialised row. If materialisation races (two simultaneous requests), the second request gets the already-materialised row via `firstOrCreate`.

**Editing an occurrence:** If the organiser edits only "this occurrence", a materialised row is created (if not already) and updated. Editing "all future occurrences" updates the parent RRULE **and deletes all materialised child rows whose `start_at > NOW()`**. The cascade `cascadeOnDelete` on `event_attendees.event_id` means RSVPs on deleted future occurrences are also deleted. **This is intentional** — users must re-RSVP after the schedule changes. A notification is sent to affected attendees: "The recurring event schedule was updated. Please re-RSVP for upcoming occurrences." Already-passed materialised occurrences are preserved for historical record.

### RSVP Counter Consistency

`EventRsvpController@update` wraps all changes in `DB::transaction` with `lockForUpdate()` on the event row:

1. Load event `lockForUpdate()`
2. Check `max_attendees` only when new status is `going`: if `going_count >= max_attendees` → 422 "Event is full"
3. Find existing RSVP row (if any)
4. Decrement old status counter — **only if the old status was `going` or `maybe`**. If old status was `not_going`, no decrement (there is no `not_going_count` column).
5. Insert/update RSVP row
6. Increment new status counter — **only if new status is `going` or `maybe`**. If new status is `not_going`, no increment.
7. Commit

`not_going` status: stored in `event_attendees` for the user's own reference, but no counter column exists. Removing a `not_going` RSVP (`DELETE /events/{id}/rsvp`) does not touch any counter.

### Reminders

`SendEventRemindersJob` scheduled every 15 minutes. Uses a **conditional atomic claim** to prevent duplicate sends:

```sql
-- Claim events to remind (atomic, prevents race with next scheduler invocation)
UPDATE events
SET reminder_sent_at = NOW()
WHERE start_at BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
  AND reminder_sent_at IS NULL
  AND status = 'published'
  AND deleted_at IS NULL
```

Then load the just-claimed events (`WHERE reminder_sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)`) and dispatch notifications. This prevents double-dispatch because `reminder_sent_at` is set **before** chunking attendees — any retry or parallel scheduler invocation finds `reminder_sent_at IS NOT NULL` and skips.

For each claimed event, dispatches `EventReminderNotification` in **chunks of 100** (`going` attendees loaded via `chunk(100)`) to avoid memory issues on large events.

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
    RecurrenceExpander.php      (on-the-fly generation + materialise() with firstOrCreate)
  Controllers/
    EventController.php         (handles virtual occurrence ID detection)
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
    2026_04_20_000003_add_event_id_generated_column_to_social_posts.php   (down: drop index + drop column)
  routes/
    api.php
resources/js/plugins/events/
  EventsPage.tsx          (calendar + list toggle, filter bar by category)
  EventCard.tsx           (compact card: cover, title, date, location/online badge, RSVP buttons; handles virtual occurrence id)
  EventDetailPage.tsx     (full detail: SafeHtml description, RSVP section, meeting URL if going, map, thread)
  CreateEventForm.tsx     (4-step: details → schedule/recurring → location/online → publish)
  EventCalendar.tsx       (month/week grid using CSS grid, no external calendar library)
resources/js/plugins/feed/
  CommentThread.tsx       (modified — add postsEndpoint + storeEndpoint props)
tests/Feature/
  EventTest.php
  EventRsvpTest.php
  EventReminderTest.php
```

## Frontend

**EventsPage:** Toggle: calendar grid / list. Filter bar: All | Worship | Youth | Outreach | Study | Fellowship. `GET /api/v1/events` with params.

**EventCard:** Cover image, title, date range (single day vs multi-day), location/online badge, going count, three RSVP quick-action buttons. "Going" shown as filled/active when user has going RSVP. Virtual occurrence id (`parent:{id}:{date}`) used as link target — backend materialises on click.

**EventDetailPage:** Cover banner, `<SafeHtml>` description, RSVP buttons with counts, "Join meeting" button (only rendered if `meeting_url` present in response), attendee avatar strip (max 10 + count), map placeholder (if lat/lng), discussion thread using modified `CommentThread` component with `postsEndpoint` and `storeEndpoint` props pointing to `/events/{id}/posts`.

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
- Virtual occurrence id in list → materialises on RSVP → becomes numeric id
- `GET /events/{id}/attendees` on private community event unauthenticated → 401

**EventRsvpTest:**
- RSVP going increments `going_count`
- Change from going to maybe: `going_count` decrements, `maybe_count` increments (atomic)
- RSVP going when `going_count >= max_attendees` → 422
- RSVP maybe when event full → 201 (maybe is always allowed)
- Remove RSVP decrements correct counter
- `not_going` RSVP does not change any counter
- `not_going` → `going` transition: no decrement attempt on `not_going_count`
- DELETE RSVP when status was `not_going` → 200, no counter change

**EventReminderTest:**
- Job sends only to `going` attendees
- Sets `reminder_sent_at` before dispatching, does not re-send
- Does not fire for cancelled events
- Does not fire for past events (start_at already passed)
- Concurrent job invocations do not double-send (atomic UPDATE claim)
