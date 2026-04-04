<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Base job class with common functionality.
 * 
 * All jobs should extend this class for consistent behavior.
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Church context for multi-tenant jobs.
     */
    protected ?int $churchId = null;

    /**
     * Set the church context for this job.
     */
    public function forChurch(int $churchId): static
    {
        $this->churchId = $churchId;
        return $this;
    }

    /**
     * Get the tags that should identify the job.
     */
    public function tags(): array
    {
        $tags = [class_basename($this)];
        
        if ($this->churchId) {
            $tags[] = "church:{$this->churchId}";
        }
        
        return $tags;
    }
}
