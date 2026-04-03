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
        // Enhance existing meetings table with additional fields
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('meeting_id', 100)->nullable()->after('meeting_url'); // Platform-specific meeting ID
            $table->string('meeting_password', 50)->nullable()->after('meeting_id'); // Meeting password
            $table->unsignedInteger('max_participants')->nullable()->after('meeting_password');
            $table->boolean('requires_registration')->default(false)->after('max_participants');
            
            $table->index('event_id');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('is_active');
        });
        
        // meeting_registrations (if registration required)
        Schema::create('meeting_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('registered_at')->useCurrent();
            $table->boolean('attended')->default(false);
            $table->timestamp('attended_at')->nullable();
            
            $table->unique(['meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_registrations');
        
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn([
                'event_id',
                'meeting_id',
                'meeting_password',
                'max_participants',
                'requires_registration'
            ]);
        });
    }
};
