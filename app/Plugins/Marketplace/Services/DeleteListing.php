<?php

namespace App\Plugins\Marketplace\Services;

use App\Plugins\Marketplace\Models\Listing;

class DeleteListing
{
    public function execute(Listing $listing): void
    {
        // Delete related offers
        $listing->offers()->delete();
        
        // Delete favorites
        $listing->favorites()->delete();
        
        // Soft delete the listing
        $listing->delete();
    }
}
