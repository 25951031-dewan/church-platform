<?php

namespace App\Plugins\Timeline\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFeedLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && auth('sanctum')->user()->church_id;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'layout_data' => 'nullable|json',
            'left_sidebar_config' => 'nullable|json',
            'right_sidebar_config' => 'nullable|json',
            'mobile_config' => 'nullable|json',
            'responsive_settings' => 'nullable|json',
            'widget_instances' => 'array',
            'widget_instances.*.widget_id' => 'required|exists:timeline_feed_widgets,id',
            'widget_instances.*.pane' => ['required', Rule::in(['left', 'center', 'right'])],
            'widget_instances.*.position' => 'integer|min:0',
            'widget_instances.*.config' => 'nullable|json',
            'widget_instances.*.styling' => 'nullable|json',
            'widget_instances.*.is_visible' => 'boolean',
            'widget_instances.*.is_collapsible' => 'boolean',
            'widget_instances.*.is_collapsed' => 'boolean',
            'widget_instances.*.responsive_behavior' => 'nullable|json',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Layout name is required.',
            'name.max' => 'Layout name cannot exceed 100 characters.',
            'widget_instances.*.widget_id.exists' => 'Selected widget does not exist.',
            'widget_instances.*.pane.in' => 'Pane must be left, center, or right.',
        ];
    }
}