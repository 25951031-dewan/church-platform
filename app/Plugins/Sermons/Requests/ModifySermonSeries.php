<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySermonSeries extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
        ];
    }
}
