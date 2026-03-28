<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class CrupdateSermon
{
    public function execute(array $data, ?Sermon $sermon = null): Sermon
    {
        $fields = [
            'title', 'description', 'content', 'speaker', 'speaker_id',
            'image', 'thumbnail', 'video_url', 'audio_url', 'pdf_notes',
            'scripture_reference', 'series', 'series_id', 'category',
            'sermon_date', 'duration_minutes', 'is_featured', 'is_active',
            'is_published', 'tags', 'meta_title', 'meta_description',
        ];

        if ($sermon) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $sermon->update($updateData);
        } else {
            $createData = ['author_id' => $data['author_id']];
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $sermon = Sermon::create($createData);
        }

        return $sermon;
    }
}
