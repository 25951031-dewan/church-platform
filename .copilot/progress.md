# Project Progress — Church Platform

> Keep this file updated after every session. Reference it with:
> `#file:.copilot/progress.md` at the start of any Copilot Chat.

## Status: v5-foundation branch — Plans 1–10 COMPLETE

### ✅ Completed Plans

| Plan | Feature | Plugin Directory |
|------|---------|-----------------|
| 1 | Foundation, Auth, Admin shell | core |
| 2 | Timeline (posts, reactions, comments) | `app/Plugins/Timeline/` |
| 3 | Groups (FB-style) | `app/Plugins/Groups/` |
| 4 | Events + Sermons | `app/Plugins/Events/` + `app/Plugins/Sermons/` |
| 5 | Prayer wall | `app/Plugins/Prayer/` |
| 6 | Church Builder (mini-sites) | `app/Plugins/ChurchBuilder/` |
| 7 | Library (books, PDF) | `app/Plugins/Library/` |
| 8 | Blog + Live Meetings | `app/Plugins/Blog/` + `app/Plugins/LiveMeeting/` |
| 9 | Chat (WebSocket) | `common/foundation` (Chat) |
| 10 | Notifications (7 types, OneSignal) | `common/foundation` (Notifications) |
| 11 | Feed Customizer backend | `app/Plugins/Timeline/` (FeedLayout, FeedWidget) |

### ✅ Admin UI (BeMusic-style dark panel)
- `AdminLayout.tsx` — sidebar with lucide icons
- `DashboardPage.tsx` — real stats
- `UsersPage.tsx`, `RolesPage.tsx`, `PluginsPage.tsx`
- Settings: General, Email, Auth, Appearance, Notifications, LiveMeetings
- `FeedCustomizerPage.tsx` — feed widget drag-drop builder
- `SystemPage.tsx` — system info + cache clear

### 🔜 Next: Plan 12 — Choose one:
- **Giving plugin** — Stripe/PayPal donations, Fund model (HIGH VALUE)
- **Volunteers plugin** — shift scheduling, role assignments
- **Fundraising plugin** — campaigns, goals

### ⚠️ Known disabled plugins (code not yet built):
`giving`, `volunteers`, `fundraising`, `stories`, `pastoral`
`marketplace` — code exists in `app/Plugins/Marketplace/` but disabled

---

## Last Session Fixes (2026-04-05)
- PluginController → writes to `config/plugins.json` (was writing to DB only)
- Legacy dead routes removed from `routes/api.php`
- Prayer `POST prayer-requests` route added
- FeedWidget model: missing scopes + column accessors fixed
- FeedLayoutPolicy created and registered
- FeedWidgetSeeder: 6 default widgets seeded
- PluginsPage wired into router + AdminLayout nav
- `@common/stores` → `@app/common/stores` import fixed in PluginsPage
