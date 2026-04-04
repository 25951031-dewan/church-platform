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
        Schema::create('feed_layouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable(); // NULL = global/default, specific = church-level
            $table->string('name')->default('Main Feed');
            $table->boolean('is_active')->default(true);
            $table->json('layout_data'); // Widget positions and configurations
            $table->json('left_sidebar_config')->nullable(); // Left sidebar widget configuration
            $table->json('right_sidebar_config')->nullable(); // Right sidebar widget configuration
            $table->json('mobile_config')->nullable(); // Mobile-specific settings
            $table->json('responsive_settings')->nullable(); // Responsive breakpoints and behaviors
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Ensure only one active layout per church
            $table->unique(['church_id', 'is_active'], 'unique_church_active_layout')
                  ->where('is_active', true);
            $table->foreign('church_id')->references('id')->on('churches')->onDelete('cascade');
            $table->index(['church_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_layouts');
    }
};
