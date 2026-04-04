<?php

namespace App\Plugins\Timeline\Commands;

use Illuminate\Console\Command;
use App\Plugins\Timeline\Models\TimelineSetting;
use App\Plugins\Timeline\Models\DailyVerse;
use App\Plugins\Timeline\Services\TimelineSettingsService;

class TimelineTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeline:test {action? : Action to test (settings|verses|validation)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test timeline settings and daily verse functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? 'all';

        switch ($action) {
            case 'settings':
                return $this->testSettings();
            case 'verses':
                return $this->testVerses();
            case 'validation':
                return $this->testValidation();
            case 'all':
            default:
                $this->testSettings();
                $this->testVerses();
                $this->testValidation();
                return 0;
        }
    }

    protected function testSettings(): int
    {
        $this->info('🧪 Testing Timeline Settings...');

        // Test default settings
        $defaults = TimelineSetting::getDefaultSettings();
        $this->line("✅ Default settings loaded: " . count($defaults) . " settings");

        // Test setting a value
        TimelineSetting::setValue('posts_enabled', false);
        $value = TimelineSetting::getValue('posts_enabled', true);
        $this->line($value === false ? "✅ Setting posts_enabled = false works" : "❌ Setting failed");

        // Test service class
        $service = new TimelineSettingsService();
        $postsEnabled = $service->arePostsEnabled();
        $this->line($postsEnabled === false ? "✅ Service method works" : "❌ Service method failed");

        // Reset setting
        TimelineSetting::setValue('posts_enabled', true);

        return 0;
    }

    protected function testVerses(): int
    {
        $this->info('📖 Testing Daily Verses...');

        // Create test verse for today
        $verse = DailyVerse::updateOrCreate(
            [
                'church_id' => null,
                'verse_date' => today()
            ],
            [
                'reference' => 'John 3:16',
                'text' => 'For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.',
                'translation' => 'NIV',
                'reflection' => 'This verse shows God\'s incredible love for humanity.',
                'is_active' => true
            ]
        );

        $this->line("✅ Created/updated today's verse: {$verse->reference}");

        // Test getting today's verse
        $todaysVerse = DailyVerse::getTodaysVerse();
        $this->line($todaysVerse ? "✅ Retrieved today's verse: {$todaysVerse->reference}" : "❌ Failed to get today's verse");

        // Test service method
        $service = new TimelineSettingsService();
        $serviceVerse = $service->getTodaysVerse();
        $this->line($serviceVerse ? "✅ Service method works: {$serviceVerse->reference}" : "❌ Service method failed");

        // Test CSV export
        $csvData = DailyVerse::exportToCsv();
        $this->line("✅ Exported " . count($csvData) . " verses to CSV format");

        return 0;
    }

    protected function testValidation(): int
    {
        $this->info('🔍 Testing Validation...');

        $service = new TimelineSettingsService();

        // Test content validation
        $shortContent = "Hello world";
        $errors = $service->validatePostContent($shortContent);
        $this->line(empty($errors) ? "✅ Short content validation passed" : "❌ Short content validation failed");

        // Test user posting validation (fake user ID)
        $errors = $service->canUserPost(999);
        $this->line("✅ User posting validation: " . (empty($errors) ? "Can post" : implode(', ', $errors)));

        // Test feature flags
        $photosEnabled = $service->arePhotoPostsEnabled();
        $this->line("✅ Photo posts enabled: " . ($photosEnabled ? "Yes" : "No"));

        $commentsEnabled = $service->areCommentsEnabled();
        $this->line("✅ Comments enabled: " . ($commentsEnabled ? "Yes" : "No"));

        return 0;
    }
}