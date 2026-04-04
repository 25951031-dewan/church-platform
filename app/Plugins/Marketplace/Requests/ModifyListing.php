<?php

namespace App\Plugins\Marketplace\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyListing extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:marketplace_categories,id',
            'condition' => 'required|in:new,like_new,good,fair,poor',
            'is_negotiable' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'array|max:10',
            'images.*' => 'string|max:500',
            'specifications' => 'array',
            'location' => 'nullable|string|max:255',
            'contact_method' => 'nullable|in:chat,email,phone',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'Please select a valid category.',
            'condition.in' => 'Invalid condition. Choose: new, like_new, good, fair, or poor.',
            'images.max' => 'Maximum 10 images allowed.',
        ];
    }
}
