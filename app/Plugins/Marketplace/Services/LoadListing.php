<?php

namespace App\Plugins\Marketplace\Services;

use App\Plugins\Marketplace\Models\Listing;

class LoadListing
{
    public function execute(Listing $listing, array $params = []): Listing
    {
        $with = ['seller', 'category'];

        // Optionally load offers
        if (!empty($params['with_offers'])) {
            $with[] = 'offers.buyer';
        }

        // Optionally load favorites count
        if (!empty($params['with_favorites_count'])) {
            $listing->loadCount('favorites');
        }

        $listing->load($with);

        // Increment view count if viewing details
        if (!empty($params['track_view'])) {
            $listing->incrementViews();
        }

        return $listing;
    }
}
