<?php

namespace App\Plugins\Marketplace\Policies;

use App\Models\User;
use App\Plugins\Marketplace\Models\Listing;

class ListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Listing $listing): bool
    {
        // Anyone can view active listings
        if ($listing->is_active && $listing->status === 'available') {
            return true;
        }
        
        // Only owner can view inactive/sold listings
        return $user && $listing->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Listing $listing): bool
    {
        return $listing->user_id === $user->id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $listing->user_id === $user->id;
    }
}
