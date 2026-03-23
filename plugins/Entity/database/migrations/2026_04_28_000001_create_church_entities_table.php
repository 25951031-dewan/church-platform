<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['page', 'community'])->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('profile_image')->nullable();
            // Page-specific
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->json('social_links')->nullable();
            $table->json('action_button')->nullable();
            $table->boolean('is_verified')->default(false);
            // Community-specific (Sprint 8)
            $table->enum('privacy', ['public', 'closed', 'secret'])->default('public');
            $table->boolean('allow_posts')->default(true);
            $table->boolean('require_approval')->default(false);
            // Sub-pages (Sprint 9)
            $table->unsignedBigInteger('parent_entity_id')->nullable();
            // Counters
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_entities');
    }
};
