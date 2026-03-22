<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['follow', 'member'])->default('follow');
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['church_id', 'user_id']);
            $table->index(['church_id', 'type']);
            $table->index(['user_id', 'type']);
        });

        // Data migration: copy from church_page_members if that table exists
        // (handles upgrades from the old single-church schema)
        if (Schema::hasTable('church_page_members')) {
            \Illuminate\Support\Facades\DB::statement("
                INSERT IGNORE INTO church_members (church_id, user_id, type, role, joined_at, created_at, updated_at)
                SELECT church_id, user_id,
                    CASE WHEN is_member = 1 THEN 'member' ELSE 'follow' END AS type,
                    COALESCE(role, 'member') AS role,
                    created_at AS joined_at,
                    created_at,
                    updated_at
                FROM church_page_members
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('church_members');
    }
};
