<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sermon_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('church_id');
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('church_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('church_id');
        });

        // Add normalized FKs to sermons (alongside existing string columns)
        Schema::table('sermons', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('series')
                ->constrained('sermon_series')->nullOnDelete();
            $table->foreignId('speaker_id')->nullable()->after('speaker')
                ->constrained('speakers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sermons', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropForeign(['speaker_id']);
            $table->dropColumn(['series_id', 'speaker_id']);
        });

        Schema::dropIfExists('speakers');
        Schema::dropIfExists('sermon_series');
    }
};
