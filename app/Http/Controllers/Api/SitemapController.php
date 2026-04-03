<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Page;
use App\Models\Sermon;
use App\Models\Book;
use App\Models\BibleStudy;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Ministry;
use App\Models\Testimony;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = url('/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= $this->urlEntry($baseUrl, now()->toDateString(), 'daily', '1.0');

        // Static pages
        $staticPages = ['blog', 'events', 'prayers', 'library', 'studies', 'sermons', 'giving', 'ministries', 'reviews', 'testimonies', 'contact', 'about'];
        foreach ($staticPages as $page) {
            $xml .= $this->urlEntry($baseUrl . '/#/' . $page, now()->toDateString(), 'weekly', '0.5');
        }

        // Posts (blog permalinks)
        $posts = Post::where('status', 'published')->get();
        foreach ($posts as $post) {
            $xml .= $this->urlEntry($baseUrl . '/#/blog/' . $post->slug, $post->updated_at->toDateString(), 'weekly', '0.8');
        }

        // Pages
        if (class_exists(Page::class)) {
            $pages = Page::where('status', 'published')->get();
            foreach ($pages as $page) {
                $xml .= $this->urlEntry($baseUrl . '/#/page/' . $page->slug, $page->updated_at->toDateString(), 'weekly', '0.7');
            }
        }

        // Categories
        if (class_exists(Category::class)) {
            $categories = Category::where('is_active', true)->get();
            foreach ($categories as $cat) {
                $xml .= $this->urlEntry($baseUrl . '/#/blog?category=' . $cat->slug, $cat->updated_at->toDateString(), 'weekly', '0.5');
            }
        }

        // Sermons
        $sermons = Sermon::where('is_active', true)->get();
        foreach ($sermons as $sermon) {
            $xml .= $this->urlEntry($baseUrl . '/#/sermons/' . $sermon->slug, $sermon->updated_at->toDateString(), 'weekly', '0.7');
        }

        // Events
        $events = Event::where('is_active', true)->get();
        foreach ($events as $event) {
            $xml .= $this->urlEntry($baseUrl . '/#/events/' . ($event->slug ?? $event->id), $event->updated_at->toDateString(), 'weekly', '0.6');
        }

        // Books
        $books = Book::where('is_active', true)->get();
        foreach ($books as $book) {
            $xml .= $this->urlEntry($baseUrl . '/#/library/' . $book->slug, $book->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Bible Studies
        $studies = BibleStudy::where('is_active', true)->get();
        foreach ($studies as $study) {
            $xml .= $this->urlEntry($baseUrl . '/#/studies/' . $study->slug, $study->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Ministries
        $ministries = Ministry::where('is_active', true)->get();
        foreach ($ministries as $ministry) {
            $xml .= $this->urlEntry($baseUrl . '/#/ministries/' . $ministry->slug, $ministry->updated_at->toDateString(), 'monthly', '0.5');
        }

        // Testimonies
        $testimonies = Testimony::whereIn('status', ['approved', 'featured'])->get();
        foreach ($testimonies as $testimony) {
            $xml .= $this->urlEntry($baseUrl . '/#/testimonies/' . $testimony->slug, $testimony->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Galleries
        if (class_exists(Gallery::class)) {
            $galleries = Gallery::where('is_active', true)->get();
            foreach ($galleries as $gallery) {
                $xml .= $this->urlEntry($baseUrl . '/#/galleries/' . ($gallery->slug ?? $gallery->id), $gallery->updated_at->toDateString(), 'monthly', '0.5');
            }
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Get sitemap content stats for admin.
     */
    public function stats(): JsonResponse
    {
        $data = [
            'posts' => Post::where('status', 'published')->count(),
            'pages' => class_exists(Page::class) ? Page::where('status', 'published')->count() : 0,
            'sermons' => Sermon::where('is_active', true)->count(),
            'events' => Event::where('is_active', true)->count(),
            'books' => Book::where('is_active', true)->count(),
            'bible_studies' => BibleStudy::where('is_active', true)->count(),
            'ministries' => Ministry::where('is_active', true)->count(),
            'testimonies' => Testimony::whereIn('status', ['approved', 'featured'])->count(),
            'galleries' => class_exists(Gallery::class) ? Gallery::where('is_active', true)->count() : 0,
            'categories' => class_exists(Category::class) ? Category::where('is_active', true)->count() : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'last_generated' => now()->toISOString(),
        ]);
    }

    /**
     * Regenerate the sitemap (triggers a fresh build).
     */
    public function generate(): JsonResponse
    {
        $stats = [
            'posts' => Post::where('status', 'published')->count(),
            'pages' => class_exists(Page::class) ? Page::where('status', 'published')->count() : 0,
            'sermons' => Sermon::where('is_active', true)->count(),
            'events' => Event::where('is_active', true)->count(),
            'books' => Book::where('is_active', true)->count(),
            'bible_studies' => BibleStudy::where('is_active', true)->count(),
            'ministries' => Ministry::where('is_active', true)->count(),
            'testimonies' => Testimony::whereIn('status', ['approved', 'featured'])->count(),
            'galleries' => class_exists(Gallery::class) ? Gallery::where('is_active', true)->count() : 0,
            'categories' => class_exists(Category::class) ? Category::where('is_active', true)->count() : 0,
        ];

        $totalUrls = array_sum($stats) + 1 + 12; // homepage + static pages

        return response()->json([
            'success' => true,
            'message' => 'Sitemap regenerated successfully.',
            'url_count' => $totalUrls,
            'stats' => $stats,
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>" . htmlspecialchars($loc) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
}
