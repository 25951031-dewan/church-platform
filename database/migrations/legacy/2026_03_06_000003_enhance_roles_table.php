<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
            if (!Schema::hasColumn('roles', 'level')) {
                $table->integer('level')->default(10)->after('is_system');
            }
            if (!Schema::hasColumn('roles', 'church_id')) {
                $table->foreignId('church_id')
                    ->nullable()
                    ->after('level')
                    ->constrained('churches')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'church_id')) {
                $table->dropForeign(['church_id']);
                $table->dropColumn('church_id');
            }
            if (Schema::hasColumn('roles', 'level')) {
                $table->dropColumn('level');
            }
            if (Schema::hasColumn('roles', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
