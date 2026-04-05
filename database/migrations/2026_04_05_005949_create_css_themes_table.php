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
        Schema::create('css_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('site'); // site | user
            $table->boolean('is_dark')->default(true);
            $table->boolean('default_dark')->default(false);
            $table->boolean('default_light')->default(false);
            $table->json('values'); // CSS variable key-value pairs
            $table->json('font')->nullable(); // Font configuration
            $table->timestamps();
            
            // Only one default theme per mode
            $table->unique(['default_dark'], 'unique_default_dark')->where('default_dark', true);
            $table->unique(['default_light'], 'unique_default_light')->where('default_light', true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('css_themes');
    }
};
