<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('location', 300)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('meeting_url')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('events')->nullOnDelete();
            $table->enum('category', ['worship', 'youth', 'outreach', 'study', 'fellowship', 'other'])->default('other');
            $table->unsignedInteger('max_attendees')->nullable();
            $table->unsignedInteger('going_count')->default(0);
            $table->unsignedInteger('maybe_count')->default(0);
            $table->enum('status', ['published', 'draft', 'cancelled'])->default('published');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['church_id', 'start_at']);
            $table->index(['community_id', 'start_at']);
            $table->index(['start_at', 'status']);
            $table->index(['reminder_sent_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
