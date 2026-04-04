<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToChurch;
use Carbon\Carbon;

class DailyVerse extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'verse_date',
        'reference',
        'text',
        'translation',
        'reflection',
        'is_active'
    ];

    protected $casts = [
        'verse_date' => 'date',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'verse_date'
    ];

    /**
     * Get today's verse
     */
    public static function getTodaysVerse(?int $churchId = null): ?self
    {
        return static::where('church_id', $churchId)
            ->where('verse_date', today())
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get verse for specific date
     */
    public static function getVerseForDate(Carbon $date, ?int $churchId = null): ?self
    {
        return static::where('church_id', $churchId)
            ->where('verse_date', $date->toDateString())
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get verses for date range
     */
    public static function getVersesForDateRange(Carbon $startDate, Carbon $endDate, ?int $churchId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('church_id', $churchId)
            ->whereBetween('verse_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_active', true)
            ->orderBy('verse_date')
            ->get();
    }

    /**
     * Create verses from CSV import data
     */
    public static function createFromCsvData(array $data, ?int $churchId = null): array
    {
        $created = [];
        $errors = [];

        foreach ($data as $index => $row) {
            try {
                $verse = static::updateOrCreate(
                    [
                        'church_id' => $churchId,
                        'verse_date' => $row['verse_date']
                    ],
                    [
                        'reference' => $row['reference'],
                        'text' => $row['text'],
                        'translation' => $row['translation'] ?? 'NIV',
                        'reflection' => $row['reflection'] ?? null,
                        'is_active' => $row['is_active'] ?? true
                    ]
                );

                $created[] = $verse;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                    'data' => $row
                ];
            }
        }

        return [
            'created' => $created,
            'errors' => $errors
        ];
    }

    /**
     * Export verses to CSV format
     */
    public static function exportToCsv(?int $churchId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = static::where('church_id', $churchId);
        
        if ($startDate) {
            $query->where('verse_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('verse_date', '<=', $endDate);
        }
        
        $verses = $query->orderBy('verse_date')->get();
        
        return $verses->map(function ($verse) {
            return [
                'verse_date' => $verse->verse_date->toDateString(),
                'reference' => $verse->reference,
                'text' => $verse->text,
                'translation' => $verse->translation,
                'reflection' => $verse->reflection,
                'is_active' => $verse->is_active ? 'true' : 'false'
            ];
        })->toArray();
    }

    /**
     * Get sample CSV data for export
     */
    public static function getSampleCsvData(): array
    {
        return [
            [
                'verse_date' => '2024-04-04',
                'reference' => 'John 3:16',
                'text' => 'For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.',
                'translation' => 'NIV',
                'reflection' => 'This verse reminds us of God\'s incredible love for humanity and the sacrifice He made for our salvation.',
                'is_active' => 'true'
            ],
            [
                'verse_date' => '2024-04-05',
                'reference' => 'Philippians 4:13',
                'text' => 'I can do all this through him who gives me strength.',
                'translation' => 'NIV',
                'reflection' => 'Through Christ, we have the strength to overcome any challenge that comes our way.',
                'is_active' => 'true'
            ],
            [
                'verse_date' => '2024-04-06',
                'reference' => 'Psalm 23:1',
                'text' => 'The Lord is my shepherd, I lack nothing.',
                'translation' => 'NIV',
                'reflection' => 'God provides for all our needs and guides us with loving care.',
                'is_active' => 'true'
            ]
        ];
    }
}