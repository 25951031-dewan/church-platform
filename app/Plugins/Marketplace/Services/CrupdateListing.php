<?php

namespace App\Plugins\Marketplace\Services;

use App\Plugins\Marketplace\Models\Listing;

class CrupdateListing
{
    public function execute(array $data, ?Listing $listing = null): Listing
    {
        $fields = [
            'title', 'description', 'price', 'condition', 'category_id',
            'is_negotiable', 'is_featured', 'is_active', 'images',
            'specifications', 'location', 'contact_method', 'expires_at',
        ];

        if ($listing) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $listing->update($updateData);
        } else {
            $createData = [
                'status' => 'available',
                'view_count' => 0,
                'is_active' => true,
                'is_negotiable' => $data['is_negotiable'] ?? true,
            ];
            
            if (isset($data['user_id'])) {
                $createData['user_id'] = $data['user_id'];
            }
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            
            $listing = Listing::create($createData);
        }

        return $listing;
    }
}
