<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('builder_data')->nullable()->after('content');
            $table->longText('builder_html')->nullable()->after('builder_data');
            $table->text('builder_css')->nullable()->after('builder_html');
            $table->boolean('use_builder')->default(false)->after('builder_css');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['builder_data', 'builder_html', 'builder_css', 'use_builder']);
        });
    }
};
