<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('church_id')
                ->nullable()
                ->after('author_id')
                ->constrained('churches')
                ->nullOnDelete();

            $table->index(['church_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropIndex(['church_id', 'status']);
            $table->dropColumn('church_id');
        });
    }
};
