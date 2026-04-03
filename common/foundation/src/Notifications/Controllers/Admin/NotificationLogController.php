<?php

namespace Common\Notifications\Controllers\Admin;

use Common\Notifications\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::query()->with('user:id,name,email')->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }

        return response()->json($query->paginate($request->integer('per_page', 50)));
    }
}
