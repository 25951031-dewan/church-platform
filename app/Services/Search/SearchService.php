<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Log;

/**
 * Main Search Service - façade for search operations.
 */
class SearchService
{
    protected SearchDriverInterface $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    /**
     * Global search across all content types.
     */
    public function search(string $query, array $options = []): SearchResults
    {
        $query = trim($query);
        $minLength = config('search.settings.min_query_length', 2);

        if (strlen($query) < $minLength) {
            return new SearchResults([], 0, 1, 20, 0, $query);
        }

        return $this->driver->search($query, $options);
    }

    /**
     * Search within a specific content type.
     */
    public function searchType(string $type, string $query, array $options = []): SearchResults
    {
        $query = trim($query);
        $minLength = config('search.settings.min_query_length', 2);

        if (strlen($query) < $minLength) {
            return new SearchResults([], 0, 1, 20, 0, $query);
        }

        return $this->driver->searchIndex($type, $query, $options);
    }

    /**
     * Index a model for searching.
     */
    public function indexModel(string $type, $model): void
    {
        $config = config("search.models.{$type}");
        
        if (!$config) {
            return;
        }

        $data = $this->extractSearchableData($model, $config['fields']);
        $this->driver->index($type, $model->id, $data);
    }

    /**
     * Remove a model from the search index.
     */
    public function removeModel(string $type, int $id): void
    {
        $this->driver->delete($type, $id);
    }

    /**
     * Reindex all documents of a type.
     */
    public function reindexType(string $type): int
    {
        $config = config("search.models.{$type}");
        
        if (!$config || !class_exists($config['model'])) {
            return 0;
        }

        // Flush existing index
        $this->driver->flush($type);

        // Reindex all models
        $count = 0;
        $config['model']::chunk(100, function ($models) use ($type, $config, &$count) {
            foreach ($models as $model) {
                $data = $this->extractSearchableData($model, $config['fields']);
                $this->driver->index($type, $model->id, $data);
                $count++;
            }
        });

        Log::info("Reindexed {$count} documents for type: {$type}");

        return $count;
    }

    /**
     * Check if search service is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->driver->isHealthy();
    }

    /**
     * Get the current driver name.
     */
    public function getDriverName(): string
    {
        return config('search.driver', 'database');
    }

    /**
     * Get available search types.
     */
    public function getAvailableTypes(): array
    {
        return array_keys(config('search.models', []));
    }

    protected function resolveDriver(): SearchDriverInterface
    {
        $driver = config('search.driver', 'database');

        return match ($driver) {
            'meilisearch' => new MeilisearchDriver(),
            'database' => new DatabaseSearchDriver(),
            default => new DatabaseSearchDriver(),
        };
    }

    protected function extractSearchableData($model, array $fields): array
    {
        $data = ['id' => $model->id];

        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                // Handle nested fields
                [$relation, $column] = explode('.', $field, 2);
                $related = $model->{$relation};
                $data[$field] = $related ? $related->{$column} : null;
            } else {
                $data[$field] = $model->{$field};
            }
        }

        // Add timestamps
        $data['created_at'] = $model->created_at?->toISOString();
        $data['updated_at'] = $model->updated_at?->toISOString();

        return $data;
    }
}
