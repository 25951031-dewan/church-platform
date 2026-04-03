<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('counselor_id')->nullable();
            $table->string('subject');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->foreign('counselor_id')->references('id')->on('users')->nullOnDelete();
            $table->index('church_id');
            $table->index('user_id');
            $table->index('counselor_id');
            $table->index('status');
        });

        Schema::create('counseling_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('counseling_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body'); // encrypted at application level
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_messages');
        Schema::dropIfExists('counseling_threads');
    }
};
