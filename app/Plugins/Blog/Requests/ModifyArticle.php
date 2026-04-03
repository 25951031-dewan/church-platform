<?php

namespace App\Plugins\Blog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyArticle extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'title' => $required . 'string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:article_categories,id',
            'church_id' => 'nullable|exists:churches,id',
            'status' => 'nullable|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }
}
