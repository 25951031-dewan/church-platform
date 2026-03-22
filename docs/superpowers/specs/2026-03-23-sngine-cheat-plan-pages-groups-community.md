# Sngine Cheat Plan — Pages, Groups & Community Feed
**Church Platform — Backend Architecture Reference**
_Reverse-engineered from Sngine v2.0–v4.x changelog + public documentation_
_Date: 2026-03-23_

---

## 1. Sngine Mental Model (What We're Mapping)

| Sngine Concept | Facebook Equivalent | Church Platform Equivalent |
|---|---|---|
| **Page** | Facebook Page | Church official page, ministry page, campus page |
| **Group** | Facebook Group | Ministry community, small group, prayer circle |
| **User profile** | Personal profile | Member profile |
| **Newsfeed** | Home feed | Church home feed |
| **Page Admin** | Page admin | Page owner/admin |
| **Group Admin/Moderator** | Group admin/mod | Community leader/moderator |

---

## 2. Sngine — Inferred Database Schema

### 2.1 `pages` table

```sql
CREATE TABLE pages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,          -- creator/owner
  name            VARCHAR(255)    NOT NULL,
  username        VARCHAR(100)    UNIQUE,             -- vanity URL slug
  description     TEXT,
  category_id     BIGINT UNSIGNED,                   -- hierarchical categories
  cover_photo     VARCHAR(500),
  profile_photo   VARCHAR(500),

  -- Contact & identity
  website         VARCHAR(500),
  address         TEXT,
  phone           VARCHAR(50),
  email           VARCHAR(255),
  social_links    JSON,                              -- {facebook, twitter, instagram, ...}
  action_button   JSON,                              -- {type: 'contact_us|book_now', url: '...'}

  -- Verification
  is_verified     TINYINT(1) DEFAULT 0,
  verified_at     TIMESTAMP NULL,
  business_id     VARCHAR(100),                      -- external business identifier

  -- Features toggles (per-page)
  allow_reviews   TINYINT(1) DEFAULT 1,
  allow_tips      TINYINT(1) DEFAULT 0,
  allow_events    TINYINT(1) DEFAULT 1,
  allow_jobs      TINYINT(1) DEFAULT 0,
  allow_offers    TINYINT(1) DEFAULT 0,
  allow_products  TINYINT(1) DEFAULT 0,

  -- Counters (denormalized)
  likes_count     INT UNSIGNED DEFAULT 0,
  followers_count INT UNSIGNED DEFAULT 0,

  -- Metadata
  custom_fields   JSON,
  meta            JSON,                              -- SEO, og-meta, extra config

  country         VARCHAR(10),
  language        VARCHAR(10),

  is_active       TINYINT(1) DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at      TIMESTAMP NULL,

  INDEX idx_user_id (user_id),
  INDEX idx_category_id (category_id),
  INDEX idx_country (country),
  INDEX idx_is_verified (is_verified)
);
```

### 2.2 `page_members` table (followers + admins unified)

```sql
CREATE TABLE page_members (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  role       ENUM('admin','moderator','member') NOT NULL DEFAULT 'member',
                                                 -- 'member' = regular liker/follower
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_page_user (page_id, user_id),
  INDEX idx_page_role (page_id, role)
);
```

> **Key insight:** In Sngine, "liking" a page = becoming a `member` row here.
> Admins are just `role=admin` rows in the same table.

### 2.3 `groups` table

```sql
CREATE TABLE groups (
  id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id                 BIGINT UNSIGNED NOT NULL,        -- creator
  name                    VARCHAR(255)    NOT NULL,
  username                VARCHAR(100)    UNIQUE,
  description             TEXT,
  category_id             BIGINT UNSIGNED,
  cover_photo             VARCHAR(500),
  profile_photo           VARCHAR(500),

  -- Privacy
  privacy                 ENUM('public','closed','secret') NOT NULL DEFAULT 'public',

  -- Post rules
  allow_member_posts      TINYINT(1) DEFAULT 1,
  posts_require_approval  TINYINT(1) DEFAULT 0,

  -- Feature toggles (per-group)
  allow_reviews           TINYINT(1) DEFAULT 1,
  allow_events            TINYINT(1) DEFAULT 1,
  allow_products          TINYINT(1) DEFAULT 0,

  -- Counters
  members_count           INT UNSIGNED DEFAULT 0,

  -- Metadata
  custom_fields           JSON,
  meta                    JSON,

  country                 VARCHAR(10),
  language                VARCHAR(10),

  is_active               TINYINT(1) DEFAULT 1,
  created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at              TIMESTAMP NULL,

  INDEX idx_user_id (user_id),
  INDEX idx_category_id (category_id),
  INDEX idx_privacy (privacy)
);
```

