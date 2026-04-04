<?php

namespace App\Plugins\Marketplace\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Marketplace\Models\Listing;
use App\Plugins\Marketplace\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Listing $listing, Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:500',
        ]);

        // Can't make offer on own listing
        if ($listing->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'Cannot make offer on your own listing',
            ], 422);
        }

        // Check if user already has pending offer
        $existingOffer = $listing->offers()
            ->where('user_id', $request->user()->id)
            ->pending()
            ->first();

        if ($existingOffer) {
            return response()->json([
                'message' => 'You already have a pending offer',
            ], 422);
        }

        $offer = Offer::create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'amount' => $request->input('amount'),
            'message' => $request->input('message'),
            'status' => 'pending',
        ]);

        return response()->json([
            'offer' => $offer->load('buyer'),
        ], 201);
    }

    public function accept(Offer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);

        $offer->accept();

        // Optionally mark listing as sold
        // $offer->listing->markAsSold();

        return response()->json([
            'offer' => $offer->fresh(),
        ]);
    }

    public function reject(Offer $offer): JsonResponse
    {
        $this->authorize('respond', $offer);

        $offer->reject();

        return response()->json([
            'offer' => $offer->fresh(),
        ]);
    }

    public function counter(Offer $offer, Request $request): JsonResponse
    {
        $this->authorize('respond', $offer);

        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $offer->counter($request->input('amount'));

        return response()->json([
            'offer' => $offer->fresh(),
        ]);
    }

    public function myOffers(Request $request): JsonResponse
    {
        $offers = Offer::with(['listing', 'listing.seller'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'pagination' => $offers,
        ]);
    }

    public function receivedOffers(Request $request): JsonResponse
    {
        $offers = Offer::with(['listing', 'buyer'])
            ->whereHas('listing', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'pagination' => $offers,
        ]);
    }
}
