<?php

namespace App\Plugins\Marketplace\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Marketplace\Models\Favorite;
use App\Plugins\Marketplace\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function toggle(Listing $listing, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $favorite = Favorite::where('listing_id', $listing->id)
            ->where('user_id', $userId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
        } else {
            Favorite::create([
                'listing_id' => $listing->id,
                'user_id' => $userId,
            ]);
            $isFavorited = true;
        }

        return response()->json([
            'is_favorited' => $isFavorited,
            'favorites_count' => $listing->favorites()->count(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $favorites = Listing::whereHas('favorites', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })
            ->with(['seller', 'category'])
            ->active()
            ->paginate(20);

        return response()->json([
            'pagination' => $favorites,
        ]);
    }
}
