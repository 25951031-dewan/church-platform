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
        Schema::create('channel_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('channelable_id'); // The item ID (post, event, etc.)
            $table->string('channelable_type'); // The item type (App\Plugins\Timeline\Models\Post, etc.)
            $table->integer('position')->default(0); // Order within channel
            $table->boolean('is_featured')->default(false); // Featured/pinned items
            $table->json('metadata')->nullable(); // Additional data (visibility rules, etc.)
            $table->timestamps();
            
            $table->foreign('channel_id')->references('id')->on('feed_channels')->onDelete('cascade');
            $table->index(['channel_id', 'channelable_type', 'position']);
            $table->index(['channelable_id', 'channelable_type']);
            $table->index(['channel_id', 'is_featured']);
            $table->unique(['channel_id', 'channelable_id', 'channelable_type'], 'unique_channel_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_items');
    }
};
