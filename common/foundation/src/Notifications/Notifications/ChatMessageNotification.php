<?php

namespace Common\Notifications\Notifications;

class ChatMessageNotification extends BaseNotification
{
    public function __construct(private string $senderName, private string $preview, private int $conversationId) {}

    public function type(): string { return 'chat'; }
    public function title(): string { return "New message from {$this->senderName}"; }
    public function body(): string { return $this->preview; }
    public function actionUrl(): ?string { return "/chat?conversation={$this->conversationId}"; }
}
