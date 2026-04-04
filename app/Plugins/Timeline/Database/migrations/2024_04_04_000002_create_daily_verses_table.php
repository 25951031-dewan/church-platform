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
        Schema::create('daily_verses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable(); // NULL = global, specific = church-level
            $table->date('verse_date');
            $table->string('reference'); // e.g., "John 3:16"
            $table->text('text'); // Actual verse text
            $table->text('translation')->nullable(); // NIV, ESV, etc.
            $table->text('reflection')->nullable(); // Optional daily reflection/devotion
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['church_id', 'verse_date']);
            $table->foreign('church_id')->references('id')->on('churches')->onDelete('cascade');
            $table->index('verse_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_verses');
    }
};