<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->enum('type', ['text', 'photo', 'video', 'announcement'])->default('text');
            $table->text('content')->nullable();
            $table->enum('visibility', ['public', 'members', 'private'])->default('public');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('church_id');
            $table->index('visibility');
            $table->index('is_pinned');
            $table->index('published_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
