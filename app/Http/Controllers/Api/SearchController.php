<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Global search across all content types.
     * GET /api/search?q=query&types[]=sermons&types[]=posts
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:200',
            'types' => 'array',
            'types.*' => 'string|in:' . implode(',', $this->searchService->getAvailableTypes()),
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $results = $this->searchService->search(
            $request->input('q'),
            [
                'types' => $request->input('types'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 20),
            ]
        );

        return response()->json([
            'data' => $results->toArray(),
        ]);
    }

    /**
     * Search within a specific content type.
     * GET /api/search/{type}?q=query&status=published
     */
    public function searchType(string $type, Request $request): JsonResponse
    {
        $availableTypes = $this->searchService->getAvailableTypes();

        if (!in_array($type, $availableTypes)) {
            return response()->json([
                'message' => 'Invalid search type',
                'available_types' => $availableTypes,
            ], 422);
        }

        $request->validate([
            'q' => 'required|string|min:2|max:200',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        // Extract filters from query params (exclude reserved params)
        $reservedParams = ['q', 'page', 'per_page'];
        $filters = array_diff_key($request->all(), array_flip($reservedParams));

        $results = $this->searchService->searchType(
            $type,
            $request->input('q'),
            [
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 20),
                'filters' => $filters,
            ]
        );

        return response()->json([
            'data' => $results->toArray(),
        ]);
    }

    /**
     * Get search suggestions/autocomplete.
     * GET /api/search/suggest?q=ser
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'limit' => 'integer|min:1|max:10',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 5);

        // Quick search with limit
        $results = $this->searchService->search($query, [
            'per_page' => $limit,
            'page' => 1,
        ]);

        // Extract just titles/names for suggestions
        $suggestions = collect($results->hits)->map(function ($hit) {
            return [
                'text' => $hit['title'] ?? $hit['name'] ?? $hit['subject'] ?? 'Unknown',
                'type' => $hit['_type'] ?? 'unknown',
                'id' => $hit['_id'] ?? $hit['id'] ?? null,
            ];
        })->unique('text')->values()->take($limit);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Get search health status (admin).
     * GET /api/search/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'healthy' => $this->searchService->isHealthy(),
            'driver' => $this->searchService->getDriverName(),
            'available_types' => $this->searchService->getAvailableTypes(),
        ]);
    }
}
