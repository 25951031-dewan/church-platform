<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('primary_color')->default('#4F46E5'); // Indigo default
            $table->string('secondary_color')->default('#10B981'); // Emerald default
            $table->json('active_widgets')->nullable(); // Store which widgets are on/off
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'active_widgets']);
        });
    }
};