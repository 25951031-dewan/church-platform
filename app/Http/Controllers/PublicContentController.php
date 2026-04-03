<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Page;
use App\Models\Ministry;
use App\Models\BibleStudy;
use App\Models\Book;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PublicContentController extends Controller
{
    private function getSettings()
    {
        return Cache::remember('church_settings', 3600, function () {
            return Setting::first();
        });
    }

    /**
     * Blog post detail - /blog/{slug}
     */
    public function post(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $post->increment('view_count');

        $relatedPosts = Post::where('status', 'published')
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->latest('published_at')
            ->limit(3)
            ->get();

        $settings = $this->getSettings();

        return view('public.post', compact('post', 'relatedPosts', 'settings'));
    }

    /**
     * Static page - /page/{slug}
     */
    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $settings = $this->getSettings();

        return view('public.page', compact('page', 'settings'));
    }

    /**
     * Ministries listing - /ministries
     */
    public function ministries()
    {
        $ministries = Cache::remember('public_ministries', 1800, function () {
            return Ministry::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });

        $settings = $this->getSettings();

        return view('public.ministries', compact('ministries', 'settings'));
    }

    /**
     * Ministry detail - /ministries/{slug}
     */
    public function ministry(string $slug)
    {
        $ministry = Ministry::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $settings = $this->getSettings();

        return view('public.ministry', compact('ministry', 'settings'));
    }

    /**
     * Bible studies listing - /bible-studies
     */
    public function bibleStudies()
    {
        $studies = BibleStudy::where('is_published', true)
            ->latest()
            ->paginate(12);

        $settings = $this->getSettings();

        return view('public.bible-studies', compact('studies', 'settings'));
    }

    /**
     * Bible study detail - /bible-studies/{slug}
     */
    public function bibleStudy(string $slug)
    {
        $study = BibleStudy::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $study->increment('view_count');

        $settings = $this->getSettings();

        return view('public.bible-study', compact('study', 'settings'));
    }

    /**
     * Library listing - /library
     */
    public function library()
    {
        $books = Book::where('is_active', true)
            ->latest()
            ->paginate(12);

        $settings = $this->getSettings();

        return view('public.library', compact('books', 'settings'));
    }

    /**
     * Book detail - /library/{slug}
     */
    public function book(string $slug)
    {
        $book = Book::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $book->increment('view_count');

        $settings = $this->getSettings();

        return view('public.book', compact('book', 'settings'));
    }

    /**
     * About page - /about
     */
    public function about()
    {
        $settings = $this->getSettings();

        $ministries = Ministry::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('public.about', compact('settings', 'ministries'));
    }
}
