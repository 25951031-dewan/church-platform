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

**Non-shareable types:** Posts with `type IN (poll)` cannot be reshared (`crossPost`). The `PostController@crossPost` method returns 422 "Poll posts cannot be reshared" if the parent post is a poll. Prayer and blessing posts *can* be reshared; the reshare copies `meta` from the parent so `BlessingCard` renders correctly on the reshared copy.

### New Table: `poll_votes`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| post_id | FK → social_posts cascadeOnDelete | |
| user_id | FK → users cascadeOnDelete | |
| option_id | string(40) | Matches `meta.options[].id` |
| created_at | timestamp | |
| UNIQUE | (post_id, user_id, option_id) | Prevents duplicate vote per option. When `allow_multiple=false`, enforced at app level (not DB level) — app queries existing votes before inserting. |

Index: `(post_id, option_id)` for fast count queries.

**Vote counts — source of truth:** `poll_votes` rows are authoritative. `meta.options[].votes_count` is a **denormalised cache** updated inside a `lockForUpdate()` transaction. If counts diverge, a `RecalculatePollVotesJob` can recompute from `poll_votes`. The `GET /posts/{id}/votes` endpoint always returns counts from `poll_votes` (not meta), so the UI is always accurate even if meta diverges.

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
   - `prayer`: no required meta
3. Generates stable `opt_{ulid}` ids for each poll option server-side
4. Initialises `votes_count: 0` for each poll option
5. `crossPost` method: blocks `type=poll` reshares with 422; for all other types, copies `meta` from parent into reshare row. **This is a new code change** — the current `crossPost` implementation does not propagate `meta`. This sprint modifies it to do so.

**`PostController` TypeScript interface update** — `PostCard.tsx` `Post` interface is extended:
```ts
interface Post {
  id: number; body: string; type: 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
  meta?: Record<string, any>;
  author: Author; church?: { name: string };
  reactions_count: number; comments_count: number; created_at: string;
}
```

**`FeedController@home`, `@community`, `@church`** — each method extended to accept `?type=` query param:
```php
->when($request->type, fn ($q) => $q->where('type', $request->type))
```
This is a **new addition to FeedController** — it does not currently exist.

