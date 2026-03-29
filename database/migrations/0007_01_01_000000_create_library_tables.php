<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('cover')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->integer('pages_count')->nullable();
            $table->year('published_year')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_categories');
    }
};
