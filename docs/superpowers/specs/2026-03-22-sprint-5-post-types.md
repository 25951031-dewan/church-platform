# Sprint 5 — Unified Post Types Design Spec
## Prayer · Blessings · Polls · Bible Study

## Goal

Extend the existing `Post` plugin so members can create spiritually-typed posts — Prayer Requests, Blessings, Polls, and Bible Studies — in one unified feed with a filter bar. Everything reuses `social_posts` with the existing `type` and `meta` columns plus one new `poll_votes` table.

## Architecture

### Type System

The `social_posts.type` column (already exists, currently `'post'`) gains four new values:

| Type | `body` contains | `meta` contains |
|---|---|---|
| `post` | Post text (existing) | anything (existing) |
| `prayer` | Prayer request text | `{answered: bool, answered_at: timestamp\|null}` |
| `blessing` | Testimony text | `{scripture: string\|null}` |
| `poll` | Poll body/context text (optional) | `{question: string, options: [{id, text, votes_count}], ends_at: timestamp\|null, allow_multiple: bool}` |
| `bible_study` | Study body / reflection text | `{scripture: string, passage: string, study_guide: string\|null}` |

**Rule:** `body` always holds the human-written prose. `meta` always holds structured/typed data. This is the consistent split — no exceptions.

**Anonymous prayer:** `is_anonymous` maps to the top-level `social_posts.is_anonymous` column (already exists, not in `meta`). When `is_anonymous = true`, the API serializer returns `"author": null` and `PrayerCard` renders "Anonymous" as the author name. **This is a new serializer behaviour**: `PostResource` (or the array transform in `PostController`) must check `is_anonymous` and null out the `author` field. No test currently covers this — tests are added in Sprint 5.

**Non-shareable types:** Posts with `type = 'poll'` cannot be reshared. The `PostController@crossPost` method returns 422 "Poll posts cannot be reshared" when:
1. Called directly via the `POST /posts/{id}/cross-post` route (guard on the `crossPost` method).
2. Called internally from `store()` via `cross_post_targets` — the `store()` method itself must check `$request->type === 'poll' && $request->filled('cross_post_targets')` and return 422 before invoking `crossPost`. Both entry points must be guarded.

Prayer and blessing posts *can* be reshared. The reshare copies both `meta` AND `body` from the parent — `body` must be copied because `BlessingCard` and `PrayerCard` render the `body` field as primary content. The existing `crossPost` sets `body = null` on reshares; this sprint changes it to copy `body` from the parent when the parent `type` is not `post`.

**Prayer reactions:** The generic `POST /api/v1/reactions` endpoint remains callable on prayer posts (the spec does not block it). The "Pray 🙏" button in `PrayerCard` is a dedicated reaction with emoji `🙏`, equivalent to a standard reaction. The reaction bar is visually replaced with a single prominent "Pray 🙏" button, but the underlying mechanism is the same Reaction plugin endpoint.

### New Table: `poll_votes`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| post_id | FK → social_posts cascadeOnDelete | |
| user_id | FK → users cascadeOnDelete | |
| option_id | string(40) | Matches `meta.options[].id` (`opt_` + ULID, max 30 chars, fits in 40) |
| created_at | timestamp | |
| UNIQUE | (post_id, user_id, option_id) | Prevents duplicate vote per option. When `allow_multiple=false`, enforced at app level inside `lockForUpdate()` transaction (see Poll Vote Logic). |

Index: `(post_id, option_id)` for fast count queries.

**Vote counts — source of truth:** `poll_votes` rows are authoritative. `meta.options[].votes_count` is a **denormalised cache** updated inside a `lockForUpdate()` transaction. If counts diverge, a `RecalculatePollVotesJob` can recompute from `poll_votes`. The `GET /posts/{id}/votes` endpoint always returns counts from `poll_votes` (not meta), so the UI is always accurate even if meta diverges.

**Poll option immutability:** Once a poll has any `poll_votes` rows, its `meta.options` array may not be edited. `PostController@update` returns 422 "Cannot edit poll options after voting has begun" if the request includes `meta.options` and `poll_votes WHERE post_id = ?` returns any rows.

### New Migration: Index on `social_posts.type`

Sprint 5 adds a migration that adds an index on `social_posts.type` for feed filtering performance:

```php
$table->index('type');
```

This is a separate migration file in the Post plugin (`plugins/Post/database/migrations/2026_04_25_000002_add_type_index_to_social_posts.php`).

### Modified: Post Plugin

**`PostController@store`** extended:
1. Accept `type` field (validated: `in:post,prayer,blessing,poll,bible_study`, default `post`)
2. Accept `meta` field, validated per type:
   - `poll`: `meta.question` required string, `meta.options` required array 2–10 items each with `text` string, `meta.ends_at` optional datetime, `meta.allow_multiple` optional bool default false
   - `bible_study`: `meta.scripture` required string, `meta.passage` required string, `meta.study_guide` optional string
   - `blessing`: `meta.scripture` optional string
   - `prayer`: no required meta (initial `meta = {answered: false, answered_at: null}` set server-side)
