<?php

namespace App\Plugins\Timeline\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDailyVerseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && auth('sanctum')->user()->church_id;
    }

    public function rules(): array
    {
        $verse = $this->route('verse');
        
        return [
            'verse_date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('timeline_daily_verses', 'verse_date')
                    ->where('church_id', auth('sanctum')->user()->church_id)
                    ->ignore($verse->id)
            ],
            'verse_text' => 'sometimes|required|string|max:2000',
            'verse_reference' => 'sometimes|required|string|max:100',
            'translation' => 'nullable|string|max:10',
            'theme' => 'nullable|string|max:100',
            'author_note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'verse_date.required' => 'Verse date is required.',
            'verse_date.unique' => 'A verse already exists for this date.',
            'verse_text.required' => 'Verse text is required.',
            'verse_text.max' => 'Verse text cannot exceed 2000 characters.',
            'verse_reference.required' => 'Verse reference is required.',
        ];
    }
}