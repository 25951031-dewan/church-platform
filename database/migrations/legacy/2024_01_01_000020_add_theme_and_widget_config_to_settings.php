<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if columns already exist (they are included in the main settings migration)
        if (Schema::hasColumn('settings', 'theme_config')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->json('theme_config')->nullable();
            $table->json('widget_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'theme_config')) {
                $table->dropColumn(['theme_config', 'widget_config']);
            }
        });
    }
};
