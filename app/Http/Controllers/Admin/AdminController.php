<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Event;
use App\Models\PrayerRequest;
use App\Models\Book;
use App\Models\Sermon;
use App\Models\BibleStudy;
use App\Models\Review;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\Verse;
use App\Models\Blessing;
use App\Models\Gallery;
use App\Models\Ministry;
use App\Models\Donation;
use App\Models\Testimony;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'posts' => Post::count(),
            'events' => Event::count(),
            'prayer_requests' => PrayerRequest::count(),
            'books' => Book::count(),
            'sermons' => Sermon::count(),
            'bible_studies' => BibleStudy::count(),
            'reviews' => Review::count(),
            'contacts' => ContactMessage::count(),
            'newsletter_subscribers' => NewsletterSubscriber::count(),
            'verses' => Verse::count(),
            'blessings' => Blessing::count(),
            'galleries' => Gallery::count(),
            'ministries' => Ministry::count(),
            'donations' => Donation::count(),
            'testimonies' => Testimony::count(),
        ];

        $recentPrayerRequests = PrayerRequest::latest()->take(5)->get();
        $upcomingEvents = Event::where('start_date', '>=', now())->orderBy('start_date', 'asc')->take(5)->get();
        $recentContacts = ContactMessage::latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentPrayerRequests',
            'upcomingEvents',
            'recentContacts'
        ));
    }

    public function verses()
    {
        return view('admin.manage', ['section' => 'verses']);
    }

    public function blessings()
    {
        return view('admin.manage', ['section' => 'blessings']);
    }

    public function prayers()
    {
        return view('admin.manage', ['section' => 'prayers']);
    }

    public function events()
    {
        return view('admin.manage', ['section' => 'events']);
    }

    public function posts()
    {
        return view('admin.manage', ['section' => 'posts']);
    }

    public function sermons()
    {
        return view('admin.manage', ['section' => 'sermons']);
    }

    public function books()
    {
        return view('admin.manage', ['section' => 'books']);
    }

    public function bibleStudies()
    {
        return view('admin.manage', ['section' => 'bible-studies']);
    }

    public function reviews()
    {
        return view('admin.manage', ['section' => 'reviews']);
    }

    public function galleries()
    {
        return view('admin.manage', ['section' => 'galleries']);
    }

    public function ministries()
    {
        return view('admin.manage', ['section' => 'ministries']);
    }

    public function contacts()
    {
        return view('admin.manage', ['section' => 'contacts']);
    }

    public function newsletter()
    {
        return view('admin.manage', ['section' => 'newsletter']);
    }

    public function donations()
    {
        return view('admin.manage', ['section' => 'donations']);
    }

    public function users()
    {
        return view('admin.manage', ['section' => 'users']);
    }

    public function settings()
    {
        return view('admin.manage', ['section' => 'settings']);
    }
}
