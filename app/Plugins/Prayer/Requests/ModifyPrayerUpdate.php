<?php

namespace App\Plugins\Prayer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyPrayerUpdate extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:2000',
            'status_change' => 'nullable|string|in:still_praying,partially_answered,answered,no_change',
        ];
    }
}