3. Generates stable `opt_{ulid}` ids for each poll option server-side
4. Initialises `votes_count: 0` for each poll option
5. Guard: if `type = 'poll'` AND `cross_post_targets` is present → 422 before any processing
6. `crossPost` route method: guards `type = 'poll'` → 422. For other types, copies both `body` and `meta` from parent.

**`PostController` TypeScript interface update** — `PostCard.tsx` `Post` interface is extended:
```ts
interface Post {
  id: number; body: string | null; type: 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
  meta?: Record<string, any>;
  author: Author | null;  // null when is_anonymous = true
  church?: { name: string };
  reactions_count: number; comments_count: number; created_at: string;
}
```

**`FeedController@home`, `@community`, `@church`** — each method extended to accept a validated `?type=` query param:
```php
// In each method, inject Request $request (add to @community and @church signatures)
$request->validate(['type' => 'nullable|in:post,prayer,blessing,poll,bible_study']);
// ...
->when($request->type, fn ($q) => $q->where('type', $request->validated('type')))
```
This is a **new addition to FeedController** — it does not currently exist. The `@community(int $communityId)` and `@church(int $churchId)` method signatures must also have `Request $request` injected (they currently do not).

### New API Endpoints (added to Post plugin routes)

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/posts/{id}/answer-prayer` | sanctum (author only) | Toggle `meta.answered`. If toggling to `true`, sets `meta.answered_at = now()`. If toggling to `false`, sets `meta.answered_at = null`. Returns `{answered: bool, answered_at: string\|null}`. |
| POST | `/posts/{id}/vote` | sanctum | Cast poll vote. Body: `{option_id: string}`. |
| DELETE | `/posts/{id}/vote` | sanctum (own votes only) | Remove all authenticated user's votes from this poll. For `allow_multiple=true`, removes all option votes. |
| GET | `/posts/{id}/votes` | optional | Returns `{counts: {option_id: int}, user_vote: string\|null, user_votes: string[]}`. `user_vote` (singular) is for `allow_multiple=false` polls (first/only vote). `user_votes` (array) covers `allow_multiple=true`. Both always present. When unauthenticated, both are `null`/`[]`. Counts always come from `poll_votes` table. **This route must be outside the `auth:sanctum` middleware group** — add it to the public routes group in `plugins/Post/routes/api.php`. |

### Poll Vote Logic (`PollVoteController@store`)

1. Load post — verify `type = 'poll'`
2. Check poll not expired: `meta.ends_at` is non-null AND in the past → 422 "Poll has ended". If `ends_at` is `null`, the poll never expires.
3. Verify `option_id` exists in `meta.options` array → 422 "Invalid option"
4. `DB::transaction` with `lockForUpdate()` on **the post row** (serializes all concurrent votes):
   a. If `allow_multiple = false`: load existing votes (`poll_votes WHERE post_id = ? AND user_id = ?`). If found with same `option_id` → 422 "Already voted". If found with different `option_id` → delete old vote row, then resolve old option's array index from the **locked post's current `meta`**, decrement old `votes_count` via `JSON_SET`, proceed to insert new vote.
   b. If `allow_multiple = true`: check for existing vote on this specific option (`poll_votes WHERE post_id = ? AND user_id = ? AND option_id = ?`) → 422 "Already voted for this option" if exists. No cap on total options per user.
   c. Resolve the target option's array index from the **locked post's current `meta`** (inside transaction, not before). This prevents index-mismatch corruption if meta was concurrently modified.
   d. Insert `poll_votes` row
   e. Increment `meta.options[idx].votes_count` via `JSON_SET` using the resolved index
5. Return `{counts: {option_id: count}, user_vote: string|null, user_votes: string[]}`

> **Note on race safety:** `lockForUpdate()` on the post row serializes all concurrent vote transactions for the same post. Two simultaneous `allow_multiple=false` votes with different option_ids cannot both succeed — the second transaction sees the first's inserted `poll_votes` row when it re-reads inside the lock, and correctly returns 422.

### `DELETE /posts/{id}/vote` Logic

1. Find `poll_votes WHERE post_id = ? AND user_id = auth()->id()` — all votes by this user on this poll
2. Abort 404 if none found
3. `DB::transaction` with `lockForUpdate()` on post row:
   - For each vote row: resolve option index from locked `meta`, decrement `votes_count` via `JSON_SET`
   - Delete all vote rows
4. Return 200 `{message: "Vote removed"}`

> For `allow_multiple=true` polls, all votes are removed atomically. The frontend `PollCard` shows a "Remove vote" button (not per-option remove) for simplicity in Sprint 5.

## Feed Filter UI

`FeedPage` gains a horizontal filter tab bar:

```
[ All ] [ 🙏 Prayer ] [ ✨ Blessings ] [ 📊 Polls ] [ 📖 Bible Study ]
```

Active tab appends `?type=prayer` etc. to the feed URL. Tab state derived from URL query param so it is bookmarkable. `FeedPage` passes `type` to `load()` which includes it in the API call.

## Compose Post UI

`CreatePostModal` (new component) — renders as a modal triggered by a "Create post" button in FeedPage:

1. **Type selector row** at the bottom: Post | 🙏 Prayer | ✨ Blessing | 📊 Poll | 📖 Bible Study
2. **Shared fields for all types:** body textarea (label changes per type: "What's on your mind?" / "What would you like prayer for?" / "Share your testimony..." / poll body optional / study reflection), audience selector, media attach (Post/Blessing types only)
3. **Type-specific fields:**
   - **Prayer**: "anonymous" toggle (sets `is_anonymous = true` on the post; not in `meta`)
   - **Blessing**: scripture reference text input (optional, maps to `meta.scripture`)
   - **Poll**: question text input (required) + dynamic option inputs (min 2, max 10, + Add option button) + optional end date picker + allow multiple toggle
   - **Bible Study**: scripture reference (required) + passage textarea (required) + study guide textarea (optional)
4. Submit calls `POST /api/v1/posts` with `type`, `meta`, and (for prayer) `is_anonymous`

## Post Card Renderers

`PostCard` dispatches by `post.type` to type-specific sub-components:

| Type | Component | Special UI |
|---|---|---|
| `post` | `PostCard` (existing) | unchanged |
| `prayer` | `PrayerCard` | 🙏 purple badge; renders `post.author?.name ?? 'Anonymous'`; reaction bar replaced with single "Pray 🙏" button (calls Reaction endpoint with `🙏` emoji); green "Answered ✓" banner when `meta.answered = true`; author sees "Mark as Answered" toggle |
| `blessing` | `BlessingCard` | ✨ gold badge, scripture reference (if present) displayed below body in italic |
| `poll` | `PollCard` | Question prominent; option buttons with animated vote bars (width = votes/total %); vote counts; expired badge if `ends_at` passed; `user_votes` array used to highlight selected options (works for both single and multi-select polls) |
| `bible_study` | `BibleStudyCard` | 📖 blue badge, scripture reference prominent in header, passage in blockquote, study guide collapsible |

## File Structure

```
plugins/Post/
  Controllers/
    PollVoteController.php          (new)
    PrayerAnswerController.php      (new)
  Services/
    PollVoteService.php             (new — vote upsert, meta JSON_SET, option index resolved inside lockForUpdate)
  database/migrations/
    2026_04_25_000001_create_poll_votes_table.php   (new)
    2026_04_25_000002_add_type_index_to_social_posts.php  (new)
  routes/api.php                    (modified — add new endpoints; GET /votes on public group)
  Models/
    Post.php                        (modified — type validation, meta helpers, pollVotes relationship, crossPost guard for poll + body copy for typed reshares)
    PollVote.php                    (new)
