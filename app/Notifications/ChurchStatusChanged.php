<?php

namespace App\Notifications;

use App\Models\Church;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChurchStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected Church $church,
        protected string $status,
        protected ?string $rejectionNote = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'Church Platform');

        if ($this->status === 'approved') {
            return (new MailMessage)
                ->subject("Your Church Listing Has Been Approved — {$appName}")
                ->greeting("Great news, {$notifiable->name}!")
                ->line("Your church **{$this->church->name}** has been approved and is now listed in the church directory.")
                ->action('View Your Church Listing', url("/churches/{$this->church->slug}"))
                ->line('Thank you for being part of our platform.');
        }

        return (new MailMessage)
            ->subject("Your Church Listing Status — {$appName}")
            ->greeting("Hello, {$notifiable->name}")
            ->line("Unfortunately, your church **{$this->church->name}** has not been approved at this time.")
            ->when($this->rejectionNote, fn($m) => $m->line("**Reason:** {$this->rejectionNote}"))
            ->line('Please update your listing and resubmit if you believe this is an error.')
            ->action('Contact Support', url('/'))
            ->line('Thank you for your understanding.');
    }
}
