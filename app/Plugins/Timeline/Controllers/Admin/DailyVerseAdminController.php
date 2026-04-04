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
     * Get sample verses for auto-scheduling
     */
    protected function getSampleVerses(): array
    {
        return [
            ['text' => 'For I know the plans I have for you, declares the Lord, plans to prosper you and not to harm you, to give you hope and a future.', 'reference' => 'Jeremiah 29:11'],
            ['text' => 'Trust in the Lord with all your heart and lean not on your own understanding; in all your ways submit to him, and he will make your paths straight.', 'reference' => 'Proverbs 3:5-6'],
            ['text' => 'And we know that in all things God works for the good of those who love him, who have been called according to his purpose.', 'reference' => 'Romans 8:28'],
            ['text' => 'Have I not commanded you? Be strong and courageous. Do not be afraid; do not be discouraged, for the Lord your God will be with you wherever you go.', 'reference' => 'Joshua 1:9'],
            ['text' => 'But those who hope in the Lord will renew their strength. They will soar on wings like eagles; they will run and not grow weary, they will walk and not be faint.', 'reference' => 'Isaiah 40:31'],
            ['text' => 'The Lord is my shepherd, I lack nothing. He makes me lie down in green pastures, he leads me beside quiet waters, he refreshes my soul.', 'reference' => 'Psalm 23:1-3'],
            ['text' => 'Cast all your anxiety on him because he cares for you.', 'reference' => '1 Peter 5:7'],
            ['text' => 'I can do all this through him who gives me strength.', 'reference' => 'Philippians 4:13'],
            ['text' => 'The Lord your God is with you, the Mighty Warrior who saves. He will take great delight in you; in his love he will no longer rebuke you, but will rejoice over you with singing.', 'reference' => 'Zephaniah 3:17'],
            ['text' => 'Be still, and know that I am God; I will be exalted among the nations, I will be exalted in the earth.', 'reference' => 'Psalm 46:10'],
        ];
    }
}