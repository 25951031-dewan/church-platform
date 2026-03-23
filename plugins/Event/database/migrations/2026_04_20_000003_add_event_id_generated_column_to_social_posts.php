<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE social_posts
                ADD COLUMN event_id BIGINT UNSIGNED
                    GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.event_id')) AS UNSIGNED)) STORED
            ");
            DB::statement('ALTER TABLE social_posts ADD INDEX idx_social_posts_event_id (event_id)');
        }
        // SQLite: add a plain nullable column (test-only fallback)
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('social_posts', function ($table) {
                $table->unsignedBigInteger('event_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE social_posts DROP INDEX idx_social_posts_event_id');
            DB::statement('ALTER TABLE social_posts DROP COLUMN event_id');
        }
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('social_posts', function ($table) {
                $table->dropColumn('event_id');
            });
        }
    }
};
