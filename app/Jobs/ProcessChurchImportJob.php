<?php

namespace App\Jobs;

use App\Models\Church;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class ProcessChurchImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly string $filePath,
        private readonly int $importedBy,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        if (! file_exists($this->filePath)) {
            Log::warning('ProcessChurchImportJob: file not found', ['path' => $this->filePath]);
            return;
        }

        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);

        $batch = [];
        $now   = now()->toDateTimeString();

        foreach ($csv->getRecords() as $record) {
            $name = trim($record['name'] ?? '');

            if (empty($name)) {
                continue;
            }

            $batch[] = [
                'name'       => $name,
                'city'       => trim($record['city'] ?? ''),
                'country'    => trim($record['country'] ?? ''),
                'address'    => trim($record['address'] ?? ''),
                'phone'      => trim($record['phone'] ?? ''),
                'email'      => trim($record['email'] ?? ''),
                'website'    => trim($record['website'] ?? ''),
                'status'     => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 100) {
                DB::table('churches')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('churches')->insertOrIgnore($batch);
        }

        @unlink($this->filePath);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessChurchImportJob failed', [
            'file'  => $this->filePath,
            'error' => $e->getMessage(),
        ]);
    }
}
