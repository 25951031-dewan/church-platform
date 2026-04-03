<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('church_name')->default('My Church');
            $table->string('tagline', 500)->nullable();
            $table->text('description')->nullable();
            $table->text('church_description')->nullable();
            $table->string('email')->nullable();
            $table->string('church_email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('church_phone')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('church_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('youtube_url', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('twitter_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();
            $table->text('service_times')->nullable();
            $table->string('pastor_name')->nullable();
            $table->string('pastor_title')->nullable();
            $table->text('pastor_bio')->nullable();
            $table->string('pastor_photo')->nullable();
            $table->text('about_text')->nullable();
            $table->text('mission_statement')->nullable();
            $table->text('vision_statement')->nullable();
            $table->string('logo')->nullable();
            $table->string('church_logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('church_banner')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color', 20)->default('#4F46E5');
            $table->string('secondary_color', 20)->default('#7C3AED');
            $table->text('footer_text')->nullable();
            $table->text('google_maps_embed')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('meta_title', 70)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->text('google_analytics_id')->nullable();
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->string('donation_link')->nullable();
            $table->json('theme_config')->nullable();
            $table->json('widget_config')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
