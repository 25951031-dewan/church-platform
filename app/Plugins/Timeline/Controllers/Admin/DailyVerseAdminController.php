<?php

namespace App\Plugins\Timeline\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Timeline\Models\DailyVerse;
use App\Plugins\Timeline\Requests\CreateDailyVerseRequest;
use App\Plugins\Timeline\Requests\UpdateDailyVerseRequest;
use App\Plugins\Timeline\Requests\ImportDailyVersesRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DailyVerseAdminController extends Controller
{
    /**
     * Get paginated daily verses for admin management
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DailyVerse::class);

        $query = DailyVerse::query()
            ->forChurch(auth('sanctum')->user()->church_id);

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('verse_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('verse_date', '<=', $request->end_date);
        }

        // Filter by translation
        if ($request->has('translation')) {
            $query->where('translation', $request->translation);
        }

        // Search by text or reference
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('verse_text', 'like', "%{$search}%")
                  ->orWhere('verse_reference', 'like', "%{$search}%")
                  ->orWhere('theme', 'like', "%{$search}%");
            });
        }

        $verses = $query->orderBy('verse_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'verses' => $verses,
            'stats' => $this->getVerseStats(),
        ]);
    }

    /**
     * Show a specific daily verse
     */
    public function show(DailyVerse $verse): JsonResponse
    {
        $this->authorize('view', $verse);
        return response()->json(['verse' => $verse]);
    }

    /**
     * Create a new daily verse
     */
    public function store(CreateDailyVerseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['church_id'] = auth('sanctum')->user()->church_id;
        $data['slug'] = $this->generateSlug($data['verse_reference']);

        $verse = DailyVerse::create($data);

        return response()->json([
            'verse' => $verse,
            'message' => 'Daily verse created successfully.',
        ], 201);
    }

    /**
     * Update an existing daily verse
     */
    public function update(UpdateDailyVerseRequest $request, DailyVerse $verse): JsonResponse
    {
        $this->authorize('update', $verse);

        $data = $request->validated();
        if (isset($data['verse_reference'])) {
            $data['slug'] = $this->generateSlug($data['verse_reference']);
        }

        $verse->update($data);

        return response()->json([
            'verse' => $verse,
            'message' => 'Daily verse updated successfully.',
        ]);
    }

    /**
     * Delete a daily verse
     */
    public function destroy(DailyVerse $verse): JsonResponse
    {
        $this->authorize('delete', $verse);
        $verse->delete();

        return response()->json([
            'message' => 'Daily verse deleted successfully.',
        ]);
    }

    /**
     * Bulk import verses from CSV
     */
    public function import(ImportDailyVersesRequest $request): JsonResponse
    {
        $file = $request->file('csv_file');
        $churchId = auth('sanctum')->user()->church_id;
        $replace = $request->boolean('replace_existing', false);
        
        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Parse CSV
        $data = array_map('str_getcsv', file($file->path()));
        $header = array_shift($data);

        // Validate CSV structure
        $requiredColumns = ['verse_date', 'verse_text', 'verse_reference'];
        $missingColumns = array_diff($requiredColumns, $header);
        
        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'Missing required columns: ' . implode(', ', $missingColumns),
            ], 422);
        }

        foreach ($data as $rowIndex => $row) {
            try {
                if (count($row) !== count($header)) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Column count mismatch";
                    continue;
                }

                $rowData = array_combine($header, $row);
                
                // Validate date
                try {
                    $verseDate = Carbon::createFromFormat('Y-m-d', $rowData['verse_date']);
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": Invalid date format";
                    continue;
                }

                // Check if verse already exists for this date
                $existing = DailyVerse::forChurch($churchId)
                    ->where('verse_date', $verseDate->format('Y-m-d'))
                    ->first();

                if ($existing && !$replace) {
                    $skipped++;
                    continue;
                }

                $verseData = [
                    'church_id' => $churchId,
                    'verse_date' => $verseDate->format('Y-m-d'),
                    'verse_text' => $rowData['verse_text'],
                    'verse_reference' => $rowData['verse_reference'],
                    'translation' => $rowData['translation'] ?? 'NIV',
                    'theme' => $rowData['theme'] ?? null,
                    'author_note' => $rowData['author_note'] ?? null,
                    'slug' => $this->generateSlug($rowData['verse_reference']),
                ];

                if ($existing && $replace) {
                    $existing->update($verseData);
                } else {
                    DailyVerse::create($verseData);
                }

                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Import completed. {$imported} verses imported, {$skipped} skipped.",
        ]);
    }

    /**
     * Export verses to CSV
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', DailyVerse::class);

        $query = DailyVerse::forChurch(auth('sanctum')->user()->church_id);

        // Apply date filters
        if ($request->has('start_date')) {
            $query->where('verse_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('verse_date', '<=', $request->end_date);
        }

        $verses = $query->orderBy('verse_date')->get();

        $csv = "verse_date,verse_text,verse_reference,translation,theme,author_note\n";
        
        foreach ($verses as $verse) {
            $csv .= sprintf(
                "%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $verse->verse_date,
                str_replace('"', '""', $verse->verse_text),
                str_replace('"', '""', $verse->verse_reference),
                str_replace('"', '""', $verse->translation),
                str_replace('"', '""', $verse->theme ?? ''),
                str_replace('"', '""', $verse->author_note ?? '')
            );
        }

        $filename = 'daily-verses-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Auto-schedule verses for upcoming dates
     */
    public function autoSchedule(Request $request): JsonResponse
    {
        $this->authorize('create', DailyVerse::class);

        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'days_count' => 'required|integer|min:1|max:365',
            'translation' => 'nullable|string|max:10',
            'themes' => 'array',
        ]);

        $churchId = auth('sanctum')->user()->church_id;
        $translation = $request->get('translation', 'NIV');
        $themes = $request->get('themes', []);
        
        // Sample verses for auto-scheduling (in production, this would come from a larger database)
        $sampleVerses = $this->getSampleVerses();
        
        $created = 0;
        $startDate = Carbon::parse($request->start_date);

        for ($i = 0; $i < $request->days_count; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            
            // Skip if verse already exists for this date
            $existing = DailyVerse::forChurch($churchId)
                ->where('verse_date', $currentDate->format('Y-m-d'))
                ->exists();
                
            if ($existing) {
                continue;
            }

            // Select a random verse
            $verseIndex = $i % count($sampleVerses);
            $sampleVerse = $sampleVerses[$verseIndex];
            
            DailyVerse::create([
                'church_id' => $churchId,
                'verse_date' => $currentDate->format('Y-m-d'),
                'verse_text' => $sampleVerse['text'],
                'verse_reference' => $sampleVerse['reference'],
                'translation' => $translation,
                'theme' => !empty($themes) ? $themes[array_rand($themes)] : null,
                'slug' => $this->generateSlug($sampleVerse['reference']),
            ]);

            $created++;
        }

        return response()->json([
            'created' => $created,
            'message' => "Auto-scheduled {$created} daily verses.",
        ]);
    }

    /**
     * Get verse statistics
     */
    protected function getVerseStats(): array
    {
        $churchId = auth('sanctum')->user()->church_id;
        
        return [
            'total' => DailyVerse::forChurch($churchId)->count(),
            'this_year' => DailyVerse::forChurch($churchId)
                ->whereYear('verse_date', now()->year)
                ->count(),
            'upcoming' => DailyVerse::forChurch($churchId)
                ->where('verse_date', '>', now()->format('Y-m-d'))
                ->count(),
            'translations' => DailyVerse::forChurch($churchId)
                ->select('translation')
                ->groupBy('translation')
                ->pluck('translation'),
        ];
    }

    /**
     * Generate slug from verse reference
     */
    protected function generateSlug(string $reference): string
    {
        return Str::slug($reference);
    }

    /**
     * Bulk delete daily verses
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', DailyVerse::class);

        $request->validate([
            'verse_ids' => 'required|array|min:1',
            'verse_ids.*' => 'integer|exists:daily_verses,id',
        ]);

        $churchId = auth('sanctum')->user()->church_id;
        $verseIds = $request->verse_ids;

        // Ensure verses belong to the church
        $verses = DailyVerse::forChurch($churchId)
            ->whereIn('id', $verseIds)
            ->get();

        if ($verses->count() !== count($verseIds)) {
            return response()->json([
                'message' => 'Some verses were not found or do not belong to your church.',
            ], 422);
        }

        $deletedCount = DailyVerse::whereIn('id', $verseIds)->delete();

        return response()->json([
            'deleted' => $deletedCount,
            'message' => "Successfully deleted {$deletedCount} daily verses.",
        ]);
    }

    /**
     * Bulk update daily verses
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->authorize('update', DailyVerse::class);

        $request->validate([
            'verse_ids' => 'required|array|min:1',
            'verse_ids.*' => 'integer|exists:daily_verses,id',
            'updates' => 'required|array',
            'updates.translation' => 'nullable|string|max:10',
            'updates.theme' => 'nullable|string|max:100',
            'updates.is_active' => 'nullable|boolean',
        ]);

        $churchId = auth('sanctum')->user()->church_id;
        $verseIds = $request->verse_ids;
        $updates = array_filter($request->updates, function($value) {
            return $value !== null;
        });

        // Ensure verses belong to the church
        $verses = DailyVerse::forChurch($churchId)
            ->whereIn('id', $verseIds)
            ->get();

        if ($verses->count() !== count($verseIds)) {
            return response()->json([
                'message' => 'Some verses were not found or do not belong to your church.',
            ], 422);
        }

        $updatedCount = DailyVerse::whereIn('id', $verseIds)->update($updates);

        return response()->json([
            'updated' => $updatedCount,
            'message' => "Successfully updated {$updatedCount} daily verses.",
        ]);
    }

    /**
     * Activate/deactivate a daily verse
     */
    public function activate(Request $request, DailyVerse $verse): JsonResponse
    {
        $this->authorize('update', $verse);

        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $verse->update(['is_active' => $request->is_active]);

        $action = $request->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'verse' => $verse->fresh(),
            'message' => "Daily verse {$action} successfully.",
        ]);
    }

    /**
     * Get today's verse for public consumption
     */
    public function getTodaysVerse(Request $request): JsonResponse
    {
        // Get church ID from query parameter or default church
        $churchId = $request->query('church_id', 1);

        $todaysVerse = DailyVerse::getTodaysVerse($churchId);

        if (!$todaysVerse) {
            return response()->json([
                'verse' => null,
                'message' => 'No daily verse found for today.',
            ]);
        }

        return response()->json([
            'verse' => [
                'id' => $todaysVerse->id,
                'verse_text' => $todaysVerse->verse_text,
                'verse_reference' => $todaysVerse->verse_reference,
                'translation' => $todaysVerse->translation,
                'theme' => $todaysVerse->theme,
                'verse_date' => $todaysVerse->verse_date,
            ],
        ]);
    }