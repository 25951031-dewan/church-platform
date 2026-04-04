<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Http;

/**
 * Meilisearch driver for fast, typo-tolerant search.
 * Requires a Meilisearch server running.
 */
class MeilisearchDriver implements SearchDriverInterface
{
    protected string $host;
    protected string $key;
    protected array $config;

    public function __construct()
    {
        $this->host = config('search.meilisearch.host', 'http://127.0.0.1:7700');
        $this->key = config('search.meilisearch.key', '');
        $this->config = config('search.models', []);
    }

    public function search(string $query, array $options = []): SearchResults
    {
        $startTime = microtime(true);
        $allHits = [];
        $types = $options['types'] ?? array_keys($this->config);
        $perPage = min($options['per_page'] ?? 20, 100);
        $page = $options['page'] ?? 1;

        // Multi-index search
        $queries = [];
        foreach ($types as $type) {
            if (isset($this->config[$type])) {
                $queries[] = [
                    'indexUid' => $type,
                    'q' => $query,
                    'limit' => $perPage,
                ];
            }
        }

        $response = $this->request('POST', '/multi-search', [
            'queries' => $queries,
        ]);

        if (isset($response['results'])) {
            foreach ($response['results'] as $result) {
                foreach ($result['hits'] ?? [] as $hit) {
                    $hit['_type'] = $result['indexUid'];
                    $allHits[] = $hit;
                }
            }
        }

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
        $perPage = min($options['per_page'] ?? 20, 100);
        $page = $options['page'] ?? 1;
        $offset = ($page - 1) * $perPage;

        $params = [
            'q' => $query,
            'limit' => $perPage,
            'offset' => $offset,
            'attributesToHighlight' => ['*'],
        ];

        // Add filters
        if (!empty($options['filters'])) {
            $filterStrings = [];
            foreach ($options['filters'] as $field => $value) {
                if ($value !== null && $value !== '') {
                    $filterStrings[] = "{$field} = " . (is_string($value) ? "\"{$value}\"" : $value);
                }
            }
            if (!empty($filterStrings)) {
                $params['filter'] = implode(' AND ', $filterStrings);
            }
        }

        $response = $this->request('POST', "/indexes/{$index}/search", $params);

        $hits = array_map(function ($hit) {
            $hit['_id'] = $hit['id'] ?? null;
            $hit['_score'] = $hit['_rankingScore'] ?? 0;
            return $hit;
        }, $response['hits'] ?? []);

        return new SearchResults(
            hits: $hits,
            totalHits: $response['estimatedTotalHits'] ?? count($hits),
            page: $page,
            perPage: $perPage,
            processingTimeMs: $response['processingTimeMs'] ?? 0,
            query: $query,
            facets: $response['facetDistribution'] ?? []
        );
    }

    public function index(string $index, int $id, array $data): void
    {
        $data['id'] = $id;
        $this->request('POST', "/indexes/{$index}/documents", [$data]);
    }

    public function delete(string $index, int $id): void
    {
        $this->request('DELETE', "/indexes/{$index}/documents/{$id}");
    }

    public function flush(string $index): void
    {
        $this->request('DELETE', "/indexes/{$index}/documents");
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders($this->getHeaders())
                ->get("{$this->host}/health");
            
            return $response->successful() && ($response->json('status') === 'available');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Configure index settings (filterable attributes, etc).
     */
    public function configureIndex(string $index): void
    {
        if (!isset($this->config[$index])) {
            return;
        }

        $config = $this->config[$index];
        
        // Set searchable attributes
        $this->request('PUT', "/indexes/{$index}/settings/searchable-attributes", 
            $config['fields'] ?? []
        );

        // Set filterable attributes
        if (!empty($config['filters'])) {
            $this->request('PUT', "/indexes/{$index}/settings/filterable-attributes",
                $config['filters']
            );
        }

        // Enable typo tolerance
        $this->request('PATCH', "/indexes/{$index}/settings/typo-tolerance", [
            'enabled' => config('search.settings.typo_tolerance', true),
        ]);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $request = Http::timeout(30)->withHeaders($this->getHeaders());

        $response = match (strtoupper($method)) {
            'GET' => $request->get("{$this->host}{$path}"),
            'POST' => $request->post("{$this->host}{$path}", $data),
            'PUT' => $request->put("{$this->host}{$path}", $data),
            'PATCH' => $request->patch("{$this->host}{$path}", $data),
            'DELETE' => $request->delete("{$this->host}{$path}"),
            default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
        };

        if ($response->failed()) {
            throw new \RuntimeException("Meilisearch error: " . $response->body());
        }

        return $response->json() ?? [];
    }

    protected function getHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        
        if ($this->key) {
            $headers['Authorization'] = "Bearer {$this->key}";
        }

        return $headers;
    }
}
