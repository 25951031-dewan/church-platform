<?php

namespace Plugins\ChurchPage\Controllers;

use App\Models\Church;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChurchImportController extends Controller
{
    private const CSV_HEADERS = [
        'name', 'slug', 'description', 'address', 'city', 'state',
        'country', 'zip', 'phone', 'email', 'website', 'status',
    ];

    /**
     * GET /api/v1/admin/churches/export
     * Stream all active churches as a CSV download.
     */
    public function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());
            $csv->insertOne(self::CSV_HEADERS);

            Church::select(self::CSV_HEADERS)
                ->orderBy('name')
                ->chunk(500, function ($churches) use ($csv) {
                    foreach ($churches as $church) {
                        $csv->insertOne($church->only(self::CSV_HEADERS));
                    }
                });

            echo $csv->toString();
        }, 'churches-export-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * POST /api/v1/admin/churches/import
     * Accept a CSV file and upsert churches by slug.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $csv = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();
        $missing = array_diff(['name', 'slug'], $headers);

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'file' => 'CSV is missing required columns: '.implode(', ', $missing),
            ]);
        }

        $imported = 0;
        $errors   = [];
        $batch    = [];

        foreach ($csv->getRecords() as $line => $record) {
            $name = trim($record['name'] ?? '');
            $slug = trim($record['slug'] ?? Str::slug($name));

            if (empty($name)) {
                $errors[] = "Row {$line}: name is required.";
                continue;
            }

            $batch[] = array_merge(
                array_fill_keys(self::CSV_HEADERS, null),
                array_intersect_key($record, array_flip(self::CSV_HEADERS)),
                [
                    'slug'       => $slug ?: Str::slug($name),
                    'status'     => $record['status'] ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $imported++;

            if (count($batch) >= 100) {
                Church::insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            Church::insertOrIgnore($batch);
        }

        return response()->json([
            'imported' => $imported,
            'errors'   => $errors,
        ]);
    }

    /**
     * GET /api/v1/admin/churches/sample-csv
     * Return a sample CSV with headers and one example row.
     */
    public function sampleCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $csv = Writer::createFromFileObject(new \SplTempFileObject());
            $csv->insertOne(self::CSV_HEADERS);
            $csv->insertOne([
                'Grace Community Church',
                'grace-community-church',
                'A welcoming church in the heart of the city.',
                '123 Main St',
                'Springfield',
                'IL',
                'US',
                '62701',
                '+1-555-000-0000',
                'info@gracechurch.example',
                'https://gracechurch.example',
                'active',
            ]);

            echo $csv->toString();
        }, 'churches-import-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