### New API Endpoints (added to Post plugin routes)

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/posts/{id}/answer-prayer` | sanctum (author only) | Toggle `meta.answered`. If toggling to `true`, sets `meta.answered_at = now()`. If toggling to `false`, sets `meta.answered_at = null`. Response: `{answered: bool, answered_at: string\|null}`. |
| POST | `/posts/{id}/vote` | sanctum | Cast poll vote. Body: `{option_id: string}`. |
| DELETE | `/posts/{id}/vote` | sanctum (own vote only) | Remove authenticated user's vote from this poll. |
| GET | `/posts/{id}/votes` | optional | Returns `{counts: {option_id: int}, user_vote: string\|null}`. When unauthenticated, `user_vote` is always `null`. Counts always come from `poll_votes` table. |

### Poll Vote Logic (`PollVoteController@store`)

1. Load post — verify `type = 'poll'`
2. Check poll not expired: `meta.ends_at` is non-null AND in the past → 422 "Poll has ended". If `ends_at` is `null`, the poll never expires.
3. Verify `option_id` exists in `meta.options` array → 422 "Invalid option"
4. `DB::transaction` with `lockForUpdate()` on post row:
   a. If `allow_multiple = false`: check for existing vote on this post (`poll_votes WHERE post_id = ? AND user_id = ?`). If found with same `option_id` → 422 "Already voted". If found with different `option_id` → delete old vote, decrement old `votes_count` in meta, proceed to insert new vote.
   b. If `allow_multiple = true`: check for existing vote on this specific option (`poll_votes WHERE post_id = ? AND user_id = ? AND option_id = ?`) → 422 "Already voted for this option" if exists. There is **no cap on how many options a user may vote on** — a user may vote on all options if desired. Only duplicate votes on the same option are blocked.
   c. Insert `poll_votes` row
   d. Increment `meta.options[option_id].votes_count` using MySQL JSON path update:
      ```sql
      UPDATE social_posts
      SET meta = JSON_SET(meta, CONCAT('$.options[', idx, '].votes_count'), votes_count + 1)
      WHERE id = ?
      ```
      (The option array index `idx` is resolved by the service before entering the transaction.)
5. Return `{counts: {option_id: count}, user_vote: option_id}`

### `DELETE /posts/{id}/vote` Logic

1. Find `poll_votes WHERE post_id = ? AND user_id = auth()->id()` — all votes by this user on this poll
2. Abort 404 if none found
3. `DB::transaction`: delete vote row(s), decrement corresponding `votes_count` in meta
4. Return 200 `{message: "Vote removed"}`

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
   - **Prayer**: "anonymous" toggle (sets `is_anonymous = true`)
   - **Blessing**: scripture reference text input (optional)
   - **Poll**: question text input (required) + dynamic option inputs (min 2, max 10, + Add option button) + optional end date picker + allow multiple toggle
   - **Bible Study**: scripture reference (required) + passage textarea (required) + study guide textarea (optional)
4. Submit calls `POST /api/v1/posts` with `type` and `meta`

## Post Card Renderers

`PostCard` dispatches by `post.type` to type-specific sub-components:

| Type | Component | Special UI |
|---|---|---|
| `post` | `PostCard` (existing) | unchanged |
| `prayer` | `PrayerCard` | 🙏 purple badge, reaction bar replaced with "Pray 🙏" button, green "Answered ✓" banner when `meta.answered = true`. Author sees "Mark as Answered" toggle. |
| `blessing` | `BlessingCard` | ✨ gold badge, scripture reference (if present) displayed below body in italic |
| `poll` | `PollCard` | Question prominent, option buttons with animated vote bars (width = votes/total %), vote counts, expired badge if `ends_at` passed, user's selected option highlighted |
| `bible_study` | `BibleStudyCard` | 📖 blue badge, scripture reference prominent in header, passage in blockquote, study guide collapsible |

## File Structure

```
plugins/Post/
  Controllers/
    PollVoteController.php          (new)
    PrayerAnswerController.php      (new)
  Services/
    PollVoteService.php             (new — handles vote upsert, meta update)
  database/migrations/
    2026_04_25_000001_create_poll_votes_table.php   (new)
    2026_04_25_000002_add_type_index_to_social_posts.php  (new)
  routes/api.php                    (modified — add new endpoints)
  Models/
    Post.php                        (modified — type validation, meta helpers, pollVotes relationship, crossPost guard)
    PollVote.php                    (new)
plugins/Feed/
  Controllers/
    FeedController.php              (modified — add ?type= filter to all three feed methods)
resources/js/plugins/feed/
  CreatePostModal.tsx               (new)
  PrayerCard.tsx                    (new)
  BlessingCard.tsx                  (new)
  PollCard.tsx                      (new)
  BibleStudyCard.tsx                (new)
  FeedPage.tsx                      (modified — filter tab bar, CreatePostModal trigger)
  PostCard.tsx                      (modified — type dispatch, updated Post interface)
tests/Feature/
  PostTypeTest.php                  (new)
  PollVoteTest.php                  (new)
```

## Testing

**PostTypeTest:**
- Create prayer post → 201, type=prayer
- Create blessing with scripture in meta → 201
- Create bible study with scripture + passage → 201
- Create poll with 1 option → 422
- Create poll with 11 options → 422
- `GET /api/v1/feed?type=prayer` returns only prayer posts
- `GET /api/v1/feed?type=poll` returns only poll posts
- Author marks prayer answered → `meta.answered = true`, `answered_at` set
- Non-author cannot mark prayer answered → 403
- Reshare poll → 422
- Reshare blessing → 201, reshare has `meta.scripture` copied from parent

**PollVoteTest:**
- Vote on valid option → 201, `votes_count` incremented in meta and verifiable from `GET /posts/{id}/votes`
- Vote on same option twice (allow_multiple=false) → 422
- Change vote (allow_multiple=false) → 200, old option decremented, new option incremented
- Vote on expired poll → 422
- Vote with invalid option_id → 422
- `GET /posts/{id}/votes` unauthenticated → `user_vote: null`, counts present
- `DELETE /posts/{id}/vote` removes own vote, decrements count
- `DELETE /posts/{id}/vote` cannot remove another user's vote (ownership enforced)
