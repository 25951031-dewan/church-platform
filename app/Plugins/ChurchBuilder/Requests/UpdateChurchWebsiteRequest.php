<?php

namespace App\Plugins\ChurchBuilder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChurchWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user() && $this->route('church')->isChurchAdmin(auth()->id());
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'website' => 'sometimes|nullable|url|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:100',
            'zip_code' => 'sometimes|nullable|string|max:20',
            'country' => 'sometimes|nullable|string|max:100',
            'denomination' => 'sometimes|nullable|string|max:100',
            'short_description' => 'sometimes|nullable|string|max:500',
            'service_hours' => 'sometimes|nullable|array',
            'service_hours.*.day' => 'required_with:service_hours|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'service_hours.*.time' => 'required_with:service_hours|string|max:50',
            'service_hours.*.service_type' => 'required_with:service_hours|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Church name is required',
            'email.email' => 'Please provide a valid email address',
            'website.url' => 'Please provide a valid website URL',
            'service_hours.*.day.in' => 'Invalid day of week',
        ];
    }
}