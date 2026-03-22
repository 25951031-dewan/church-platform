<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->string('type')->default('post');
            $table->text('body')->nullable();
            $table->json('media')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['published', 'draft', 'removed'])->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['community_id', 'created_at']);
            $table->index(['church_id', 'created_at']);
            $table->index(['parent_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
