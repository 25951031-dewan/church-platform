<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PaginateBooks
{
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = Book::query()
            ->active()
            ->with(['category:id,name,slug', 'uploader:id,name,avatar'])
            ->withCount('reactions');

        if ($request->has('church_id')) {
            $query->where('church_id', $request->input('church_id'));
        }

        if ($request->has('category_id')) {
            $query->byCategory((int) $request->input('category_id'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $orderBy = $request->input('order_by', 'created_at');
        $orderDir = $request->input('order_dir', 'desc');
        $allowedOrders = ['created_at', 'title', 'author', 'view_count', 'download_count'];

        if (in_array($orderBy, $allowedOrders)) {
            $query->orderBy($orderBy, $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        return $query->paginate(min((int) $request->input('per_page', 12), 50));
    }
}
