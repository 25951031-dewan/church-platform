<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance groups table
        Schema::table('groups', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name');
            $table->text('rules')->nullable()->after('description');
            $table->unsignedInteger('member_count')->default(0)->after('cover_image');
            $table->boolean('is_featured')->default(false)->after('member_count');
        });

        // Enhance group_members with status for private group workflows
        Schema::table('group_members', function (Blueprint $table) {
            $table->enum('status', ['approved', 'pending', 'invited'])->default('approved')->after('role');
        });

        // Add group_id FK to timeline_posts so group posts reuse the timeline system
        Schema::table('timeline_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('church_id');
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('timeline_posts', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['slug', 'rules', 'member_count', 'is_featured']);
        });
    }
};
