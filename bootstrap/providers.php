<?php

return [
    App\Providers\AppServiceProvider::class,
    Plugins\ChurchPage\ChurchPageServiceProvider::class,
    Plugins\Analytics\AnalyticsServiceProvider::class,
    Plugins\Community\CommunityServiceProvider::class,
    Plugins\Post\PostServiceProvider::class,
    Plugins\Faq\FaqServiceProvider::class,
    Plugins\Comment\CommentServiceProvider::class,
    Plugins\Reaction\ReactionServiceProvider::class,
    Plugins\Feed\FeedServiceProvider::class,
    Plugins\Event\EventServiceProvider::class,
    Plugins\Entity\EntityServiceProvider::class,
    Plugins\Installer\InstallerServiceProvider::class,
];
