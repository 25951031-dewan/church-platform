<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Message reactions (emoji reactions on messages)
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('emoji', 32); // emoji character or shortcode
            $table->timestamp('created_at');

            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index(['message_id', 'emoji']);
        });

        // Message replies (threaded conversations)
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_id')->nullable()->after('file_entry_id');
            $table->boolean('is_edited')->default(false)->after('reply_to_id');
            $table->timestamp('edited_at')->nullable()->after('is_edited');
            
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('messages')
                ->onDelete('set null');
        });

        // Read receipts (per-message read status)
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');

            $table->unique(['message_id', 'user_id']);
            $table->index(['message_id', 'read_at']);
        });

        // Pinned messages
        Schema::create('pinned_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('pinned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('pinned_at');

            $table->unique(['conversation_id', 'message_id']);
        });

        // Add conversation settings
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('name');
            $table->text('description')->nullable()->after('avatar');
            $table->json('settings')->nullable()->after('description');
        });

        // Add role to conversation_user (for group admins)
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->string('role')->default('member')->after('is_muted'); // member, admin, owner
            $table->string('nickname')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pinned_messages');
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('message_reactions');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['reply_to_id', 'is_edited', 'edited_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'description', 'settings']);
        });

        Schema::table('conversation_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'nickname']);
        });
    }
};
