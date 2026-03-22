<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'captcha_provider')) {
                $table->string('captcha_provider')->default('turnstile')->after('value');
            }
            if (! Schema::hasColumn('settings', 'turnstile_site_key')) {
                $table->string('turnstile_site_key')->nullable()->after('captcha_provider');
            }
            if (! Schema::hasColumn('settings', 'turnstile_secret_key')) {
                $table->string('turnstile_secret_key')->nullable()->after('turnstile_site_key');
            }
            if (! Schema::hasColumn('settings', 'captcha_enabled')) {
                $table->boolean('captcha_enabled')->default(false)->after('turnstile_secret_key');
            }
        });

        // Seed captcha row
        DB::table('settings')->insertOrIgnore([
            'key'              => 'captcha',
            'captcha_provider' => 'turnstile',
            'captcha_enabled'  => false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'captcha_provider', 'turnstile_site_key',
                'turnstile_secret_key', 'captcha_enabled',
            ]);
        });

        DB::table('settings')->where('key', 'captcha')->delete();
    }
};
