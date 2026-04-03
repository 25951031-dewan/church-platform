<?php

namespace Common\Notifications\Notifications;

use App\Plugins\Sermons\Models\Sermon;

class NewSermonNotification extends BaseNotification
{
    public function __construct(private Sermon $sermon) {}

    public function type(): string { return 'sermon'; }
    public function title(): string { return 'New sermon available'; }
    public function body(): string { return $this->sermon->title; }
    public function actionUrl(): ?string { return "/sermons/{$this->sermon->id}"; }
}
