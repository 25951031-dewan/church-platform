<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTemplate;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Display a paginated listing of newsletter subscribers (admin).
     */
    public function subscribers(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::query()->latest();

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $subscribers,
        ]);
    }

    /**
     * Subscribe to the newsletter (public).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name'  => 'nullable|string|max:255',
        ]);

        // Check if already subscribed
        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already subscribed to our newsletter.',
                ], 409);
            }

            // Re-activate if previously unsubscribed
            $existing->update([
                'is_active'     => true,
                'token'         => Str::random(64),
                'subscribed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! You have been re-subscribed to our newsletter.',
                'data'    => $existing->fresh(),
            ]);
        }

        $subscriber = NewsletterSubscriber::create([
            'email'         => $validated['email'],
            'name'          => $validated['name'] ?? null,
            'token'         => Str::random(64),
            'is_active'     => true,
            'subscribed_at' => now(),
        ]);

        // Send welcome email and sync with Mailchimp
        try {
            $emailService = new EmailService();
            $unsubscribeUrl = url('/api/newsletter/unsubscribe/' . $subscriber->token);
            $emailService->sendWelcomeEmail($subscriber->email, $subscriber->name, $unsubscribeUrl);
            $emailService->addToMailchimpList($subscriber->email, $subscriber->name);
        } catch (\Exception $e) {
            Log::warning('Newsletter post-subscribe actions failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!',
            'data'    => $subscriber,
        ], 201);
    }

    /**
     * Unsubscribe from the newsletter using token (public).
     */
    public function unsubscribe(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe token.',
            ], 404);
        }

        if (!$subscriber->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already unsubscribed.',
            ], 400);
        }

        $subscriber->update([
            'is_active'       => false,
            'unsubscribed_at' => now(),
        ]);

        // Sync unsubscribe with Mailchimp
        try {
            $emailService = new EmailService();
            $emailService->removeFromMailchimpList($subscriber->email);
        } catch (\Exception $e) {
            Log::warning('Mailchimp unsubscribe sync failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'You have been successfully unsubscribed from our newsletter.',
        ]);
    }

    /**
     * List newsletter templates (admin).
     */
    public function templates(Request $request): JsonResponse
    {
        $query = NewsletterTemplate::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $templates,
        ]);
    }

    /**
     * Store a new newsletter template.
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'type'    => 'nullable|string|max:50',
        ]);

        $validated['created_by'] = $request->user()?->id;

        $template = NewsletterTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully.',
            'data'    => $template,
        ], 201);
    }

    /**
     * Update a newsletter template.
     */
    public function updateTemplate(Request $request, NewsletterTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
            'type'    => 'nullable|string|max:50',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully.',
            'data'    => $template->fresh(),
        ]);
    }

    /**
     * Delete a newsletter template.
     */
    public function destroyTemplate(NewsletterTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully.',
        ]);
    }

    /**
     * Send a newsletter template to all active subscribers.
     */
    public function sendTemplate(NewsletterTemplate $template): JsonResponse
    {
        $subscribers = NewsletterSubscriber::where('is_active', true)->get();

        if ($subscribers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscribers to send to.',
            ], 400);
        }

        $sentCount = 0;
        $emailService = new EmailService();

        foreach ($subscribers as $subscriber) {
            try {
                $unsubscribeUrl = url('/api/newsletter/unsubscribe/' . $subscriber->token);
                $emailService->sendNewsletter(
                    $subscriber->email,
                    $subscriber->name,
                    $template->subject,
                    $template->body,
                    $unsubscribeUrl
                );
                $sentCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to send newsletter to ' . $subscriber->email . ': ' . $e->getMessage());
                continue;
            }
        }

        $template->update([
            'sent_at'          => now(),
            'recipients_count' => $sentCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Newsletter sent to {$sentCount} subscriber(s).",
            'data'    => $template->fresh(),
        ]);
    }

    /**
     * Export subscriber list as CSV.
     */
    public function exportSubscribers(): \Illuminate\Http\Response
    {
        $subscribers = NewsletterSubscriber::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['email', 'name', 'subscribed_at', 'created_at']);

        $csv = "Email,Name,Subscribed Date\n";
        foreach ($subscribers as $sub) {
            $date = $sub->subscribed_at ? $sub->subscribed_at->toDateString() : ($sub->created_at ? $sub->created_at->toDateString() : '');
            $csv .= '"' . str_replace('"', '""', $sub->email) . '","' . str_replace('"', '""', $sub->name ?? '') . '","' . $date . "\"\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="newsletter-subscribers.csv"',
        ]);
    }
}
