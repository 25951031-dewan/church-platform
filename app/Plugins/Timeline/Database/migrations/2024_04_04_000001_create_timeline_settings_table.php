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
        Schema::create('timeline_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable(); // NULL = global, specific = church-level
            $table->string('setting_key', 100);
            $table->text('setting_value');
            $table->timestamps();
            
            $table->unique(['church_id', 'setting_key']);
            $table->foreign('church_id')->references('id')->on('churches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_settings');
    }
};