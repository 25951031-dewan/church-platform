<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add category and pastoral flag to prayer_requests
        Schema::table('prayer_requests', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('is_urgent');
            $table->boolean('pastoral_flag')->default(false)->after('category');
            $table->foreignId('flagged_by')->nullable()->after('pastoral_flag')
                ->constrained('users')->nullOnDelete();
        });

        // Prayer updates (progress notes from the requester)
        Schema::create('prayer_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prayer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->enum('status_change', ['still_praying', 'partially_answered', 'answered', 'no_change'])
                ->default('no_change');
            $table->timestamps();

            $table->index(['prayer_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_updates');

        Schema::table('prayer_requests', function (Blueprint $table) {
            $table->dropForeign(['flagged_by']);
            $table->dropColumn(['category', 'pastoral_flag', 'flagged_by']);
        });
    }
};
