<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that receive a nullable church_id FK.
     * The churches and users tables are excluded (users.church_id already exists).
     */
    protected array $tables = [
        'posts',
        'pages',
        'events',
        'event_registrations',
        'sermons',
        'books',
        'bible_studies',
        'prayer_requests',
        'reviews',
        'testimonies',
        'verses',
        'blessings',
        'announcements',
        'galleries',
        'gallery_images',
        'ministries',
        'donations',
        'contact_messages',
        'categories',
        'menus',
        'settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'church_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->foreignId('church_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('churches')
                    ->nullOnDelete();
                $blueprint->index('church_id', "idx_{$table}_church_id");
            });
        }

        // newsletter_subscribers and newsletter_templates may exist
        foreach (['newsletter_subscribers', 'newsletter_templates'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'church_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->foreignId('church_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('churches')
                        ->nullOnDelete();
                    $blueprint->index('church_id', "idx_{$table}_church_id");
                });
            }
        }

        // Add module_config column to settings table
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'module_config')) {
            Schema::table('settings', function (Blueprint $blueprint) {
                $blueprint->json('module_config')->nullable()->after('widget_config');
            });
        }
    }

    public function down(): void
    {
        $all = array_merge(
            $this->tables,
            ['newsletter_subscribers', 'newsletter_templates']
        );

        foreach ($all as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'church_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(['church_id']);
                $blueprint->dropIndex("idx_{$table}_church_id");
                $blueprint->dropColumn('church_id');
            });
        }

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'module_config')) {
            Schema::table('settings', function (Blueprint $blueprint) {
                $blueprint->dropColumn('module_config');
            });
        }
    }
};