### 2.4 `group_members` table

```sql
CREATE TABLE group_members (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id    BIGINT UNSIGNED NOT NULL,
  user_id     BIGINT UNSIGNED NOT NULL,
  role        ENUM('admin','moderator','member') NOT NULL DEFAULT 'member',
  status      ENUM('pending','approved','declined','banned') NOT NULL DEFAULT 'approved',
  invited_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_group_user (group_id, user_id),
  INDEX idx_group_status (group_id, status),
  INDEX idx_group_role   (group_id, role)
);
```

> **Privacy → join flow mapping:**
> - `public` → `status = 'approved'` immediately on join
> - `closed` → `status = 'pending'` until admin approves
> - `secret` → only via invite (`invited_by` set), `status = 'approved'`

### 2.5 `social_posts` table (unified — already exists in our platform)

```sql
-- Key columns relevant to pages/groups context:
ALTER TABLE social_posts ADD COLUMN page_id  BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE social_posts ADD COLUMN group_id BIGINT UNSIGNED NULL AFTER page_id;
-- Note: page_id XOR group_id — a post belongs to at most ONE container
-- If both are null, post belongs to user profile (or global)

-- For groups with approval:
ALTER TABLE social_posts ADD COLUMN is_approved TINYINT(1) DEFAULT 1;
ALTER TABLE social_posts ADD COLUMN approved_by BIGINT UNSIGNED NULL;
ALTER TABLE social_posts ADD COLUMN approved_at TIMESTAMP NULL;

-- Who is the author "as" (post as page)
ALTER TABLE social_posts ADD COLUMN posted_as ENUM('user','page') DEFAULT 'user';
ALTER TABLE social_posts ADD COLUMN page_actor_id BIGINT UNSIGNED NULL;
-- page_actor_id: when posted_as='page', this is the page ID acting as author
```

### 2.6 `page_categories` / `group_categories` tables

```sql
-- Unified hierarchical category tree (used for both pages and groups)
CREATE TABLE entity_categories (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   BIGINT UNSIGNED NULL,         -- NULL = root, supports infinite levels
  entity_type ENUM('page','group') NOT NULL,
  name        VARCHAR(255) NOT NULL,
  slug        VARCHAR(255) NOT NULL,
  description TEXT,
  sort_order  INT DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  UNIQUE KEY uq_type_slug (entity_type, slug),
  INDEX idx_parent (parent_id)
);
```

---

## 3. Privacy & Access Control Matrix

### Pages (always public by nature)

| Action | Visitor (unauthenticated) | Member (follower) | Moderator | Admin | Platform Admin |
|---|---|---|---|---|---|
| View page | ✅ | ✅ | ✅ | ✅ | ✅ |
| View posts | ✅ | ✅ | ✅ | ✅ | ✅ |
| Like/follow page | ❌ (login) | ✅ (unfollow) | ✅ | ✅ | ✅ |
| Create post | ❌ | ❌ | ✅ | ✅ | ✅ |
| Pin/delete post | ❌ | ❌ | ✅ | ✅ | ✅ |
| Manage members | ❌ | ❌ | ❌ | ✅ | ✅ |
| Edit page settings | ❌ | ❌ | partial | ✅ | ✅ |
| Delete page | ❌ | ❌ | ❌ | ✅ | ✅ |

### Groups — by privacy level

