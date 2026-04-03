<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // notifications table (Laravel default with customizations)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('read_at');
            $table->index('created_at');
        });
        
        // notification_preferences (per-user, per-type channel settings)
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type', 100); // 'sermon', 'prayer', 'event', 'group', 'chat', 'meeting'
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false); // Off by default (costs money)
            $table->boolean('in_app_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'notification_type']);
        });
        
        // notification_logs (admin delivery tracking)
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id'); // Links to notifications.id
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20); // 'push', 'email', 'sms', 'database'
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced'])->default('pending');
            $table->text('provider_response')->nullable(); // OneSignal/Twilio response
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('notification_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('channel');
        });
        
        // notification_templates (admin-customizable templates)
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->unique(); // 'new_sermon', 'prayer_update', etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('push_title')->nullable(); // Push notification title template
            $table->text('push_body')->nullable(); // Push notification body template
            $table->string('email_subject')->nullable(); // Email subject template
            $table->text('email_body')->nullable(); // Email body (Blade/HTML)
            $table->string('sms_body', 160)->nullable(); // SMS body (160 char limit)
            $table->json('variables')->nullable(); // Available variables: {user_name}, {sermon_title}, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // push_subscriptions (OneSignal player IDs per user)
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('player_id')->unique(); // OneSignal player ID
            $table->string('device_type', 20)->nullable(); // 'web', 'ios', 'android'
            $table->string('device_name')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
