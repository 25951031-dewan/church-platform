<?php

namespace App\Plugins\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyEvent extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'location_url' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|max:100',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'max_attendees' => 'nullable|integer|min:1',
            'registration_required' => 'nullable|boolean',
            'registration_link' => 'nullable|string|max:500',
            'meeting_url' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