| Action | Secret | Closed | Public |
|---|---|---|---|
| Discoverable (search/browse) | ❌ (invite only) | ✅ | ✅ |
| View members list | Members only | Members only | ✅ |
| View posts | Members only | Members only | ✅ |
| Join flow | Invite only → approved | Request → admin approves | Click join → approved |
| Post as member | If `allow_member_posts=1` | Same | Same |
| Post approval | If `posts_require_approval=1` | Same | Same |

---

## 4. Feed Architecture (Newsfeed Scoping)

### Feed Sources + Filtering

```
GET /feed/home        → posts from: friends + followed pages + joined groups
GET /feed/page/{id}   → posts scoped to page_id = {id}
GET /feed/group/{id}  → posts scoped to group_id = {id}, filtered by membership
GET /feed/church      → posts with page_id = (church official page id)
```

### Core Feed Query Pattern (Sngine-style)

```sql
-- Home feed (authenticated user)
SELECT sp.* FROM social_posts sp
WHERE sp.deleted_at IS NULL
  AND sp.is_approved = 1
  AND (
    -- from friends
    (sp.page_id IS NULL AND sp.group_id IS NULL AND sp.privacy IN ('public','friends')
      AND sp.user_id IN (SELECT friend_id FROM friendships WHERE user_id = ? AND status = 'accepted'))
    OR
    -- from followed pages
    (sp.page_id IN (SELECT page_id FROM page_members WHERE user_id = ?))
    OR
    -- from joined groups
    (sp.group_id IN (SELECT group_id FROM group_members WHERE user_id = ? AND status = 'approved'))
    OR
    -- own posts
    sp.user_id = ?
  )
ORDER BY sp.created_at DESC
LIMIT 20 OFFSET ?;
```

### Post Visibility Rules

| Post location | Who can see it |
|---|---|
| User profile, privacy=`public` | Everyone |
| User profile, privacy=`friends` | Friends only |
| User profile, privacy=`only_me` | Only the author |
| Page post | Everyone (pages are public) |
| Group post, group=`public` | Everyone |
| Group post, group=`closed` or `secret` | Approved members only |
| Group post + `is_approved=0` | Only admins/moderators of that group |

---

## 5. "Post As Page" Mechanism

Sngine allows page admins to post *as the page* (not as themselves):

```
POST body:
{
  "body": "Sunday service is at 10am",
  "posted_as": "page",
  "page_actor_id": 42,
  "page_id": 42
}
```

**Backend logic:**
1. Verify `page_actor_id` admin via `page_members WHERE page_id=42 AND user_id=auth AND role IN ('admin','moderator')`
2. Set `social_posts.posted_as = 'page'`, `social_posts.page_actor_id = 42`
3. In feed display: show page avatar/name as author (not the user)

---

## 6. Invitation & Join Flow

### Page (Follow/Like)

```
POST /pages/{id}/follow   → INSERT page_members (role='member', status='approved')
POST /pages/{id}/unfollow → DELETE FROM page_members WHERE page_id=? AND user_id=? AND role='member'
```

### Group Join

```
POST /groups/{id}/join →
  IF group.privacy = 'public'  → INSERT with status='approved', increment members_count
  IF group.privacy = 'closed'  → INSERT with status='pending'  (no counter increment yet)
  IF group.privacy = 'secret'  → 403 (can only join via invite)

POST /groups/{id}/approve/{user_id}  → UPDATE status='approved', increment members_count
POST /groups/{id}/decline/{user_id}  → DELETE row

POST /groups/{id}/invite →
  INSERT group_members (status='approved', invited_by=auth_user)
```

---

## 7. Roles & Permission Escalation

```
POST /pages/{id}/members/{user_id}/promote  → role='moderator' or role='admin'
POST /pages/{id}/members/{user_id}/demote   → role='member'
POST /pages/{id}/members/{user_id}/remove   → DELETE row

POST /groups/{id}/members/{user_id}/promote → same pattern
POST /groups/{id}/members/{user_id}/ban     → status='banned'
```

**Rule:** There must always be at least 1 `role='admin'` per page/group — prevent admin-less entity.

---

## 8. Church Platform — Recommended Adaptation

### Unified Entity Model: `church_entities`

Instead of two separate `pages` and `groups` tables, use a single polymorphic entity table:

