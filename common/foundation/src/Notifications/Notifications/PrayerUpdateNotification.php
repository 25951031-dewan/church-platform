<?php

namespace Common\Notifications\Notifications;

use App\Plugins\Prayer\Models\PrayerUpdate;

class PrayerUpdateNotification extends BaseNotification
{
    public function __construct(private PrayerUpdate $update) {}

    public function type(): string { return 'prayer'; }
    public function title(): string { return 'Prayer request updated'; }
    public function body(): string { return \Illuminate\Support\Str::limit((string) $this->update->content, 120); }
    public function actionUrl(): ?string { return "/prayers/{$this->update->prayer_request_id}"; }
}
