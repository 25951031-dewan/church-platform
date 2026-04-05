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
        Schema::create('landing_page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // hero-simple-centered, features-grid, cta-simple-centered, etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('config')->nullable(); // Section-specific settings (heading, text, image, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_sections');
    }
};
