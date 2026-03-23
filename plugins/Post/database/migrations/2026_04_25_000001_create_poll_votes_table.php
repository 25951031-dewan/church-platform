<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('option_id', 40);
            $table->timestamp('created_at')->useCurrent();

            // Prevents duplicate vote per option. For allow_multiple=false, app checks (post_id, user_id).
            $table->unique(['post_id', 'user_id', 'option_id']);
            $table->index(['post_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
