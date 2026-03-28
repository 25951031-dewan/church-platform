<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add verification to churches
        Schema::table('churches', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_featured');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });

        // Add status to church_user pivot for future approval workflow
        Schema::table('church_user', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved'])->default('approved')->after('role');
        });

        // Church pages (custom static pages per church)
        Schema::create('church_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['church_id', 'slug']);
            $table->index(['church_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_pages');

        Schema::table('church_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('churches', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['is_verified', 'verified_at', 'verified_by']);
        });
    }
};
