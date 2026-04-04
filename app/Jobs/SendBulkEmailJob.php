<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class SendBulkEmailJob extends BaseJob
{
    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public array $recipientIds,
        public string $subject,
        public string $body,
        public ?int $churchId = null
    ) {
        $this->churchId = $churchId;
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        Log::info("Sending bulk email", [
            'recipients' => count($this->recipientIds),
            'subject' => $this->subject,
        ]);

        // Placeholder for bulk email:
        // - Chunk recipients
        // - Send via configured mail driver
        // - Track delivery status
    }

    public function tags(): array
    {
        return [
            'email',
            'bulk',
            "recipients:" . count($this->recipientIds),
            ...parent::tags(),
        ];
    }
}
