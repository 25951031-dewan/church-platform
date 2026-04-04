# Live Meetings Plugin

Plan 10 extends meetings with registration, attendance tracking, admin management, and live-notification scheduling.

## Backend

- Enhanced `meetings` model fields: `event_id`, `meeting_id`, `meeting_password`, `max_participants`, `requires_registration`
- New `meeting_registrations` table and model
- Public/admin controllers with registration and check-in endpoints
- Admin stats endpoint for attendance rates

## API

- `GET /api/v1/meetings`
- `GET /api/v1/meetings/live`
- `GET /api/v1/meetings/{meeting}`
- `POST /api/v1/meetings/{meeting}/register`
- `DELETE /api/v1/meetings/{meeting}/register`
- `POST /api/v1/meetings/{meeting}/check-in`
- Admin:
  - `GET /api/v1/admin/meetings`
  - `POST /api/v1/admin/meetings`
  - `GET /api/v1/admin/meetings/{meeting}`
  - `PUT /api/v1/admin/meetings/{meeting}`
  - `DELETE /api/v1/admin/meetings/{meeting}`
  - `GET /api/v1/admin/meetings/{meeting}/stats`

## Scheduler

- `php artisan notifications:send-event-reminders 24h`
- `php artisan notifications:send-event-reminders 1h`
- `php artisan meetings:check-live`

## Admin Settings

Live meetings configuration defaults are seeded under section **22** (`live_meetings.*`) and editable at:

- `/admin/settings/live-meetings`
