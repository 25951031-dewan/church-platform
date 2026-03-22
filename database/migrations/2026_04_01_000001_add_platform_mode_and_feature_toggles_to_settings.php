<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Only add columns if they don't already exist (safe for re-runs)
            if (! Schema::hasColumn('settings', 'platform_mode')) {
                $table->enum('platform_mode', ['single', 'multi'])->default('single')->after('value');
            }
            if (! Schema::hasColumn('settings', 'show_church_directory')) {
                $table->boolean('show_church_directory')->default(false)->after('platform_mode');
            }
            if (! Schema::hasColumn('settings', 'feature_toggles')) {
                $table->json('feature_toggles')->nullable()->after('show_church_directory');
            }
            if (! Schema::hasColumn('settings', 'default_church_id')) {
                $table->unsignedBigInteger('default_church_id')->nullable()->after('feature_toggles');
            }
        });

        // Seed default platform settings row
        DB::table('settings')->insertOrIgnore([
            'key'                  => 'platform',
            'platform_mode'        => 'single',
            'show_church_directory' => false,
            'feature_toggles'      => json_encode([
                'announcement'  => true,
                'verse'         => true,
                'blessing'      => true,
                'prayer'        => true,
                'blog'          => true,
                'events'        => true,
                'library'       => true,
                'bible_studies' => true,
                'testimony'     => true,
                'galleries'     => true,
                'ministries'    => true,
                'sermons'       => true,
                'reviews'       => true,
                'hymns'         => true,
                'bible_reader'  => true,
            ]),
            'default_church_id' => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'platform_mode',
                'show_church_directory',
                'feature_toggles',
                'default_church_id',
            ]);
        });

        DB::table('settings')->where('key', 'platform')->delete();
    }
};
