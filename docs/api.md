# API Reference

Base URL: `/api/v1/`  
Auth: `Authorization: Bearer {token}` (Laravel Sanctum)  
Format: JSON

Legend: 🔓 public · 🔑 auth required

---

## Auth

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/login` | 🔓 | Email/password login → token |
| POST | `/api/v1/logout` | 🔑 | Invalidate token |
| POST | `/api/v1/register` | 🔓 | Create account |

---

## Feed

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/feed` | 🔓 | Home feed (all posts) |
| GET | `/api/v1/feed?type=prayer` | 🔓 | Filter by post type |
| GET | `/api/v1/feed/community/{id}` | 🔓 | Community feed |
| GET | `/api/v1/feed/church/{id}` | 🔓 | Church feed |

---

## Posts

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/posts` | 🔑 | Create post (type: post\|prayer\|blessing\|poll\|bible_study) |
| POST | `/api/v1/posts/{id}/cross-post` | 🔑 | Re-share a post |
| POST | `/api/v1/posts/{id}/answer-prayer` | 🔑 | Toggle prayer answered (author only) |
| POST | `/api/v1/posts/{id}/vote` | 🔑 | Vote on a poll option |
| DELETE | `/api/v1/posts/{id}/vote` | 🔑 | Remove poll vote |
| GET | `/api/v1/posts/{id}/votes` | 🔓 | Get poll vote counts |

### Create Post body
```json
{
  "type": "post",
  "body": "string",
  "community_id": 1,
  "church_id": 1,
  "is_anonymous": false,
  "meta": {}
}
```

### Poll meta
```json
{
  "type": "poll",
  "body": "Which service do you prefer?",
  "meta": {
    "options": [{"label": "9am"}, {"label": "11am"}],
    "expires_at": "2026-05-01T00:00:00Z"
  }
}
```

---

## Comments

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/posts/{postId}/comments` | 🔓 | List comments (threaded) |
| POST | `/api/v1/comments` | 🔑 | Create comment |
| DELETE | `/api/v1/comments/{id}` | 🔑 | Delete comment (author only) |

---

## Reactions

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/reactions` | 🔑 | Toggle reaction (creates or removes) |
| GET | `/api/v1/reactions/{type}/{id}` | 🔓 | Get reaction summary for a resource |

---

## Communities

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/communities` | 🔓 | List communities |
| GET | `/api/v1/communities/{id}` | 🔓 | Get community |
| POST | `/api/v1/communities` | 🔑 | Create community |
| POST | `/api/v1/communities/{id}/join` | 🔑 | Join community |
| DELETE | `/api/v1/communities/{id}/leave` | 🔑 | Leave community |
| GET | `/api/v1/counsel-groups` | 🔓 | List counsel groups |
| POST | `/api/v1/counsel-groups` | 🔑 | Create counsel group |
| POST | `/api/v1/counsel-groups/{id}/request-join` | 🔑 | Request to join |
| POST | `/api/v1/counsel-groups/{id}/approve/{userId}` | 🔑 | Approve member (leader) |

---

## Events

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/events` | 🔓 | List events (filters: church_id, community_id, category, from, to, scope) |
| GET | `/api/v1/events/{id}` | 🔓 | Get event (meeting_url hidden unless going attendee) |
| POST | `/api/v1/events` | 🔑 | Create event |
| PATCH | `/api/v1/events/{id}` | 🔑 | Update event (owner only) |
| DELETE | `/api/v1/events/{id}` | 🔑 | Delete event (owner only) |
| GET | `/api/v1/events/{id}/attendees` | 🔓 | List attendees |
| POST | `/api/v1/events/{id}/rsvp` | 🔑 | Set RSVP status (going\|maybe\|not_going) |
| DELETE | `/api/v1/events/{id}/rsvp` | 🔑 | Remove RSVP |
| GET | `/api/v1/events/{id}/posts` | 🔓 | Event discussion posts |
| POST | `/api/v1/events/{id}/posts` | 🔑 | Post to event discussion |

### Create Event body
```json
{
  "title": "Sunday Worship",
  "description": "string",
  "start_at": "2026-06-01T09:00:00",
  "end_at": "2026-06-01T11:00:00",
  "category": "worship",
  "location": "string",
  "meeting_url": "https://zoom.us/j/...",
  "max_capacity": 100,
  "recurrence_rule": "FREQ=WEEKLY;BYDAY=SU",
  "community_id": 1,
  "church_id": 1
}
```

Event categories: `worship | prayer | youth | outreach | study | social | other`

---

## Churches

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/churches` | 🔓 | List churches |
| GET | `/api/v1/churches/{slug}` | 🔓 | Get church |
| GET | `/api/v1/churches/{slug}/members` | 🔓 | List members |

### Admin Church Management
| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/admin/churches/export` | 🔑 | Export members CSV |
| POST | `/api/v1/admin/churches/import` | 🔑 | Import members CSV |
| GET | `/api/v1/admin/churches/sample-csv` | 🔑 | Download sample CSV |

---

## FAQ

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/faq` | 🔓 | List FAQ categories + articles |
| GET | `/api/v1/faq/{id}` | 🔓 | Get FAQ article |
| GET | `/api/v1/admin/faq/categories` | 🔑 | Admin list categories |
| POST | `/api/v1/admin/faq/categories` | 🔑 | Create category |
| PATCH | `/api/v1/admin/faq/categories/{id}` | 🔑 | Update category |
| DELETE | `/api/v1/admin/faq/categories/{id}` | 🔑 | Delete category |
| GET | `/api/v1/admin/faq/faqs` | 🔑 | Admin list articles |
| POST | `/api/v1/admin/faq/faqs` | 🔑 | Create article |
| PATCH | `/api/v1/admin/faq/faqs/{id}` | 🔑 | Update article |
| DELETE | `/api/v1/admin/faq/faqs/{id}` | 🔑 | Delete article |

---

## Analytics (Admin)

| Method | Path | Auth | Description |
|---|---|---|---|
| GET | `/api/v1/admin/analytics` | 🔑 | Platform analytics dashboard |

---

## Error Responses

| Code | Meaning |
|---|---|
| 401 | Unauthenticated |
| 403 | Forbidden (policy check failed) |
| 404 | Not found |
| 422 | Validation error — body contains `errors` object |
| 429 | Rate limited |
