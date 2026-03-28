<?php

namespace App\Plugins\Groups\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyGroupMember extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => 'required|string|in:admin,moderator,member',
        ];
    }
}
