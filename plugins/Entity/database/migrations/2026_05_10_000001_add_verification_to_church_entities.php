<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_entities', function (Blueprint $table) {
            $table->timestamp('verification_requested_at')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('church_entities', function (Blueprint $table) {
            $table->dropColumn('verification_requested_at');
        });
    }
};
