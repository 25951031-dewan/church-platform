<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add custom_profile_fields JSON config to settings
        Schema::table('settings', function (Blueprint $table) {
            $table->json('custom_profile_fields')->nullable()->after('widget_config');
        });

        // Add extra profile fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('church_name')->nullable()->after('phone');
            $table->string('social_id')->nullable()->after('church_name');
            $table->text('spiritual_background')->nullable()->after('social_id');
            $table->json('custom_fields')->nullable()->after('spiritual_background');
            $table->boolean('profile_completed')->default(false)->after('custom_fields');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('custom_profile_fields');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['church_name', 'social_id', 'spiritual_background', 'custom_fields', 'profile_completed']);
        });
    }
};
