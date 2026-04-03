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
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // General Settings (Tab 1)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('service_hours')->nullable(); // [{day, time, label}]
            $table->string('denomination')->nullable();
            $table->integer('year_founded')->nullable();

            // About & History (Tab 2)
            $table->text('short_description')->nullable();
            $table->longText('history')->nullable(); // Rich text HTML
            $table->text('mission_statement')->nullable();
            $table->text('vision_statement')->nullable();
            $table->json('documents')->nullable(); // [{name, file_path, uploaded_at}]

            // Appearance (Tab 3)
            $table->string('logo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('primary_color', 20)->default('#4F46E5');
            $table->string('secondary_color', 20)->nullable();

            // SEO & Social (Tab 4)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('tiktok_url')->nullable();

            // Admin relationship
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Stats
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
        });

        // Add church_id to users for Church Admin assignment
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->after('role_id')->constrained('churches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
        });
        Schema::dropIfExists('churches');
    }
};
