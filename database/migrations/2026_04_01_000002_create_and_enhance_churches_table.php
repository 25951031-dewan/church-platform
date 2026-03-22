<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Phase 1.2 enhancements
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('members_count')->default(0);
            $table->json('social_links')->nullable();
            $table->json('custom_pages')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['city', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
