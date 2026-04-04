<?php

namespace App\Plugins\Timeline\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDailyVersesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && auth('sanctum')->user()->church_id;
    }

    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'replace_existing' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSV file is required.',
            'csv_file.mimes' => 'File must be a CSV file.',
            'csv_file.max' => 'File size cannot exceed 2MB.',
        ];
    }
}