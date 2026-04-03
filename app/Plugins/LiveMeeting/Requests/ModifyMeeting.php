<?php

namespace App\Plugins\LiveMeeting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifyMeeting extends FormRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('PUT') || $this->isMethod('PATCH') ? '' : 'required|';

        return [
            'title' => $required . 'string|max:255',
            'description' => 'nullable|string|max:1000',
            'meeting_url' => $required . 'url|max:500',
            'event_id' => 'nullable|exists:events,id',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'max_participants' => 'nullable|integer|min:1',
            'requires_registration' => 'nullable|boolean',
            'platform' => 'nullable|in:zoom,google_meet,youtube,other',
            'church_id' => 'nullable|exists:churches,id',
            'starts_at' => $required . 'date',
            'ends_at' => $required . 'date|after:starts_at',
            'timezone' => 'nullable|string|max:50',
            'is_recurring' => 'nullable|boolean',
            'recurrence_rule' => 'nullable|in:weekly,biweekly,monthly',
            'cover_image' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
