<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Verse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerseController extends Controller
{
    /**
     * List all verses with pagination.
     */
    public function index(): JsonResponse
    {
        $verses = Verse::orderBy('display_date', 'desc')->paginate(15);

        return response()->json($verses);
    }

    /**
     * Get today's verse of the day.
     */
    public function today(): JsonResponse
    {
        $verse = Verse::where('display_date', now()->toDateString())->first();

        if (!$verse) {
            return response()->json([
                'message' => 'No verse found for today.',
            ], 404);
        }

        return response()->json([
            'verse' => $verse,
        ]);
    }

    /**
     * Store a new verse.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference'   => ['required', 'string', 'max:255'],
            'verse_text'  => ['required', 'string'],
            'display_date' => ['required', 'date', 'unique:verses,display_date'],
            'translation' => ['nullable', 'string', 'max:50'],
        ]);

        $verse = Verse::create($validated);

        return response()->json([
            'message' => 'Verse created successfully.',
            'verse'   => $verse,
        ], 201);
    }

    /**
     * Update an existing verse.
     */
    public function update(Request $request, Verse $verse): JsonResponse
    {
        $validated = $request->validate([
            'reference'   => ['sometimes', 'required', 'string', 'max:255'],
            'verse_text'  => ['sometimes', 'required', 'string'],
            'display_date' => ['sometimes', 'required', 'date', 'unique:verses,display_date,' . $verse->id],
            'translation' => ['nullable', 'string', 'max:50'],
        ]);

        $verse->update($validated);

        return response()->json([
            'message' => 'Verse updated successfully.',
            'verse'   => $verse->fresh(),
        ]);
    }

    /**
     * Delete a verse.
     */
    public function destroy(Verse $verse): JsonResponse
    {
        $verse->delete();

        return response()->json([
            'message' => 'Verse deleted successfully.',
        ]);
    }

    /**
     * Download a sample CSV template for bulk import.
     */
    public function sampleCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['reference', 'verse_text', 'display_date', 'translation']);
            fputcsv($handle, ['John 3:16', 'For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.', '2026-03-01', 'NIV']);
            fputcsv($handle, ['Psalm 23:1', 'The LORD is my shepherd; I shall not want.', '2026-03-02', 'KJV']);
            fputcsv($handle, ['Philippians 4:13', 'I can do all things through Christ which strengtheneth me.', '2026-03-03', 'KJV']);
            fclose($handle);
        }, 'verses-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Bulk import verses from a CSV file.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return response()->json(['message' => 'Unable to read the file.'], 422);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'CSV file is empty.'], 422);
        }

        $header = array_map(fn($col) => strtolower(trim($col)), $header);
        $required = ['reference', 'verse_text', 'display_date'];
        $missing = array_diff($required, $header);

        if (!empty($missing)) {
            fclose($handle);
            return response()->json([
                'message' => 'CSV is missing required columns: ' . implode(', ', $missing),
            ], 422);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) !== count($header)) {
                $errors[] = "Row {$row}: column count mismatch.";
                $skipped++;
                continue;
            }

            $record = array_combine($header, $data);

            $validator = Validator::make($record, [
                'reference'    => ['required', 'string', 'max:255'],
                'verse_text'   => ['required', 'string'],
                'display_date' => ['required', 'date'],
                'translation'  => ['nullable', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$row}: " . implode(' ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $exists = Verse::where('display_date', $record['display_date'])->exists();
            if ($exists) {
                $errors[] = "Row {$row}: A verse already exists for date {$record['display_date']}.";
                $skipped++;
                continue;
            }

            Verse::create([
                'reference'    => $record['reference'],
                'verse_text'   => $record['verse_text'],
                'display_date' => $record['display_date'],
                'translation'  => $record['translation'] ?? 'KJV',
            ]);

            $imported++;
        }

        fclose($handle);

        return response()->json([
            'message'  => "{$imported} verse(s) imported successfully." . ($skipped ? " {$skipped} row(s) skipped." : ''),
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }
}
