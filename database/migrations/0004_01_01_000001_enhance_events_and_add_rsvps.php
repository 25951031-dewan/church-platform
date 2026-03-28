<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add meeting_url to events for Zoom/Meet links
        Schema::table('events', function (Blueprint $table) {
            $table->string('meeting_url')->nullable()->after('registration_link');
        });

        // Authenticated member RSVPs (separate from guest registrations)
        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['attending', 'interested', 'not_going'])->default('attending');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rsvps');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('meeting_url');
        });
    }
};
