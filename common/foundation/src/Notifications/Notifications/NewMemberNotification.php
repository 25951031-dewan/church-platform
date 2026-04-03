<?php

namespace Common\Notifications\Notifications;

use Common\Auth\Models\User;

class NewMemberNotification extends BaseNotification
{
    public function __construct(private User $member) {}

    public function type(): string { return 'member'; }
    public function title(): string { return 'New member joined'; }
    public function body(): string { return $this->member->name . ' just joined.'; }
    public function actionUrl(): ?string { return '/admin/users'; }
}
