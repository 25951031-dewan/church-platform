<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Email provider: smtp, mailchimp, sendgrid, mailgun, ses
            $table->string('mail_provider')->default('smtp')->after('custom_js');

            // SMTP settings
            $table->string('smtp_host')->nullable()->after('mail_provider');
            $table->integer('smtp_port')->default(587)->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->default('tls')->after('smtp_password'); // tls, ssl, none
            $table->string('mail_from_address')->nullable()->after('smtp_encryption');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');

            // Mailchimp settings
            $table->string('mailchimp_api_key')->nullable()->after('mail_from_name');
            $table->string('mailchimp_list_id')->nullable()->after('mailchimp_api_key');
            $table->string('mailchimp_server_prefix')->nullable()->after('mailchimp_list_id'); // e.g., us1, us2

            // SendGrid settings
            $table->string('sendgrid_api_key')->nullable()->after('mailchimp_server_prefix');

            // Mailgun settings
            $table->string('mailgun_domain')->nullable()->after('sendgrid_api_key');
            $table->string('mailgun_secret')->nullable()->after('mailgun_domain');

            // Email workflow settings
            $table->boolean('email_contact_notification')->default(true)->after('mailgun_secret');
            $table->string('email_contact_recipient')->nullable()->after('email_contact_notification');
            $table->boolean('email_newsletter_enabled')->default(true)->after('email_contact_recipient');
            $table->boolean('email_welcome_enabled')->default(false)->after('email_newsletter_enabled');
            $table->text('email_welcome_template')->nullable()->after('email_welcome_enabled');
            $table->text('email_signature')->nullable()->after('email_welcome_template');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_provider', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
                'smtp_encryption', 'mail_from_address', 'mail_from_name',
                'mailchimp_api_key', 'mailchimp_list_id', 'mailchimp_server_prefix',
                'sendgrid_api_key', 'mailgun_domain', 'mailgun_secret',
                'email_contact_notification', 'email_contact_recipient',
                'email_newsletter_enabled', 'email_welcome_enabled',
                'email_welcome_template', 'email_signature',
            ]);
        });
    }
};
