<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Services\ChurchLoader;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public Church Display Controller
 * Handles public church pages at /church/{slug}
 */
class PublicChurchController extends Controller
{
    public function __construct(
        private ChurchLoader $loader
    ) {}

    /**
     * GET /church/{slug}
     * Show public church page
     */
    public function show(string $slug): Response
    {
        $church = Church::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        // Increment view count
        $church->incrementView();

        // Load church data for public display
        $churchData = $this->loader->loadForPublicDisplay($church);

        return Inertia::render('Public/ChurchProfile', [
            'church' => $churchData,
            'seo' => [
                'title' => $church->meta_title ?: $church->name,
                'description' => $church->meta_description ?: $church->short_description,
                'canonical' => url("/church/{$church->slug}"),
            ]
        ]);
    }

    /**
     * GET /churches
     * Public church directory
     */
    public function index(Request $request): Response
    {
        $query = Church::query()
            ->where('status', 'approved')
            ->with(['admin:id,display_name']);

        // Search functionality
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('denomination', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Location filter
        if ($location = $request->input('location')) {
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('state', 'like', "%{$location}%");
            });
        }

        // Denomination filter
        if ($denomination = $request->input('denomination')) {
            $query->where('denomination', $denomination);
        }

        // Sorting
        $sort = $request->input('sort', 'featured');
        match ($sort) {
            'newest' => $query->latest(),
            'name' => $query->orderBy('name'),
            'popular' => $query->orderByDesc('view_count'),
            default => $query->orderByDesc('is_featured')->latest()
        };

        $churches = $query->paginate(12);

        // Get filter options
        $denominations = Church::where('status', 'approved')
            ->whereNotNull('denomination')
            ->distinct('denomination')
            ->orderBy('denomination')
            ->pluck('denomination');

        return Inertia::render('Public/ChurchDirectory', [
            'churches' => $churches,
            'filters' => [
                'search' => $request->input('search'),
                'location' => $request->input('location'),
                'denomination' => $request->input('denomination'),
                'sort' => $sort,
            ],
            'denominations' => $denominations,
        ]);
    }

    /**
     * API version of church directory
     */
    public function apiIndex(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Church::query()
            ->where('status', 'approved')
            ->with(['admin:id,display_name'])
            ->withCount(['approvedMembers']);

        // Search functionality
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('denomination', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Location filter
        if ($location = $request->input('location')) {
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('state', 'like', "%{$location}%");
            });
        }

        // Denomination filter
        if ($denomination = $request->input('denomination')) {
            $query->where('denomination', $denomination);
        }

        // Sorting
        $sort = $request->input('sort', 'featured');
        match ($sort) {
            'newest' => $query->latest(),
            'name' => $query->orderBy('name'),
            'popular' => $query->orderByDesc('view_count'),
            default => $query->orderByDesc('is_featured')->latest()
        };

        $churches = $query->paginate(12);

        // Get filter options
        $denominations = Church::where('status', 'approved')
            ->whereNotNull('denomination')
            ->distinct('denomination')
            ->orderBy('denomination')
            ->pluck('denomination');

        return response()->json([
            'churches' => $churches,
            'filters' => [
                'search' => $request->input('search'),
                'location' => $request->input('location'),
                'denomination' => $request->input('denomination'),
                'sort' => $sort,
            ],
            'denominations' => $denominations,
        ]);
    }

    /**
     * API version of church detail
     */
    public function apiShow(string $slug): \Illuminate\Http\JsonResponse
    {
        $church = Church::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        // Increment view count
        $church->incrementView();

        // Load church data for public display
        $churchData = $this->loader->loadForPublicDisplay($church);

        return response()->json([
            'church' => $churchData,
        ]);
    }

    /**
     * GET /church/{slug}/{page}
     * Show church custom page
     */
    public function showPage(string $slug, string $pageSlug): Response
    {
        $church = Church::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        $page = $church->publishedPages()
            ->where('slug', $pageSlug)
            ->firstOrFail();

        $churchData = $this->loader->loadForPublicDisplay($church);

        return Inertia::render('Public/ChurchPage', [
            'church' => $churchData,
            'page' => $page,
            'seo' => [
                'title' => $page->title . ' - ' . $church->name,
                'description' => $church->meta_description ?: $church->short_description,
                'canonical' => url("/church/{$church->slug}/{$page->slug}"),
            ]
        ]);
    }
}