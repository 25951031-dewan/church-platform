<?php

namespace App\Plugins\Marketplace\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Marketplace\Models\Listing;
use App\Plugins\Marketplace\Requests\ModifyListing;
use App\Plugins\Marketplace\Services\CrupdateListing;
use App\Plugins\Marketplace\Services\DeleteListing;
use App\Plugins\Marketplace\Services\LoadListing;
use App\Plugins\Marketplace\Services\PaginateListings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(Request $request, PaginateListings $paginator): JsonResponse
    {
        $listings = $paginator->execute($request->all());

        return response()->json([
            'pagination' => $listings,
        ]);
    }

    public function show(Listing $listing, Request $request, LoadListing $loader): JsonResponse
    {
        $listing = $loader->execute($listing, [
            'track_view' => true,
            'with_favorites_count' => true,
        ]);

        return response()->json([
            'listing' => $listing,
        ]);
    }

    public function store(ModifyListing $request, CrupdateListing $service): JsonResponse
    {
        $this->authorize('create', Listing::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $listing = $service->execute($data);

        return response()->json([
            'listing' => $listing,
        ], 201);
    }

    public function update(
        Listing $listing,
        ModifyListing $request,
        CrupdateListing $service
    ): JsonResponse {
        $this->authorize('update', $listing);

        $listing = $service->execute($request->validated(), $listing);

        return response()->json([
            'listing' => $listing,
        ]);
    }

    public function destroy(Listing $listing, DeleteListing $service): JsonResponse
    {
        $this->authorize('delete', $listing);

        $service->execute($listing);

        return response()->json(null, 204);
    }

    public function markSold(Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $listing->markAsSold();

        return response()->json([
            'listing' => $listing->fresh(),
        ]);
    }

    public function myListings(Request $request, PaginateListings $paginator): JsonResponse
    {
        $params = array_merge($request->all(), [
            'user_id' => $request->user()->id,
            'status' => $request->input('status', 'all'),
        ]);

        $listings = $paginator->execute($params);

        return response()->json([
            'pagination' => $listings,
        ]);
    }
}
