<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add appearance/theme columns to settings
        Schema::table('settings', function (Blueprint $table) {
            // Appearance / Theme settings (BeMusic-style)
            if (!Schema::hasColumn('settings', 'default_theme')) {
                $table->string('default_theme', 20)->default('light')->after('widget_config');
            }
            if (!Schema::hasColumn('settings', 'themes_config')) {
                $table->json('themes_config')->nullable()->after('default_theme');
            }

            // Mobile theme settings
            if (!Schema::hasColumn('settings', 'mobile_theme_enabled')) {
                $table->boolean('mobile_theme_enabled')->default(true)->after('themes_config');
            }
            if (!Schema::hasColumn('settings', 'mobile_theme_config')) {
                $table->json('mobile_theme_config')->nullable()->after('mobile_theme_enabled');
            }

            // PWA settings
            if (!Schema::hasColumn('settings', 'pwa_enabled')) {
                $table->boolean('pwa_enabled')->default(true)->after('mobile_theme_config');
            }
            if (!Schema::hasColumn('settings', 'pwa_name')) {
                $table->string('pwa_name')->nullable()->after('pwa_enabled');
            }
            if (!Schema::hasColumn('settings', 'pwa_short_name')) {
                $table->string('pwa_short_name', 50)->nullable()->after('pwa_name');
            }
            if (!Schema::hasColumn('settings', 'pwa_description')) {
                $table->text('pwa_description')->nullable()->after('pwa_short_name');
            }
            if (!Schema::hasColumn('settings', 'pwa_theme_color')) {
                $table->string('pwa_theme_color', 20)->default('#4F46E5')->after('pwa_description');
            }
            if (!Schema::hasColumn('settings', 'pwa_background_color')) {
                $table->string('pwa_background_color', 20)->default('#ffffff')->after('pwa_theme_color');
            }
            if (!Schema::hasColumn('settings', 'pwa_display')) {
                $table->string('pwa_display', 20)->default('standalone')->after('pwa_background_color');
            }
            if (!Schema::hasColumn('settings', 'pwa_orientation')) {
                $table->string('pwa_orientation', 20)->default('any')->after('pwa_display');
            }
            if (!Schema::hasColumn('settings', 'pwa_icon_192')) {
                $table->string('pwa_icon_192')->nullable()->after('pwa_orientation');
            }
            if (!Schema::hasColumn('settings', 'pwa_icon_512')) {
                $table->string('pwa_icon_512')->nullable()->after('pwa_icon_192');
            }

            // Auth provider settings (configurable from admin)
            if (!Schema::hasColumn('settings', 'auth_google_enabled')) {
                $table->boolean('auth_google_enabled')->default(false)->after('pwa_icon_512');
            }
            if (!Schema::hasColumn('settings', 'auth_google_client_id')) {
                $table->string('auth_google_client_id', 500)->nullable()->after('auth_google_enabled');
            }
            if (!Schema::hasColumn('settings', 'auth_google_client_secret')) {
                $table->string('auth_google_client_secret', 500)->nullable()->after('auth_google_client_id');
            }
            if (!Schema::hasColumn('settings', 'auth_facebook_enabled')) {
                $table->boolean('auth_facebook_enabled')->default(false)->after('auth_google_client_secret');
            }
            if (!Schema::hasColumn('settings', 'auth_facebook_client_id')) {
                $table->string('auth_facebook_client_id', 500)->nullable()->after('auth_facebook_enabled');
            }
            if (!Schema::hasColumn('settings', 'auth_facebook_client_secret')) {
                $table->string('auth_facebook_client_secret', 500)->nullable()->after('auth_facebook_client_id');
            }

            // File storage settings
            if (!Schema::hasColumn('settings', 'storage_driver')) {
                $table->string('storage_driver', 20)->default('local')->after('auth_facebook_client_secret');
            }
            if (!Schema::hasColumn('settings', 'storage_s3_key')) {
                $table->string('storage_s3_key', 500)->nullable()->after('storage_driver');
            }
            if (!Schema::hasColumn('settings', 'storage_s3_secret')) {
                $table->string('storage_s3_secret', 500)->nullable()->after('storage_s3_key');
            }
            if (!Schema::hasColumn('settings', 'storage_s3_region')) {
                $table->string('storage_s3_region', 50)->nullable()->after('storage_s3_secret');
            }
            if (!Schema::hasColumn('settings', 'storage_s3_bucket')) {
                $table->string('storage_s3_bucket')->nullable()->after('storage_s3_region');
            }
            if (!Schema::hasColumn('settings', 'max_upload_size')) {
                $table->integer('max_upload_size')->default(10)->after('storage_s3_bucket');
            }
            if (!Schema::hasColumn('settings', 'allowed_file_types')) {
                $table->string('allowed_file_types', 500)->default('jpg,jpeg,png,gif,webp,svg,pdf,mp3,mp4')->after('max_upload_size');
            }

            // Cache/performance settings
            if (!Schema::hasColumn('settings', 'cache_driver')) {
                $table->string('cache_driver', 20)->default('file')->after('allowed_file_types');
            }
            if (!Schema::hasColumn('settings', 'cache_ttl')) {
                $table->integer('cache_ttl')->default(3600)->after('cache_driver');
            }
            if (!Schema::hasColumn('settings', 'enable_page_cache')) {
                $table->boolean('enable_page_cache')->default(false)->after('cache_ttl');
            }
            if (!Schema::hasColumn('settings', 'enable_minification')) {
                $table->boolean('enable_minification')->default(false)->after('enable_page_cache');
            }
            if (!Schema::hasColumn('settings', 'cdn_url')) {
                $table->string('cdn_url', 500)->nullable()->after('enable_minification');
            }

            // Logging settings
            if (!Schema::hasColumn('settings', 'log_channel')) {
                $table->string('log_channel', 20)->default('daily')->after('cdn_url');
            }

            // Queue settings
            if (!Schema::hasColumn('settings', 'queue_driver')) {
                $table->string('queue_driver', 20)->default('sync')->after('log_channel');
            }
        });

        // Create CSS themes table (BeMusic-style)
        if (!Schema::hasTable('css_themes')) {
            Schema::create('css_themes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_dark')->default(false);
                $table->boolean('is_default')->default(false);
                $table->json('colors');
                $table->text('custom_css')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Create localizations table (BeMusic-style)
        if (!Schema::hasTable('localizations')) {
            Schema::create('localizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('language', 10)->unique();
                $table->json('lines')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('css_themes');
        Schema::dropIfExists('localizations');

        $columns = [
            'default_theme', 'themes_config', 'mobile_theme_enabled', 'mobile_theme_config',
            'pwa_enabled', 'pwa_name', 'pwa_short_name', 'pwa_description', 'pwa_theme_color',
            'pwa_background_color', 'pwa_display', 'pwa_orientation', 'pwa_icon_192', 'pwa_icon_512',
            'auth_google_enabled', 'auth_google_client_id', 'auth_google_client_secret',
            'auth_facebook_enabled', 'auth_facebook_client_id', 'auth_facebook_client_secret',
            'storage_driver', 'storage_s3_key', 'storage_s3_secret', 'storage_s3_region',
            'storage_s3_bucket', 'max_upload_size', 'allowed_file_types',
            'cache_driver', 'cache_ttl', 'enable_page_cache', 'enable_minification', 'cdn_url',
            'log_channel', 'queue_driver',
        ];

        Schema::table('settings', function (Blueprint $table) use ($columns) {
            $existing = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $existing[] = $col;
                }
            }
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
