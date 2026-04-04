# Plan 10: Notifications + Live Meetings — Implementation Plan

> **Phase**: 2 (Build the Community) — Final Completion  
> **Timeline**: Week 12-13  
> **Status**: 🚀 Ready to Implement  
> **Created**: 2026-04-03  
> **Depends On**: Plans 1-9 (all complete)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Goals](#2-goals)
3. [Tech Stack](#3-tech-stack)
4. [Architecture](#4-architecture)
5. [Database Schema](#5-database-schema)
6. [Part A: Notifications System](#6-part-a-notifications-system)
7. [Part B: Live Meetings](#7-part-b-live-meetings)
8. [Implementation Tasks](#8-implementation-tasks)
9. [Testing Strategy](#9-testing-strategy)
10. [Deployment Checklist](#10-deployment-checklist)

---

## 1. Overview

Plan 10 completes **Phase 2 (Build the Community)** by adding:

1. **Multi-channel Notifications** — Push, email, SMS, and in-app notifications with per-user preferences
2. **Live Meetings Integration** — Zoom/Google Meet support with "Live Now" badges

This plan enables real-time engagement across all platform features:
- New sermon uploaded → Push notification to followers
- Prayer request update → Email + push to prayer warriors
- Event starting in 1 hour → SMS reminder
- Group post → Push to members
- Chat message → Push (if user offline)
- Meeting going live → "Live Now" badge + push notification

---

## 2. Goals

### Primary Goals

| Goal | Metric |
|------|--------|
| Multi-channel delivery | 4 channels: Push, Email, SMS, In-app |
| User preferences | Per-channel, per-event-type toggles |
| Real-time delivery | < 3 second latency for push/in-app |
| Live meeting integration | Zoom + Google Meet support |
| "Live Now" detection | Time-based badge on active meetings |

### Success Criteria

- [ ] Push notifications work via OneSignal
- [ ] Email notifications via Laravel Mail
- [ ] SMS notifications via Twilio (optional, admin-configured)
- [ ] In-app notification center (dropdown + full page)
- [ ] User can configure preferences per channel
- [ ] Events can have Zoom/Meet links
- [ ] "Live Now" badge shows for active meetings
- [ ] Admin can view delivery logs
- [ ] Admin can manage notification templates

---

## 3. Tech Stack

### Notification Services

| Service | Purpose | Pricing |
|---------|---------|---------|
| **OneSignal** | Push notifications (web + mobile) | Free: 10K subscribers |
| **Laravel Mail** | Email notifications | Via configured SMTP/Mailgun/SES |
| **Twilio** | SMS notifications | Pay-per-message (~$0.01/SMS) |
| **Laravel Broadcasting** | In-app real-time (reuses Chat infrastructure) | Already configured |

### Meeting Services

| Service | Purpose | Integration |
|---------|---------|-------------|
| **Zoom** | Video meetings | OAuth + API (auto-create meetings) |
| **Google Meet** | Video meetings | Link generation (manual or API) |
| **YouTube Live** | Livestreaming | Embed URL support |

### Laravel Packages

```bash
# Notifications (Laravel built-in)
# No additional packages needed - Laravel Notification system

# OneSignal Push
composer require laravel-notification-channels/onesignal

# Twilio SMS
composer require laravel-notification-channels/twilio

# Zoom API (optional, for auto-create)
composer require macsidigital/laravel-zoom
```

---

## 4. Architecture

### Notification Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        NOTIFICATION SYSTEM                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Event Trigger                                                         │
│   ┌──────────────┐                                                      │
│   │ SermonCreated│ ────┐                                                │
│   │ PrayerUpdated│     │                                                │
│   │ EventReminder│     │      ┌────────────────────┐                    │
│   │ GroupPostSent│ ────┼────► │ NotificationService │                   │
│   │ ChatMessage  │     │      └─────────┬──────────┘                    │
│   │ MeetingLive  │ ────┘                │                               │
│   └──────────────┘                      │                               │
│                                         ▼                               │
│                           ┌─────────────────────────┐                   │
│                           │ Check User Preferences  │                   │
│                           │ (notification_preferences)                  │
│                           └─────────────┬───────────┘                   │
│                                         │                               │
│           ┌─────────────────────────────┼─────────────────────────────┐ │
│           │                             │                             │ │
│           ▼                             ▼                             ▼ │
│   ┌──────────────┐           ┌──────────────┐           ┌───────────┐  │
│   │   OneSignal  │           │ Laravel Mail │           │  Twilio   │  │
│   │    (Push)    │           │   (Email)    │           │   (SMS)   │  │
│   └──────────────┘           └──────────────┘           └───────────┘  │
│           │                             │                             │ │
│           │                             │                             │ │
│           └─────────────────────────────┼─────────────────────────────┘ │
│                                         │                               │
│                                         ▼                               │
│                           ┌─────────────────────────┐                   │
│                           │   Broadcasting (Echo)   │                   │
│                           │      (In-App)           │                   │
│                           └─────────────────────────┘                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Live Meeting Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        LIVE MEETING SYSTEM                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Event Creation                                                        │
│   ┌──────────────────┐                                                  │
│   │ Admin creates    │                                                  │
│   │ event with       │                                                  │
│   │ meeting enabled  │                                                  │
│   └────────┬─────────┘                                                  │
│            │                                                            │
│            ▼                                                            │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │                    Meeting Configuration                        │   │
│   ├─────────────────────────────────────────────────────────────────┤   │
│   │                                                                 │   │
│   │   Option A: Manual URL Entry                                    │   │
│   │   ┌─────────────────────────────────────────────────────────┐   │   │
│   │   │ Admin pastes Zoom/Meet/YouTube link directly            │   │   │
│   │   └─────────────────────────────────────────────────────────┘   │   │
│   │                                                                 │   │
│   │   Option B: Auto-Create (Zoom API)                              │   │
│   │   ┌─────────────────────────────────────────────────────────┐   │   │
│   │   │ System calls Zoom API → Creates meeting → Stores URL    │   │   │
│   │   └─────────────────────────────────────────────────────────┘   │   │
│   │                                                                 │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   Live Detection (Time-based)                                           │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │                                                                 │   │
│   │   is_live = (starts_at <= now) AND (ends_at >= now)             │   │
│   │                                                                 │   │
│   │   Frontend polls every 30s or uses WebSocket for instant update │   │
│   │                                                                 │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   UI Components                                                         │
│   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                  │
│   │ "Live Now"   │  │ "Join Live"  │  │ Upcoming     │                  │
│   │    Badge     │  │    Button    │  │   Meetings   │                  │
│   └──────────────┘  └──────────────┘  └──────────────┘                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### File Structure

```
common/foundation/src/
├── Notifications/
│   ├── Models/
│   │   ├── DatabaseNotification.php
│   │   └── NotificationPreference.php
│   ├── Controllers/
│   │   ├── NotificationController.php
│   │   ├── NotificationPreferenceController.php
│   │   └── Admin/
│   │       ├── NotificationLogController.php
│   │       └── NotificationTemplateController.php
│   ├── Requests/
│   │   └── UpdatePreferencesRequest.php
│   ├── Notifications/
│   │   ├── BaseNotification.php
│   │   ├── NewSermonNotification.php
│   │   ├── PrayerUpdateNotification.php
│   │   ├── EventReminderNotification.php
│   │   ├── GroupPostNotification.php
│   │   ├── ChatMessageNotification.php
│   │   ├── MeetingLiveNotification.php
│   │   └── NewMemberNotification.php
│   ├── Channels/
│   │   └── OneSignalChannel.php  (if not using package)
│   ├── Events/
│   │   └── NotificationSent.php
│   └── Services/
│       └── NotificationService.php
│
├── LiveMeetings/
│   ├── Models/
│   │   └── Meeting.php
│   ├── Controllers/
│   │   ├── MeetingController.php
│   │   └── Admin/
│   │       └── MeetingController.php
│   ├── Requests/
│   │   └── CreateMeetingRequest.php
│   ├── Policies/
│   │   └── MeetingPolicy.php
│   ├── Events/
│   │   └── MeetingStarted.php
│   └── Services/
│       ├── ZoomService.php
│       └── GoogleMeetService.php

resources/client/plugins/
├── notifications/
│   ├── types.ts
│   ├── hooks/
│   │   ├── useNotifications.ts
│   │   ├── useNotificationPreferences.ts
│   │   ├── useMarkAsRead.ts
│   │   └── index.ts
│   ├── components/
│   │   ├── NotificationDropdown.tsx
│   │   ├── NotificationItem.tsx
│   │   ├── NotificationCenter.tsx
│   │   ├── NotificationPreferencesForm.tsx
│   │   ├── UnreadNotificationBadge.tsx
│   │   └── index.ts
│   └── pages/
│       └── NotificationsPage.tsx
│
├── live-meetings/
│   ├── types.ts
│   ├── hooks/
│   │   ├── useMeetings.ts
│   │   ├── useLiveMeetings.ts
│   │   └── index.ts
│   ├── components/
│   │   ├── LiveBadge.tsx
│   │   ├── JoinMeetingButton.tsx
│   │   ├── UpcomingMeetingsWidget.tsx
│   │   ├── MeetingCard.tsx
│   │   └── index.ts
│   └── admin/
│       └── MeetingManagerPage.tsx
```

---

## 5. Database Schema

### Notifications Tables

```sql
-- notifications (Laravel default table with customizations)
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,                    -- UUID
    type VARCHAR(255) NOT NULL,                 -- Notification class name
    notifiable_type VARCHAR(255) NOT NULL,      -- User model
    notifiable_id BIGINT UNSIGNED NOT NULL,     -- User ID
    data JSON NOT NULL,                         -- Notification payload
    read_at TIMESTAMP NULL,                     -- When user read it
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_notifiable (notifiable_type, notifiable_id),
    INDEX idx_read_at (read_at),
    INDEX idx_created_at (created_at)
);

-- notification_preferences (per-user, per-type channel settings)
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(100) NOT NULL,    -- 'sermon', 'prayer', 'event', 'group', 'chat', 'meeting'
    push_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,          -- Off by default (costs money)
    in_app_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_type (user_id, notification_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- notification_logs (admin delivery tracking)
CREATE TABLE notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id CHAR(36) NOT NULL,          -- Links to notifications.id
    user_id BIGINT UNSIGNED NOT NULL,
    channel VARCHAR(20) NOT NULL,               -- 'push', 'email', 'sms', 'database'
    status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') DEFAULT 'pending',
    provider_response TEXT NULL,                -- OneSignal/Twilio response
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_notification (notification_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_channel (channel)
);

-- notification_templates (admin-customizable templates)
CREATE TABLE notification_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL UNIQUE,          -- 'new_sermon', 'prayer_update', etc.
    name VARCHAR(255) NOT NULL,                 -- Display name
    description TEXT NULL,
    push_title VARCHAR(255) NULL,               -- Push notification title template
    push_body TEXT NULL,                        -- Push notification body template
    email_subject VARCHAR(255) NULL,            -- Email subject template
    email_body TEXT NULL,                       -- Email body (Blade/HTML)
    sms_body VARCHAR(160) NULL,                 -- SMS body (160 char limit)
    variables JSON NULL,                        -- Available variables: {user_name}, {sermon_title}, etc.
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- push_subscriptions (OneSignal player IDs per user)
CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    player_id VARCHAR(255) NOT NULL,            -- OneSignal player ID
    device_type VARCHAR(20) NULL,               -- 'web', 'ios', 'android'
    device_name VARCHAR(255) NULL,
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_player (player_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Live Meetings Table

```sql
-- meetings (linked to events)
CREATE TABLE meetings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NULL,              -- Optional link to event (can be standalone)
    church_id BIGINT UNSIGNED NULL,             -- Church scope
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    platform ENUM('zoom', 'google_meet', 'youtube', 'custom') NOT NULL DEFAULT 'zoom',
    meeting_url VARCHAR(500) NOT NULL,          -- The join URL
    meeting_id VARCHAR(100) NULL,               -- Platform-specific meeting ID
    meeting_password VARCHAR(50) NULL,          -- Meeting password (if any)
    host_user_id BIGINT UNSIGNED NULL,          -- User who created/hosts
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_rule VARCHAR(255) NULL,          -- iCal RRULE format
    timezone VARCHAR(50) DEFAULT 'UTC',
    max_participants INT UNSIGNED NULL,
    requires_registration BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_event (event_id),
    INDEX idx_church (church_id),
    INDEX idx_starts_at (starts_at),
    INDEX idx_ends_at (ends_at),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- meeting_registrations (if registration required)
CREATE TABLE meeting_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attended BOOLEAN DEFAULT FALSE,
    attended_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_registration (meeting_id, user_id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Settings Additions

```php
// Admin Settings for Notifications (Section 21)
'onesignal_app_id' => '',
'onesignal_rest_api_key' => '',
'onesignal_safari_web_id' => '',    // For Safari push

'twilio_sid' => '',
'twilio_token' => '',
'twilio_from_number' => '',
'twilio_enabled' => false,          // Master toggle for SMS

'notification_channels_default' => [
    'sermon' => ['push' => true, 'email' => true, 'sms' => false],
    'prayer' => ['push' => true, 'email' => true, 'sms' => false],
    'event' => ['push' => true, 'email' => true, 'sms' => true],
    'group' => ['push' => true, 'email' => false, 'sms' => false],
    'chat' => ['push' => true, 'email' => false, 'sms' => false],
    'meeting' => ['push' => true, 'email' => true, 'sms' => false],
],

// Admin Settings for Live Meetings (Section 22)
'zoom_client_id' => '',
'zoom_client_secret' => '',
'zoom_account_id' => '',            // For Server-to-Server OAuth
'zoom_enabled' => false,

'google_meet_enabled' => false,     // Just link pasting, no API

'default_meeting_platform' => 'zoom',
'auto_create_meetings' => false,    // Auto-create when event created
```

---

## 6. Part A: Notifications System

### 6.1 Notification Types (Event Matrix)

| Event | Trigger | Push | Email | SMS | In-App |
|-------|---------|------|-------|-----|--------|
| **New Sermon** | Sermon published | ✅ | ✅ | ❌ | ✅ |
| **Prayer Update** | Update added to prayer request | ✅ | ✅ | ❌ | ✅ |
| **Prayer Answered** | Prayer marked as answered | ✅ | ✅ | ❌ | ✅ |
| **Event Reminder (24h)** | 24 hours before event | ✅ | ✅ | ✅ | ✅ |
| **Event Reminder (1h)** | 1 hour before event | ✅ | ❌ | ✅ | ✅ |
| **Group Post** | New post in user's group | ✅ | ❌ | ❌ | ✅ |
| **Group Invite** | User invited to group | ✅ | ✅ | ❌ | ✅ |
| **Chat Message** | New message (user offline) | ✅ | ❌ | ❌ | ✅ |
| **Meeting Live** | Meeting started | ✅ | ❌ | ❌ | ✅ |
| **New Member** | New user registered | ❌ | ✅ (admin) | ❌ | ✅ |
| **Reaction** | Someone reacted to user's content | ❌ | ❌ | ❌ | ✅ |
| **Comment** | Someone commented on user's content | ✅ | ❌ | ❌ | ✅ |

### 6.2 Base Notification Class

```php
<?php
// common/foundation/src/Notifications/Notifications/BaseNotification.php

namespace Common\Notifications\Notifications;

use Common\Notifications\Models\NotificationPreference;
use Common\Notifications\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The notification type key (e.g., 'sermon', 'prayer', 'event')
     */
    abstract public function notificationType(): string;

    /**
     * Get the notification's delivery channels based on user preferences.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always store in database
        
        $prefs = NotificationPreference::where('user_id', $notifiable->id)
            ->where('notification_type', $this->notificationType())
            ->first();
        
        // Use defaults if no preference set
        $defaults = config('services.notification_channels_default.' . $this->notificationType(), [
            'push' => true,
            'email' => true,
            'sms' => false,
        ]);
        
        // Check each channel
        if ($prefs?->push_enabled ?? $defaults['push']) {
            if ($notifiable->pushSubscriptions()->exists()) {
                $channels[] = OneSignalChannel::class;
            }
        }
        
        if ($prefs?->email_enabled ?? $defaults['email']) {
            $channels[] = 'mail';
        }
        
        if ($prefs?->sms_enabled ?? $defaults['sms']) {
            if (config('services.twilio.enabled') && $notifiable->phone) {
                $channels[] = TwilioChannel::class;
            }
        }
        
        // Broadcast for in-app (always if enabled)
        if ($prefs?->in_app_enabled ?? true) {
            $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    /**
     * Get the array representation of the notification.
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);
        
        return (new MailMessage)
            ->subject($data['title'] ?? 'New Notification')
            ->line($data['body'] ?? '')
            ->action($data['action_text'] ?? 'View', $data['action_url'] ?? url('/'))
            ->line('Thank you for being part of our community!');
    }

    /**
     * Get the OneSignal representation of the notification.
     */
    public function toOneSignal(object $notifiable): OneSignalMessage
    {
        $data = $this->toArray($notifiable);
        
        return OneSignalMessage::create()
            ->setSubject($data['title'] ?? 'New Notification')
            ->setBody($data['body'] ?? '')
            ->setUrl($data['action_url'] ?? url('/'));
    }

    /**
     * Get the Twilio SMS representation of the notification.
     */
    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $data = $this->toArray($notifiable);
        
        // SMS has 160 char limit
        $message = substr($data['sms_body'] ?? $data['body'] ?? '', 0, 160);
        
        return (new TwilioSmsMessage())
            ->content($message);
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
```

### 6.3 Example Notification: New Sermon

```php
<?php
// common/foundation/src/Notifications/Notifications/NewSermonNotification.php

namespace Common\Notifications\Notifications;

use App\Models\Sermon;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalMessage;

class NewSermonNotification extends BaseNotification
{
    public function __construct(
        public Sermon $sermon
    ) {}

    public function notificationType(): string
    {
        return 'sermon';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_sermon',
            'title' => 'New Sermon Available',
            'body' => "'{$this->sermon->title}' by {$this->sermon->speaker->name} is now available.",
            'action_text' => 'Listen Now',
            'action_url' => url("/sermons/{$this->sermon->slug}"),
            'sermon_id' => $this->sermon->id,
            'sermon_title' => $this->sermon->title,
            'speaker_name' => $this->sermon->speaker->name,
            'thumbnail' => $this->sermon->cover_image_url,
            'icon' => 'sermon',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Sermon: {$this->sermon->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new sermon has been published:")
            ->line("**{$this->sermon->title}**")
            ->line("Speaker: {$this->sermon->speaker->name}")
            ->action('Listen Now', url("/sermons/{$this->sermon->slug}"))
            ->line('We hope this message blesses you!');
    }

    public function toOneSignal(object $notifiable): OneSignalMessage
    {
        return OneSignalMessage::create()
            ->setSubject('New Sermon Available')
            ->setBody("'{$this->sermon->title}' is now available")
            ->setIcon($this->sermon->cover_image_url)
            ->setUrl(url("/sermons/{$this->sermon->slug}"));
    }
}
```

### 6.4 Example Notification: Event Reminder

```php
<?php
// common/foundation/src/Notifications/Notifications/EventReminderNotification.php

namespace Common\Notifications\Notifications;

use App\Models\Event;
use NotificationChannels\Twilio\TwilioSmsMessage;

class EventReminderNotification extends BaseNotification
{
    public function __construct(
        public Event $event,
        public string $reminderType = '24h' // '24h' or '1h'
    ) {}

    public function notificationType(): string
    {
        return 'event';
    }

    public function toArray(object $notifiable): array
    {
        $timeUntil = $this->reminderType === '24h' ? 'tomorrow' : 'in 1 hour';
        
        return [
            'type' => 'event_reminder',
            'title' => 'Event Reminder',
            'body' => "'{$this->event->title}' starts {$timeUntil}!",
            'sms_body' => "{$this->event->title} starts {$timeUntil}. " . 
                ($this->event->meeting_url ? "Join: {$this->event->meeting_url}" : "Location: {$this->event->location}"),
            'action_text' => 'View Event',
            'action_url' => url("/events/{$this->event->slug}"),
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'starts_at' => $this->event->starts_at->toISOString(),
            'location' => $this->event->location,
            'meeting_url' => $this->event->meeting_url,
            'icon' => 'calendar',
        ];
    }

    public function toTwilio(object $notifiable): TwilioSmsMessage
    {
        $data = $this->toArray($notifiable);
        
        return (new TwilioSmsMessage())
            ->content($data['sms_body']);
    }
}
```

### 6.5 Notification Controller

```php
<?php
// common/foundation/src/Notifications/Controllers/NotificationController.php

namespace Common\Notifications\Controllers;

use Common\Core\BaseController;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseController
{
    /**
     * Get user's notifications with pagination.
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();
        
        // Filter by read/unread
        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', 'like', '%' . $request->type . '%');
        }
        
        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread count only (for badge).
     */
    public function unreadCount(Request $request)
    {
        return $this->success([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();
        
        $notification->markAsRead();
        
        return $this->success(['notification' => $notification]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        return $this->success(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $request->user()
            ->notifications()
            ->where('id', $id)
            ->delete();
        
        return $this->success(['message' => 'Notification deleted']);
    }

    /**
     * Delete all read notifications.
     */
    public function clearRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();
        
        return $this->success(['message' => 'Read notifications cleared']);
    }
}
```

### 6.6 Notification Preferences Controller

```php
<?php
// common/foundation/src/Notifications/Controllers/NotificationPreferenceController.php

namespace Common\Notifications\Controllers;

use Common\Core\BaseController;
use Common\Notifications\Models\NotificationPreference;
use Common\Notifications\Requests\UpdatePreferencesRequest;
use Illuminate\Http\Request;

class NotificationPreferenceController extends BaseController
{
    /**
     * Get user's notification preferences.
     */
    public function index(Request $request)
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)
            ->get()
            ->keyBy('notification_type');
        
        // Merge with defaults for types that don't have preferences yet
        $types = ['sermon', 'prayer', 'event', 'group', 'chat', 'meeting', 'comment', 'reaction'];
        $defaults = config('services.notification_channels_default', []);
        
        $result = [];
        foreach ($types as $type) {
            $pref = $preferences->get($type);
            $default = $defaults[$type] ?? ['push' => true, 'email' => true, 'sms' => false];
            
            $result[$type] = [
                'notification_type' => $type,
                'push_enabled' => $pref?->push_enabled ?? $default['push'],
                'email_enabled' => $pref?->email_enabled ?? $default['email'],
                'sms_enabled' => $pref?->sms_enabled ?? $default['sms'],
                'in_app_enabled' => $pref?->in_app_enabled ?? true,
            ];
        }
        
        return $this->success(['preferences' => $result]);
    }

    /**
     * Update notification preferences.
     */
    public function update(UpdatePreferencesRequest $request)
    {
        $data = $request->validated();
        
        foreach ($data['preferences'] as $type => $channels) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'notification_type' => $type,
                ],
                [
                    'push_enabled' => $channels['push_enabled'] ?? true,
                    'email_enabled' => $channels['email_enabled'] ?? true,
                    'sms_enabled' => $channels['sms_enabled'] ?? false,
                    'in_app_enabled' => $channels['in_app_enabled'] ?? true,
                ]
            );
        }
        
        return $this->success(['message' => 'Preferences updated']);
    }

    /**
     * Register push subscription (OneSignal player ID).
     */
    public function registerPush(Request $request)
    {
        $request->validate([
            'player_id' => 'required|string',
            'device_type' => 'nullable|string|in:web,ios,android',
            'device_name' => 'nullable|string|max:255',
        ]);
        
        $request->user()->pushSubscriptions()->updateOrCreate(
            ['player_id' => $request->player_id],
            [
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'last_active_at' => now(),
            ]
        );
        
        return $this->success(['message' => 'Push subscription registered']);
    }

    /**
     * Unregister push subscription.
     */
    public function unregisterPush(Request $request)
    {
        $request->validate([
            'player_id' => 'required|string',
        ]);
        
        $request->user()->pushSubscriptions()
            ->where('player_id', $request->player_id)
            ->delete();
        
        return $this->success(['message' => 'Push subscription removed']);
    }
}
```

### 6.7 Frontend: Notification Dropdown

```tsx
// resources/client/plugins/notifications/components/NotificationDropdown.tsx

import { useState, useRef, useEffect } from 'react';
import { useNotifications, useMarkAsRead, useMarkAllAsRead } from '../hooks';
import { NotificationItem } from './NotificationItem';
import { UnreadNotificationBadge } from './UnreadNotificationBadge';
import { Button } from '@common/ui/buttons/button';
import { BellIcon, CheckIcon } from '@heroicons/react/24/outline';
import { AnimatePresence, motion } from 'framer-motion';
import { useClickOutside } from '@common/utils/hooks/use-click-outside';
import { Link } from 'react-router-dom';

export function NotificationDropdown() {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  
  const { data, isLoading } = useNotifications({ unreadOnly: false, perPage: 10 });
  const markAllAsRead = useMarkAllAsRead();
  
  useClickOutside(dropdownRef, () => setIsOpen(false));

  const notifications = data?.notifications?.data ?? [];
  const unreadCount = data?.unread_count ?? 0;

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Bell Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-muted hover:text-main rounded-full hover:bg-alt transition-colors"
        aria-label="Notifications"
      >
        <BellIcon className="w-6 h-6" />
        <UnreadNotificationBadge count={unreadCount} />
      </button>

      {/* Dropdown Panel */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: -10, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -10, scale: 0.95 }}
            transition={{ duration: 0.15 }}
            className="absolute right-0 mt-2 w-80 md:w-96 bg-paper rounded-lg shadow-xl border border-divider z-50 max-h-[70vh] overflow-hidden flex flex-col"
          >
            {/* Header */}
            <div className="flex items-center justify-between p-4 border-b border-divider">
              <h3 className="font-semibold text-main">Notifications</h3>
              {unreadCount > 0 && (
                <Button
                  variant="text"
                  size="xs"
                  onClick={() => markAllAsRead.mutate()}
                  disabled={markAllAsRead.isPending}
                  startIcon={<CheckIcon className="w-4 h-4" />}
                >
                  Mark all read
                </Button>
              )}
            </div>

            {/* Notification List */}
            <div className="overflow-y-auto flex-1">
              {isLoading ? (
                <div className="p-8 text-center text-muted">Loading...</div>
              ) : notifications.length === 0 ? (
                <div className="p-8 text-center text-muted">
                  <BellIcon className="w-12 h-12 mx-auto mb-2 opacity-50" />
                  <p>No notifications yet</p>
                </div>
              ) : (
                <div className="divide-y divide-divider">
                  {notifications.map((notification) => (
                    <NotificationItem
                      key={notification.id}
                      notification={notification}
                      onClose={() => setIsOpen(false)}
                    />
                  ))}
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="p-3 border-t border-divider bg-alt/50">
              <Link
                to="/notifications"
                onClick={() => setIsOpen(false)}
                className="block text-center text-sm text-primary hover:underline"
              >
                View all notifications
              </Link>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
```

### 6.8 Frontend: Notification Preferences Form

```tsx
// resources/client/plugins/notifications/components/NotificationPreferencesForm.tsx

import { useForm } from 'react-hook-form';
import { useNotificationPreferences, useUpdatePreferences } from '../hooks';
import { Button } from '@common/ui/buttons/button';
import { Switch } from '@common/ui/forms/toggle/switch';
import { toast } from '@common/ui/toast/toast';

interface PreferencesForm {
  preferences: Record<string, {
    push_enabled: boolean;
    email_enabled: boolean;
    sms_enabled: boolean;
    in_app_enabled: boolean;
  }>;
}

const NOTIFICATION_TYPES = [
  { key: 'sermon', label: 'New Sermons', description: 'When new sermons are published' },
  { key: 'prayer', label: 'Prayer Updates', description: 'Updates on prayer requests you follow' },
  { key: 'event', label: 'Event Reminders', description: 'Reminders before events you\'re attending' },
  { key: 'group', label: 'Group Activity', description: 'New posts in your groups' },
  { key: 'chat', label: 'Chat Messages', description: 'New messages (when offline)' },
  { key: 'meeting', label: 'Live Meetings', description: 'When meetings go live' },
  { key: 'comment', label: 'Comments', description: 'When someone comments on your content' },
  { key: 'reaction', label: 'Reactions', description: 'When someone reacts to your content' },
];

export function NotificationPreferencesForm() {
  const { data, isLoading } = useNotificationPreferences();
  const updatePrefs = useUpdatePreferences();
  
  const { register, handleSubmit, watch, setValue } = useForm<PreferencesForm>({
    defaultValues: { preferences: data?.preferences ?? {} },
  });

  const onSubmit = (formData: PreferencesForm) => {
    updatePrefs.mutate(formData, {
      onSuccess: () => toast.positive('Preferences saved!'),
      onError: () => toast.danger('Failed to save preferences'),
    });
  };

  if (isLoading) {
    return <div className="p-4 text-center text-muted">Loading preferences...</div>;
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="border-b border-divider">
              <th className="text-left py-3 px-4 font-medium text-main">Notification Type</th>
              <th className="text-center py-3 px-4 font-medium text-main">Push</th>
              <th className="text-center py-3 px-4 font-medium text-main">Email</th>
              <th className="text-center py-3 px-4 font-medium text-main">SMS</th>
              <th className="text-center py-3 px-4 font-medium text-main">In-App</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-divider">
            {NOTIFICATION_TYPES.map(({ key, label, description }) => (
              <tr key={key} className="hover:bg-alt/30">
                <td className="py-4 px-4">
                  <div className="font-medium text-main">{label}</div>
                  <div className="text-sm text-muted">{description}</div>
                </td>
                <td className="text-center py-4 px-4">
                  <Switch
                    {...register(`preferences.${key}.push_enabled`)}
                    defaultChecked={data?.preferences?.[key]?.push_enabled}
                  />
                </td>
                <td className="text-center py-4 px-4">
                  <Switch
                    {...register(`preferences.${key}.email_enabled`)}
                    defaultChecked={data?.preferences?.[key]?.email_enabled}
                  />
                </td>
                <td className="text-center py-4 px-4">
                  <Switch
                    {...register(`preferences.${key}.sms_enabled`)}
                    defaultChecked={data?.preferences?.[key]?.sms_enabled}
                  />
                </td>
                <td className="text-center py-4 px-4">
                  <Switch
                    {...register(`preferences.${key}.in_app_enabled`)}
                    defaultChecked={data?.preferences?.[key]?.in_app_enabled}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex justify-end">
        <Button 
          type="submit" 
          variant="flat" 
          color="primary"
          disabled={updatePrefs.isPending}
        >
          {updatePrefs.isPending ? 'Saving...' : 'Save Preferences'}
        </Button>
      </div>
    </form>
  );
}
```

---

## 7. Part B: Live Meetings

### 7.1 Meeting Model

```php
<?php
// common/foundation/src/LiveMeetings/Models/Meeting.php

namespace Common\LiveMeetings\Models;

use App\Models\User;
use App\Models\Event;
use App\Models\Church;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meeting extends Model
{
    protected $fillable = [
        'event_id',
        'church_id',
        'title',
        'description',
        'platform',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'host_user_id',
        'starts_at',
        'ends_at',
        'is_recurring',
        'recurrence_rule',
        'timezone',
        'max_participants',
        'requires_registration',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_recurring' => 'boolean',
        'requires_registration' => 'boolean',
        'is_active' => 'boolean',
        'max_participants' => 'integer',
    ];

    protected $appends = ['is_live', 'status'];

    /**
     * Check if meeting is currently live.
     */
    public function getIsLiveAttribute(): bool
    {
        $now = now();
        return $this->is_active 
            && $this->starts_at <= $now 
            && $this->ends_at >= $now;
    }

    /**
     * Get meeting status.
     */
    public function getStatusAttribute(): string
    {
        $now = now();
        
        if (!$this->is_active) {
            return 'cancelled';
        }
        
        if ($this->ends_at < $now) {
            return 'ended';
        }
        
        if ($this->starts_at <= $now && $this->ends_at >= $now) {
            return 'live';
        }
        
        if ($this->starts_at->diffInHours($now) <= 1) {
            return 'starting_soon';
        }
        
        return 'upcoming';
    }

    /**
     * Get platform icon.
     */
    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'zoom' => '/icons/zoom.svg',
            'google_meet' => '/icons/google-meet.svg',
            'youtube' => '/icons/youtube.svg',
            default => '/icons/video.svg',
        };
    }

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_registrations')
            ->withPivot(['registered_at', 'attended', 'attended_at'])
            ->withTimestamps();
    }

    // Scopes

    public function scopeLive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at', 'asc');
    }

    public function scopeForChurch($query, int $churchId)
    {
        return $query->where('church_id', $churchId);
    }
}
```

### 7.2 Meeting Controller

```php
<?php
// common/foundation/src/LiveMeetings/Controllers/MeetingController.php

namespace Common\LiveMeetings\Controllers;

use Common\Core\BaseController;
use Common\LiveMeetings\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends BaseController
{
    /**
     * List meetings (upcoming + live).
     */
    public function index(Request $request)
    {
        $query = Meeting::with(['event', 'church', 'host'])
            ->where('is_active', true);
        
        // Filter by church
        if ($request->has('church_id')) {
            $query->where('church_id', $request->church_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            match ($request->status) {
                'live' => $query->live(),
                'upcoming' => $query->upcoming(),
                default => null,
            };
        }
        
        $meetings = $query
            ->orderBy('starts_at', 'asc')
            ->paginate($request->get('per_page', 20));
        
        return $this->success(['meetings' => $meetings]);
    }

    /**
     * Get currently live meetings.
     */
    public function live(Request $request)
    {
        $meetings = Meeting::with(['event', 'church', 'host'])
            ->live()
            ->get();
        
        return $this->success(['meetings' => $meetings]);
    }

    /**
     * Get a single meeting.
     */
    public function show(Request $request, Meeting $meeting)
    {
        $meeting->load(['event', 'church', 'host']);
        
        // Check if user is registered (if registration required)
        if ($meeting->requires_registration && $request->user()) {
            $meeting->is_registered = $meeting->registrations()
                ->where('user_id', $request->user()->id)
                ->exists();
        }
        
        return $this->success(['meeting' => $meeting]);
    }

    /**
     * Register for a meeting.
     */
    public function register(Request $request, Meeting $meeting)
    {
        if (!$meeting->requires_registration) {
            return $this->error('This meeting does not require registration.');
        }
        
        if ($meeting->max_participants) {
            $currentCount = $meeting->registrations()->count();
            if ($currentCount >= $meeting->max_participants) {
                return $this->error('This meeting is full.');
            }
        }
        
        $meeting->registrations()->syncWithoutDetaching([
            $request->user()->id => ['registered_at' => now()]
        ]);
        
        return $this->success(['message' => 'Registration successful']);
    }

    /**
     * Unregister from a meeting.
     */
    public function unregister(Request $request, Meeting $meeting)
    {
        $meeting->registrations()->detach($request->user()->id);
        
        return $this->success(['message' => 'Registration cancelled']);
    }

    /**
     * Mark attendance (for check-in).
     */
    public function checkIn(Request $request, Meeting $meeting)
    {
        if (!$meeting->is_live) {
            return $this->error('Meeting is not currently live.');
        }
        
        $meeting->registrations()->updateExistingPivot($request->user()->id, [
            'attended' => true,
            'attended_at' => now(),
        ]);
        
        return $this->success(['message' => 'Checked in successfully']);
    }
}
```

### 7.3 Admin Meeting Controller

```php
<?php
// common/foundation/src/LiveMeetings/Controllers/Admin/MeetingController.php

namespace Common\LiveMeetings\Controllers\Admin;

use Common\Core\BaseController;
use Common\LiveMeetings\Models\Meeting;
use Common\LiveMeetings\Requests\CreateMeetingRequest;
use Common\LiveMeetings\Services\ZoomService;
use Illuminate\Http\Request;

class MeetingController extends BaseController
{
    public function __construct(
        protected ZoomService $zoomService
    ) {}

    /**
     * List all meetings (admin).
     */
    public function index(Request $request)
    {
        $query = Meeting::with(['event', 'church', 'host'])
            ->withCount('registrations');
        
        // Search
        if ($request->has('query')) {
            $query->where('title', 'like', "%{$request->query}%");
        }
        
        // Filter by status
        if ($request->has('status')) {
            match ($request->status) {
                'live' => $query->live(),
                'upcoming' => $query->upcoming(),
                'ended' => $query->where('ends_at', '<', now()),
                'cancelled' => $query->where('is_active', false),
                default => null,
            };
        }
        
        $meetings = $query
            ->orderBy('starts_at', 'desc')
            ->paginate($request->get('per_page', 20));
        
        return $this->success(['pagination' => $meetings]);
    }

    /**
     * Create a new meeting.
     */
    public function store(CreateMeetingRequest $request)
    {
        $data = $request->validated();
        
        // Auto-create Zoom meeting if enabled
        if ($data['platform'] === 'zoom' && 
            config('services.zoom.enabled') && 
            config('services.zoom.auto_create')) {
            
            $zoomMeeting = $this->zoomService->createMeeting([
                'topic' => $data['title'],
                'start_time' => $data['starts_at'],
                'duration' => $data['ends_at']->diffInMinutes($data['starts_at']),
                'timezone' => $data['timezone'] ?? config('app.timezone'),
            ]);
            
            $data['meeting_url'] = $zoomMeeting['join_url'];
            $data['meeting_id'] = $zoomMeeting['id'];
            $data['meeting_password'] = $zoomMeeting['password'] ?? null;
        }
        
        $meeting = Meeting::create($data);
        
        return $this->success(['meeting' => $meeting], 201);
    }

    /**
     * Update a meeting.
     */
    public function update(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'platform' => 'sometimes|in:zoom,google_meet,youtube,custom',
            'meeting_url' => 'sometimes|url',
            'meeting_password' => 'nullable|string|max:50',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'max_participants' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $meeting->update($data);
        
        return $this->success(['meeting' => $meeting]);
    }

    /**
     * Delete a meeting.
     */
    public function destroy(Meeting $meeting)
    {
        // Optionally delete from Zoom
        if ($meeting->platform === 'zoom' && 
            $meeting->meeting_id && 
            config('services.zoom.enabled')) {
            
            $this->zoomService->deleteMeeting($meeting->meeting_id);
        }
        
        $meeting->delete();
        
        return $this->success(['message' => 'Meeting deleted']);
    }

    /**
     * Get meeting statistics.
     */
    public function stats(Meeting $meeting)
    {
        return $this->success([
            'total_registrations' => $meeting->registrations()->count(),
            'attended' => $meeting->registrations()->wherePivot('attended', true)->count(),
            'registrations_by_day' => $meeting->registrations()
                ->selectRaw('DATE(registered_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date'),
        ]);
    }
}
```

### 7.4 Zoom Service

```php
<?php
// common/foundation/src/LiveMeetings/Services/ZoomService.php

namespace Common\LiveMeetings\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ZoomService
{
    protected string $baseUrl = 'https://api.zoom.us/v2';

    /**
     * Get OAuth access token (Server-to-Server OAuth).
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('zoom_access_token', 3500, function () {
            $response = Http::withBasicAuth(
                config('services.zoom.client_id'),
                config('services.zoom.client_secret')
            )->asForm()->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => config('services.zoom.account_id'),
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to get Zoom access token: ' . $response->body());
            }
            
            return $response->json('access_token');
        });
    }

    /**
     * Make authenticated API request.
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $response = Http::withToken($this->getAccessToken())
            ->$method($this->baseUrl . $endpoint, $data);
        
        if (!$response->successful()) {
            throw new \Exception('Zoom API error: ' . $response->body());
        }
        
        return $response->json();
    }

    /**
     * Create a Zoom meeting.
     */
    public function createMeeting(array $data): array
    {
        return $this->request('post', '/users/me/meetings', [
            'topic' => $data['topic'],
            'type' => 2, // Scheduled meeting
            'start_time' => $data['start_time']->toIso8601String(),
            'duration' => $data['duration'],
            'timezone' => $data['timezone'],
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => true,
                'mute_upon_entry' => true,
                'waiting_room' => false,
            ],
        ]);
    }

    /**
     * Get meeting details.
     */
    public function getMeeting(string $meetingId): array
    {
        return $this->request('get', "/meetings/{$meetingId}");
    }

    /**
     * Update a meeting.
     */
    public function updateMeeting(string $meetingId, array $data): array
    {
        return $this->request('patch', "/meetings/{$meetingId}", $data);
    }

    /**
     * Delete a meeting.
     */
    public function deleteMeeting(string $meetingId): void
    {
        $this->request('delete', "/meetings/{$meetingId}");
    }

    /**
     * Get meeting participants (for ended meetings).
     */
    public function getMeetingParticipants(string $meetingId): array
    {
        return $this->request('get', "/past_meetings/{$meetingId}/participants");
    }
}
```

### 7.5 Frontend: Live Badge Component

```tsx
// resources/client/plugins/live-meetings/components/LiveBadge.tsx

import { motion } from 'framer-motion';
import clsx from 'clsx';

interface LiveBadgeProps {
  size?: 'sm' | 'md' | 'lg';
  pulse?: boolean;
  className?: string;
}

export function LiveBadge({ size = 'md', pulse = true, className }: LiveBadgeProps) {
  const sizeClasses = {
    sm: 'text-xs px-1.5 py-0.5',
    md: 'text-sm px-2 py-1',
    lg: 'text-base px-3 py-1.5',
  };

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 font-semibold text-white bg-danger rounded-full',
        sizeClasses[size],
        className
      )}
    >
      {pulse && (
        <motion.span
          className="w-2 h-2 bg-white rounded-full"
          animate={{
            scale: [1, 1.2, 1],
            opacity: [1, 0.7, 1],
          }}
          transition={{
            duration: 1.5,
            repeat: Infinity,
            ease: 'easeInOut',
          }}
        />
      )}
      LIVE
    </span>
  );
}
```

### 7.6 Frontend: Join Meeting Button

```tsx
// resources/client/plugins/live-meetings/components/JoinMeetingButton.tsx

import { Button } from '@common/ui/buttons/button';
import { VideoCameraIcon } from '@heroicons/react/24/solid';
import { Meeting } from '../types';
import { LiveBadge } from './LiveBadge';

interface JoinMeetingButtonProps {
  meeting: Meeting;
  size?: 'sm' | 'md' | 'lg';
  showLiveBadge?: boolean;
}

export function JoinMeetingButton({ 
  meeting, 
  size = 'md',
  showLiveBadge = true 
}: JoinMeetingButtonProps) {
  const isLive = meeting.is_live;
  const canJoin = isLive || meeting.status === 'starting_soon';

  if (!canJoin) {
    return null;
  }

  return (
    <div className="flex items-center gap-2">
      {showLiveBadge && isLive && <LiveBadge size="sm" />}
      
      <Button
        variant="flat"
        color={isLive ? 'danger' : 'primary'}
        size={size}
        startIcon={<VideoCameraIcon className="w-5 h-5" />}
        onClick={() => window.open(meeting.meeting_url, '_blank')}
      >
        {isLive ? 'Join Live' : 'Join Meeting'}
      </Button>
    </div>
  );
}
```

### 7.7 Frontend: Upcoming Meetings Widget

```tsx
// resources/client/plugins/live-meetings/components/UpcomingMeetingsWidget.tsx

import { useUpcomingMeetings, useLiveMeetings } from '../hooks';
import { MeetingCard } from './MeetingCard';
import { LiveBadge } from './LiveBadge';
import { CalendarIcon } from '@heroicons/react/24/outline';
import { Link } from 'react-router-dom';

interface UpcomingMeetingsWidgetProps {
  limit?: number;
  churchId?: number;
  showLive?: boolean;
}

export function UpcomingMeetingsWidget({ 
  limit = 5, 
  churchId,
  showLive = true 
}: UpcomingMeetingsWidgetProps) {
  const { data: liveData } = useLiveMeetings();
  const { data: upcomingData, isLoading } = useUpcomingMeetings({ limit, churchId });

  const liveMeetings = liveData?.meetings ?? [];
  const upcomingMeetings = upcomingData?.meetings?.data ?? [];

  if (isLoading) {
    return (
      <div className="p-4 bg-paper rounded-lg border border-divider">
        <div className="animate-pulse space-y-3">
          <div className="h-5 w-32 bg-alt rounded" />
          <div className="h-20 bg-alt rounded" />
          <div className="h-20 bg-alt rounded" />
        </div>
      </div>
    );
  }

  const hasContent = liveMeetings.length > 0 || upcomingMeetings.length > 0;

  if (!hasContent) {
    return (
      <div className="p-6 bg-paper rounded-lg border border-divider text-center">
        <CalendarIcon className="w-12 h-12 mx-auto mb-2 text-muted opacity-50" />
        <p className="text-muted">No upcoming meetings</p>
      </div>
    );
  }

  return (
    <div className="bg-paper rounded-lg border border-divider overflow-hidden">
      <div className="p-4 border-b border-divider flex items-center justify-between">
        <h3 className="font-semibold text-main flex items-center gap-2">
          <CalendarIcon className="w-5 h-5" />
          Meetings
        </h3>
        {liveMeetings.length > 0 && (
          <LiveBadge size="sm" />
        )}
      </div>

      <div className="divide-y divide-divider">
        {/* Live Meetings First */}
        {showLive && liveMeetings.map((meeting) => (
          <MeetingCard key={meeting.id} meeting={meeting} isLive />
        ))}

        {/* Upcoming Meetings */}
        {upcomingMeetings.slice(0, limit - liveMeetings.length).map((meeting) => (
          <MeetingCard key={meeting.id} meeting={meeting} />
        ))}
      </div>

      <Link
        to="/meetings"
        className="block p-3 text-center text-sm text-primary hover:bg-alt/50 border-t border-divider"
      >
        View all meetings
      </Link>
    </div>
  );
}
```

---

## 8. Implementation Tasks

### Task 1: Database Migrations

**Files to create:**
- `database/migrations/0010_01_01_000001_create_notification_tables.php`
- `database/migrations/0010_01_01_000002_create_meeting_tables.php`

**Steps:**
1. Create notifications table (Laravel default with additions)
2. Create notification_preferences table
3. Create notification_logs table
4. Create notification_templates table
5. Create push_subscriptions table
6. Create meetings table
7. Create meeting_registrations table
8. Add indexes for performance

### Task 2: Notification Models

**Files to create:**
- `common/foundation/src/Notifications/Models/NotificationPreference.php`
- `common/foundation/src/Notifications/Models/NotificationLog.php`
- `common/foundation/src/Notifications/Models/NotificationTemplate.php`
- `common/foundation/src/Notifications/Models/PushSubscription.php`

**Steps:**
1. Create NotificationPreference model with user relationship
2. Create NotificationLog model for tracking delivery
3. Create NotificationTemplate model for admin templates
4. Create PushSubscription model for OneSignal player IDs
5. Add relationships to User model

### Task 3: Meeting Model & Policy

**Files to create:**
- `common/foundation/src/LiveMeetings/Models/Meeting.php`
- `common/foundation/src/LiveMeetings/Models/MeetingRegistration.php`
- `common/foundation/src/LiveMeetings/Policies/MeetingPolicy.php`

**Steps:**
1. Create Meeting model with is_live accessor
2. Create MeetingRegistration pivot model
3. Create MeetingPolicy with view/create/update/delete/host permissions
4. Register policy in AuthServiceProvider

### Task 4: Notification Service

**Files to create:**
- `common/foundation/src/Notifications/Services/NotificationService.php`

**Steps:**
1. Create NotificationService for sending notifications
2. Implement channel preference checking
3. Implement logging for delivery tracking
4. Create helper methods for common notification patterns

### Task 5: Base Notification Classes

**Files to create:**
- `common/foundation/src/Notifications/Notifications/BaseNotification.php`
- `common/foundation/src/Notifications/Notifications/NewSermonNotification.php`
- `common/foundation/src/Notifications/Notifications/PrayerUpdateNotification.php`
- `common/foundation/src/Notifications/Notifications/EventReminderNotification.php`
- `common/foundation/src/Notifications/Notifications/GroupPostNotification.php`
- `common/foundation/src/Notifications/Notifications/ChatMessageNotification.php`
- `common/foundation/src/Notifications/Notifications/MeetingLiveNotification.php`
- `common/foundation/src/Notifications/Notifications/NewMemberNotification.php`

**Steps:**
1. Create BaseNotification with channel logic
2. Implement each notification type with proper formatting
3. Include mail, OneSignal, Twilio, and broadcast representations
4. Add templates for email content

### Task 6: Notification Controllers

**Files to create:**
- `common/foundation/src/Notifications/Controllers/NotificationController.php`
- `common/foundation/src/Notifications/Controllers/NotificationPreferenceController.php`
- `common/foundation/src/Notifications/Controllers/Admin/NotificationLogController.php`
- `common/foundation/src/Notifications/Controllers/Admin/NotificationTemplateController.php`

**Steps:**
1. Create NotificationController for listing/marking read
2. Create NotificationPreferenceController for user settings
3. Create admin controllers for logs and templates
4. Add push subscription registration endpoints

### Task 7: Meeting Controllers

**Files to create:**
- `common/foundation/src/LiveMeetings/Controllers/MeetingController.php`
- `common/foundation/src/LiveMeetings/Controllers/Admin/MeetingController.php`
- `common/foundation/src/LiveMeetings/Requests/CreateMeetingRequest.php`

**Steps:**
1. Create public MeetingController for listing/viewing/registering
2. Create Admin MeetingController for CRUD operations
3. Create form requests with validation
4. Add statistics endpoint for attendance tracking

### Task 8: External Service Integrations

**Files to create:**
- `common/foundation/src/LiveMeetings/Services/ZoomService.php`
- `common/foundation/src/Notifications/Channels/OneSignalChannel.php` (if not using package)

**Steps:**
1. Install Laravel notification channel packages
2. Create ZoomService for meeting API
3. Configure OneSignal in config/services.php
4. Configure Twilio in config/services.php
5. Add service credentials to .env.example

### Task 9: Event Listeners

**Files to create:**
- `common/foundation/src/Notifications/Listeners/SendSermonNotification.php`
- `common/foundation/src/Notifications/Listeners/SendPrayerUpdateNotification.php`
- `common/foundation/src/Notifications/Listeners/SendEventReminderNotification.php`
- `common/foundation/src/Notifications/Listeners/SendMeetingLiveNotification.php`

**Steps:**
1. Create listeners for each notification trigger
2. Register listeners in EventServiceProvider
3. Implement job scheduling for event reminders (24h, 1h before)
4. Add queue configuration for notification processing

### Task 10: API Routes

**Files to modify:**
- `routes/api.php`

**Routes to add:**
```php
// Notifications
Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
    
    // Preferences
    Route::get('/preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/preferences', [NotificationPreferenceController::class, 'update']);
    Route::post('/push/register', [NotificationPreferenceController::class, 'registerPush']);
    Route::post('/push/unregister', [NotificationPreferenceController::class, 'unregisterPush']);
});

// Meetings
Route::prefix('meetings')->group(function () {
    Route::get('/', [MeetingController::class, 'index']);
    Route::get('/live', [MeetingController::class, 'live']);
    Route::get('/{meeting}', [MeetingController::class, 'show']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{meeting}/register', [MeetingController::class, 'register']);
        Route::delete('/{meeting}/register', [MeetingController::class, 'unregister']);
        Route::post('/{meeting}/check-in', [MeetingController::class, 'checkIn']);
    });
});

// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'isAdmin'])->group(function () {
    Route::apiResource('meetings', Admin\MeetingController::class);
    Route::get('meetings/{meeting}/stats', [Admin\MeetingController::class, 'stats']);
    
    Route::get('notification-logs', [Admin\NotificationLogController::class, 'index']);
    Route::apiResource('notification-templates', Admin\NotificationTemplateController::class);
});
```

### Task 11: Settings Configuration

**Files to modify:**
- `config/services.php`
- `database/seeders/SettingsSeeder.php`

**Steps:**
1. Add OneSignal configuration
2. Add Twilio configuration
3. Add Zoom configuration
4. Add default notification channel matrix
5. Create admin settings sections (21: Notifications, 22: Live Meetings)

### Task 12: Frontend - Notification Types & Hooks

**Files to create:**
- `resources/client/plugins/notifications/types.ts`
- `resources/client/plugins/notifications/hooks/useNotifications.ts`
- `resources/client/plugins/notifications/hooks/useNotificationPreferences.ts`
- `resources/client/plugins/notifications/hooks/useMarkAsRead.ts`
- `resources/client/plugins/notifications/hooks/useUpdatePreferences.ts`
- `resources/client/plugins/notifications/hooks/index.ts`

**Steps:**
1. Define TypeScript types for notifications and preferences
2. Create React Query hooks for fetching notifications
3. Create mutation hooks for marking read
4. Create hooks for preferences management
5. Add real-time subscription via Echo

### Task 13: Frontend - Notification Components

**Files to create:**
- `resources/client/plugins/notifications/components/NotificationDropdown.tsx`
- `resources/client/plugins/notifications/components/NotificationItem.tsx`
- `resources/client/plugins/notifications/components/NotificationCenter.tsx`
- `resources/client/plugins/notifications/components/NotificationPreferencesForm.tsx`
- `resources/client/plugins/notifications/components/UnreadNotificationBadge.tsx`
- `resources/client/plugins/notifications/components/index.ts`

**Steps:**
1. Create NotificationDropdown with bell icon
2. Create NotificationItem for individual notifications
3. Create NotificationCenter full page view
4. Create preferences form with toggles
5. Create unread badge component

### Task 14: Frontend - Meeting Types & Hooks

**Files to create:**
- `resources/client/plugins/live-meetings/types.ts`
- `resources/client/plugins/live-meetings/hooks/useMeetings.ts`
- `resources/client/plugins/live-meetings/hooks/useLiveMeetings.ts`
- `resources/client/plugins/live-meetings/hooks/useUpcomingMeetings.ts`
- `resources/client/plugins/live-meetings/hooks/index.ts`

**Steps:**
1. Define TypeScript types for meetings
2. Create hooks for fetching meetings
3. Create hook for live meetings with polling
4. Create registration mutation hooks

### Task 15: Frontend - Meeting Components

**Files to create:**
- `resources/client/plugins/live-meetings/components/LiveBadge.tsx`
- `resources/client/plugins/live-meetings/components/JoinMeetingButton.tsx`
- `resources/client/plugins/live-meetings/components/UpcomingMeetingsWidget.tsx`
- `resources/client/plugins/live-meetings/components/MeetingCard.tsx`
- `resources/client/plugins/live-meetings/components/index.ts`

**Steps:**
1. Create pulsing LiveBadge component
2. Create JoinMeetingButton with platform icons
3. Create UpcomingMeetingsWidget for sidebar
4. Create MeetingCard for list views

### Task 16: Frontend - Pages

**Files to create:**
- `resources/client/plugins/notifications/pages/NotificationsPage.tsx`
- `resources/client/plugins/live-meetings/pages/MeetingsPage.tsx`
- `resources/client/plugins/live-meetings/admin/MeetingManagerPage.tsx`

**Steps:**
1. Create NotificationsPage with filters
2. Create MeetingsPage showing live + upcoming
3. Create admin MeetingManagerPage with CRUD
4. Add routes to router configuration

### Task 17: Admin UI Integration

**Files to modify:**
- Admin Settings pages for Notifications (Section 21)
- Admin Settings pages for Live Meetings (Section 22)

**Steps:**
1. Create Notifications settings page (OneSignal, Twilio, defaults)
2. Create Live Meetings settings page (Zoom, Google Meet)
3. Create Notification Templates manager
4. Create Notification Logs viewer
5. Create Meeting Manager dashboard

### Task 18: OneSignal SDK Integration

**Files to create:**
- `resources/client/common/onesignal/onesignal.ts`

**Steps:**
1. Install OneSignal SDK: `npm install react-onesignal`
2. Initialize OneSignal with app ID
3. Handle push subscription registration
4. Sync player ID with backend on login

### Task 19: Scheduler Jobs

**Files to create:**
- `app/Console/Commands/SendEventReminders.php`
- `app/Console/Commands/CheckMeetingsLive.php`

**Steps:**
1. Create command for 24h event reminders
2. Create command for 1h event reminders
3. Create command for meeting live notifications
4. Register commands in Console/Kernel.php

### Task 20: Feature Tests

**Files to create:**
- `tests/Feature/Notifications/NotificationTest.php`
- `tests/Feature/Notifications/NotificationPreferenceTest.php`
- `tests/Feature/LiveMeetings/MeetingTest.php`

**Steps:**
1. Test notification creation and delivery
2. Test preference management
3. Test meeting CRUD operations
4. Test live meeting detection
5. Test registration flow

### Task 21: Documentation

**Files to create:**
- `docs/plugins/notifications.md`
- `docs/plugins/live-meetings.md`

**Steps:**
1. Document notification system architecture
2. Document OneSignal setup guide
3. Document Twilio setup guide
4. Document Zoom API setup guide
5. Document admin configuration options

---

## 9. Testing Strategy

### Unit Tests

```php
// Notification preference checking
public function test_notification_respects_user_preferences()
{
    $user = User::factory()->create();
    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => 'sermon',
        'push_enabled' => false,
        'email_enabled' => true,
    ]);
    
    $notification = new NewSermonNotification($sermon);
    $channels = $notification->via($user);
    
    $this->assertNotContains(OneSignalChannel::class, $channels);
    $this->assertContains('mail', $channels);
}

// Meeting is_live accessor
public function test_meeting_is_live_when_in_progress()
{
    $meeting = Meeting::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);
    
    $this->assertTrue($meeting->is_live);
}
```

### Feature Tests

```php
// Notification API
public function test_user_can_list_notifications()
{
    $user = User::factory()->create();
    Notification::factory()->count(5)->create(['notifiable_id' => $user->id]);
    
    $response = $this->actingAs($user)->getJson('/api/v1/notifications');
    
    $response->assertOk()
        ->assertJsonCount(5, 'notifications.data');
}

// Meeting registration
public function test_user_can_register_for_meeting()
{
    $user = User::factory()->create();
    $meeting = Meeting::factory()->create(['requires_registration' => true]);
    
    $response = $this->actingAs($user)
        ->postJson("/api/v1/meetings/{$meeting->id}/register");
    
    $response->assertOk();
    $this->assertTrue($meeting->registrations()->where('user_id', $user->id)->exists());
}
```

---

## 10. Deployment Checklist

### Environment Variables

```env
# OneSignal (Push Notifications)
ONESIGNAL_APP_ID=your-app-id
ONESIGNAL_REST_API_KEY=your-rest-api-key
ONESIGNAL_SAFARI_WEB_ID=web.onesignal.auto.xxx

# Twilio (SMS)
TWILIO_SID=your-account-sid
TWILIO_TOKEN=your-auth-token
TWILIO_FROM=+1234567890
TWILIO_ENABLED=false

# Zoom (Video Meetings)
ZOOM_CLIENT_ID=your-client-id
ZOOM_CLIENT_SECRET=your-client-secret
ZOOM_ACCOUNT_ID=your-account-id
ZOOM_ENABLED=false
```

### OneSignal Setup

1. Create account at https://onesignal.com
2. Create a new app
3. Configure Web Push (Chrome, Firefox, Safari)
4. Copy App ID and REST API Key to .env
5. Add OneSignal SDK script to frontend

### Twilio Setup

1. Create account at https://twilio.com
2. Get Account SID and Auth Token
3. Purchase a phone number
4. Verify phone numbers in test mode
5. Enable in .env when ready

### Zoom Setup

1. Create app at https://marketplace.zoom.us
2. Choose "Server-to-Server OAuth" app type
3. Get Client ID, Client Secret, Account ID
4. Grant Meeting:Write and Meeting:Read scopes
5. Activate app and copy credentials

### Scheduler Setup

```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker

```bash
# For production, use Supervisor
php artisan queue:work --queue=notifications,default
```

---

## Summary

**Plan 10** delivers the final Phase 2 features:

| Feature | Status |
|---------|--------|
| Multi-channel Notifications | 🔲 |
| OneSignal Push Integration | 🔲 |
| Twilio SMS Integration | 🔲 |
| Notification Preferences | 🔲 |
| Notification Center UI | 🔲 |
| Live Meetings Model | 🔲 |
| Zoom API Integration | 🔲 |
| "Live Now" Badge | 🔲 |
| Meeting Registration | 🔲 |
| Admin Notification Templates | 🔲 |
| Admin Meeting Manager | 🔲 |

**Total Tasks**: 21  
**Estimated Time**: 4-6 hours (with Opus agent)

After Plan 10, **Phase 2 (Build the Community) is complete!** 🎉

Next: Phase 3 — Grow & Monetize (Blog, Volunteers, Fundraising, Stories, Pastoral Care)
