<?php

namespace Common\Settings\Controllers;

use Common\Settings\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $settings,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['settings' => $this->settings->getAll()]);
    }

    public function show(string $group): JsonResponse
    {
        return response()->json(['settings' => $this->settings->getByGroup($group)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        $this->settings->setMany($validated['settings']);

        return response()->json(['message' => 'Settings updated']);
    }
}
