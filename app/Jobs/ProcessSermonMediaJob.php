<?php

namespace App\Jobs;

use App\Plugins\Sermon\Models\Sermon;
use Illuminate\Support\Facades\Log;

class ProcessSermonMediaJob extends BaseJob
{
    public int $tries = 3;
    public int $timeout = 600; // 10 minutes for media processing

    public function __construct(
        public Sermon $sermon
    ) {
        $this->churchId = $sermon->church_id;
        $this->onQueue('media');
    }

    public function handle(): void
    {
        Log::info("Processing sermon media", [
            'sermon_id' => $this->sermon->id,
        ]);

        // Placeholder for media processing:
        // - Generate audio waveform
        // - Create video thumbnails
        // - Transcode to different formats
        // - Extract audio from video
    }

    public function tags(): array
    {
        return [
            'sermon',
            'media',
            "sermon:{$this->sermon->id}",
            ...parent::tags(),
        ];
    }
}
