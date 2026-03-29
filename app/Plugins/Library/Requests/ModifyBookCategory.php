<?php

namespace App\Plugins\Library\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifyBookCategory extends FormRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('bookCategory')?->id;

        $parentIdRules = ['nullable', 'integer', 'exists:book_categories,id'];
        if ($categoryId) {
            $parentIdRules[] = Rule::notIn([$categoryId]);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'parent_id' => $parentIdRules,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
