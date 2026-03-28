<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\SermonSeries;

class CrupdateSermonSeries
{
    public function execute(array $data, ?SermonSeries $series = null): SermonSeries
    {
        if ($series) {
            $series->update([
                'name' => $data['name'] ?? $series->name,
                'description' => $data['description'] ?? $series->description,
                'image' => $data['image'] ?? $series->image,
            ]);
        } else {
            $series = SermonSeries::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image' => $data['image'] ?? null,
                'church_id' => $data['church_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);
        }

        return $series;
    }
}