```sql
CREATE TABLE church_entities (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type         ENUM('page','community') NOT NULL,
                -- 'page'      = church page (public, follow model)
                -- 'community' = church community/group (join model)
  owner_id     BIGINT UNSIGNED NOT NULL,       -- creator user
  name         VARCHAR(255) NOT NULL,
  slug         VARCHAR(100) UNIQUE,
  description  TEXT,
  category_id  BIGINT UNSIGNED NULL,

  -- Media
  cover_image    VARCHAR(500),
  profile_image  VARCHAR(500),

  -- Page-specific
  website        VARCHAR(500),
  address        TEXT,
  phone          VARCHAR(50),
  social_links   JSON,
  action_button  JSON,                         -- {type, url, label}
  is_verified    TINYINT(1) DEFAULT 0,

  -- Community-specific
  privacy        ENUM('public','closed','secret') DEFAULT 'public',
  allow_posts    TINYINT(1) DEFAULT 1,
  require_approval TINYINT(1) DEFAULT 0,

  -- Feature flags
  features       JSON,                         -- {allow_events, allow_prayer, allow_giving}

  -- Counters
  members_count  INT UNSIGNED DEFAULT 0,
  posts_count    INT UNSIGNED DEFAULT 0,

  -- Config
  meta           JSON,
  country        VARCHAR(10),
  is_active      TINYINT(1) DEFAULT 1,

  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at     TIMESTAMP NULL,

  INDEX idx_type_slug (type, slug),
  INDEX idx_owner (owner_id),
  INDEX idx_type_active (type, is_active)
);
```

### Unified Member Table: `entity_members`

```sql
CREATE TABLE entity_members (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_id   BIGINT UNSIGNED NOT NULL,
  user_id     BIGINT UNSIGNED NOT NULL,
  role        ENUM('admin','moderator','member') NOT NULL DEFAULT 'member',
  status      ENUM('pending','approved','declined','banned') NOT NULL DEFAULT 'approved',
  invited_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_entity_user (entity_id, user_id),
  INDEX idx_entity_status (entity_id, status),
  INDEX idx_entity_role   (entity_id, role),
  INDEX idx_user_entities (user_id, status)
);
```

> **Why unified?** Posts, reactions, comments, events, media — all attach to `entity_id`.
> No need for `page_id` AND `group_id` columns everywhere. One polymorphic FK.

---

## 9. Social Posts Extended Schema for Pages/Communities

Add these columns to the existing `social_posts` table:

```sql
-- Container (entity)
entity_id       BIGINT UNSIGNED NULL,    -- page or community ID
entity_type     ENUM('page','community') NULL,

-- Author identity
posted_as       ENUM('user','entity') DEFAULT 'user',
actor_entity_id BIGINT UNSIGNED NULL,    -- when posted_as='entity', this = entity_id

-- Moderation
is_approved     TINYINT(1) DEFAULT 1,
approved_by     BIGINT UNSIGNED NULL,
approved_at     TIMESTAMP NULL,
is_pinned       TINYINT(1) DEFAULT 0,
```

**Composite index for feed queries:**
```sql
INDEX idx_feed_entity (entity_id, is_approved, created_at DESC);
INDEX idx_feed_user   (user_id, is_approved, created_at DESC);
```

---

## 10. API Route Map

### Pages (`/api/pages`)

```
GET    /pages                    → browse/discover (filter: category, country, verified)
POST   /pages                    → create page (auth required)
GET    /pages/{slug}             → page profile
PUT    /pages/{id}               → update page (admin only)
DELETE /pages/{id}               → soft-delete (admin or platform-admin)

GET    /pages/{id}/posts         → page feed
POST   /pages/{id}/posts         → create post as page/admin

POST   /pages/{id}/follow        → follow/like page
DELETE /pages/{id}/follow        → unfollow

GET    /pages/{id}/members       → followers + admins
PUT    /pages/{id}/members/{uid}/role   → promote/demote (admin only)
DELETE /pages/{id}/members/{uid}        → remove (admin only)

POST   /pages/{id}/verify        → submit verification request
GET    /pages/{id}/insights      → analytics (admin only)
```

