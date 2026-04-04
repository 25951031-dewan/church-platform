<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\DB;

/**
 * Database-based search driver using SQL LIKE queries.
 * Works out of the box with any database, no extra setup needed.
 */
class DatabaseSearchDriver implements SearchDriverInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('search.models', []);
    }

    public function search(string $query, array $options = []): SearchResults
    {
        $startTime = microtime(true);
        $allHits = [];
        $types = $options['types'] ?? array_keys($this->config);
        $perPage = min($options['per_page'] ?? 20, 100);
        $page = $options['page'] ?? 1;

        foreach ($types as $type) {
            if (!isset($this->config[$type])) {
                continue;
            }

            $results = $this->searchIndex($type, $query, [
                'per_page' => $perPage,
                'page' => 1, // Get first page of each type for global search
                'filters' => $options['filters'][$type] ?? [],
            ]);

            foreach ($results->hits as $hit) {
                $hit['_type'] = $type;
                $allHits[] = $hit;
            }
        }

        // Sort by relevance score (if available) or created_at
        usort($allHits, fn($a, $b) => ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0));

        // Paginate combined results
        $offset = ($page - 1) * $perPage;
        $paginatedHits = array_slice($allHits, $offset, $perPage);

        return new SearchResults(
            hits: $paginatedHits,
            totalHits: count($allHits),
            page: $page,
            perPage: $perPage,
            processingTimeMs: (microtime(true) - $startTime) * 1000,
            query: $query
        );
    }

    public function searchIndex(string $index, string $query, array $options = []): SearchResults
    {
        $startTime = microtime(true);
        
        if (!isset($this->config[$index])) {
            return new SearchResults([], 0, 1, 20, 0, $query);
        }

        $config = $this->config[$index];
        $modelClass = $config['model'];
        $fields = $config['fields'];
        $perPage = min($options['per_page'] ?? 20, 100);
        $page = $options['page'] ?? 1;

        if (!class_exists($modelClass)) {
            return new SearchResults([], 0, $page, $perPage, 0, $query);
        }

        $queryBuilder = $modelClass::query();

        // Build search conditions
        $searchTerms = $this->tokenize($query);
        
        $queryBuilder->where(function ($q) use ($fields, $searchTerms) {
            foreach ($fields as $field) {
                foreach ($searchTerms as $term) {
                    // Handle nested fields (e.g., 'speaker.name')
                    if (str_contains($field, '.')) {
                        [$relation, $column] = explode('.', $field, 2);
                        $q->orWhereHas($relation, function ($subQ) use ($column, $term) {
                            $subQ->where($column, 'like', "%{$term}%");
                        });
                    } else {
                        $q->orWhere($field, 'like', "%{$term}%");
                    }
                }
            }
        });

        // Apply filters
        if (!empty($options['filters'])) {
            foreach ($options['filters'] as $field => $value) {
                if ($value !== null && $value !== '') {
                    $queryBuilder->where($field, $value);
                }
            }
        }

        // Get total count
        $total = $queryBuilder->count();

        // Paginate and get results
        $results = $queryBuilder
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Transform to search hits
        $hits = $results->map(function ($model) use ($query, $fields) {
            $data = $model->toArray();
            $data['_id'] = $model->id;
            $data['_score'] = $this->calculateScore($model, $query, $fields);
            return $data;
        })->toArray();

        return new SearchResults(
            hits: $hits,
            totalHits: $total,
            page: $page,
            perPage: $perPage,
            processingTimeMs: (microtime(true) - $startTime) * 1000,
            query: $query
        );
    }

    public function index(string $index, int $id, array $data): void
    {
        // Database driver doesn't need explicit indexing - data is already in DB
    }

    public function delete(string $index, int $id): void
    {
        // Database driver doesn't need explicit deletion
    }

    public function flush(string $index): void
    {
        // Database driver doesn't need explicit flushing
    }

    public function isHealthy(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function tokenize(string $query): array
    {
        // Split on whitespace and filter short terms
        $terms = preg_split('/\s+/', trim($query));
        $minLength = config('search.settings.min_query_length', 2);
        
        return array_filter($terms, fn($term) => strlen($term) >= $minLength);
    }

    protected function calculateScore($model, string $query, array $fields): float
    {
        $score = 0;
        $queryLower = strtolower($query);
        
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                continue; // Skip relation fields for scoring
            }
            
            $value = strtolower((string) ($model->{$field} ?? ''));
            
            // Exact match in field
            if ($value === $queryLower) {
                $score += 100;
            }
            // Starts with query
            elseif (str_starts_with($value, $queryLower)) {
                $score += 50;
            }
            // Contains query
            elseif (str_contains($value, $queryLower)) {
                $score += 25;
            }
        }
        
        return $score;
    }
}
