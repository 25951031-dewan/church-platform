<?php

namespace Common\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'push_title',
        'push_body',
        'email_subject',
        'email_body',
        'sms_body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Available template variables.
     */
    public static function availableVariables(): array
    {
        return [
            '{user_name}' => 'Recipient name',
            '{user_first_name}' => 'Recipient first name',
            '{church_name}' => 'Church name',
            '{sermon_title}' => 'Sermon title',
            '{sermon_speaker}' => 'Sermon speaker',
            '{prayer_title}' => 'Prayer request title',
            '{event_title}' => 'Event title',
            '{event_date}' => 'Event date/time',
            '{group_name}' => 'Group name',
            '{meeting_title}' => 'Meeting title',
            '{meeting_url}' => 'Meeting join URL',
            '{action_url}' => 'Action link',
        ];
    }

    /**
     * Replace template variables with actual values.
     */
    public function render(string $template, array $data): string
    {
        $output = $template;

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $output = str_replace($placeholder, $value, $output);
        }

        return $output;
    }

    /**
     * Get rendered push title.
     */
    public function getPushTitle(array $data): string
    {
        return $this->render($this->push_title ?? '', $data);
    }

    /**
     * Get rendered push body.
     */
    public function getPushBody(array $data): string
    {
        return $this->render($this->push_body ?? '', $data);
    }

    /**
     * Get rendered email subject.
     */
    public function getEmailSubject(array $data): string
    {
        return $this->render($this->email_subject ?? '', $data);
    }

    /**
     * Get rendered email body.
     */
    public function getEmailBody(array $data): string
    {
        return $this->render($this->email_body ?? '', $data);
    }

    /**
     * Get rendered SMS body.
     */
    public function getSmsBody(array $data): string
    {
        return $this->render($this->sms_body ?? '', $data);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
