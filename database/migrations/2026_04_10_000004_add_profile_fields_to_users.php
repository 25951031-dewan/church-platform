<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('cover_image')->nullable()->after('avatar');
            $table->string('bio', 500)->nullable()->after('cover_image');
            $table->string('location', 100)->nullable()->after('bio');
            $table->string('website')->nullable()->after('location');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar','cover_image','bio','location','website']);
        });
    }
};
