<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id')->nullable()->after('community_id');
            // string, not enum — SQLite does not support enum columns in ALTER TABLE
            $table->string('posted_as')->default('user')->after('entity_id');
            $table->unsignedBigInteger('actor_entity_id')->nullable()->after('posted_as');
            // nullable tri-state: null=pending, true=approved, false=rejected
            $table->boolean('is_approved')->nullable()->after('actor_entity_id');
            $table->boolean('is_pinned')->default(false)->after('is_approved');

            $table->index(['entity_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['entity_id', 'status', 'published_at']);
            $table->dropColumn(['entity_id', 'posted_as', 'actor_entity_id', 'is_approved', 'is_pinned']);
        });
    }
};
