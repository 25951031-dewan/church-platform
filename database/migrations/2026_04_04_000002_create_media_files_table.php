<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // sanitised display name
            $table->string('original_name');                // original file name from upload
            $table->string('mime_type', 100);
            $table->string('path');                         // storage-relative path
            $table->string('disk', 20)->default('public');  // local / s3
            $table->unsignedBigInteger('size');             // bytes
            $table->foreignId('church_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['church_id', 'created_at']);
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
