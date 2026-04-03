<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleStudy;
use App\Models\Book;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PrayerRequest;
use App\Models\Review;
use App\Models\Sermon;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return aggregated dashboard statistics for the admin panel.
     */
    public function stats(): JsonResponse
    {
        // Counts of all main models
        $counts = [
            'users'           => User::count(),
            'posts'           => Post::count(),
            'events'          => Event::count(),
            'prayer_requests' => PrayerRequest::count(),
            'books'           => Book::count(),
            'sermons'         => Sermon::count(),
            'bible_studies'   => BibleStudy::count(),
            'reviews'         => Review::count(),
            'contacts'        => ContactMessage::count(),
            'subscribers'     => NewsletterSubscriber::where('is_active', true)->count(),
        ];

        // Additional useful stats
        $additionalStats = [
            'pending_reviews'    => Review::where('is_approved', false)->count(),
            'unread_contacts'    => ContactMessage::where('is_read', false)->count(),
            'published_posts'    => Post::where('status', 'published')->count(),
            'draft_posts'        => Post::where('status', 'draft')->count(),
        ];

        // Recent prayer requests (last 10)
        $recentPrayers = PrayerRequest::latest()
            ->take(10)
            ->get();

        // Upcoming events (next 10 events from today)
        $upcomingEvents = Event::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'counts'           => $counts,
                'additional_stats' => $additionalStats,
                'recent_prayers'   => $recentPrayers,
                'upcoming_events'  => $upcomingEvents,
            ],
        ]);
    }
}
