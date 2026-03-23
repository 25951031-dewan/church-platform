<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('status');
            $table->boolean('is_approved')->nullable()->after('is_pinned');
            $table->unsignedBigInteger('approved_by')->nullable()->after('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'is_approved', 'approved_by']);
        });
    }
};
