<?php

namespace Common\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'nullable|string|max:5000',
            'type' => 'in:text,image,file,audio',
            'file_entry_id' => 'nullable|integer',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // At least body or file must be present
            if (empty($this->body) && empty($this->file_entry_id)) {
                $validator->errors()->add('body', 'Message must have text or a file attachment.');
            }
        });
    }
}
