# Notifications Plugin

Plan 10 adds a unified notifications system with in-app, email, SMS, and OneSignal push channels.

## Backend

- Models: `NotificationPreference`, `NotificationLog`, `NotificationTemplate`, `PushSubscription`
- Service: `Common\\Notifications\\Services\\NotificationService`
- Notification classes: sermon, prayer update, event reminder, group post, chat message, meeting live, new member
- API:
  - `GET /api/v1/notifications`
  - `GET /api/v1/notifications/unread-count`
  - `POST /api/v1/notifications/{id}/read`
  - `POST /api/v1/notifications/read-all`
  - `DELETE /api/v1/notifications/{id}`
  - `DELETE /api/v1/notifications/clear-read`
  - `GET/PUT /api/v1/notifications/preferences`
  - `POST /api/v1/notifications/push/register`
  - `POST /api/v1/notifications/push/unregister`

## Admin

- `GET /api/v1/admin/notification-logs`
- CRUD `/api/v1/admin/notification-templates`
- Frontend pages:
  - `/admin/notification-logs`
  - `/admin/notification-templates`
  - `/admin/settings/notifications`

## OneSignal and Twilio

Configure:

- `ONESIGNAL_APP_ID`
- `ONESIGNAL_REST_API_KEY`
- `TWILIO_SID`
- `TWILIO_TOKEN`
- `TWILIO_FROM`

Default settings are seeded under section **21** (`notifications.*`).
