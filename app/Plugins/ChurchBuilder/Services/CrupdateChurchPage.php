<?php

namespace App\Plugins\ChurchBuilder\Services;

use App\Plugins\ChurchBuilder\Models\ChurchPage;

class CrupdateChurchPage
{
    public function execute(array $data, ?ChurchPage $page = null): ChurchPage
    {
        $fields = ['title', 'slug', 'body', 'sort_order', 'is_published'];

        if ($page) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $page->update($updateData);
        } else {
            $createData = [
                'church_id' => $data['church_id'],
                'created_by' => $data['created_by'] ?? null,
            ];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $page = ChurchPage::create($createData);
        }

        return $page;
    }
}
