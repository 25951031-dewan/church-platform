<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['system', 'church', 'custom'])->default('custom');
            $table->unsignedInteger('level')->default(10);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('church_id')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('church_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
