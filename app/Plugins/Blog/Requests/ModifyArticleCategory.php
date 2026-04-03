<?php

namespace App\Plugins\Blog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyArticleCategory extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'name' => $required . 'string|max:255',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
