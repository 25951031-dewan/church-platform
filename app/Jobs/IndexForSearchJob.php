<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class IndexForSearchJob extends BaseJob
{
    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $modelClass,
        public int $modelId,
        public string $action = 'upsert'
    ) {
        $this->onQueue('search');
    }

    public function handle(): void
    {
        Log::info("Indexing for search", [
            'model' => $this->modelClass,
            'id' => $this->modelId,
            'action' => $this->action,
        ]);

        // Placeholder for search indexing:
        // - Meilisearch / Algolia / Elasticsearch
        // - Scout integration
        
        /** @var Model $model */
        $model = $this->modelClass::find($this->modelId);
        
        if (!$model) {
            if ($this->action === 'delete') {
                // Remove from search index
            }
            return;
        }

        // Index the model
        // $model->searchable();
    }

    public function tags(): array
    {
        return [
            'search',
            $this->action,
            class_basename($this->modelClass),
            ...parent::tags(),
        ];
    }
}
