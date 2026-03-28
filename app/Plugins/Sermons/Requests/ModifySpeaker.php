<?php

namespace App\Plugins\Sermons\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifySpeaker extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'image' => 'nullable|string|max:500',
        ];
    }
}
