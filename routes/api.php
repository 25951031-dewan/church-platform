<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\BibleStudyController;
use App\Http\Controllers\Api\BlessingController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MinistryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PrayerRequestController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SermonController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TestimonyController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\VerseController;
use App\Http\Controllers\Api\AppearanceController;
use App\Http\Controllers\Api\MobileThemeController;
use App\Http\Controllers\Api\LocalizationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Common\Notifications\Controllers\Admin\NotificationLogController;
use Common\Notifications\Controllers\Admin\NotificationTemplateController;
use Common\Notifications\Controllers\NotificationController;
use Common\Notifications\Controllers\NotificationPreferenceController;
use Common\Settings\Controllers\SettingController as FoundationSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// V1 Foundation API
Route::prefix('v1')->group(function () {
    // Auth (public)
    Route::post('login', [\Common\Auth\Controllers\AuthController::class, 'login']);
    Route::post('register', [\Common\Auth\Controllers\AuthController::class, 'register']);

    // Settings (public)
    Route::get('settings', [FoundationSettingController::class, 'index']);
    Route::get('settings/{group}', [FoundationSettingController::class, 'show']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('logout', [\Common\Auth\Controllers\AuthController::class, 'logout']);
        Route::get('me', [\Common\Auth\Controllers\AuthController::class, 'me']);

        // Settings
        Route::put('settings', [FoundationSettingController::class, 'update'])
            ->middleware('permission:admin.access');

        // Roles & Permissions
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('roles', [\Common\Auth\Controllers\RoleController::class, 'index']);
            Route::get('permissions', [\Common\Auth\Controllers\PermissionController::class, 'index']);
        });
        Route::middleware('permission:roles.create')->post('roles', [\Common\Auth\Controllers\RoleController::class, 'store']);
        Route::middleware('permission:roles.update')->put('roles/{role}', [\Common\Auth\Controllers\RoleController::class, 'update']);
        Route::middleware('permission:roles.delete')->delete('roles/{role}', [\Common\Auth\Controllers\RoleController::class, 'destroy']);

        // Reactions & Comments (shared foundation)
        Route::post('reactions/toggle', [\Common\Reactions\Controllers\ReactionController::class, 'toggle'])
            ->middleware('permission:reactions.create');

        Route::get('comments', [\Common\Comments\Controllers\CommentController::class, 'index']);
        Route::post('comments', [\Common\Comments\Controllers\CommentController::class, 'store']);
        Route::put('comments/{comment}', [\Common\Comments\Controllers\CommentController::class, 'update']);
        Route::delete('comments/{comment}', [\Common\Comments\Controllers\CommentController::class, 'destroy']);

        Route::prefix('notifications')->group(function () {
            Route::get('/preferences', [NotificationPreferenceController::class, 'index']);
            Route::put('/preferences', [NotificationPreferenceController::class, 'update']);
            Route::post('/push/register', [NotificationPreferenceController::class, 'registerPush']);
            Route::post('/push/unregister', [NotificationPreferenceController::class, 'unregisterPush']);
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
        });

        Route::prefix('admin')->middleware('permission:admin.access')->group(function () {
            Route::get('notification-logs', [NotificationLogController::class, 'index']);
            Route::get('notification-templates', [NotificationTemplateController::class, 'index']);
            Route::post('notification-templates', [NotificationTemplateController::class, 'store']);
            Route::get('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'show']);
            Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update']);
            Route::delete('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'destroy']);
        });

        // Timeline Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('timeline')) {
            require app_path('Plugins/Timeline/Routes/api.php');
        }

        // Groups Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('groups')) {
            require app_path('Plugins/Groups/Routes/api.php');
        }

        // Events Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('events')) {
            require app_path('Plugins/Events/Routes/api.php');
        }

        // Sermons Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('sermons')) {
            require app_path('Plugins/Sermons/Routes/api.php');
        }

        // Prayer Plugin routes (authenticated)
        if (app(\Common\Core\PluginManager::class)->isEnabled('prayer')) {
            require app_path('Plugins/Prayer/Routes/api.php');
        }

        // ChurchBuilder Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('church_builder')) {
            require base_path('app/Plugins/ChurchBuilder/Routes/api.php');
        }

        // Library Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('library')) {
            require app_path('Plugins/Library/Routes/api.php');
        }

        // Blog Plugin routes (authenticated mutations)
        if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
            require app_path('Plugins/Blog/Routes/api.php');
        }

        // LiveMeeting Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('live_meeting')) {
            require app_path('Plugins/LiveMeeting/Routes/api.php');
        }

        // Chat Plugin routes
        if (app(\Common\Core\PluginManager::class)->isEnabled('chat')) {
            Route::prefix('chat')->group(function () {
                // Conversations
                Route::get('conversations', [\Common\Chat\Controllers\ConversationController::class, 'index']);
                Route::post('conversations', [\Common\Chat\Controllers\ConversationController::class, 'store']);
                Route::get('conversations/{conversation}', [\Common\Chat\Controllers\ConversationController::class, 'show']);
                Route::post('conversations/{conversation}/read', [\Common\Chat\Controllers\ConversationController::class, 'markAsRead']);
                Route::delete('conversations/{conversation}', [\Common\Chat\Controllers\ConversationController::class, 'destroy']);

                // Messages
                Route::get('conversations/{conversation}/messages', [\Common\Chat\Controllers\MessageController::class, 'index']);
                Route::post('conversations/{conversation}/messages', [\Common\Chat\Controllers\MessageController::class, 'store']);
                Route::put('messages/{message}', [\Common\Chat\Controllers\MessageController::class, 'update']);
                Route::delete('messages/{message}', [\Common\Chat\Controllers\MessageController::class, 'destroy']);

                // Typing indicator
                Route::post('conversations/{conversation}/typing', [\Common\Chat\Controllers\ChatPresenceController::class, 'typing']);

                // Presence
                Route::post('presence', [\Common\Chat\Controllers\ChatPresenceController::class, 'update']);

                // ━━━━━━ Advanced Chat Features ━━━━━━
                
                // Reactions
                Route::post('messages/{message}/reactions', [\Common\Chat\Controllers\ReactionController::class, 'toggle']);
                Route::get('messages/{message}/reactions', [\Common\Chat\Controllers\ReactionController::class, 'index']);

                // Pinned Messages
                Route::get('conversations/{conversation}/pins', [\Common\Chat\Controllers\PinController::class, 'index']);
                Route::post('messages/{message}/pin', [\Common\Chat\Controllers\PinController::class, 'store']);
                Route::delete('messages/{message}/pin', [\Common\Chat\Controllers\PinController::class, 'destroy']);

                // Read Receipts
                Route::post('conversations/{conversation}/read-receipts', [\Common\Chat\Controllers\ReadReceiptController::class, 'markRead']);
                Route::get('messages/{message}/read-receipts', [\Common\Chat\Controllers\ReadReceiptController::class, 'show']);

                // Admin moderation routes
                Route::middleware('permission:chat.moderate')->prefix('admin')->group(function () {
                    Route::get('conversations', [\Common\Chat\Controllers\Admin\ChatModerationController::class, 'index']);
                    Route::get('conversations/{conversation}/messages', [\Common\Chat\Controllers\Admin\ChatModerationController::class, 'messages']);
                    Route::delete('messages/{message}/force', [\Common\Chat\Controllers\Admin\ChatModerationController::class, 'forceDelete']);
                    Route::post('messages/{message}/restore', [\Common\Chat\Controllers\Admin\ChatModerationController::class, 'restore']);
                });
            });
        }
    });
});

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

