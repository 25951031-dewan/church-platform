<?php

namespace Common\Notifications\Notifications;

use App\Plugins\LiveMeeting\Models\Meeting;

class MeetingLiveNotification extends BaseNotification
{
    public function __construct(private Meeting $meeting) {}

    public function type(): string { return 'meeting'; }
    public function title(): string { return 'Meeting is now live'; }
    public function body(): string { return $this->meeting->title; }
    public function actionUrl(): ?string { return "/meetings/{$this->meeting->id}"; }
}
