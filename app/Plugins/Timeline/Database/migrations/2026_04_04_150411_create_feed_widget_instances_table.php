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
        Schema::create('feed_widget_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layout_id');
            $table->unsignedBigInteger('widget_id');
            $table->enum('pane', ['left', 'center', 'right']); // Which pane this widget appears in
            $table->integer('position')->default(0); // Position within the pane
            $table->json('config')->nullable(); // Instance-specific configuration overrides
            $table->json('styling')->nullable(); // Custom CSS classes, dimensions, etc.
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_collapsible')->default(false); // Can user collapse this widget
            $table->boolean('is_collapsed')->default(false); // Default collapsed state
            $table->json('responsive_behavior')->nullable(); // Mobile/tablet specific behavior
            $table->timestamps();
            
            $table->foreign('layout_id')->references('id')->on('feed_layouts')->onDelete('cascade');
            $table->foreign('widget_id')->references('id')->on('feed_widgets')->onDelete('cascade');
            $table->index(['layout_id', 'pane', 'position']);
            $table->index(['layout_id', 'is_visible']);
            $table->unique(['layout_id', 'widget_id', 'pane'], 'unique_widget_per_pane');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_widget_instances');
    }
};
