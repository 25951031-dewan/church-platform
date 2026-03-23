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
            $table->enum('posted_as', ['user', 'entity'])->default('user')->after('entity_id');
            $table->unsignedBigInteger('actor_entity_id')->nullable()->after('posted_as');
            $table->boolean('is_approved')->default(true)->after('actor_entity_id');
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
