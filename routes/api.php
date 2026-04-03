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
            ->middleware('permission:settings.update');

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
                Route::delete('messages/{message}', [\Common\Chat\Controllers\MessageController::class, 'destroy']);

                // Typing indicator
                Route::post('conversations/{conversation}/typing', [\Common\Chat\Controllers\ChatPresenceController::class, 'typing']);

                // Presence
                Route::post('presence', [\Common\Chat\Controllers\ChatPresenceController::class, 'update']);

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

    // ----------------------------------------------------------------
    // Sermons — manage_sermons
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_sermons')->group(function () {
        Route::get('/sermons', [SermonController::class, 'index']);
        Route::post('/sermons', [SermonController::class, 'store']);
        Route::put('/sermons/{sermon}', [SermonController::class, 'update']);
        Route::delete('/sermons/{sermon}', [SermonController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Books & Bible Studies — manage_books / manage_bible_studies
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_books')->group(function () {
        Route::get('/books', [BookController::class, 'index']);
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });

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

    // ----------------------------------------------------------------
    // Events — manage_events
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_events')->group(function () {
        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Prayer Requests — manage_prayers
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_prayers')->group(function () {
        Route::get('/prayer-requests', [PrayerRequestController::class, 'index']);
        Route::put('/prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'update']);
        Route::patch('/prayer-requests/{prayerRequest}/status', [PrayerRequestController::class, 'updateStatus']);
        Route::delete('/prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'destroy']);
    });

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
    // System Settings — manage_settings
    // ----------------------------------------------------------------
    Route::middleware('permission:manage_settings')->group(function () {
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

    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/reset-password', [ForgotPasswordController::class, 'adminResetPassword']);
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
    });
});
