<?php

namespace App\Services;

use App\Models\Church;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ChurchImportService
{
    /**
     * Expected CSV columns (case-insensitive header matching).
     */
    protected array $columnMap = [
        'name'         => ['name', 'church name', 'church_name'],
        'email'        => ['email', 'email address'],
        'phone'        => ['phone', 'phone number', 'tel'],
        'address'      => ['address', 'street address'],
        'city'         => ['city'],
        'state'        => ['state', 'province'],
        'zip_code'     => ['zip', 'zip code', 'postal code', 'zip_code'],
        'country'      => ['country'],
        'denomination' => ['denomination', 'type'],
        'website'      => ['website', 'url', 'web'],
        'pastor_name'  => ['pastor', 'pastor name', 'pastor_name', 'contact person'],
        'short_description' => ['description', 'about', 'short description'],
    ];

    /**
     * Import churches from an uploaded CSV file.
     * Returns: ['created' => int, 'skipped' => int, 'errors' => array]
     */
    public function import(UploadedFile $file): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['created' => 0, 'skipped' => 0, 'errors' => ['Could not open CSV file.']];
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['created' => 0, 'skipped' => 0, 'errors' => ['CSV file is empty or invalid.']];
        }

        // Map header positions to field names
        $headerMap = $this->mapHeaders(array_map('trim', $headers));

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($row)) === 0) {
                continue; // skip blank rows
            }

            try {
                $data = $this->parseRow($row, $headerMap);
                $result = $this->createOrSkip($data, $rowNumber);
                if ($result === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors'  => $errors,
        ];
    }

    /**
     * Generate a sample CSV template for download.
     */
    public function generateSampleCsv(): string
    {
        $headers = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'denomination', 'website', 'pastor_name', 'short_description'];
        $example = [
            'Grace Community Church',
            'info@gracechurch.com',
            '+1 555-123-4567',
            '123 Main Street',
            'Springfield',
            'IL',
            '62701',
            'USA',
            'Baptist',
            'https://gracechurch.com',
            'Pastor John Smith',
            'A welcoming community of faith.',
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        fputcsv($output, $example);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    protected function mapHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $lower = strtolower(trim($header));
            foreach ($this->columnMap as $field => $aliases) {
                if (in_array($lower, $aliases, true)) {
                    $map[$index] = $field;
                    break;
                }
            }
        }
        return $map;
    }

    protected function parseRow(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $index => $field) {
            $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
        }
        return $data;
    }

    protected function createOrSkip(array $data, int $rowNumber): string
    {
        $name = $data['name'] ?? null;
        $city = $data['city'] ?? null;

        if (empty($name)) {
            throw new \InvalidArgumentException('Name is required.');
        }

        // Skip if church already exists (same name + city)
        $exists = Church::where('name', $name)
            ->when($city, fn($q) => $q->where('city', $city))
            ->exists();

        if ($exists) {
            return 'skipped';
        }

        $slug = $this->generateUniqueSlug($name);

        Church::create(array_filter([
            'name'              => $name,
            'slug'              => $slug,
            'status'            => 'pending',
            'email'             => $data['email'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'address'           => $data['address'] ?? null,
            'city'              => $city,
            'state'             => $data['state'] ?? null,
            'zip_code'          => $data['zip_code'] ?? null,
            'country'           => $data['country'] ?? null,
            'denomination'      => $data['denomination'] ?? null,
            'website'           => $data['website'] ?? null,
            'short_description' => $data['short_description'] ?? null,
        ], fn($v) => $v !== null && $v !== ''));

        return 'created';
    }

    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Church::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