// Public Content Routes
Route::get('/verses/today', [VerseController::class, 'today']);
Route::get('/blessings/today', [BlessingController::class, 'today']);
Route::get('/events/upcoming', [EventController::class, 'upcoming']);
Route::get('/posts/published', [PostController::class, 'published']);
Route::get('/posts/featured', [PostController::class, 'featured']);
Route::get('/posts/{slug}', [PostController::class, 'show']);
Route::post('/posts/{slug}/view', [PostController::class, 'incrementView']);
Route::get('/sermons/featured', [SermonController::class, 'featured']);
Route::get('/sermons/{slug}', [SermonController::class, 'show']);
Route::get('/books/featured', [BookController::class, 'featured']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::get('/books/{book}/download', [BookController::class, 'download']);
Route::get('/bible-studies/featured', [BibleStudyController::class, 'featured']);
Route::get('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'show']);
Route::get('/reviews/approved', [ReviewController::class, 'approved']);
Route::get('/testimonies/approved', [TestimonyController::class, 'approved']);
Route::get('/testimonies/featured', [TestimonyController::class, 'featured']);
Route::get('/testimonies/{slug}', [TestimonyController::class, 'show']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/galleries/{gallery}', [GalleryController::class, 'show']);
Route::get('/ministries', [MinistryController::class, 'index']);
Route::get('/ministries/{ministry}', [MinistryController::class, 'show']);
Route::get('/prayer-requests/public', [PrayerRequestController::class, 'publicRequests']);
Route::get('/settings', [SettingController::class, 'show']);
Route::get('/settings/widgets/public', [SettingController::class, 'widgetConfig']);

// Announcements (public)
Route::get('/announcements/active', [AnnouncementController::class, 'active']);

// Pages (public)
Route::get('/pages/published', [PageController::class, 'published']);
Route::get('/pages/{slug}', [PageController::class, 'show']);

// Categories (public)
Route::get('/categories', [CategoryController::class, 'all']);

// Menus (public)
Route::get('/menus/{location}', [MenuController::class, 'show']);

// Custom Profile Fields (public - for registration form)
Route::get('/settings/profile-fields/public', [SettingController::class, 'profileFields']);

// Mobile Theme (public)
Route::get('/mobile-theme', [MobileThemeController::class, 'show']);
Route::get('/pwa-config', [MobileThemeController::class, 'pwaConfig']);

// Localizations (public)
Route::get('/translations/{language}', [LocalizationController::class, 'getTranslations']);

// Appearance (public - CSS themes)
Route::get('/appearance', [AppearanceController::class, 'index']);
Route::get('/appearance/themes', [AppearanceController::class, 'themes']);

// Search (public)
Route::get('/search', [App\Http\Controllers\Api\SearchController::class, 'search']);
Route::get('/search/suggest', [App\Http\Controllers\Api\SearchController::class, 'suggest']);
Route::get('/search/health', [App\Http\Controllers\Api\SearchController::class, 'health']);
Route::get('/search/{type}', [App\Http\Controllers\Api\SearchController::class, 'searchType']);

// Churches (public)
Route::get('/churches', [ChurchController::class, 'directory']);
Route::get('/churches/{slug}', [ChurchController::class, 'showBySlug'])->where('slug', '^(?!admin$)[a-zA-Z0-9\-]+$');
Route::post('/churches/{slug}/view', [ChurchController::class, 'incrementView'])->where('slug', '^(?!admin$)[a-zA-Z0-9\-]+$');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap/stats', [SitemapController::class, 'stats']);

// Public Submissions
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe']);
Route::post('/prayer-requests', [PrayerRequestController::class, 'store']);

// Public prayer submission (guest-friendly, plugin version)
if (app(\Common\Core\PluginManager::class)->isEnabled('prayer')) {
    Route::post('/prayer-requests/submit', [\App\Plugins\Prayer\Controllers\PrayerRequestController::class, 'store']);
}

// Blog public routes (no auth required — articles are public for SEO)
Route::prefix('v1')->group(function () {
    if (app(\Common\Core\PluginManager::class)->isEnabled('blog')) {
        require app_path('Plugins/Blog/Routes/public.php');
    }
});

Route::post('/reviews', [ReviewController::class, 'store']);
Route::post('/testimonies', [TestimonyController::class, 'store']);
Route::post('/events/{event}/register', [EventController::class, 'register']);
Route::post('/prayer-requests/{prayerRequest}/pray', [PrayerRequestController::class, 'pray']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth — no permission required
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard — any authenticated user
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ----------------------------------------------------------------
    // Content Management — manage_posts
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_posts')->group(function () {
        Route::get('/posts', [PostController::class, 'index']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::put('/posts/{post}', [PostController::class, 'update']);
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);

        Route::get('/pages', [PageController::class, 'index']);
        Route::post('/pages', [PageController::class, 'store']);
        Route::put('/pages/{page}', [PageController::class, 'update']);
        Route::delete('/pages/{page}', [PageController::class, 'destroy']);

        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    });

    // Sermons CRUD → handled by Plugins/Sermons/Routes/api.php at /api/v1/sermons
    // Books CRUD   → handled by Plugins/Library/Routes/api.php  at /api/v1/books

    // ----------------------------------------------------------------
    // Books & Bible Studies — manage_bible_studies
    // ----------------------------------------------------------------

    Route::middleware('permission:manage_bible_studies')->group(function () {
        Route::get('/bible-studies', [BibleStudyController::class, 'index']);
        Route::post('/bible-studies', [BibleStudyController::class, 'store']);
        Route::put('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'update']);
        Route::delete('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Verses & Blessings — manage_posts (daily content)
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_posts')->group(function () {
        Route::get('/verses', [VerseController::class, 'index']);
        Route::post('/verses', [VerseController::class, 'store']);
        Route::put('/verses/{verse}', [VerseController::class, 'update']);
        Route::delete('/verses/{verse}', [VerseController::class, 'destroy']);
        Route::get('/verses/sample-csv', [VerseController::class, 'sampleCsv']);
        Route::post('/verses/import-csv', [VerseController::class, 'importCsv']);

        Route::get('/blessings', [BlessingController::class, 'index']);
        Route::post('/blessings', [BlessingController::class, 'store']);
        Route::put('/blessings/{blessing}', [BlessingController::class, 'update']);
        Route::delete('/blessings/{blessing}', [BlessingController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Testimonies & Reviews — manage_testimonies
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_testimonies')->group(function () {
        Route::get('/testimonies', [TestimonyController::class, 'index']);
        Route::put('/testimonies/{testimony}', [TestimonyController::class, 'update']);
        Route::patch('/testimonies/{testimony}/status', [TestimonyController::class, 'updateStatus']);
        Route::delete('/testimonies/{testimony}', [TestimonyController::class, 'destroy']);

        Route::get('/reviews', [ReviewController::class, 'index']);
        Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve']);
        Route::patch('/reviews/{review}/toggle-featured', [ReviewController::class, 'toggleFeatured']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Galleries — manage_galleries
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_galleries')->group(function () {
        Route::post('/galleries', [GalleryController::class, 'store']);
        Route::post('/galleries/{gallery}/images', [GalleryController::class, 'addImage']);
        Route::delete('/gallery-images/{galleryImage}', [GalleryController::class, 'removeImage']);
        Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Ministries — manage_ministries
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_ministries')->group(function () {
        Route::post('/ministries', [MinistryController::class, 'store']);
        Route::put('/ministries/{ministry}', [MinistryController::class, 'update']);
        Route::delete('/ministries/{ministry}', [MinistryController::class, 'destroy']);
    });

    // Events CRUD        → handled by Plugins/Events/Routes/api.php  at /api/v1/events
    // Prayer Requests    → handled by Plugins/Prayer/Routes/api.php  at /api/v1/prayer-requests

    // ----------------------------------------------------------------
    // Contacts — manage_contacts
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_contacts')->group(function () {
        Route::get('/contacts', [ContactController::class, 'index']);
        Route::get('/contacts/{contactMessage}', [ContactController::class, 'show']);
        Route::patch('/contacts/{contactMessage}/read', [ContactController::class, 'markRead']);
        Route::post('/contacts/{contactMessage}/reply', [ContactController::class, 'reply']);
        Route::delete('/contacts/{contactMessage}', [ContactController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Newsletter — manage_newsletter / send_newsletter
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_newsletter')->group(function () {
        Route::get('/newsletter/subscribers', [NewsletterController::class, 'subscribers']);
        Route::get('/newsletter/subscribers/export', [NewsletterController::class, 'exportSubscribers']);
        Route::get('/newsletter/templates', [NewsletterController::class, 'templates']);
        Route::post('/newsletter/templates', [NewsletterController::class, 'storeTemplate']);
        Route::put('/newsletter/templates/{template}', [NewsletterController::class, 'updateTemplate']);
        Route::delete('/newsletter/templates/{template}', [NewsletterController::class, 'destroyTemplate']);
    });
    Route::middleware('permission:send_newsletter')->group(function () {
        Route::post('/newsletter/templates/{template}/send', [NewsletterController::class, 'sendTemplate']);
    });

    // ----------------------------------------------------------------
    // Donations — manage_donations
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_donations')->group(function () {
        // Donation read endpoints (existing controllers)
    });

    // ----------------------------------------------------------------
    // System Settings — admin.access
    // ----------------------------------------------------------------
    Route::middleware('permission:admin.access')->group(function () {
        Route::put('/settings', [SettingController::class, 'update']);
        Route::get('/settings/email', [SettingController::class, 'emailSettings']);
        Route::put('/settings/email', [SettingController::class, 'updateEmailSettings']);
        Route::post('/settings/email/test', [SettingController::class, 'testEmail']);
        Route::get('/settings/widgets', [SettingController::class, 'widgetConfig']);
        Route::put('/settings/widgets', [SettingController::class, 'updateWidgetConfig']);
        Route::get('/settings/profile-fields', [SettingController::class, 'profileFields']);
        Route::put('/settings/profile-fields', [SettingController::class, 'updateProfileFields']);
        Route::post('/sitemap/generate', [SitemapController::class, 'generate']);
    });

    // ----------------------------------------------------------------
    // Appearance — manage_appearance
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_appearance')->group(function () {
        Route::put('/appearance', [AppearanceController::class, 'update']);
        Route::post('/appearance/themes', [AppearanceController::class, 'saveTheme']);
        Route::put('/appearance/themes/{theme}', [AppearanceController::class, 'saveTheme']);
        Route::delete('/appearance/themes/{theme}', [AppearanceController::class, 'deleteTheme']);
        Route::post('/appearance/favicon', [AppearanceController::class, 'generateFavicon']);
        Route::put('/mobile-theme', [MobileThemeController::class, 'update']);
        Route::put('/pwa-config', [MobileThemeController::class, 'updatePwaConfig']);
    });

    // ----------------------------------------------------------------
    // Localizations — manage_localizations
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_localizations')->group(function () {
        Route::get('/localizations', [LocalizationController::class, 'index']);
        Route::get('/localizations/{localization}', [LocalizationController::class, 'show']);
        Route::post('/localizations', [LocalizationController::class, 'store']);
        Route::put('/localizations/{localization}', [LocalizationController::class, 'update']);
        Route::delete('/localizations/{localization}', [LocalizationController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Menus & Categories — manage_menus / manage_categories
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_menus')->group(function () {
        Route::get('/menus', [MenuController::class, 'index']);
        Route::post('/menus', [MenuController::class, 'store']);
        Route::put('/menus/{menu}', [MenuController::class, 'update']);
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
    });

    Route::middleware('permission:manage_categories')->group(function () {
        Route::get('/categories/admin', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Users & Roles — manage_users / manage_roles / assign_roles
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });

    Route::middleware('permission:assign_roles')->group(function () {
        Route::post('/roles/assign', [RoleController::class, 'assignRole']);
    });

    Route::middleware('permission:admin.access')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/reset-password', [ForgotPasswordController::class, 'adminResetPassword']);
    });

    // User Profile & Timeline Settings (authenticated users)
    Route::prefix('user/profile-settings')->group(function () {
        Route::get('/timeline-preferences', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'getTimelinePreferences']);
        Route::put('/timeline-preferences', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'updateTimelinePreferences']);
        Route::get('/privacy-settings', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'getPrivacySettings']);
        Route::put('/privacy-settings', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'updatePrivacySettings']);
        Route::get('/content-filters', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'getContentFilters']);
        Route::put('/content-filters', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'updateContentFilters']);
        Route::post('/reset-to-defaults', [\App\Http\Controllers\Api\UserProfileSettingsController::class, 'resetToDefaults']);
    });

    // ----------------------------------------------------------------
    // Churches — manage_churches / approve_churches
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_churches')->group(function () {
        Route::get('/churches/admin', [ChurchController::class, 'index']);
        Route::get('/churches/admin/available-admins', [ChurchController::class, 'availableAdmins']);
        Route::get('/churches/admin/my-church', [ChurchController::class, 'myChurch']);
        Route::get('/churches/admin/import-csv/sample', [ChurchController::class, 'sampleCsv']);
        Route::post('/churches/admin/import-csv', [ChurchController::class, 'importCsv']);
        Route::post('/churches/admin', [ChurchController::class, 'store']);
        Route::get('/churches/admin/{church}', [ChurchController::class, 'show']);
        Route::post('/churches/admin/{church}', [ChurchController::class, 'update']);
        Route::patch('/churches/admin/{church}/featured', [ChurchController::class, 'toggleFeatured']);
        Route::post('/churches/admin/{church}/documents', [ChurchController::class, 'uploadDocument']);
        Route::delete('/churches/admin/{church}/documents', [ChurchController::class, 'deleteDocument']);
        Route::delete('/churches/admin/{church}', [ChurchController::class, 'destroy']);
    });

    Route::middleware('permission:approve_churches')->group(function () {
        Route::patch('/churches/admin/{church}/status', [ChurchController::class, 'updateStatus']);
    });

    // ----------------------------------------------------------------
    // System & Deploy — manage_settings (super admin in practice)
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/system/status', [SystemController::class, 'status']);
        Route::post('/system/git-pull', [SystemController::class, 'gitPull']);
        Route::post('/system/migrate', [SystemController::class, 'migrate']);
        Route::post('/system/build', [SystemController::class, 'buildAssets']);
        Route::post('/system/clear-cache', [SystemController::class, 'clearCache']);
        Route::post('/system/optimize', [SystemController::class, 'optimize']);
        Route::post('/system/deploy', [SystemController::class, 'deploy']);
        Route::get('/system/git-log', [SystemController::class, 'gitLog']);

        // Zip-based update (shared hosting) — upload package, extract, migrate
        Route::get('/update/status', [\App\Http\Controllers\Admin\UpdateController::class, 'status']);
        Route::post('/update/upload', [\App\Http\Controllers\Admin\UpdateController::class, 'upload']);
        Route::post('/update/migrate', [\App\Http\Controllers\Admin\UpdateController::class, 'migrate']);
        Route::post('/update/clear-caches', [\App\Http\Controllers\Admin\UpdateController::class, 'clearCaches']);

        // Enhanced Admin Dashboard
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard/analytics', [\App\Http\Controllers\Admin\DashboardController::class, 'analytics']);
            Route::get('/dashboard/activity', [\App\Http\Controllers\Admin\DashboardController::class, 'recentActivity']);
            Route::get('/dashboard/health', [\App\Http\Controllers\Admin\DashboardController::class, 'systemHealth']);

            // User Management
            Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index']);
            Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store']);
            Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'show']);
            Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update']);
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy']);
            Route::post('/users/{user}/toggle-active', [\App\Http\Controllers\Admin\UserManagementController::class, 'toggleActive']);
            Route::post('/users/{user}/impersonate', [\App\Http\Controllers\Admin\UserManagementController::class, 'impersonate']);
            Route::post('/impersonate/stop', [\App\Http\Controllers\Admin\UserManagementController::class, 'stopImpersonating']);

            // System Management
            Route::get('/system/info', [\App\Http\Controllers\Admin\SystemController::class, 'info']);
            Route::post('/system/cache/clear', [\App\Http\Controllers\Admin\SystemController::class, 'clearCache']);
            Route::post('/system/optimize', [\App\Http\Controllers\Admin\SystemController::class, 'optimizeApp']);
            Route::get('/system/logs', [\App\Http\Controllers\Admin\SystemController::class, 'logs']);
            Route::delete('/system/logs', [\App\Http\Controllers\Admin\SystemController::class, 'clearLogs']);
            Route::post('/system/maintenance', [\App\Http\Controllers\Admin\SystemController::class, 'maintenanceMode']);
            Route::get('/system/queue', [\App\Http\Controllers\Admin\SystemController::class, 'queueStatus']);
            Route::post('/system/queue/retry', [\App\Http\Controllers\Admin\SystemController::class, 'retryFailedJobs']);

            // Plugin Management
            Route::get('/plugins', [\App\Http\Controllers\Admin\PluginController::class, 'index']);
            Route::post('/plugins/enable', [\App\Http\Controllers\Admin\PluginController::class, 'enable']);
            Route::post('/plugins/disable', [\App\Http\Controllers\Admin\PluginController::class, 'disable']);
            Route::get('/plugins/{plugin}', [\App\Http\Controllers\Admin\PluginController::class, 'show']);
            Route::post('/plugins/install', [\App\Http\Controllers\Admin\PluginController::class, 'install']);
            Route::delete('/plugins/{plugin}', [\App\Http\Controllers\Admin\PluginController::class, 'uninstall']);
            Route::get('/plugins/{plugin}/settings', [\App\Http\Controllers\Admin\PluginController::class, 'getSettings']);
            Route::put('/plugins/{plugin}/settings', [\App\Http\Controllers\Admin\PluginController::class, 'updateSettings']);

            // Landing Page Customizer
            Route::prefix('landing-page')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\LandingPageController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Admin\LandingPageController::class, 'store']);
                Route::put('/{section}', [\App\Http\Controllers\Admin\LandingPageController::class, 'update']);
                Route::delete('/{section}', [\App\Http\Controllers\Admin\LandingPageController::class, 'destroy']);
                Route::post('/reorder', [\App\Http\Controllers\Admin\LandingPageController::class, 'reorder']);
                Route::put('/settings', [\App\Http\Controllers\Admin\LandingPageController::class, 'updateSettings']);
                Route::get('/templates', [\App\Http\Controllers\Admin\LandingPageController::class, 'templates']);
                Route::get('/preview', [\App\Http\Controllers\Admin\LandingPageController::class, 'preview']);
            });

            // Menu Customizer
            Route::prefix('menus')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\MenuController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Admin\MenuController::class, 'store']);
                Route::put('/{menuItem}', [\App\Http\Controllers\Admin\MenuController::class, 'update']);
                Route::delete('/{menuItem}', [\App\Http\Controllers\Admin\MenuController::class, 'destroy']);
                Route::post('/reorder', [\App\Http\Controllers\Admin\MenuController::class, 'reorder']);
                Route::put('/settings', [\App\Http\Controllers\Admin\MenuController::class, 'updateSettings']);
                Route::get('/locations', [\App\Http\Controllers\Admin\MenuController::class, 'locations']);
                Route::get('/routes', [\App\Http\Controllers\Admin\MenuController::class, 'availableRoutes']);
                Route::get('/preview', [\App\Http\Controllers\Admin\MenuController::class, 'preview']);
            });

            // Theme & Appearance
            Route::prefix('appearance')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AppearanceController::class, 'index']);
                Route::put('/theme', [\App\Http\Controllers\Admin\AppearanceController::class, 'updateTheme']);
                Route::post('/upload-logo', [\App\Http\Controllers\Admin\AppearanceController::class, 'uploadLogo']);
                Route::put('/custom-css', [\App\Http\Controllers\Admin\AppearanceController::class, 'updateCustomCss']);
                Route::get('/themes', [\App\Http\Controllers\Admin\AppearanceController::class, 'themes']);
                Route::get('/color-palettes', [\App\Http\Controllers\Admin\AppearanceController::class, 'colorPalettes']);
                Route::post('/preview', [\App\Http\Controllers\Admin\AppearanceController::class, 'preview']);
                Route::post('/reset', [\App\Http\Controllers\Admin\AppearanceController::class, 'reset']);
                Route::get('/export', [\App\Http\Controllers\Admin\AppearanceController::class, 'export']);
                Route::post('/import', [\App\Http\Controllers\Admin\AppearanceController::class, 'import']);
            });

            // Settings Management
            Route::prefix('settings')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index']);
                Route::put('/general', [\App\Http\Controllers\Admin\SettingsController::class, 'updateGeneral']);
                Route::put('/contact', [\App\Http\Controllers\Admin\SettingsController::class, 'updateContact']);
                Route::put('/social', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSocial']);
                Route::put('/worship', [\App\Http\Controllers\Admin\SettingsController::class, 'updateWorship']);
                Route::put('/notifications', [\App\Http\Controllers\Admin\SettingsController::class, 'updateNotifications']);
                Route::put('/security', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSecurity']);
                Route::put('/integrations', [\App\Http\Controllers\Admin\SettingsController::class, 'updateIntegrations']);
                Route::get('/export', [\App\Http\Controllers\Admin\SettingsController::class, 'export']);
                Route::post('/import', [\App\Http\Controllers\Admin\SettingsController::class, 'import']);
                Route::post('/reset', [\App\Http\Controllers\Admin\SettingsController::class, 'reset']);
                
                // System settings (super admin only)
                Route::get('/system', [\App\Http\Controllers\Admin\SettingsController::class, 'getSystemSettings']);
                Route::put('/system', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSystemSettings']);
            });
        });
    });
});
