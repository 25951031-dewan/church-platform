<?php

namespace Database\Seeders;

use Common\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'notifications.section' => '21',
            'notifications.enabled' => '1',
            'notifications.default_channels' => json_encode([
                'sermon' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
                'prayer' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
                'event' => ['push' => true, 'email' => true, 'sms' => true, 'in_app' => true],
                'group' => ['push' => true, 'email' => false, 'sms' => false, 'in_app' => true],
                'chat' => ['push' => true, 'email' => false, 'sms' => false, 'in_app' => true],
                'meeting' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
                'member' => ['push' => false, 'email' => true, 'sms' => false, 'in_app' => true],
            ]),
            'notifications.onesignal_app_id' => '',
            'notifications.onesignal_rest_api_key' => '',
            'notifications.twilio_sid' => '',
            'notifications.twilio_token' => '',
            'notifications.twilio_from' => '',

            'live_meetings.section' => '22',
            'live_meetings.enabled' => '1',
            'live_meetings.zoom_client_id' => '',
            'live_meetings.zoom_client_secret' => '',
            'live_meetings.zoom_account_id' => '',
            'live_meetings.zoom_webhook_secret' => '',
            'live_meetings.default_platform' => 'zoom',
            'live_meetings.registration_required_default' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
