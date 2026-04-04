<?php

namespace App\Services\Search;

class SearchResults implements \Countable, \IteratorAggregate
{
    public function __construct(
        public readonly array $hits,
        public readonly int $totalHits,
        public readonly int $page,
        public readonly int $perPage,
        public readonly float $processingTimeMs,
        public readonly string $query,
        public readonly array $facets = []
    ) {}

    public function count(): int
    {
        return count($this->hits);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->hits);
    }

    public function isEmpty(): bool
    {
        return empty($this->hits);
    }

    public function totalPages(): int
    {
        return (int) ceil($this->totalHits / $this->perPage);
    }

    public function hasMorePages(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function toArray(): array
    {
        return [
            'hits' => $this->hits,
            'total' => $this->totalHits,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages(),
            'processing_time_ms' => $this->processingTimeMs,
            'query' => $this->query,
            'facets' => $this->facets,
        ];
    }
}
