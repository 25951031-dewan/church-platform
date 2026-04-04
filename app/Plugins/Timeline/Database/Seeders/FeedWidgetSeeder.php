<?php

namespace App\Plugins\Timeline\Database\Seeders;

use App\Plugins\Timeline\Models\FeedWidget;
use Illuminate\Database\Seeder;

class FeedWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $widgets = [
            [
                'widget_key'    => 'post_feed',
                'display_name'  => 'Post Feed',
                'description'   => 'Timeline posts and community updates',
                'component_path' => 'timeline/widgets/PostFeedWidget',
                'icon'          => 'message-square',
                'category'      => 'content',
                'sort_order'    => 1,
                'default_config' => ['posts_per_page' => 10, 'allow_reactions' => true, 'allow_comments' => true],
            ],
            [
                'widget_key'    => 'daily_verse',
                'display_name'  => 'Daily Verse',
                'description'   => 'Daily Bible verse with reflection',
                'component_path' => 'timeline/widgets/DailyVerseWidget',
                'icon'          => 'book-open',
                'category'      => 'content',
                'sort_order'    => 2,
                'default_config' => ['show_reflection' => true, 'show_reference' => true, 'translation' => 'NIV'],
            ],
            [
                'widget_key'    => 'announcements',
                'display_name'  => 'Announcements',
                'description'   => 'Church announcements and updates',
                'component_path' => 'timeline/widgets/AnnouncementsWidget',
                'icon'          => 'megaphone',
                'category'      => 'content',
                'sort_order'    => 3,
                'default_config' => ['max_announcements' => 5, 'show_dates' => true],
            ],
            [
                'widget_key'    => 'events',
                'display_name'  => 'Upcoming Events',
                'description'   => 'Upcoming church events',
                'component_path' => 'timeline/widgets/EventsWidget',
                'icon'          => 'calendar',
                'category'      => 'content',
                'sort_order'    => 4,
                'default_config' => ['days_ahead' => 30, 'max_events' => 5],
            ],
            [
                'widget_key'    => 'prayer_requests',
                'display_name'  => 'Prayer Requests',
                'description'   => 'Community prayer requests',
                'component_path' => 'timeline/widgets/PrayerRequestsWidget',
                'icon'          => 'hands-praying',
                'category'      => 'interaction',
                'sort_order'    => 5,
                'default_config' => ['max_requests' => 5, 'allow_anonymous' => true],
            ],
            [
                'widget_key'    => 'sermons',
                'display_name'  => 'Recent Sermons',
                'description'   => 'Latest sermon recordings and notes',
                'component_path' => 'timeline/widgets/SermonsWidget',
                'icon'          => 'mic',
                'category'      => 'content',
                'sort_order'    => 6,
                'default_config' => ['max_sermons' => 3, 'show_thumbnail' => true],
            ],
        ];

        foreach ($widgets as $widget) {
            $widget['default_config'] = json_encode($widget['default_config']);

            FeedWidget::firstOrCreate(
                ['widget_key' => $widget['widget_key']],
                $widget
            );
        }
    }
}
