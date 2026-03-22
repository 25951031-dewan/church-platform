<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('privacy', ['public', 'private'])->default('public');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);

            // Phase 2.2 counsel group fields
            $table->boolean('is_counsel_group')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->json('counsellor_ids')->nullable();
            $table->unsignedSmallInteger('max_members')->nullable();
            $table->boolean('is_anonymous_posting')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['church_id', 'status']);
            $table->index(['is_counsel_group', 'status']);
        });

        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['pending', 'approved', 'banned'])->default('approved');
            $table->timestamps();

            $table->unique(['community_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_members');
        Schema::dropIfExists('communities');
    }
};
