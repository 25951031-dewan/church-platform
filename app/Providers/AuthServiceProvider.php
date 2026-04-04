<?php

namespace App\Providers;

use App\Plugins\Timeline\Models\FeedLayout;
use App\Plugins\Timeline\Policies\FeedLayoutPolicy;
use Common\Chat\Models\Conversation;
use Common\Chat\Policies\ConversationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Conversation::class => ConversationPolicy::class,
        FeedLayout::class   => FeedLayoutPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
