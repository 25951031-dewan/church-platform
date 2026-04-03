<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Display a paginated listing of contact messages (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query()->latest();

        if ($request->has('status')) {
            if ($request->status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->status === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->status === 'replied') {
                $query->whereNotNull('replied_at');
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    /**
     * Store a newly submitted contact message (public).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:30',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        $validated['is_read'] = false;

        $contact = ContactMessage::create($validated);

        // Send notification email to admin using EmailService
        try {
            $emailService = new EmailService();
            $emailService->sendContactNotification($validated);
        } catch (\Exception $e) {
            Log::warning('Failed to send contact notification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully. We will get back to you soon.',
            'data'    => $contact,
        ], 201);
    }

    /**
     * Display the specified contact message.
     */
    public function show(ContactMessage $contactMessage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $contactMessage,
        ]);
    }

    /**
     * Mark a contact message as read.
     */
    public function markRead(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read.',
            'data'    => $contactMessage->fresh(),
        ]);
    }

    /**
     * Reply to a contact message.
     */
    public function reply(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $validated = $request->validate([
            'reply_subject' => 'nullable|string|max:255',
            'reply_message' => 'required|string|max:10000',
        ]);

        $replySubject = $validated['reply_subject'] ?? 'Re: ' . $contactMessage->subject;

        // Send reply using EmailService
        try {
            $emailService = new EmailService();
            $emailService->send(
                $contactMessage->email,
                $replySubject,
                $validated['reply_message'],
                $contactMessage->name
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply email. Please check mail configuration.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        $contactMessage->update([
            'is_read'       => true,
            'read_at'       => $contactMessage->read_at ?? now(),
            'reply_message' => $validated['reply_message'],
            'replied_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully.',
            'data'    => $contactMessage->fresh(),
        ]);
    }

    /**
     * Remove the specified contact message.
     */
    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact message deleted successfully.',
        ]);
    }
}
