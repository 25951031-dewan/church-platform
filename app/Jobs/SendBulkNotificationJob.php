<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly string $notificationClass,
        private readonly array  $notificationData,
        private readonly array  $recipientIds = [],
        private readonly ?int   $churchId = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if (! class_exists($this->notificationClass)) {
            Log::error('SendBulkNotificationJob: notification class not found', [
                'class' => $this->notificationClass,
            ]);
            return;
        }

        $query = User::query();

        if (! empty($this->recipientIds)) {
            $query->whereIn('id', $this->recipientIds);
        } elseif ($this->churchId) {
            $query->whereHas('churchMemberships', fn ($q) => $q->where('church_id', $this->churchId));
        }

        $notification = new $this->notificationClass(...$this->notificationData);

        $query->select('id')->chunk(200, function ($users) use ($notification) {
            NotificationFacade::send($users, clone $notification);
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendBulkNotificationJob failed', [
            'class' => $this->notificationClass,
            'error' => $e->getMessage(),
        ]);
    }
}
