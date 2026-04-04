<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('church_settings')) {
            return;
        }

        Schema::create('church_settings', function (Blueprint $table) {
            $table->id();
            // Identity
            $table->string('church_name')->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            // Online presence
            $table->string('website_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('tiktok_url')->nullable();
            // Church-specific
            $table->string('pastor_name')->nullable();
            $table->string('pastor_title')->nullable();
            $table->text('service_times')->nullable();
            $table->text('about_text')->nullable();
            $table->text('mission_statement')->nullable();
            $table->text('vision_statement')->nullable();
            // Branding / media
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            // SMTP / email
            $table->string('mail_provider')->default('smtp');
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            // Auth providers
            $table->boolean('auth_google_enabled')->default(false);
            $table->string('auth_google_client_id')->nullable();
            $table->string('auth_google_client_secret')->nullable();
            $table->boolean('auth_facebook_enabled')->default(false);
            $table->string('auth_facebook_client_id')->nullable();
            $table->string('auth_facebook_client_secret')->nullable();
            // Misc
            $table->text('footer_text')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->json('widget_config')->nullable();
            $table->json('custom_profile_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_settings');
    }
};
