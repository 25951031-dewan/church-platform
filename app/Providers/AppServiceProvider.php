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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
    }
}
