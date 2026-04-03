<?php

namespace Common\Notifications\Controllers\Admin;

use Common\Notifications\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(NotificationTemplate::query()->paginate($request->integer('per_page', 50)));
    }

    public function show(NotificationTemplate $notificationTemplate): JsonResponse
    {
        return response()->json(['template' => $notificationTemplate]);
    }

    public function store(Request $request): JsonResponse
    {
        $template = NotificationTemplate::create($this->validated($request));
        return response()->json(['template' => $template], 201);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $notificationTemplate->update($this->validated($request, true));
        return response()->json(['template' => $notificationTemplate->fresh()]);
    }

    public function destroy(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $notificationTemplate->delete();
        return response()->noContent();
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => "{$required}|string|max:100",
            'name' => "{$required}|string|max:255",
            'description' => 'nullable|string',
            'push_title' => 'nullable|string|max:255',
            'push_body' => 'nullable|string',
            'email_subject' => 'nullable|string|max:255',
            'email_body' => 'nullable|string',
            'sms_body' => 'nullable|string|max:160',
            'variables' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);
    }
}
