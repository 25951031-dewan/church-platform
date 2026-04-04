<?php

namespace App\Console\Commands;

use App\Services\Search\SearchService;
use Illuminate\Console\Command;

class ReindexSearch extends Command
{
    protected $signature = 'search:reindex 
                            {type? : The content type to reindex (omit for all)}
                            {--force : Skip confirmation}';

    protected $description = 'Reindex content for search';

    public function handle(SearchService $searchService): int
    {
        $type = $this->argument('type');
        $availableTypes = $searchService->getAvailableTypes();

        if ($type && !in_array($type, $availableTypes)) {
            $this->error("Invalid type: {$type}");
            $this->line("Available types: " . implode(', ', $availableTypes));
            return self::FAILURE;
        }

        $types = $type ? [$type] : $availableTypes;

        $this->info("Search driver: " . $searchService->getDriverName());
        $this->newLine();

        if (!$this->option('force')) {
            $typeList = implode(', ', $types);
            if (!$this->confirm("Reindex the following types: {$typeList}?")) {
                $this->line('Cancelled.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $totalCount = 0;

        foreach ($types as $t) {
            $this->line("Reindexing: {$t}...");
            
            try {
                $count = $searchService->reindexType($t);
                $this->info("  ✓ Indexed {$count} documents");
                $totalCount += $count;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Total: {$totalCount} documents indexed.");

        return self::SUCCESS;
    }
}
