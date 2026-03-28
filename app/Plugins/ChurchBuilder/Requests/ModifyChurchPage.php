<?php

namespace App\Plugins\ChurchBuilder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyChurchPage extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
