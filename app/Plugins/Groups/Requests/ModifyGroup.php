<?php

namespace App\Plugins\Groups\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyGroup extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'rules' => 'nullable|string|max:5000',
            'type' => 'required|string|in:public,private,church_only',
            'cover_image' => 'nullable|string|max:500',
        ];

        // On update, all fields are optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
