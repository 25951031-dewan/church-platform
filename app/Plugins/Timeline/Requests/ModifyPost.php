<?php

namespace App\Plugins\Timeline\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyPost extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required_without:media|string|max:10000',
            'type' => 'sometimes|in:text,photo,video,announcement',
            'visibility' => 'sometimes|in:public,members,private',
            'is_pinned' => 'sometimes|boolean',
            'scheduled_at' => 'sometimes|nullable|date|after:now',
            'church_id' => 'sometimes|nullable|integer',
            'media' => 'sometimes|array|max:10',
            'media.*' => 'file|max:20480',
        ];
    }
}
