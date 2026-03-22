<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw event log — high write volume, append-only
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();   // SHA-256 of IP — no raw PII stored
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['url', 'created_at']);
            $table->index(['church_id', 'created_at']);
            $table->index(['session_id']);
        });

        // Pre-aggregated daily rollups — fast dashboard reads
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('metric', [
                'page_views',
                'unique_visitors',
                'new_users',
                'active_users',
                'posts',
                'prayers',
                'events',
                'sermons',
            ]);
            $table->unsignedInteger('value')->default(0);
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'metric', 'church_id']);
            $table->index(['date', 'metric', 'church_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('page_views');
    }
};
