<?php

namespace Common\Notifications\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate($request->integer('per_page', 20));
        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();
        return response()->noContent();
    }

    public function clearRead(Request $request): JsonResponse
    {
        $request->user()->readNotifications()->delete();
        return response()->noContent();
    }
}
