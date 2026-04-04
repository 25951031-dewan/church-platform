# Sngine Community Platform - Complete Technical Report

> **Purpose**: Reference document for building a Church Community App by merging BeMusic backend with Sngine Community features.
> **Scanned**: 2026-03-27
> **Version**: Sngine Community (Latest)

---

## Table of Contents

1. [Tech Stack Overview](#1-tech-stack-overview)
2. [Architecture & Design Patterns](#2-architecture--design-patterns)
3. [Database Schema (130+ Tables)](#3-database-schema-130-tables)
4. [Backend Features](#4-backend-features)
5. [Frontend Features](#5-frontend-features)
6. [Social Features](#6-social-features)
7. [Media & Content Features](#7-media--content-features)
8. [Monetization & E-Commerce](#8-monetization--e-commerce)
9. [Payment Gateways (17+)](#9-payment-gateways-17)
10. [Real-Time Communication](#10-real-time-communication)
11. [Admin Panel Features](#11-admin-panel-features)
12. [Third-Party Integrations](#12-third-party-integrations)
13. [API Architecture](#13-api-architecture)
14. [Authentication System](#14-authentication-system)
15. [Directory Structure](#15-directory-structure)
16. [Church App Mapping Notes](#16-church-app-mapping-notes)

---

## 1. Tech Stack Overview

### Backend

| Component        | Technology                          |
|------------------|-------------------------------------|
| Language         | PHP 8.2+ (runs on 8.3 via Docker)  |
| Database         | MySQL / MariaDB (mysqli)            |
| Template Engine  | Smarty 5.5                          |
| Web Server       | Apache (mod_rewrite for clean URLs) |
| WebSocket        | Workerman PHPSocket.io 2.2          |
| Container        | Docker + Docker Compose             |
| Package Manager  | Composer (PHP), npm (JS)            |

### Frontend

| Component        | Technology                     |
|------------------|--------------------------------|
| CSS Framework    | Bootstrap 5.3.3                |
| JS Library       | jQuery 3.x                     |
| Rich Text Editor | TinyMCE 7.6.1                 |
| Date/Time        | Moment.js 2.30.1              |
| Stories UI       | Zuck.js 1.6.0                 |
| Carousel         | Slick Carousel 1.8.1          |
| Client Templates | Mustache 4.2.0                |
| Icon Set         | Bootstrap Icons                |
| Image Cropping   | RCrop                          |
| Audio Recording  | Web Audio Recorder             |
| Tag Input        | Tagify 4.33.2                 |
| Sticky Sidebar   | Theia Sticky Sidebar 1.7.0    |
| Autocomplete     | Triggered Autocomplete         |
| Auto-expand      | Autosize 6.0.1                |
| Timer            | EasyTimer.js 4.6.0            |

### PHP Dependencies (composer.json)

| Package                    | Version  | Purpose                     |
|----------------------------|----------|-----------------------------|
| smarty/smarty              | 5.5      | Template engine             |
| php-gettext/gettext        | 5.7      | Internationalization (i18n) |
| ezyang/htmlpurifier        | 4.18     | XSS protection              |
| mobiledetect/mobiledetectlib | 4.8   | Device detection            |
| hybridauth/hybridauth      | 3.12     | Social OAuth login          |
| google/recaptcha           | 1.3      | Bot protection              |
| sonata-project/google-authenticator | 2.3 | Two-factor auth (TOTP) |
| twilio/sdk                 | 8.7      | SMS messaging               |
| phpmailer/phpmailer        | 6.10     | Email sending               |
| claviska/simpleimage       | 4.2      | Image processing            |
| stripe/stripe-php          | 17.5     | Stripe payments             |
| aws/aws-sdk-php            | 3.356    | AWS S3 cloud storage        |
| google/cloud-storage       | 1.48     | Google Cloud Storage        |
| oscarotero/embed           | 4.4      | Media URL embedding         |
| livekit/server-sdk-php     | 1.3      | LiveKit video/streaming     |
| workerman/phpsocket.io     | 2.2      | WebSocket real-time         |
| symfony/yaml               | 7.3      | YAML parsing                |
| fakerphp/faker             | 1.24     | Test data generation        |

---

## 2. Architecture & Design Patterns

### Pattern: Trait-Based OOP + Functional AJAX

Sngine uses a **hybrid architecture** — not traditional MVC, but a trait-composition pattern:

```
┌──────────────────────────────────────────────┐
│               class User                      │
│  (includes/class-user.php — 226KB)           │
│                                              │
│  Uses 48 Traits:                             │
│  ├── system.php (core user operations)       │
│  ├── posts.php (post CRUD)                   │
│  ├── comments.php (comment system)           │
│  ├── chat.php (messaging)                    │
│  ├── friends.php (friend system)             │
│  ├── groups.php (group management)           │
│  ├── events.php (event system)               │
│  ├── pages.php (page management)             │
│  ├── blogs.php (blog system)                 │
│  ├── forums.php (discussion boards)          │
│  ├── videos.php (video management)           │
│  ├── reels.php (short-form video)            │
│  ├── courses.php (learning)                  │
│  ├── games.php (gaming)                      │
│  ├── jobs.php (job listings)                 │
│  ├── marketplace.php (e-commerce)            │
│  ├── wallet.php (digital wallet)             │
│  ├── payments.php (payment processing)       │
│  ├── monetization.php (creator earnings)     │
│  ├── packages.php (subscription plans)       │
│  ├── affiliates.php (referral system)        │
│  ├── ads.php (advertising)                   │
│  ├── livestream.php (live streaming)         │
│  ├── calls.php (voice/video calls)           │
│  ├── notifications.php (notification system) │
│  ├── photos.php (photo management)           │
│  ├── reports.php (content moderation)        │
│  ├── logger.php (activity logging)           │
│  ├── tools.php (utility functions)           │
│  ├── support.php (support tickets)           │
│  ├── reviews.php (rating system)             │
│  ├── merits.php (achievement badges)         │
│  └── ... (48 total trait files)              │
└──────────────────────────────────────────────┘
```

### Request Flow

```
Browser Request
    │
    ├─→ Entry PHP file (index.php, profile.php, groups.php, etc.)
    │       │
    │       ├─→ bootstrap.php (init session, load config)
    │       ├─→ bootloader.php (load User class, check auth)
    │       └─→ Smarty Template (.tpl) → HTML Response
    │
    ├─→ AJAX Request (/includes/ajax/{category}/{action}.php)
    │       └─→ JSON Response
    │
    ├─→ REST API (/apis/php/{module}/{endpoint})
    │       └─→ JSON Response
    │
    └─→ WebSocket (/sockets/php/socket.php)
            └─→ Real-time Events
```

### Global Functions

`includes/functions.php` (288KB) — Contains all shared utility functions used across the platform.

---

## 3. Database Schema (130+ Tables)

### Core User Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `users`                | Primary user accounts                |
| `users_sessions`       | Session tracking                     |
| `users_accounts`       | Connected OAuth accounts             |
| `users_blocks`         | Block lists                          |
| `users_groups`         | User role groups                     |
| `users_packages`       | User subscription packages           |
| `permissions_groups`   | Role-based permissions               |

### Social/Relationship Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `friends`              | Friend connections                   |
| `followings`           | Follow relationships                 |
| `groups`               | Community groups                     |
| `groups_members`       | Group membership                     |
| `groups_admins`        | Group admin roles                    |
| `pages`                | Community pages                      |
| `pages_members`        | Page followers/members               |
| `events`               | Event listings                       |
| `events_members`       | Event attendance                     |

### Content Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `posts`                | All post types                       |
| `posts_media`          | Post media attachments               |
| `posts_photos`         | Photo posts                          |
| `posts_videos`         | Video posts                          |
| `posts_reels`          | Short-form video posts               |
| `posts_comments`       | Comment threads                      |
| `posts_reactions`      | Emoji reactions                      |
| `posts_saved`          | Saved/bookmarked posts               |
| `posts_hidden`         | Hidden posts                         |
| `blogs`                | Blog articles                        |
| `forums`               | Forum categories                     |
| `forums_threads`       | Forum discussion threads             |
| `forums_replies`       | Forum replies                        |

### Communication Tables
| Table                       | Purpose                         |
|-----------------------------|---------------------------------|
| `conversations`             | Chat conversations              |
| `conversations_messages`    | Chat messages                   |
| `conversations_users`       | Conversation participants       |
| `conversations_calls`       | Voice/video call logs           |
| `notifications`             | User notifications              |

### Monetization Tables
| Table                       | Purpose                         |
|-----------------------------|---------------------------------|
| `wallet_transactions`       | Wallet transaction history      |
| `wallet_payments`           | Wallet payment records          |
| `orders`                    | E-commerce orders               |
| `orders_items`              | Order line items                |
| `packages`                  | Subscription packages           |
| `packages_payments`         | Package payment records         |
| `payments`                  | General payment records         |
| `log_payments`              | Payment audit log               |
| `monetization_payments`     | Creator payout records          |
| `monetization_plans`        | Creator monetization plans      |
| `affiliate_payments`        | Affiliate commissions           |

### Marketplace Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `market` (products)    | Marketplace product listings         |
| `reviews`              | Product/seller reviews               |
| `offers`               | Deal/offer listings                  |

### Admin/System Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `system_options`       | Global configuration key-values      |
| `reports`              | Content report/flags                 |
| `reports_categories`   | Report categories                    |
| `blacklist`            | Banned users/IPs                     |
| `log_sessions`         | Session activity logs                |
| `ads_campaigns`        | Ad campaign management               |
| `support_tickets`      | Support ticket system                |
| `support_tickets_replies` | Support responses                 |

### Misc Tables
| Table                  | Purpose                              |
|------------------------|--------------------------------------|
| `hashtags`             | Hashtag registry                     |
| `hashtags_posts`       | Hashtag-post associations            |
| `custom_fields`        | Custom profile fields                |
| `custom_fields_values` | Custom field values                  |
| `languages`            | Supported languages                  |
| `system_countries`     | Country list                         |
| `system_genders`       | Gender options                       |
| `system_currencies`    | Currency definitions                 |
| `invitations`          | User invitations                     |
| `announcements`        | System announcements                 |
| `stickers`             | Chat/comment stickers                |

---

## 4. Backend Features

### Core Modules (48 Trait Files in `/includes/traits/`)

1. **system.php** — Core user operations, authentication, session management
2. **posts.php** — Post CRUD, post types, post visibility, scheduling
3. **comments.php** — Nested comments, reactions, moderation
4. **chat.php** — Direct messaging, group chats, message status
5. **friends.php** — Friend requests, mutual friends, friend lists
6. **groups.php** — Group CRUD, membership, admin tools
7. **events.php** — Event CRUD, attendance, invitations
8. **pages.php** — Page management, followers
9. **blogs.php** — Blog articles, categories
10. **forums.php** — Discussion boards, threads, replies
11. **videos.php** — Video upload, processing, playback
12. **reels.php** — Short-form video (TikTok-style)
13. **courses.php** — Online courses, enrollment, curriculum
14. **games.php** — Game integration
15. **jobs.php** — Job postings, applications
16. **marketplace.php** — Product listings, orders
17. **wallet.php** — Digital wallet, transactions, deposits
18. **payments.php** — Payment gateway processing
19. **monetization.php** — Creator subscriptions, earnings
20. **packages.php** — Membership/subscription packages
21. **affiliates.php** — Referral tracking, commissions
22. **ads.php** — Ad campaigns, targeting, tracking
23. **livestream.php** — Live video broadcasting
24. **calls.php** — Voice/video calling (LiveKit/Agora)
25. **notifications.php** — Push, email, SMS, in-app notifications
26. **photos.php** — Photo upload, albums, galleries
27. **reports.php** — Content flagging, moderation
28. **logger.php** — Activity logging, audit trail
29. **tools.php** — Utility/helper methods
30. **support.php** — Support ticket system
31. **reviews.php** — Product/seller ratings
32. **merits.php** — Achievement badges, points system
33. **stories.php** — Stories (24-hour ephemeral content)
34. **offers.php** — Deals/offers system
35. **movies.php** — Movie listings/streaming
36. **downloads.php** — File downloads
37. **funding.php** — Fundraising campaigns
38. **invitations.php** — User invitation system
39. **search.php** — Global search functionality
40. **hashtags.php** — Hashtag tracking, trending
41. **announcements.php** — System announcements
42. **custom_fields.php** — Custom profile fields
43. **developers.php** — API/OAuth app management
44. **verification.php** — User verification badges
45. **newsletter.php** — Email newsletters
46. **stickers.php** — Sticker packs for chat
47. **emojis.php** — Custom emoji management
48. **colored_posts.php** — Styled/colored post backgrounds

### AJAX Endpoints (167+ files in `/includes/ajax/`)

```
/includes/ajax/
├── admin/          (53 files — all admin panel actions)
├── core/           (18 files — auth, activation, 2FA)
├── users/          (32 files — profile, settings, images)
├── posts/          (24 files — create, comment, react, share)
├── payments/       (23 files — payment processing per gateway)
├── chat/           (10 files — messaging operations)
├── data/           (8 files  — data loading, uploads)
├── ads/            (ad management)
├── albums/         (photo album operations)
├── forums/         (forum operations)
├── developers/     (API app management)
└── modules/        (misc feature AJAX)
```

---

## 5. Frontend Features

### Template System
- **279+ Smarty .tpl files** in `/content/themes/default/templates/`
- Server-side rendering with Smarty 5.5
- Client-side dynamic updates via jQuery AJAX + Mustache templates
- Dark mode / Night mode support
- RTL (Right-to-Left) language support
- Responsive design (Bootstrap 5 grid)
- PWA (Progressive Web App) support

### Key UI Components
- **Newsfeed** — Infinite scroll post feed
- **Post Composer** — Rich media post creation (text, photo, video, poll, colored backgrounds)
- **Messenger** — Full chat interface with real-time updates
- **Story Viewer** — Instagram-style stories (Zuck.js)
- **Reel Player** — Vertical short-form video player
- **Live Stream Viewer** — Live broadcasting with chat
- **Photo Lightbox** — Gallery with reactions
- **Profile Cards** — Hover popover with quick actions
- **Notification Center** — Real-time notification dropdown
- **Search** — Global autocomplete search
- **Admin Dashboard** — Full management interface

---

## 6. Social Features

### Relationship System
- **Friends** — Mutual friend connections with request/accept flow
- **Followers** — One-way follow system
- **Blocking** — User blocking/muting
- **Mentions** — @username mentions in posts/comments

### Content Interactions
- **Reactions** — Multi-emoji reaction system (like, love, haha, wow, sad, angry + custom)
- **Comments** — Nested threaded comments with reactions
- **Sharing** — Post sharing, external social share buttons
- **Saving** — Bookmark/save posts for later
- **Hiding** — Hide posts from feed
- **Pinning** — Pin posts in groups/pages
- **Reporting** — Flag content for moderation

### Discovery
- **Hashtags** — Hashtag creation, trending hashtags, hashtag feeds
- **Search** — Global search across users, posts, groups, pages, events
- **People Directory** — User discovery and browsing
- **Suggestions** — Friend/group/page suggestions

### Groups & Communities
- Group creation with cover photos, descriptions, rules
- Join/leave with optional admin approval
- Group-specific post feeds
- Group admin/moderator roles
- Group member management

### Pages
- Official page creation
- Page follower system
- Page-specific content feed
- Social media links (Facebook, Twitter, YouTube, Instagram, LinkedIn, VK)

### Events
- Event creation with date, location, cover photo
- RSVP system (interested/attending)
- Event invitations
- Event-specific posts and updates

---

## 7. Media & Content Features

### Posts
- Text posts with rich formatting
- Photo posts (single + multi-photo albums)
- Video posts (upload + embed)
- Colored/styled background posts
- Anonymous posts
- Scheduled posts
- Post visibility (public, friends-only, private)
- Post boosting/promotion
- Memory posts (anniversary reminders)

### Photos & Albums
- Photo upload with cropping
- Album creation and management
- Photo galleries with lightbox viewer
- Cover photo management
- Profile photo management

### Videos
- Video upload (MP4, WebM)
- Video playback
- Video feeds

### Reels (Short-Form Video)
- Vertical short-form video (TikTok-style)
- Reel reactions and comments
- Reel sharing
- Reel feed with vertical scrolling

### Stories
- 24-hour ephemeral content
- Story creation (photo/video)
- Story viewer with viewer list
- Story replies and reactions
- Story highlights/archives

### Live Streaming
- Live video broadcasting (LiveKit WebRTC)
- Real-time viewer count
- Live chat during streams
- Paid/subscriber-only streams
- Tipping during live streams
- Stream recording and archiving

### Blogs & Articles
- Blog post creation with rich text editor (TinyMCE)
- Blog categories
- Blog comments
- Blog feed/carousel

### Forums
- Discussion board categories
- Thread creation and replies
- Thread pinning/locking
- Forum moderation tools
- Post approval workflows

---

## 8. Monetization & E-Commerce

### Digital Wallet
- User wallet balance management
- Deposit via multiple payment gateways
- Transaction history
- Wallet-based purchases
- Withdrawal requests

### Subscription Packages
- System-wide membership tiers (Free, Pro, Premium, etc.)
- Recurring billing
- Package-specific permissions and features
- Trial periods

### Creator Monetization
- User-to-user subscriptions
- Subscriber-only content
- Subscription tiers per creator
- Earnings dashboard
- Payout requests

### Tipping
- Tips during live streams
- Tip tracking and history

### Marketplace
- Product listings with photos and descriptions
- Product categories
- Shopping cart and checkout
- Order management and tracking
- Seller profiles
- Product reviews and star ratings
- Commission system for platform

### Offers/Deals
- Offer creation
- Offer categories
- Acceptance workflow

### Fundraising
- Campaign creation with goals
- Donation tracking
- Donor management
- Campaign updates

### Jobs
- Job posting creation
- Job applications
- Candidate tracking
- Job categories and location filters

### Courses
- Course creation and publishing
- Curriculum/lesson structure
- Student enrollment
- Course categories

### Affiliate System
- Referral link generation
- Multi-level affiliate tracking
- Commission tracking and payouts
- Affiliate tier system

### Points & Merits
- Points earning through activities
- Merit badges/achievements
- Points leaderboards

### Advertising
- Ad campaign creation
- Ad placement management
- CPM-based advertising
- Performance tracking
- Advertiser dashboard

---

## 9. Payment Gateways (17+)

| Gateway            | Type                  | Features                           |
|--------------------|-----------------------|------------------------------------|
| **Stripe**         | Credit/Debit Cards    | Subscriptions, webhooks, ACH       |
| **PayPal**         | Digital Wallet        | Subscriptions, express checkout    |
| **Razorpay**       | India-focused         | UPI, cards, net banking            |
| **Flutterwave**    | Africa-focused        | Mobile money, cards                |
| **Paystack**       | Africa-focused        | Cards, bank transfer               |
| **2Checkout**      | Global                | Multi-currency                     |
| **Authorize.net**  | US-focused            | Credit cards                       |
| **Shift4**         | Global                | Card processing                    |
| **Verotel**        | EU-focused            | Adult/premium content              |
| **Coinbase Commerce** | Cryptocurrency     | Bitcoin, Ethereum, etc.            |
| **CoinPayments**   | Cryptocurrency        | 100+ altcoins                      |
| **Plisio**         | Cryptocurrency        | Crypto gateway                     |
| **Cashfree**       | India-focused         | UPI, cards, wallets                |
| **MyFatoorah**     | Middle East           | MENA payment methods               |
| **MercadoPago**    | Latin America         | Regional payment methods           |
| **MoneyPoolsCash** | Custom                | Cash pooling                       |
| **Bank Transfer**  | Manual                | Wire/ACH (admin-approved)          |
| **Cash on Delivery** | Manual              | Pay on receipt                     |

### Webhook Handlers (`/webhooks/`)
Dedicated webhook files for: Stripe, PayPal, Coinbase, Flutterwave, Paystack, Cashfree, MercadoPago, MyFatoorah, Plisio, Shift4, and more.

---

## 10. Real-Time Communication

### WebSocket Server
- **Technology**: Workerman PHPSocket.io 2.2
- **Location**: `/sockets/php/socket.php`
- **Features**:
  - Real-time chat message delivery
  - Real-time notification delivery
  - Live activity feed updates
  - User online/offline presence
  - Live stream chat

### Voice/Video Calling
- **LiveKit** (WebRTC) — Video conferencing and live streaming
- **Agora** — Alternative real-time communication provider
- Voice calls, video calls, group calls

### Push Notifications
- **OneSignal** — Browser push notifications
- **Email** — PHPMailer 6.10
- **SMS** — Twilio SDK 8.7
- **In-App** — Real-time notification center

---

## 11. Admin Panel Features

### Dashboard
- System analytics and statistics
- User activity metrics
- Revenue overview
- Recent activity feed

### User Management
- User search, list, and filtering
- User profile editing
- User suspension/ban
- Login as user (impersonation)
- User role/group assignment
- User verification management

### Content Management
- Post moderation and approval queue
- Comment moderation
- Blog management
- Forum management
- Announcement management
- Sticker pack management
- Custom emoji management

### System Configuration
- General settings (site name, logo, etc.)
- Design/theme customization
- Custom CSS injection
- Language management
- Country/currency configuration
- Gender/demographic options
- Feature flags (enable/disable modules)

### Monetization Admin
- Payment gateway configuration
- Bank account setup
- Transaction management
- Withdrawal request processing
- Package/plan management
- Ad campaign management

### Moderation
- Content report dashboard
- Report categories management
- Blacklist/ban management
- IP blocking

### Developer Tools
- API/OAuth app management
- Webhook configuration
- API key generation

### Advanced
- Custom fields management (profile fields)
- Permission group management (role-based access)
- PWA settings
- Changelog/version tracking
- Support ticket management

---

## 12. Third-Party Integrations

### Cloud Storage
| Service              | Purpose                         |
|----------------------|---------------------------------|
| AWS S3               | File storage and CDN            |
| Google Cloud Storage | Alternative cloud storage       |
| FTP                  | Traditional file transfer       |

### Social Login (OAuth via HybridAuth)
| Provider    | Status       |
|-------------|--------------|
| Facebook    | Supported    |
| Google      | Supported    |
| Twitter/X   | Supported    |
| LinkedIn    | Supported    |
| VKontakte   | Supported    |
| WordPress   | Supported    |
| Sngine App  | Custom OAuth |

### Communication
| Service     | Purpose                    |
|-------------|----------------------------|
| Twilio      | SMS/phone verification     |
| PHPMailer   | Transactional email        |
| OneSignal   | Push notifications         |
| LiveKit     | Video/streaming WebRTC     |
| Agora       | Real-time communication    |

### Security
| Service               | Purpose                |
|-----------------------|------------------------|
| Google reCAPTCHA      | Bot protection         |
| Google Authenticator  | 2FA (TOTP codes)       |
| HTML Purifier         | XSS prevention         |

### Media
| Service       | Purpose                    |
|---------------|----------------------------|
| Embed (oscarotero) | URL media embedding   |
| SimpleImage   | Server-side image processing |

---

## 13. API Architecture

### REST API (`/apis/php/`)
- **Pattern**: Custom Express.js-like PHP routing (`$app->get()`, `$app->post()`, etc.)
- **Auth**: Session-based + token validation
- **Format**: JSON request/response

**API Modules**:
```
/apis/php/modules/
├── auth/           — Authentication endpoints
├── user/           — User data and profiles
├── chat/           — Messaging API
├── data/           — Data operations
├── monetization/   — Payment/wallet API
└── app/            — App-level endpoints
```

**Core Routes** (`/apis/php/routes/core.php`):
- `GET /ping` — Health check
- `GET /400`, `/401`, `/403`, `/404`, `/500` — Error handlers

### Legacy OAuth API (`/api.php`)
- `?do=oauth` — OAuth token endpoints
- `?do=authorize` — App authorization (app_id, app_secret, auth_key)
- `?do=get_user_info` — User data retrieval (access_token)
- HMAC-SHA256 signature validation

---

## 14. Authentication System

### Methods
1. **Email/Password** — Traditional registration and login
2. **Social OAuth** — Facebook, Google, Twitter, LinkedIn, VK, WordPress (via HybridAuth)
3. **Phone/SMS** — SMS-based activation and verification (Twilio)
4. **Two-Factor Auth** — TOTP codes via Google Authenticator
5. **OAuth API** — App-level authentication for third-party integrations

### Auth Flow
```
Register → Email/Phone Verification → Login → (Optional 2FA) → Session Created
                                                                      │
Social Login → OAuth Provider → Callback → Link/Create Account → Session Created
```

### Key Auth Files
```
/includes/ajax/core/
├── signin.php                     — Login AJAX handler
├── signup.php                     — Registration AJAX handler
├── two_factor_authentication.php  — 2FA verification
├── activation_email.php           — Email verification
├── activation_phone.php           — Phone/SMS verification
├── forget_password.php            — Password reset initiation
├── forget_password_confirm.php    — Reset token verification
└── forget_password_reset.php      — Password update

/modules/
├── sign.php         — Login/Register/Logout/Reset routing
└── connect.php      — Social OAuth login (HybridAuth)
```

### Security Features
- Password hashing (PHP `password_hash()`)
- Session hash verification (CSRF protection)
- Device fingerprinting
- Rate limiting
- reCAPTCHA bot protection
- Email/phone activation required
- Two-factor authentication (optional)
- Admin "login as" impersonation

---

## 15. Directory Structure

```
/sngine community/
│
├── index.php              — Homepage / Newsfeed
├── admin.php              — Admin panel entry
├── api.php                — Legacy OAuth API
├── bootstrap.php          — Init session, load config
├── bootloader.php         — Load User class, check auth
├── install.php            — Installation wizard
├── composer.json          — PHP dependencies
├── package.json           — JS dependencies
├── Dockerfile             — PHP 8.3 + Apache container
├── docker-compose.yml     — Full stack (PHP, MySQL, phpMyAdmin)
│
├── profile.php            — User profiles
├── posts.php / post.php   — Post feeds / single post
├── messages.php           — Messenger
├── settings.php           — User settings
├── groups.php             — Groups
├── events.php             — Events
├── pages.php              — Pages
├── forums.php             — Forums
├── blogs.php              — Blogs
├── jobs.php               — Jobs
├── games.php              — Games
├── market.php             — Marketplace
├── wallet.php             — Wallet
├── search.php             — Search
├── movies.php             — Movies
├── courses.php            — Courses
├── funding.php            — Fundraising
├── offers.php             — Offers/Deals
├── saved.php              — Saved items
├── stories.php            — Stories
├── reels.php              — Reels/Short videos
│
├── /includes/
│   ├── config.php              — Database config (DB_NAME, DB_USER, etc.)
│   ├── config-example.php      — Config template
│   ├── class-user.php          — Main User class (226KB, uses 48 traits)
│   ├── class-image.php         — Image processing class
│   ├── class-pager.php         — Pagination class
│   ├── functions.php           — Global functions (288KB)
│   ├── exceptions.php          — Custom exception classes
│   ├── sys_ver.php             — Version info
│   │
│   ├── /traits/                — 48 feature trait files
│   │   ├── system.php, posts.php, comments.php, chat.php,
│   │   ├── friends.php, groups.php, events.php, pages.php,
│   │   ├── blogs.php, forums.php, videos.php, reels.php,
│   │   ├── courses.php, games.php, jobs.php, marketplace.php,
│   │   ├── wallet.php, payments.php, monetization.php,
│   │   ├── packages.php, affiliates.php, ads.php,
│   │   ├── livestream.php, calls.php, notifications.php,
│   │   ├── photos.php, reports.php, logger.php, tools.php,
│   │   ├── support.php, reviews.php, merits.php, ...
│   │   └── (48 files total)
│   │
│   └── /ajax/                  — 167+ AJAX endpoint files
│       ├── /admin/     (53)    — Admin panel actions
│       ├── /core/      (18)    — Auth, activation, 2FA
│       ├── /users/     (32)    — Profile, settings
│       ├── /posts/     (24)    — Post CRUD, reactions
│       ├── /payments/  (23)    — Payment processing
│       ├── /chat/      (10)    — Messaging
│       ├── /data/      (8)     — Data loading, uploads
│       ├── /ads/               — Ad management
│       ├── /albums/            — Photo albums
│       ├── /forums/            — Forum operations
│       ├── /developers/        — API app management
│       └── /modules/           — Misc features
│
├── /apis/php/
│   ├── index.php               — API initialization
│   ├── /routes/core.php        — Core API routes
│   └── /modules/
│       ├── auth/               — Auth API
│       ├── user/               — User API
│       ├── chat/               — Chat API
│       ├── data/               — Data API
│       ├── monetization/       — Payment API
│       └── app/                — App API
│
├── /modules/
│   ├── sign.php                — Login/Register/Logout pages
│   ├── connect.php             — Social OAuth login
│   ├── activation.php          — Account activation
│   ├── contact.php             — Contact page
│   ├── started.php             — Getting started
│   └── static.php              — Static pages
│
├── /content/
│   ├── /themes/default/
│   │   ├── /templates/         — 279+ Smarty .tpl files
│   │   ├── /css/               — Stylesheets (Bootstrap, custom, RTL)
│   │   ├── /fonts/             — Font files
│   │   └── /images/            — Theme images
│   └── /uploads/               — User-generated content
│
├── /sockets/php/
│   ├── socket.php              — WebSocket server
│   └── loader.php              — Socket initialization
│
├── /webhooks/                  — Payment webhook handlers
│   ├── stripe.php, paypal.php, coinbase.php,
│   ├── flutterwave.php, paystack.php, cashfree.php,
│   ├── mercadopago.php, myfatoorah.php, plisio.php,
│   └── shift4.php, ...
│
└── /vendor/                    — Composer dependencies
```

---

## 16. Church App Mapping Notes

### Features to Adapt from Sngine → Church Community

| Sngine Feature        | Church App Equivalent                         | Priority |
|------------------------|-----------------------------------------------|----------|
| **Newsfeed/Posts**     | Church announcements, prayer requests, updates | HIGH     |
| **Groups**             | Ministry groups, Bible study groups, choirs    | HIGH     |
| **Events**             | Church services, retreats, workshops           | HIGH     |
| **Messenger/Chat**     | Member messaging, pastoral counseling          | HIGH     |
| **Notifications**      | Service reminders, event alerts                | HIGH     |
| **Profiles**           | Member profiles, ministry roles                | HIGH     |
| **Live Streaming**     | Live service broadcasts, online worship        | HIGH     |
| **Courses**            | Bible studies, discipleship programs            | HIGH     |
| **Forums**             | Discussion groups, Q&A, theology discussions   | MEDIUM   |
| **Blogs**              | Sermons, devotionals, pastor's blog            | MEDIUM   |
| **Wallet/Donations**   | Tithes, offerings, building fund donations     | HIGH     |
| **Fundraising**        | Mission trips, building projects, charity      | HIGH     |
| **Marketplace**        | Church bookstore, merchandise                  | MEDIUM   |
| **Videos/Reels**       | Sermon clips, worship highlights, testimonies  | MEDIUM   |
| **Stories**            | Daily devotionals, prayer of the day           | LOW      |
| **Jobs**               | Church staff positions, volunteer opportunities| MEDIUM   |
| **Affiliate**          | Church partner referrals                       | LOW      |
| **Points/Merits**      | Volunteer recognition, service badges          | LOW      |
| **Admin Panel**        | Church admin dashboard                         | HIGH     |
| **Ads**                | Church event promotions (internal)             | LOW      |
| **Reviews**            | Testimonials                                   | LOW      |
| **Games/Movies**       | Not needed (remove)                            | REMOVE   |

### BeMusic Backend Integration Points

When merging with BeMusic backend, consider:

1. **Music/Audio System** (from BeMusic) → Worship music, hymns, podcasts, sermon audio
2. **Playlist System** (from BeMusic) → Worship playlists, sermon series
3. **Artist Profiles** (from BeMusic) → Worship leaders, choir members, musicians
4. **Streaming** (from BeMusic) → Audio streaming for sermons, worship music
5. **Music Player** (from BeMusic) → Persistent audio player for sermons/worship
6. **Albums** (from BeMusic) → Sermon series collections, worship albums

### Unique Church Features to Add

- **Prayer Wall** — Community prayer request board
- **Sermon Archive** — Organized by date, speaker, series, scripture
- **Bible Integration** — Scripture references, daily verse, Bible reading plans
- **Small Group Finder** — Location/interest-based group matching
- **Volunteer Signup** — Ministry/service volunteer scheduling
- **Church Directory** — Opt-in member directory with privacy controls
- **Giving Dashboard** — Donation history, tax receipts, recurring giving
- **Service Check-In** — QR code attendance tracking
- **Kids Ministry** — Child check-in, parent notifications
- **Pastoral Care** — Confidential counseling request system

---

## Summary Statistics

| Metric                    | Count    |
|---------------------------|----------|
| PHP Entry Points          | 45+      |
| AJAX Endpoints            | 167+     |
| API Modules               | 6        |
| Database Tables           | 130+     |
| Feature Traits            | 48       |
| Smarty Templates          | 279+     |
| Payment Gateways          | 17+      |
| Social Login Providers    | 7        |
| Webhook Handlers          | 10+      |
| Total PHP Dependencies    | 18+      |
| Total JS Dependencies     | 20+      |

---

*This document serves as the complete technical reference for the Sngine Community platform, to be used as the foundation model for building the Church Community App with BeMusic backend integration.*
