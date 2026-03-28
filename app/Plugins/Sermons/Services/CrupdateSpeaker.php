<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Speaker;

class CrupdateSpeaker
{
    public function execute(array $data, ?Speaker $speaker = null): Speaker
    {
        if ($speaker) {
            $speaker->update([
                'name' => $data['name'] ?? $speaker->name,
                'bio' => $data['bio'] ?? $speaker->bio,
                'image' => $data['image'] ?? $speaker->image,
            ]);
        } else {
            $speaker = Speaker::create([
                'name' => $data['name'],
                'bio' => $data['bio'] ?? null,
                'image' => $data['image'] ?? null,
                'church_id' => $data['church_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);
        }

        return $speaker;
    }
}
