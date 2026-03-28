<?php

namespace App\Plugins\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyEventRsvp extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:attending,interested,not_going',
        ];
    }
}
