# Session Context — Church Platform

> Reference with `#file:.copilot/context.md` to give Copilot the minimum
> context needed to work correctly. Update "Current Focus" each sprint.

## Current Focus
**Plan 12:** Giving / Donations plugin
- Stripe + PayPal payments
- Fund model (General, Building, Missions)
- RecurringGift model
- PDF receipt generation

## Tech Stack (quick ref)
| Layer | Tech |
|-------|------|
| Backend | Laravel 12, PHP 8.4, Sanctum Bearer tokens |
| Frontend | React 19, TypeScript, React Router v7 |
| State | TanStack Query v5, Zustand |
| Styling | Tailwind CSS v4 (ALWAYS dark: `bg-[#0C0E12]` page, `bg-[#161920]` card) |
| DB | SQLite (local dev), MySQL (production) |
| Build | Vite — `public/build/` is committed to git |

## Enabled Plugins (routes are loaded for these)
timeline, groups, events, sermons, prayer, chat, library,
church_builder, blog, live_meeting

## Key Files
- Auth hook: `resources/client/common/auth/use-auth.ts`
- API client: `resources/client/common/http/api-client.ts`
- App router: `resources/client/app-router.tsx`
- Plugin config: `config/plugins.json`
- DB: `database/database.sqlite` (local)

## What NOT to do (most common Copilot mistakes)
1. `extends HandlesAuthorization` → use `extends Common\Core\BasePolicy`
2. `bg-white` / `text-gray-900` in TSX → use dark palette above
3. Adding `permissions` to `plugins.json` → use `{Name}PermissionSeeder.php`
4. `Auth::attempt()` → app uses Bearer token, not sessions
5. `@common/stores` → correct is `@app/common/stores`
6. Enabling phantom plugins in `plugins.json` → check `app/Plugins/` first
