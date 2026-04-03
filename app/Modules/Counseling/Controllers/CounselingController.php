<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Counseling\Models\CounselingMessage;
use App\Modules\Counseling\Models\CounselingThread;
use Illuminate\Http\Request;

class CounselingController extends Controller
{
    public function request(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'in:low,normal,high,urgent',
            'is_anonymous' => 'boolean',
        ]);

        $thread = CounselingThread::create([
            'user_id' => $request->user()->id,
            'church_id' => $request->user()->church_id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'] ?? 'normal',
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'status' => 'open',
        ]);

        // Create initial message
        $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return response()->json($thread->load('latestMessage'), 201);
    }

    public function myThreads(Request $request)
    {
        $threads = CounselingThread::where('user_id', $request->user()->id)
            ->with(['counselor:id,name,avatar', 'latestMessage'])
            ->latest()
            ->paginate(20);

        return response()->json($threads);
    }

    public function assignedThreads(Request $request)
    {
        if (!$request->user()->hasPermission('view_counseling_assigned')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $threads = CounselingThread::where('counselor_id', $request->user()->id)
            ->with(['requester:id,name,avatar', 'latestMessage'])
            ->latest()
            ->paginate(20);

        // Hide requester info on anonymous threads
        $threads->getCollection()->transform(function ($thread) {
            if ($thread->is_anonymous) {
                $thread->setRelation('requester', null);
            }
            return $thread;
        });

        return response()->json($threads);
    }

    public function show(Request $request, CounselingThread $thread)
    {
        if (!$request->user()->is_admin && !$thread->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $thread->load([
            'requester:id,name,avatar',
            'counselor:id,name,avatar',
            'messages' => fn($q) => $q->with('sender:id,name,avatar')->oldest(),
        ]);

        // Hide requester info if anonymous (unless user IS the requester)
        if ($thread->is_anonymous && $thread->user_id !== $request->user()->id) {
            $thread->setRelation('requester', null);
        }

        // Mark unread messages as read
        $thread->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($thread);
    }

    public function sendMessage(Request $request, CounselingThread $thread)
    {
        if (!$request->user()->is_admin && !$thread->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
        ]);

        $message = $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
            'attachments' => $validated['attachments'] ?? null,
        ]);

        // Auto-update thread status
        if ($thread->status === 'open' && $thread->counselor_id) {
            $thread->update(['status' => 'in_progress']);
        }

        return response()->json($message->load('sender:id,name,avatar'), 201);
    }

    public function assign(Request $request, CounselingThread $thread)
    {
        if (!$request->user()->is_admin && !$request->user()->hasRole('church_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'counselor_id' => 'required|exists:users,id',
        ]);

        $thread->update([
            'counselor_id' => $validated['counselor_id'],
            'status' => 'in_progress',
        ]);

        return response()->json($thread->load('counselor:id,name,avatar'));
    }

    public function updateStatus(Request $request, CounselingThread $thread)
    {
        if (!$request->user()->is_admin && !$thread->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $thread->update(['status' => $validated['status']]);

        return response()->json($thread);
    }

    // Admin: list all threads (for CounselingManager)
    public function allThreads(Request $request)
    {
        if (!$request->user()->hasPermission('manage_counseling')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = CounselingThread::with(['requester:id,name', 'counselor:id,name', 'latestMessage']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate(20));
    }
}