### Communities (`/api/communities`)

```
GET    /communities              → browse (filter: category, privacy=public)
POST   /communities              → create community (auth required)
GET    /communities/{slug}       → community profile
PUT    /communities/{id}         → update (admin only)
DELETE /communities/{id}         → soft-delete (admin only)

GET    /communities/{id}/posts   → community feed (auth + approved member for closed/secret)
POST   /communities/{id}/posts   → create post in community

POST   /communities/{id}/join    → join (public→approved, closed→pending)
DELETE /communities/{id}/join    → leave
POST   /communities/{id}/invite  → invite user (admin/moderator only)

GET    /communities/{id}/members → member list (public: everyone; closed/secret: members only)
PUT    /communities/{id}/members/{uid}/role   → promote/demote
PUT    /communities/{id}/members/{uid}/status → approve/decline/ban
DELETE /communities/{id}/members/{uid}        → remove member
```

---

## 11. Church-Specific Feature Additions (Beyond Sngine)

These are features Sngine doesn't have that are essential for a church platform:

### A. Church Page (Official Church Entity)

The church itself has ONE special `type='page'` entity seeded at install time:
- Has `is_verified=1` and `is_church_official=1` flag
- All global newsfeed announcements can be posted here
- Special "Giving" action button type (links to donation)
- "About Us", "Service Times", "Location" structured fields (not just generic description)

### B. Ministry Pages

Sub-pages (campuses, departments) linked to the church page:
```sql
ALTER TABLE church_entities ADD COLUMN parent_entity_id BIGINT UNSIGNED NULL;
-- church page (parent) → youth ministry page, worship page, women's ministry page
```

### C. Community Types for Churches

```sql
ALTER TABLE church_entities ADD COLUMN community_type
  ENUM('general','small_group','prayer_circle','bible_study','ministry_team','choir')
  DEFAULT 'general';
```

Each type unlocks type-specific post types (Prayer posts for prayer circles, etc.).

### D. Church Membership Integration

```sql
-- Link entity_members to existing church_members for cross-validation
ALTER TABLE entity_members ADD COLUMN church_member_id BIGINT UNSIGNED NULL;
-- Auto-join "All Church Members" community on church membership approval
```

### E. Giving Integration on Pages

```sql
-- Action button type extension:
action_button JSON  -- {type: 'give', campaign_id: 42, label: 'Give to this Ministry'}
```

---

## 12. Implementation Priority Order

| Sprint | What to build | Why first |
|---|---|---|
| **Sprint 6** | `church_entities` + `entity_members` tables, CRUD APIs for Pages & Communities | Foundation for all content scoping |
| **Sprint 7** | Feed scoping (entity_id filtering in FeedController), "post as page" feature | Content is useless without feed |
| **Sprint 8** | Join/follow flows, approval system, role management | Community engagement |
| **Sprint 9** | Church-specific types: ministry pages, community types, giving buttons | Church differentiation |
| **Sprint 10** | Verification, insights/analytics, moderation (pin, approve, remove posts) | Maturity features |

---

## 13. Key Design Decisions vs Sngine

| Decision | Sngine's way | Our church platform |
|---|---|---|
| **Pages vs Groups tables** | Separate `pages` + `groups` tables | Single `church_entities` table (type column) |
| **Member table** | Separate `page_members` + `group_members` | Single `entity_members` table |
| **Posts scoping** | `page_id` + `group_id` columns | `entity_id` + `entity_type` columns |
| **Verification** | Manual request + admin approval | Same + automatic for church_official type |
| **Categories** | Separate `pages_categories` + `groups_categories` | Single `entity_categories` table |
| **Post "as page"** | `page_id` on post = user posted on page | `posted_as='entity'` + `actor_entity_id` |
| **Church-specific** | ❌ Generic social network | ✅ Ministry types, giving buttons, prayer integration |
| **DB counter atomicity** | Unknown (likely not locked) | `DB::transaction + lockForUpdate()` always |
| **Privacy on pages** | Pages always public | Same — pages are always public |
| **Privacy on groups** | `public / closed / secret` | Same enum |
