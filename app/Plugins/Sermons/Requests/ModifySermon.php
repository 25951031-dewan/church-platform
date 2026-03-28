<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySermon extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'content' => 'nullable|string',
            'speaker' => 'required|string|max:255',
            'speaker_id' => 'nullable|integer|exists:speakers,id',
            'scripture_reference' => 'nullable|string|max:500',
            'series' => 'nullable|string|max:255',
            'series_id' => 'nullable|integer|exists:sermon_series,id',
            'category' => 'nullable|string|max:255',
            'sermon_date' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'video_url' => 'nullable|string|max:500',
            'audio_url' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string|max:500',
            'pdf_notes' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'tags' => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
