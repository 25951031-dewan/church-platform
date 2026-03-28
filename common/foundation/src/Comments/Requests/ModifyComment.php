<?php

namespace Common\Comments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyComment extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ];
    }
}
