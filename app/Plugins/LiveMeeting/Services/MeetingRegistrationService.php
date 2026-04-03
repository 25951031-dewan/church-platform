<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;
use App\Plugins\LiveMeeting\Models\MeetingRegistration;

class MeetingRegistrationService
{
    public function register(Meeting $meeting, int $userId): MeetingRegistration
    {
        return MeetingRegistration::firstOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $userId],
            ['registered_at' => now(), 'attended' => false]
        );
    }

    public function unregister(Meeting $meeting, int $userId): void
    {
        MeetingRegistration::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function checkIn(Meeting $meeting, int $userId): MeetingRegistration
    {
        $registration = $this->register($meeting, $userId);

        $registration->update([
            'attended' => true,
            'attended_at' => now(),
        ]);

        return $registration;
    }
}
