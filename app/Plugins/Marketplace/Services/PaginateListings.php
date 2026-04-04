<?php

namespace App\Plugins\Marketplace\Services;

use App\Plugins\Marketplace\Models\Listing;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PaginateListings
{
    public function execute(array $params): LengthAwarePaginator
    {
        $query = Listing::query()->with(['seller', 'category']);

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $this->applySorting($query, $params);

        // Paginate
        $perPage = min($params['per_page'] ?? 20, 100);
        
        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $params): void
    {
        // Status filter (default to active)
        if (($params['status'] ?? 'active') === 'active') {
            $query->active();
        } elseif (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Category filter
        if (!empty($params['category_id'])) {
            $query->byCategory($params['category_id']);
        }

        // Condition filter
        if (!empty($params['condition'])) {
            $query->byCondition($params['condition']);
        }

        // Price range filter
        $query->priceRange(
            $params['min_price'] ?? null,
            $params['max_price'] ?? null
        );

        // Seller filter
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // Search filter
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Featured filter
        if (!empty($params['featured'])) {
            $query->featured();
        }

        // Church filter (multi-tenant)
        if (!empty($params['church_id'])) {
            $query->where('church_id', $params['church_id']);
        }
    }

    protected function applySorting(Builder $query, array $params): void
    {
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = $params['sort_dir'] ?? 'desc';

        $allowedSorts = ['created_at', 'price', 'title', 'view_count'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }
    }
}
