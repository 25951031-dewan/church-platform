<?php

namespace Common\Notifications\Notifications;

class GroupPostNotification extends BaseNotification
{
    public function __construct(private string $groupName, private string $postTitle, private int $groupId) {}

    public function type(): string { return 'group'; }
    public function title(): string { return "New post in {$this->groupName}"; }
    public function body(): string { return $this->postTitle; }
    public function actionUrl(): ?string { return "/groups/{$this->groupId}"; }
}
