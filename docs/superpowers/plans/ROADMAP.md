# Church Platform — Sprint Roadmap

## Completed ✅

| Sprint | Feature | Branch | Status |
|---|---|---|---|
| Sprint 1 | Core Laravel setup, auth, churches | `main` | ✅ Done |
| Sprint 2 | Feed, Comments, Reactions, Communities, Profiles | `main` | ✅ Done |
| Sprint 3 | Media plugin (Spatie MediaLibrary) | `main` | ✅ Done |
| Sprint 4 | Events plugin — RSVP, recurring, discussion, reminders | `main` | ✅ Done |
| Sprint 5 | Post types — prayer, blessing, poll, bible study | `main` | ✅ Done |
| Sprint 6 | Church Pages — ministry pages, follow/unfollow, member roles | `main` | ✅ Done |
| Sprint 7 | Feed Scoping + Post-as-Page — entity_id on posts, page feed, post-as-page | `main` | ✅ Done |
| Sprint 8 | Community Enhancement — privacy, join approval, role management | `main` | ✅ Done |
| Sprint 9 | Church-Specific Features — ministry sub-pages, community type badges | `main` | ✅ Done |
| Sprint 10 | Moderation & Insights — post pin/approve, page verification, insights | `main` | ✅ Done |

## Remaining 🏗

All planned sprints complete. Next phase: installer, notifications, or production hardening.

## Sprint 6 — Church Pages Plugin
**Goal:** Facebook-style organizational pages for church ministries.  
**Tables:** `church_entities` (type='page'), `entity_members`  
**API:** GET/POST/PUT/DELETE `/api/v1/pages`, follow/unfollow, member role management  
**Frontend:** PageCard, PagesPage, PageDetailPage

## Sprint 7 — Feed Scoping + Post-as-Page
**Goal:** Scope posts to pages/communities; allow page admins to post *as the page*.  
**Schema change:** Add `entity_id`, `entity_type`, `posted_as`, `actor_entity_id`, `is_approved`, `is_pinned` to `social_posts`  
**API:** FeedController updated to filter by `entity_id`; `POST /posts` accepts `posted_as=entity`  
**Frontend:** Feed tab for page feed; post composer shows "Post as [Page]" toggle for admins

## Sprint 8 — Community Enhancement
**Goal:** Upgrade existing Community plugin to support full Sngine-style privacy model.  
**Schema change:** Migrate existing `communities` to also insert `church_entities type='community'` rows; add `closed/secret` privacy to communities; approval flow  
**API:** `POST /communities/{id}/join` → pending for closed; `POST /communities/{id}/approve/{uid}`; invite system  
**Frontend:** Community join state, pending request badge, approval queue for moderators

## Sprint 9 — Church-Specific Features
**Goal:** Church platform differentiators beyond Sngine.  
**Tables:** `parent_entity_id` on `church_entities` (ministry sub-pages)  
**New fields:** `community_type` enum on communities (small_group, prayer_circle, bible_study, ministry_team, choir)  
**API:** Sub-pages: `POST /pages/{id}/sub-pages`; `GET /pages/{id}/sub-pages`  
**Frontend:** Ministry hub page with sub-pages grid; community type badges

## Sprint 10 — Moderation & Insights
**Goal:** Platform maturity — moderation tools, page verification, analytics.  
**Tables:** `is_pinned`, `is_approved`, `approved_by` on `social_posts`  
**API:** `POST /posts/{id}/pin`; `POST /posts/{id}/approve`; `POST /pages/{id}/verify`; `GET /pages/{id}/insights`  
**Frontend:** Moderator panel on PageDetailPage; pinned posts section; verification badge request flow

---

## Execution Pattern
Each sprint follows: worktree → TDD implementation → Vite build → `finishing-a-development-branch` → merge to main.  
Plans live in: `docs/superpowers/plans/`  
Current sprint branch: `.worktrees/sprint-N/` on `sprint/N-feature`
