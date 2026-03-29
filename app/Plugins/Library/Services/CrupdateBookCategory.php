<?php

namespace App\Plugins\Library\Services;

use App\Plugins\Library\Models\BookCategory;

class CrupdateBookCategory
{
    public function execute(array $data, ?BookCategory $category = null): BookCategory
    {
        $fields = ['name', 'slug', 'description', 'image', 'parent_id', 'sort_order', 'is_active'];

        if ($category) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $category->update($updateData);
        } else {
            $createData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $category = BookCategory::create($createData);
        }

        return $category;
    }
}
