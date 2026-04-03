<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use App\Modules\ModuleManager;
use App\Modules\Community\CommunityServiceProvider;
use App\Modules\Counseling\CounselingServiceProvider;
use App\Services\ChurchContext;
use Common\Comments\Models\Comment;
use Common\Comments\Policies\CommentPolicy;
use Common\Settings\Services\SettingService;
use App\Plugins\Timeline\Models\Post;
use App\Plugins\Timeline\Policies\PostPolicy;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Policies\GroupPolicy;
use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Policies\EventPolicy;
use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Policies\SermonPolicy;
use App\Plugins\Prayer\Models\PrayerRequest;
use App\Plugins\Prayer\Policies\PrayerRequestPolicy;
use App\Plugins\Library\Models\Book;
use App\Plugins\Library\Policies\BookPolicy;
use App\Plugins\Blog\Models\Article;
use App\Plugins\Blog\Policies\ArticlePolicy;
use App\Plugins\LiveMeeting\Models\Meeting;
use App\Plugins\LiveMeeting\Policies\MeetingPolicy;
use App\Plugins\ChurchBuilder\Models\Church as ChurchModel;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use App\Plugins\ChurchBuilder\Policies\ChurchPolicy;
use App\Plugins\ChurchBuilder\Policies\ChurchPagePolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Sanctum::ignoreMigrations();
        // Singletons for church context and module management
        $this->app->singleton(ChurchContext::class);
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(SettingService::class);
        $this->app->singleton(\Common\Core\PluginManager::class);
        $this->app->singleton(\Common\Core\BootstrapDataService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register module service providers
        $this->app->register(CommunityServiceProvider::class);
        $this->app->register(CounselingServiceProvider::class);

        // Register policies
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Sermon::class, SermonPolicy::class);
        Gate::policy(PrayerRequest::class, PrayerRequestPolicy::class);
        Gate::policy(ChurchModel::class, ChurchPolicy::class);
        Gate::policy(ChurchPage::class, ChurchPagePolicy::class);
        Gate::policy(Book::class, BookPolicy::class);
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Meeting::class, MeetingPolicy::class);

        // Morph map (required for polymorphic reactions/comments)
        Relation::enforceMorphMap([
            'post' => Post::class,
            'comment' => Comment::class,
            'group' => Group::class,
            'event' => Event::class,
            'sermon' => Sermon::class,
            'prayer_request' => PrayerRequest::class,
            'church' => ChurchModel::class,
            'book' => Book::class,
            'article' => Article::class,
        ]);
    }
}
