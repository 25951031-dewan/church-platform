<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('church_entities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['pending', 'approved', 'declined', 'banned'])->default('approved');
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'user_id']);
            $table->index(['entity_id', 'status']);
            $table->index(['entity_id', 'role']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_members');
    }
};
