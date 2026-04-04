<?php

namespace App\Services\Search;

interface SearchDriverInterface
{
    /**
     * Search across all indexed content.
     */
    public function search(string $query, array $options = []): SearchResults;

    /**
     * Search within a specific index/type.
     */
    public function searchIndex(string $index, string $query, array $options = []): SearchResults;

    /**
     * Index a model for searching.
     */
    public function index(string $index, int $id, array $data): void;

    /**
     * Remove a model from the search index.
     */
    public function delete(string $index, int $id): void;

    /**
     * Flush all documents from an index.
     */
    public function flush(string $index): void;

    /**
     * Check if the search service is available.
     */
    public function isHealthy(): bool;
}
