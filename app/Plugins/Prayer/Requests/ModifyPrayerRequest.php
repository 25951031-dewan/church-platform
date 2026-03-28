<?php

namespace App\Plugins\Prayer\Requests;

use App\Plugins\Prayer\Models\PrayerRequest;
use Illuminate\Foundation\Http\FormRequest;

class ModifyPrayerRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'request' => 'required|string|max:5000',
            'description' => 'nullable|string|max:5000',
            'is_public' => 'nullable|boolean',
            'is_anonymous' => 'nullable|boolean',
            'is_urgent' => 'nullable|boolean',
            'category' => 'nullable|string|in:' . implode(',', PrayerRequest::CATEGORIES),
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
