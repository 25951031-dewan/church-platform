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
        Schema::create('feed_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_key', 100)->unique(); // 'daily_verse', 'announcements', 'post_feed', etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('component_path'); // React component path
            $table->string('icon', 100)->nullable(); // Icon class/name for UI
            $table->enum('category', ['content', 'interaction', 'navigation', 'custom'])->default('content');
            $table->json('default_config')->nullable(); // Default widget settings/props
            $table->json('schema')->nullable(); // JSON schema for widget configuration
            $table->boolean('is_enabled')->default(true);
            $table->boolean('requires_auth')->default(false); // Widget requires authentication
            $table->json('permissions')->nullable(); // Required permissions to use widget
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['category', 'is_enabled']);
            $table->index(['widget_key', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_widgets');
    }
};
