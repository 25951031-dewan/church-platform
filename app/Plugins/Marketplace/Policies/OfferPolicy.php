<?php

namespace App\Plugins\Marketplace\Policies;

use App\Models\User;
use App\Plugins\Marketplace\Models\Offer;

class OfferPolicy
{
    public function respond(User $user, Offer $offer): bool
    {
        // Only listing owner can respond to offers
        return $offer->listing->user_id === $user->id;
    }

    public function view(User $user, Offer $offer): bool
    {
        // Buyer or seller can view the offer
        return $offer->user_id === $user->id 
            || $offer->listing->user_id === $user->id;
    }

    public function delete(User $user, Offer $offer): bool
    {
        // Only buyer can withdraw their offer (if pending)
        return $offer->user_id === $user->id 
            && $offer->status === 'pending';
    }
}
