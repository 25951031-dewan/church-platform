<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feed_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable(); // NULL = global, specific = church-level
            $table->string('name');
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->text('description')->nullable();
            $table->string('type', 50)->default('timeline'); // timeline, announcement, prayer, etc.
            $table->json('config')->nullable(); // Channel-specific configuration
            $table->boolean('is_public')->default(true); // Public vs members-only
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_posts')->default(true); // Can users post to this channel
            $table->boolean('moderate_posts')->default(false); // Require approval for posts
            $table->json('allowed_content_types')->nullable(); // ['text', 'image', 'video', 'link']
            $table->integer('max_file_size')->nullable(); // In KB
            $table->json('permissions')->nullable(); // Who can view, post, moderate
            $table->unsignedBigInteger('created_by')->nullable(); // User who created channel
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('church_id')->references('id')->on('churches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['church_id', 'is_active', 'is_public']);
            $table->index(['type', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_channels');
    }
};
