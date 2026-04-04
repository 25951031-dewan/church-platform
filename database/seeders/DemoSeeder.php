<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('email', 'admin@demo.com')->value('id')
            ?? DB::table('users')->first()?->id
            ?? 1;

        $now = Carbon::now();

        if (DB::table('sermons')->count() > 0) {
            $this->command->info('Demo data already present, skipping.');
            return;
        }

        // ── Sermon Series ──────────────────────────────────────
        $series1 = DB::table('sermon_series')->where('slug', 'walking-in-faith')->value('id')
            ?? DB::table('sermon_series')->insertGetId([
                'name'        => 'Walking in Faith',
                'slug'        => 'walking-in-faith',
                'description' => 'A six-part series on trusting God through every season of life.',
                'created_by'  => $adminId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

        $series2 = DB::table('sermon_series')->where('slug', 'sermon-on-the-mount')->value('id')
            ?? DB::table('sermon_series')->insertGetId([
                'name'        => 'The Sermon on the Mount',
                'slug'        => 'sermon-on-the-mount',
                'description' => 'A deep dive into Jesus\' most famous teaching.',
                'created_by'  => $adminId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

        // ── Speakers ───────────────────────────────────────────
        $spk1 = DB::table('speakers')->where('slug', 'pastor-john-matthews')->value('id')
            ?? DB::table('speakers')->insertGetId([
                'name'        => 'Pastor John Matthews',
                'slug'        => 'pastor-john-matthews',
                'bio'         => 'Senior pastor with over 20 years of ministry experience.',
                'created_by'  => $adminId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

        $spk2 = DB::table('speakers')->where('slug', 'pastor-sarah-collins')->value('id')
            ?? DB::table('speakers')->insertGetId([
                'name'        => 'Pastor Sarah Collins',
                'slug'        => 'pastor-sarah-collins',
                'bio'         => 'Associate pastor and women\'s ministry director.',
                'created_by'  => $adminId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

        // ── Sermons ────────────────────────────────────────────
        $sermons = [
            [
                'title'              => 'The Armor of God',
                'slug'               => 'the-armor-of-god',
                'description'        => 'Understanding the spiritual weapons God provides for every believer.',
                'speaker'            => 'Pastor John Matthews',
                'speaker_id'         => $spk1,
                'series_id'          => $series1,
                'scripture_reference'=> 'Ephesians 6:10-18',
                'sermon_date'        => $now->copy()->subDays(7)->toDateString(),
                'duration_minutes'   => 42,
                'is_featured'        => true,
                'is_active'          => true,
            ],
            [
                'title'              => 'Blessed Are the Poor in Spirit',
                'slug'               => 'blessed-are-the-poor-in-spirit',
                'description'        => 'The Beatitudes teach us what true kingdom living looks like.',
                'speaker'            => 'Pastor John Matthews',
                'speaker_id'         => $spk1,
                'series_id'          => $series2,
                'scripture_reference'=> 'Matthew 5:1-12',
                'sermon_date'        => $now->copy()->subDays(14)->toDateString(),
                'duration_minutes'   => 38,
                'is_featured'        => false,
                'is_active'          => true,
            ],
            [
                'title'              => 'Salt and Light',
                'slug'               => 'salt-and-light',
                'description'        => 'How followers of Jesus are called to influence the world around them.',
                'speaker'            => 'Pastor Sarah Collins',
                'speaker_id'         => $spk2,
                'series_id'          => $series2,
                'scripture_reference'=> 'Matthew 5:13-16',
                'sermon_date'        => $now->copy()->subDays(21)->toDateString(),
                'duration_minutes'   => 35,
                'is_featured'        => false,
                'is_active'          => true,
            ],
            [
                'title'              => 'When Fear Comes Knocking',
                'slug'               => 'when-fear-comes-knocking',
                'description'        => 'Biblical tools for overcoming anxiety and walking in peace.',
                'speaker'            => 'Pastor John Matthews',
                'speaker_id'         => $spk1,
                'series_id'          => $series1,
                'scripture_reference'=> 'Psalm 23',
                'sermon_date'        => $now->copy()->subDays(28)->toDateString(),
                'duration_minutes'   => 44,
                'is_featured'        => true,
                'is_active'          => true,
            ],
            [
                'title'              => 'The Lord\'s Prayer',
                'slug'               => 'the-lords-prayer',
                'description'        => 'A model for how every Christian should approach prayer.',
                'speaker'            => 'Pastor Sarah Collins',
                'speaker_id'         => $spk2,
                'series_id'          => $series2,
                'scripture_reference'=> 'Matthew 6:9-13',
                'sermon_date'        => $now->copy()->subDays(35)->toDateString(),
                'duration_minutes'   => 40,
                'is_featured'        => false,
                'is_active'          => true,
            ],
        ];

        foreach ($sermons as $sermon) {
            DB::table('sermons')->insert(array_merge($sermon, [
                'view_count' => rand(20, 200),
                'author_id'  => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ── Events ─────────────────────────────────────────────
        $events = [
            [
                'title'       => 'Sunday Worship Service',
                'slug'        => 'sunday-worship-service',
                'description' => 'Join us every Sunday for an uplifting time of praise, worship and the Word.',
                'location'    => 'Main Sanctuary',
                'start_date'  => $now->copy()->next('Sunday')->setTime(10, 0)->toDateTimeString(),
                'end_date'    => $now->copy()->next('Sunday')->setTime(12, 0)->toDateTimeString(),
                'is_featured' => true,
                'is_active'   => true,
                'is_recurring'=> true,
            ],
            [
                'title'       => 'Youth Night',
                'slug'        => 'youth-night',
                'description' => 'A high-energy gathering for teens — worship, games, and real talk.',
                'location'    => 'Youth Center',
                'start_date'  => $now->copy()->addDays(5)->setTime(19, 0)->toDateTimeString(),
                'end_date'    => $now->copy()->addDays(5)->setTime(21, 30)->toDateTimeString(),
                'is_featured' => false,
                'is_active'   => true,
                'is_recurring'=> false,
            ],
            [
                'title'       => 'Women\'s Bible Study',
                'slug'        => 'womens-bible-study',
                'description' => 'A supportive community of women studying Scripture together.',
                'location'    => 'Fellowship Hall Room 3',
                'start_date'  => $now->copy()->addDays(3)->setTime(10, 0)->toDateTimeString(),
                'end_date'    => $now->copy()->addDays(3)->setTime(11, 30)->toDateTimeString(),
                'is_featured' => false,
                'is_active'   => true,
                'is_recurring'=> true,
            ],
            [
                'title'       => 'Community Outreach Day',
                'slug'        => 'community-outreach-day',
                'description' => 'Serving our neighborhood — food drive, free health screenings, and more.',
                'location'    => 'Community Park',
                'start_date'  => $now->copy()->addDays(12)->setTime(9, 0)->toDateTimeString(),
                'end_date'    => $now->copy()->addDays(12)->setTime(15, 0)->toDateTimeString(),
                'is_featured' => true,
                'is_active'   => true,
                'is_recurring'=> false,
            ],
            [
                'title'       => 'Prayer & Fasting Retreat',
                'slug'        => 'prayer-and-fasting-retreat',
                'description' => 'A weekend away to seek God, fast, and pray together as a church family.',
                'location'    => 'Mountain View Retreat Center',
                'start_date'  => $now->copy()->addDays(20)->setTime(17, 0)->toDateTimeString(),
                'end_date'    => $now->copy()->addDays(22)->setTime(14, 0)->toDateTimeString(),
                'is_featured' => true,
                'is_active'   => true,
                'is_recurring'=> false,
            ],
        ];

        foreach ($events as $event) {
            DB::table('events')->insert(array_merge($event, [
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ── Prayer Requests ────────────────────────────────────
        $prayers = [
            ['name' => 'Sarah M.',    'subject' => 'Healing for my mother',        'request' => 'My mother was diagnosed with breast cancer last week. Please pray for healing and strength for our whole family.', 'category' => 'health',        'is_urgent' => true],
            ['name' => 'James R.',    'subject' => 'Job opportunity',               'request' => 'I\'ve been unemployed for 3 months. I have an interview next week. Please pray for favor and provision.', 'category' => 'work',          'is_urgent' => false],
            ['name' => 'Anonymous',   'subject' => 'Marriage restoration',          'request' => 'My husband and I are struggling. We are seeking counseling and ask for prayer that God would heal our marriage.', 'category' => 'relationships', 'is_urgent' => false],
            ['name' => 'David K.',    'subject' => 'Financial breakthrough',        'request' => 'We are facing serious debt after a medical emergency. We trust God to provide. Please pray with us.', 'category' => 'financial',     'is_urgent' => true],
            ['name' => 'Linda P.',    'subject' => 'Son returning to faith',        'request' => 'My 22-year-old son has walked away from the Lord. I pray every day. Please agree with me for his return.', 'category' => 'family',        'is_urgent' => false],
            ['name' => 'Marcus T.',   'subject' => 'Anxiety and depression',        'request' => 'I have been battling severe anxiety. I know God is my peace. Please pray for my mental health.', 'category' => 'spiritual',     'is_urgent' => false],
            ['name' => 'Grace A.',    'subject' => 'Surgery next Monday',           'request' => 'I am having heart surgery on Monday. Please pray for the doctors and for a successful outcome.', 'category' => 'health',        'is_urgent' => true],
            ['name' => 'Thomas H.',   'subject' => 'New ministry direction',        'request' => 'I feel called to start a nonprofit. Seeking wisdom and clear confirmation from God about the next steps.', 'category' => 'spiritual',     'is_urgent' => false],
            ['name' => 'Patricia N.', 'subject' => 'Grief after losing spouse',     'request' => 'My husband passed three months ago after 43 years of marriage. The loneliness is overwhelming. Please pray for comfort.', 'category' => 'grief',         'is_urgent' => false],
            ['name' => 'Kevin O.',    'subject' => 'Prodigal daughter',             'request' => 'Our teenage daughter ran away from home. We are devastated. Please pray for her safety and return.', 'category' => 'family',        'is_urgent' => true],
        ];

        foreach ($prayers as $prayer) {
            DB::table('prayer_requests')->insert([
                'name'         => $prayer['name'],
                'subject'      => $prayer['subject'],
                'request'      => $prayer['request'],
                'category'     => $prayer['category'],
                'status'       => 'approved',
                'is_public'    => true,
                'is_anonymous' => $prayer['name'] === 'Anonymous',
                'is_urgent'    => $prayer['is_urgent'],
                'prayer_count' => rand(5, 80),
                'user_id'      => null,
                'created_at'   => $now->copy()->subDays(rand(1, 30)),
                'updated_at'   => $now,
            ]);
        }

        // ── Groups ─────────────────────────────────────────────
        $groups = [
            ['name' => 'Men\'s Fellowship',       'slug' => 'mens-fellowship',       'type' => 'public',  'description' => 'A brotherhood of men committed to spiritual growth, accountability, and serving the community.',  'is_featured' => true],
            ['name' => 'Women\'s Circle',          'slug' => 'womens-circle',          'type' => 'public',  'description' => 'A welcoming group for women to study the Word, share life, and support one another.',           'is_featured' => false],
            ['name' => 'Young Adults (18-30)',     'slug' => 'young-adults',           'type' => 'public',  'description' => 'Building community for young adults navigating faith, career, and relationships.',              'is_featured' => true],
            ['name' => 'Intercessory Prayer Team', 'slug' => 'intercessory-prayer',   'type' => 'private', 'description' => 'A dedicated team of prayer warriors covering the church and community in prayer.',               'is_featured' => false],
            ['name' => 'Worship & Arts Ministry', 'slug' => 'worship-arts-ministry',  'type' => 'public',  'description' => 'Serving through music, dance, spoken word, and creative arts in Sunday services.',              'is_featured' => false],
        ];

        foreach ($groups as $group) {
            DB::table('groups')->insert(array_merge($group, [
                'member_count' => rand(8, 45),
                'created_by'   => $adminId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]));
        }

        // ── Library ────────────────────────────────────────────
        $catDevotional = DB::table('book_categories')->where('slug', 'devotionals')->value('id')
            ?? DB::table('book_categories')->insertGetId([
                'name'       => 'Devotionals',
                'slug'       => 'devotionals',
                'is_active'  => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $catTheology = DB::table('book_categories')->where('slug', 'theology-and-study')->value('id')
            ?? DB::table('book_categories')->insertGetId([
                'name'       => 'Theology & Study',
                'slug'       => 'theology-and-study',
                'is_active'  => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $books = [
            ['title' => 'My Utmost for His Highest', 'slug' => 'my-utmost-for-his-highest', 'author' => 'Oswald Chambers',    'category_id' => $catDevotional, 'description' => 'A classic devotional that has encouraged millions of believers for over a century.',         'is_featured' => true],
            ['title' => 'The Purpose Driven Life',   'slug' => 'purpose-driven-life',        'author' => 'Rick Warren',         'category_id' => $catTheology,   'description' => 'A 40-day journey to discover the answer to life\'s most important question: "What on earth am I here for?"', 'is_featured' => true],
            ['title' => 'Mere Christianity',         'slug' => 'mere-christianity',          'author' => 'C.S. Lewis',          'category_id' => $catTheology,   'description' => 'A foundational work presenting the case for the Christian faith in clear, logical terms.',    'is_featured' => false],
            ['title' => 'Jesus Calling',             'slug' => 'jesus-calling',              'author' => 'Sarah Young',         'category_id' => $catDevotional, 'description' => 'Devotional writings inspired by the author\'s own spiritual journey into deeper intimacy with God.', 'is_featured' => false],
            ['title' => 'Knowing God',               'slug' => 'knowing-god',                'author' => 'J.I. Packer',         'category_id' => $catTheology,   'description' => 'An exploration of the nature and character of God and what it means to truly know Him.',      'is_featured' => false],
            ['title' => 'Celebration of Discipline', 'slug' => 'celebration-of-discipline',  'author' => 'Richard J. Foster',   'category_id' => $catTheology,   'description' => 'A guide to the classic spiritual disciplines that open the door to the abundant life.',     'is_featured' => false],
        ];

        foreach ($books as $book) {
            DB::table('books')->insert(array_merge($book, [
                'is_active'     => true,
                'view_count'    => rand(10, 150),
                'download_count'=> rand(5, 80),
                'uploaded_by'   => $adminId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]));
        }

        // ── Blog ───────────────────────────────────────────────
        $catFaith = DB::table('article_categories')->where('slug', 'faith-and-life')->value('id')
            ?? DB::table('article_categories')->insertGetId([
                'name'       => 'Faith & Life',
                'slug'       => 'faith-and-life',
                'is_active'  => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $catCommunity = DB::table('article_categories')->where('slug', 'community')->value('id')
            ?? DB::table('article_categories')->insertGetId([
                'name'       => 'Community',
                'slug'       => 'community',
                'is_active'  => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $articles = [
            [
                'title'        => 'Five Habits That Will Deepen Your Prayer Life',
                'slug'         => 'five-habits-deepen-prayer-life',
                'excerpt'      => 'Prayer is the heartbeat of the Christian life. Here are five practical habits that have helped believers grow in their prayer life.',
                'content'      => '<p>Prayer is not a religious obligation — it is a conversation with the living God. Yet many of us struggle to make prayer a consistent, meaningful part of our daily lives...</p>',
                'author_id'    => $adminId,
                'category_id'  => $catFaith,
                'status'       => 'published',
                'published_at' => $now->copy()->subDays(5),
                'is_featured'  => true,
                'is_active'    => true,
                'view_count'   => 124,
            ],
            [
                'title'        => 'What Our Outreach Day Taught Us About Community',
                'slug'         => 'outreach-day-community-lessons',
                'excerpt'      => 'Last month\'s outreach day was more than a service project — it was a lesson in what it truly means to love your neighbor.',
                'content'      => '<p>When we set up tables at the community park and began serving hot meals, something unexpected happened...</p>',
                'author_id'    => $adminId,
                'category_id'  => $catCommunity,
                'status'       => 'published',
                'published_at' => $now->copy()->subDays(12),
                'is_featured'  => false,
                'is_active'    => true,
                'view_count'   => 87,
            ],
            [
                'title'        => 'Understanding Grace: More Than Just Forgiveness',
                'slug'         => 'understanding-grace',
                'excerpt'      => 'Grace is one of the most central themes in the Bible, yet it\'s often misunderstood. Let\'s explore what grace really means for the believer.',
                'content'      => '<p>The word "grace" appears hundreds of times in Scripture, yet it remains one of the most misunderstood concepts in Christianity...</p>',
                'author_id'    => $adminId,
                'category_id'  => $catFaith,
                'status'       => 'published',
                'published_at' => $now->copy()->subDays(20),
                'is_featured'  => true,
                'is_active'    => true,
                'view_count'   => 215,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('articles')->insert(array_merge($article, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ── Live Meetings ──────────────────────────────────────
        $meetings = [
            [
                'title'       => 'Sunday Online Service',
                'description' => 'Join our Sunday service live from anywhere in the world.',
                'meeting_url' => 'https://youtube.com/live/example',
                'platform'    => 'youtube',
                'host_id'     => $adminId,
                'starts_at'   => $now->copy()->next('Sunday')->setTime(10, 0)->toDateTimeString(),
                'ends_at'     => $now->copy()->next('Sunday')->setTime(12, 0)->toDateTimeString(),
                'is_recurring'=> true,
                'recurrence_rule' => 'weekly',
                'is_active'   => true,
            ],
            [
                'title'       => 'Midweek Prayer Zoom',
                'description' => 'Come pray together Wednesday evenings via Zoom.',
                'meeting_url' => 'https://zoom.us/j/example',
                'platform'    => 'zoom',
                'host_id'     => $adminId,
                'starts_at'   => $now->copy()->next('Wednesday')->setTime(19, 0)->toDateTimeString(),
                'ends_at'     => $now->copy()->next('Wednesday')->setTime(20, 30)->toDateTimeString(),
                'is_recurring'=> true,
                'recurrence_rule' => 'weekly',
                'is_active'   => true,
            ],
            [
                'title'       => 'Men\'s Group Check-in',
                'description' => 'Monthly Google Meet check-in for the Men\'s Fellowship group.',
                'meeting_url' => 'https://meet.google.com/example',
                'platform'    => 'google_meet',
                'host_id'     => $adminId,
                'starts_at'   => $now->copy()->addDays(10)->setTime(20, 0)->toDateTimeString(),
                'ends_at'     => $now->copy()->addDays(10)->setTime(21, 0)->toDateTimeString(),
                'is_recurring'=> true,
                'recurrence_rule' => 'monthly',
                'is_active'   => true,
            ],
        ];

        foreach ($meetings as $meeting) {
            DB::table('meetings')->insert(array_merge($meeting, [
                'timezone'   => 'America/New_York',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->info('Demo data seeded: sermons, events, prayers, groups, library, blog, meetings.');
    }
}
