<?php

namespace App\Plugins\Library\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyBook extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:10000',
            'content' => 'nullable|string',
            'cover' => 'nullable|string|max:500',
            'pdf_path' => 'nullable|string|max:500',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'pages_count' => 'nullable|integer|min:1',
            'published_year' => 'nullable|integer|min:1000|max:2100',
            'category_id' => 'nullable|integer|exists:book_categories,id',
            'church_id' => 'nullable|integer|exists:churches,id',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(fn ($rule) => str_replace('required|', '', $rule), $rules);
        }

        return $rules;
    }
}
