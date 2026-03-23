<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            // is_pinned and is_approved already added by 2026_05_01 (entity fields migration)
            $table->unsignedBigInteger('approved_by')->nullable()->after('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['approved_by']);
        });
    }
};