plugins/Feed/
  Controllers/
    FeedController.php              (modified — inject Request, add validated ?type= filter to all three methods)
resources/js/plugins/feed/
  CreatePostModal.tsx               (new)
  PrayerCard.tsx                    (new — renders author ?? 'Anonymous')
  BlessingCard.tsx                  (new)
  PollCard.tsx                      (new — uses user_votes array)
  BibleStudyCard.tsx                (new)
  FeedPage.tsx                      (modified — filter tab bar, CreatePostModal trigger)
  PostCard.tsx                      (modified — type dispatch, updated Post interface with author: Author|null)
tests/Feature/
  PostTypeTest.php                  (new)
  PollVoteTest.php                  (new)
```

## Testing

**PostTypeTest:**
- Create prayer post → 201, type=prayer, meta.answered=false
- Create prayer post with is_anonymous=true → author is null in response
- Create blessing with scripture in meta → 201
- Create bible study with scripture + passage → 201
- Create poll with 1 option → 422
- Create poll with 11 options → 422
- `GET /api/v1/feed?type=prayer` returns only prayer posts
- `GET /api/v1/feed?type=poll` returns only poll posts
- `GET /api/v1/feed?type=invalid_type` → 422 validation error
- Author marks prayer answered → `meta.answered = true`, `answered_at` set
- Non-author cannot mark prayer answered → 403
- Reshare poll → 422 (both via cross-post route and via store with cross_post_targets)
- Reshare blessing → 201, reshare has `meta.scripture` AND `body` copied from parent

**PollVoteTest:**
- Vote on valid option → 201, `votes_count` incremented in meta and verifiable from `GET /posts/{id}/votes`
- Vote on same option twice (allow_multiple=false) → 422
- Change vote (allow_multiple=false) → 200, old option decremented, new option incremented
- Vote on expired poll → 422
- Vote with invalid option_id → 422
- `GET /posts/{id}/votes` unauthenticated → 200 (public route), `user_vote: null`, `user_votes: []`, counts present
- `DELETE /posts/{id}/vote` removes own vote(s), decrements count(s)
- `DELETE /posts/{id}/vote` when no vote exists → 404
- allow_multiple=true: vote on 3 options → 3 poll_votes rows; DELETE removes all 3 atomically
- Concurrent allow_multiple=false votes with different option_ids → only one succeeds (lockForUpdate serialization)
- Edit poll options after voting → 422
