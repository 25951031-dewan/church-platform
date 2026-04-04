# Church Website Platform - Changelog & Feature Documentation

## Table of Contents
- [Architecture Overview](#architecture-overview)
- [System Flow Charts](#system-flow-charts)
- [Complete Feature List](#complete-feature-list)
- [Version Changelog](#version-changelog)

---

## Architecture Overview

```
+-----------------------------------------------------------------------+
|                    CHURCH WEBSITE PLATFORM                             |
+-----------------------------------------------------------------------+
|                                                                       |
|  +-----------------------+          +------------------------------+  |
|  |   PUBLIC FRONTEND     |          |      ADMIN PANEL (React)     |  |
|  |   (Blade + Vanilla JS)|          |      31 Manager Components   |  |
|  |                       |          |                              |  |
|  |  - SPA with History   |   API    |  - Dashboard (stats)         |  |
|  |  - Dark/Light Theme   | <------> |  - Content Managers          |  |
|  |  - Mobile Responsive  |  REST    |  - Settings & Config         |  |
|  |  - PWA Support        |  JSON    |  - System & Deploy           |  |
|  |  - PDF Viewer         |          |  - Church Builder            |  |
|  +-----------+-----------+          +-------------+----------------+  |
|              |                                    |                   |
|              v                                    v                   |
|  +-----------------------+          +------------------------------+  |
|  |  AUTH SYSTEM           |          |  SHARED COMPONENTS           |  |
|  |  - Email/Password     |          |  - Modal, DataTable          |  |
|  |  - Google OAuth       |          |  - Pagination, FormField     |  |
|  |  - Facebook OAuth     |          |  - RichTextEditor, Alert     |  |
|  |  - Forgot/Reset PW    |          |  - API helpers (CSRF)        |  |
|  +-----------+-----------+          +-------------+----------------+  |
|              |                                    |                   |
|              +------------------------------------+                   |
|                              |                                        |
|                              v                                        |
|  +----------------------------------------------------------------+  |
|  |                LARAVEL BACKEND (API)                            |  |
|  |                                                                |  |
|  |  28 API Controllers    |  28 Eloquent Models  |  27 Migrations |  |
|  |  Laravel Sanctum Auth  |  Role-based Access   |  MySQL/SQLite  |  |
|  +----------------------------------------------------------------+  |
+-----------------------------------------------------------------------+
```

---

## System Flow Charts

### User Authentication Flow

```
                         +-------------------+
                         |   Frontend Nav    |
                         +--------+----------+
                                  |
                    +-------------+-------------+
                    |                           |
              [Guest User]              [Logged-in User]
                    |                           |
                    v                           v
          +--------+--------+        +---------+---------+
          | Login Button    |        | Avatar Dropdown   |
          | (Person Icon)   |        | - Name & Email    |
          +--------+--------+        | - Admin Panel *   |
                   |                 | - Edit Profile    |
                   v                 | - Sign Out        |
          +--------+--------+        +---------+---------+
          | Auth Modal      |                  |
          | - Login Form    |        (* Admin Panel link
          | - Register Form |          shown ONLY if
          | - Forgot PW     |          user.is_admin)
          | - Reset PW      |
          +--------+--------+
                   |
         +---------+---------+
         |                   |
    [Email/PW]         [Social OAuth]
         |                   |
         v                   v
  +------+------+    +------+------+
  | POST /login |    | /auth/      |
  | POST /reg   |    | google/     |
  +------+------+    | facebook    |
         |           +------+------+
         v                  |
  +------+------+           v
  | Sanctum     |    +------+------+
  | Token       |    | Socialite   |
  | Issued      |    | Callback    |
  +------+------+    +------+------+
         |                  |
         +--------+---------+
                  |
                  v
         +--------+--------+
         | localStorage    |
         | auth_token      |
         | auth_user (JSON)|
         +-----------------+
```

### Custom Profile Fields Flow

```
  +-------------------+         +------------------------+
  | ADMIN: Settings   |         | ADMIN: Users Manager   |
  | > User Fields Tab |         | > Create/Edit User     |
  +--------+----------+         +----------+-------------+
           |                               |
           v                               v
  +--------+----------+         +----------+-------------+
  | Configure Fields: |         | Form shows enabled     |
  | - Phone Number    |         | custom fields with     |
  | - Church Name     |         | required indicators    |
  | - Social ID       |         | View user details      |
  | - Spiritual BG    |         | modal with all fields  |
  | - Custom fields   |         +----------+-------------+
  | Toggle: Enable    |                    |
  | Toggle: Required  |                    v
  +--------+----------+         +----------+-------------+
           |                    | PUT /api/users/{id}    |
           v                    | (includes custom_fields|
  +--------+----------+         |  phone, church_name,   |
  | PUT /api/settings |         |  social_id, etc.)      |
  | /profile-fields   |         +------------------------+
  | Saves JSON config |
  +--------+----------+
           |
           v
  +--------+--------------------+
  | PUBLIC: Registration Form   |
  | & Profile Edit Modal        |
  +--------+--------------------+
           |
           v
  +--------+--------------------+
  | GET /api/settings/          |
  | profile-fields/public       |
  | -> Renders enabled fields   |
  | -> Validates required ones  |
  | -> Sends with registration  |
  +-----------------------------+
```

### Content Management Flow

```
  +------------------+     +------------------+     +------------------+
  | Admin Panel      |     | REST API         |     | Public Frontend  |
  | (React Manager)  |     | (Laravel)        |     | (Blade + JS)    |
  +--------+---------+     +--------+---------+     +--------+---------+
           |                        |                        |
           | CRUD Operations        | JSON Responses         | Data Loading
           |                        |                        |
           v                        v                        v
  +--------+---------+     +--------+---------+     +--------+---------+
  | POST   /api/X    |---->| Validate         |     | GET /api/X      |
  | PUT    /api/X/id |     | Store/Update     |---->| Paginated data  |
  | DELETE /api/X/id |     | Return JSON      |     | Rendered as     |
  | GET    /api/X    |     +------------------+     | cards/lists     |
  +------------------+                              +------------------+

  Where X = posts, events, sermons, prayers, books, bible-studies,
           reviews, testimonies, galleries, ministries, verses,
           blessings, announcements, pages, categories, menus,
           contacts, newsletters, donations, churches
```

### Homepage Widget Engine

```
  +----------------------------+
  | Admin: Homepage Customizer |
  | (Drag & Drop Widgets)      |
  +------------+---------------+
               |
               v
  +------------+---------------+
  | PUT /api/settings/widgets  |
  | Save widget_config JSON    |
  | [{id, label, icon,         |
  |   enabled, settings}]      |
  +------------+---------------+
               |
               v
  +------------+---------------+
  | Frontend: loadWidgetConfig |
  | -> applyWidgetLayout()     |
  +------------+---------------+
               |
               v
  +------------+------------------------------------------+
  | Reorder & Show/Hide Home Sections:                    |
  |                                                       |
  | hw-announcements -> Ticker/announcement strip         |
  | hw-verse         -> Verse of the day                  |
  | hw-blessing      -> Daily blessing card               |
  | hw-posts         -> Featured blog posts               |
  | hw-prayers       -> Prayer requests section           |
  | hw-events        -> Upcoming events                   |
  | hw-sermon        -> Featured sermon                   |
  | hw-testimonies   -> Recent testimonies                |
  | hw-reviews       -> Church reviews                    |
  | hw-ministries    -> Active ministries                 |
  | hw-galleries     -> Photo gallery                     |
  | hw-newsletter    -> Newsletter signup                 |
  | hw-contact       -> Quick contact form                |
  +-------------------------------------------------------+
```

---

## Complete Feature List

### 1. PUBLIC FRONTEND (13 Pages)

| Page | Route | Features |
|------|-------|----------|
| **Home** | `/` | Configurable widget-based layout, announcements ticker, verse of the day, daily blessing, featured posts, prayer wall preview, upcoming events, featured sermon, testimonies, reviews, ministries, gallery, newsletter, contact form |
| **Blog** | `/blog` | Grid/list toggle, category filters, search, pagination, sidebar (categories, recent posts, tags) |
| **Blog Detail** | `/blog/{slug}` | Full post view, permalink copy, social share, view count |
| **Events** | `#events` | Event cards with date/time/location/RSVP |
| **Prayers** | `#prayers` | Prayer wall, submit prayer (anonymous option), "pray" button counter |
| **Library** | `#library` | Book catalog, category filters, search, in-app PDF reader with zoom/page navigation |
| **Bible Studies** | `#studies` | Study cards with difficulty level and duration |
| **Sermons** | `#sermons` | Sermon archive with play buttons and metadata |
| **Giving** | `#giving` | Preset donation amounts, custom amount input |
| **Ministries** | `#ministries` | Ministry/volunteer directory |
| **Reviews** | `#reviews` | 5-star rating system, average rating display, submit review modal |
| **Testimonies** | `#testimonies` | Member testimonies with born-again/baptism dates |
| **Contact** | `#contact` | Contact form, church info display (address, phone, email, service times) |
| **About** | `#about` | Church information page |
| **Churches** | `/churches` | Church directory with search |
| **Church Detail** | `/church/{slug}` | Individual church mini-site |

### 2. AUTHENTICATION SYSTEM

| Feature | Description |
|---------|-------------|
| Email/Password Login | Standard login with Sanctum token |
| Registration | With dynamic custom profile fields (configurable by admin) |
| Social OAuth | Google and Facebook login via Laravel Socialite |
| Forgot Password | Email-based password reset flow |
| Reset Password | Token-based password reset form |
| Profile Edit | Update name, email, password, and custom fields |
| Admin Panel Access | Admin Panel link in nav dropdown (admin-only visibility) |
| Session Management | localStorage-based token persistence |

### 3. ADMIN PANEL (31 Manager Components)

#### Main
| Manager | Features |
|---------|----------|
| **Dashboard** | 12 stat cards, recent prayers, upcoming events, recent contacts, quick actions |
| **Homepage Customizer** | Drag-and-drop widget reordering, enable/disable widgets, per-widget settings |
| **Appearance Manager** | Theme editor, CSS customization, color schemes |
| **Mobile Theme** | Mobile-specific layouts, bottom nav config, pull-to-refresh, swipe gestures |

#### Content Management
| Manager | Features |
|---------|----------|
| **Announcements** | CRUD with scheduling |
| **Verses** | Daily verse management, CSV bulk import/export |
| **Blessings** | Daily blessings CRUD |
| **Prayer Requests** | View, moderate, status management |
| **Events** | CRUD with date/time/location/RSVP |
| **Sermons** | CRUD with media attachments |
| **Books** | CRUD with PDF upload, auto-slug generation |
| **Bible Studies** | CRUD with difficulty/duration metadata |
| **Reviews** | Moderation, approval workflow |
| **Testimonies** | Review and publish member testimonies |
| **Galleries** | Image gallery management with multi-upload |
| **Ministries** | Ministry/volunteer management |

#### Churches
| Manager | Features |
|---------|----------|
| **Church Directory** | Browse and manage registered churches |
| **Church Builder** | Mini-site builder for individual churches |

#### CMS
| Manager | Features |
|---------|----------|
| **Posts** | Blog post CRUD with rich text editor, categories, featured images, view tracking |
| **Pages** | Static page management |
| **Categories** | Hierarchical category tree management |
| **Menus** | Navigation menu builder (header/footer) |

#### Communication
| Manager | Features |
|---------|----------|
| **Contacts** | View contact form submissions |
| **Newsletter** | Subscriber management, template builder, send campaigns |
| **Donations** | Donation tracking and management |

#### System
| Manager | Features |
|---------|----------|
| **Users** | CRUD, password reset, custom profile fields, view user details, profile completion status |
| **Roles** | Role-based permissions management |
| **Settings** | 7 tabs: General, Mail (multi-provider), Authentication (OAuth), Uploading (S3/local), Cache, Logging & Queue, **User Fields** (custom profile fields config) |
| **Translations** | Localization/i18n management |
| **System & Deploy** | System info, update/deploy tools |
| **Sitemap Generator** | SEO sitemap generation |
| **Profile** | Admin profile management |

### 4. SETTINGS CONFIGURATION (7 Tabs)

| Tab | Settings |
|-----|----------|
| **General** | Church name, tagline, description, address, contact, social media URLs, service times, pastor info, about text, mission/vision, logo/banner/favicon, colors, footer, SEO meta, analytics, custom CSS/JS |
| **Mail** | Provider (SMTP/Mailchimp/SendGrid/Mailgun), credentials, from address, contact notifications, welcome emails, templates, signatures |
| **Authentication** | Google OAuth (client ID/secret, enable/disable), Facebook OAuth (client ID/secret, enable/disable) |
| **Uploading** | Storage driver (local/S3), S3 credentials, max upload size, allowed file types |
| **Cache** | Cache driver (file/Redis/database), TTL, page cache, minification, CDN URL |
| **Logging & Queue** | Log channel, queue driver (sync/database/Redis) |
| **User Fields** | 4 built-in fields (phone, church name, social ID, spiritual background) + custom fields. Each field: enable/disable toggle, required/optional, field type (text/textarea/select/email/tel/URL), placeholder, dropdown options |

### 5. MOBILE & PWA

| Feature | Description |
|---------|-------------|
| Responsive Design | Mobile-first with slide-in menu |
| Bottom Navigation | Configurable mobile bottom nav bar |
| Pull to Refresh | Native gesture support |
| Swipe Navigation | Swipe between pages |
| PWA Manifest | Dynamic manifest generation |
| Install Prompt | App installation banner |
| Mobile Theme | Separate mobile-specific theming |

### 6. THEMING SYSTEM

| Feature | Description |
|---------|-------------|
| Dark Mode | Default elegant dark theme with gold accents |
| Light Mode | Clean light theme alternative |
| Theme Toggle | Persistent dark/light switch |
| CSS Variables | 25+ theme variables for full customization |
| Font System | Playfair Display (headings), Source Sans 3 (body), Cormorant Garamond (elegant) |
| Admin Appearance | Visual CSS theme editor |

---

## Version Changelog

### v1.0.0 - Initial Release
- Base Laravel project with initial church platform structure

### v2.0.0 - Complete Platform
- Full Laravel backend with REST API
- React admin panel with content managers
- Database migrations for all content types
- Basic frontend with dark theme

### v2.1.0 - Bug Fixes & Stability
- Fix missing routes (`console.php`, `channels.php`)
- Add missing Laravel config files
- Fix column name mismatches in controllers and admin managers
- Add root `.htaccess` redirect

### v2.2.0 - Frontend Restoration
- Restore dark-themed church frontend
- Correct API endpoint mappings
- Fix mobile menu functionality
- Add dark/light theme toggle
- Add PWA support with manifest

### v2.3.0 - Installer & Settings
- Fix installer CSRF mismatch
- Fix migration failures
- Add `theme_config` and `widget_config` to settings
- Add GitHub Actions CI workflow

### v3.0.0 - CMS Expansion
- Add Announcements Manager
- Add Pages Manager
- Add Categories Manager (hierarchical tree)
- Add Menus Manager (header/footer)
- Add Roles & Permissions Manager
- Add Sitemap Generator
- Fix broken admin panels

### v3.1.0 - Authentication System
- Add frontend auth modal (login/signup)
- Social OAuth (Google, Facebook) via Laravel Socialite
- Laravel Sanctum token-based authentication
- Auth state persistence in localStorage

### v3.2.0 - Content Enhancements
- CSV bulk import/export for Daily Verses
- Auto-generate slug for books
- Fix prayer request and review submit buttons
- Fix user listing, role assignment, announcement modal

### v3.3.0 - PDF Reader
- In-app PDF book reader
- Page-flip animation
- Zoom in/out controls
- Page navigation

### v3.4.0 - Testimonies & Communication
- Testimony feature with frontend submission form and admin manager
- Contact page with church info display
- Newsletter manager with templates
- Newsletter subscription popup (timed)
- Enhanced sitemap generator

### v3.5.0 - Email System
- Multi-provider email settings (SMTP, Mailchimp, SendGrid, Mailgun)
- Email settings admin UI with test email
- Welcome email templates
- Contact notification emails

### v3.6.0 - Homepage Customizer
- WordPress-style homepage widget customizer
- Drag-and-drop widget reordering
- 13 configurable homepage widgets
- Per-widget settings
- System update/deploy admin panel

### v3.7.0 - Advanced Admin Features
- Appearance Editor (theme CSS customization)
- Mobile Theme Manager (bottom nav, gestures, mobile layouts)
- Translations/Localization Manager
- Extended Settings tabs (Cache, Storage, Logging, Authentication)

### v3.8.0 - Blog & Password Features
- Forgot password flow with email reset
- Profile editing modal
- Admin user password reset
- Blog post section with grid/list layouts
- Blog sidebar (categories, recent posts, tags)
- Blog post permalinks and view tracking

### v3.9.0 - Mobile & PWA Enhancements
- Fix mobile theme application on devices
- Dynamic PWA manifest generation
- Post views column tracking
- Mobile bottom navigation
- Pull-to-refresh and swipe navigation

### v3.10.0 - CMS Improvements
- Sitemap generator with SEO metadata
- Reorganized CMS sidebar navigation
- Enhanced categories tree with hierarchy
- Frontend menu rendering from admin config
- Rich text editor component
- Social sharing for blog posts
- Cache clear functionality

### v4.0.0 - Church Directory
- Church Directory for browsing registered churches
- Church Mini-Site Builder (per-church websites)
- Individual church detail pages

### v4.1.0 - Clean URLs & SSR
- Switch from hash-based to clean URL routing (HTML5 History API)
- Server-side rendering support for blog/page/ministry/about routes
- Fix route conflict for admin vs public church slugs

### v4.2.0 - Custom Profile Fields & Admin Access
- **Admin Panel Icon**: Admin Panel link in frontend user dropdown (visible only to admins)
- **Custom User Fields Configuration**: New "User Fields" tab in Settings
  - 4 built-in fields: Phone Number, Church Name, Social ID, Spiritual Background
  - Add unlimited custom fields with label, type, placeholder, options
  - Toggle fields enabled/disabled
  - Toggle fields required/optional (compulsory fill or skip)
- **Enhanced User Management**: View user details modal, profile completion status, custom fields in create/edit forms
- **Dynamic Registration Form**: Custom fields rendered dynamically during registration with client-side required validation
- **Dynamic Profile Edit**: Custom fields populated from user data in profile edit modal
- **Backend Support**: Migration for new columns, API endpoints for field config, validation rules applied from config
- Social OAuth callback now includes `is_admin` flag

### v5.0.0 - Real-Time Chat System (Latest)
- **Real-Time Messaging**: WebSocket-based chat with Laravel Broadcasting
- **1-on-1 Conversations**: Direct messaging between two users
- **Group Chats**: Multi-user conversations with custom names
- **Typing Indicators**: Real-time "user is typing..." notifications
- **Read Receipts**: Know when messages have been read
- **Online/Offline Presence**: Live user status indicators
- **Message Types**: Support for text, images, files, and audio messages
- **Unread Badges**: Red notification badges with unread message counts
- **Message Pagination**: Load messages in batches (50 per page)
- **Soft Delete Messages**: Users can delete their own messages
- **Mute Conversations**: Turn off notifications for specific chats
- **Admin Moderation**: Chat moderation tools for admins
- **Broadcasting Flexibility**: 
  - Pusher driver (shared hosting, zero setup, free tier)
  - Reverb driver (VPS self-hosted, no monthly fees)
  - Same codebase works with both drivers
- **Complete Frontend**: React 19 + TypeScript with full UI components
- **Laravel Echo Integration**: Real-time WebSocket subscriptions
- **Type-Safe Hooks**: 7 custom React Query hooks with Echo integration
- **10 UI Components**: ConversationList, MessageThread, MessageBubble, etc.
- **Permission System**: chat.send, chat.create_group, chat.attach_files, chat.moderate
- **Feature Tests**: Complete test coverage for conversations and messages
- **Documentation**: Full plugin documentation and setup guide

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| Auth | Laravel Sanctum + Socialite |
| Database | MySQL (configurable) |
| Admin Frontend | React 18 + Tailwind CSS |
| Public Frontend | Vanilla JS + Blade Templates |
| Build | Vite 5 |
| CSS | Tailwind CSS 3.4 + Custom CSS Variables |
| Icons | Font Awesome 6.5 |
| PDF | PDF.js (in-app reader) |
| PWA | Dynamic manifest + service worker |

---

## Database Models (31 Total)

```
User, Role, Setting, Church, Post, Page, Category, Menu,
Event, EventRegistration, Sermon, Book, BibleStudy,
PrayerRequest, Review, Testimony, Verse, Blessing,
Announcement, Gallery, GalleryImage, Ministry, Donation,
ContactMessage, NewsletterSubscriber, NewsletterTemplate,
Localization, CssTheme, Conversation, Message, ConversationUser
```

---

## API Endpoints Summary

| Group | Count | Base Path |
|-------|-------|-----------|
| Auth | 6 | `/login`, `/register`, `/logout`, `/profile`, `/forgot-password`, `/reset-password` |
| Social Auth | 2 | `/auth/{provider}/redirect`, `/auth/{provider}/callback` |
| Content (Public) | 18 | `/api/verses`, `/api/events`, `/api/posts`, etc. |
| Content (Admin) | 18 | `/api/verses`, `/api/events`, `/api/posts` (POST/PUT/DELETE) |
| Settings | 7 | `/api/settings`, `/api/settings/email`, `/api/settings/widgets`, `/api/settings/profile-fields` |
| System | 3 | `/api/system/*` |
| Users | 4 | `/api/users` (CRUD) |
| Churches | 4 | `/api/churches` (CRUD) |
| Chat | 10 | `/api/v1/chat/conversations`, `/api/v1/chat/messages`, `/api/v1/chat/presence`, admin moderation |

**Total: ~70+ API endpoints**
