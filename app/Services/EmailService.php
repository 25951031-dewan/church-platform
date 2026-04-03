<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected ?Setting $settings;

    public function __construct()
    {
        $this->settings = Setting::first();
        $this->configureMailer();
    }

    /**
     * Configure Laravel's mailer based on stored email settings.
     */
    protected function configureMailer(): void
    {
        if (!$this->settings) {
            return;
        }

        $provider = $this->settings->mail_provider ?? 'smtp';

        switch ($provider) {
            case 'smtp':
                $this->configureSMTP();
                break;
            case 'sendgrid':
                $this->configureSendGrid();
                break;
            case 'mailgun':
                $this->configureMailgun();
                break;
            case 'mailchimp':
                // Mailchimp Transactional (Mandrill) uses SMTP
                $this->configureMailchimpTransactional();
                break;
        }

        // Set from address/name
        if ($this->settings->mail_from_address) {
            Config::set('mail.from.address', $this->settings->mail_from_address);
        }
        if ($this->settings->mail_from_name) {
            Config::set('mail.from.name', $this->settings->mail_from_name);
        }
    }

    protected function configureSMTP(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $this->settings->smtp_host ?? '127.0.0.1');
        Config::set('mail.mailers.smtp.port', $this->settings->smtp_port ?? 587);
        Config::set('mail.mailers.smtp.username', $this->settings->smtp_username);
        Config::set('mail.mailers.smtp.password', $this->settings->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $this->settings->smtp_encryption === 'none' ? null : ($this->settings->smtp_encryption ?? 'tls'));
    }

    protected function configureSendGrid(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.sendgrid.net');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', 'apikey');
        Config::set('mail.mailers.smtp.password', $this->settings->sendgrid_api_key);
        Config::set('mail.mailers.smtp.encryption', 'tls');
    }

    protected function configureMailgun(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.mailgun.org');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', 'postmaster@' . ($this->settings->mailgun_domain ?? ''));
        Config::set('mail.mailers.smtp.password', $this->settings->mailgun_secret);
        Config::set('mail.mailers.smtp.encryption', 'tls');
    }

    protected function configureMailchimpTransactional(): void
    {
        // Mailchimp Transactional (Mandrill) SMTP
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.mandrillapp.com');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', $this->settings->mail_from_address ?? 'apikey');
        Config::set('mail.mailers.smtp.password', $this->settings->mailchimp_api_key);
        Config::set('mail.mailers.smtp.encryption', 'tls');
    }

    /**
     * Send an email using the configured provider.
     */
    public function send(string $to, string $subject, string $body, ?string $toName = null): bool
    {
        $signature = $this->settings->email_signature ?? '';
        $fullBody = $body . ($signature ? "\n\n" . $signature : '');

        Mail::raw($fullBody, function ($message) use ($to, $subject, $toName) {
            $message->to($to, $toName)->subject($subject);
        });

        return true;
    }

    /**
     * Send contact form notification to admin.
     */
    public function sendContactNotification(array $contactData): bool
    {
        if (!$this->settings || !$this->settings->email_contact_notification) {
            return false;
        }

        $recipient = $this->settings->email_contact_recipient
                  ?? $this->settings->email
                  ?? $this->settings->mail_from_address;

        if (!$recipient) {
            Log::warning('EmailService: No contact notification recipient configured.');
            return false;
        }

        $body = "New Contact Form Submission\n"
            . "----------------------------\n"
            . "Name: " . ($contactData['name'] ?? 'N/A') . "\n"
            . "Email: " . ($contactData['email'] ?? 'N/A') . "\n"
            . "Phone: " . ($contactData['phone'] ?? 'N/A') . "\n"
            . "Subject: " . ($contactData['subject'] ?? 'N/A') . "\n"
            . "----------------------------\n\n"
            . ($contactData['message'] ?? '');

        return $this->send(
            $recipient,
            'New Contact: ' . ($contactData['subject'] ?? 'Website Contact'),
            $body
        );
    }

    /**
     * Send a welcome email to a new newsletter subscriber.
     */
    public function sendWelcomeEmail(string $email, ?string $name = null, ?string $unsubscribeUrl = null): bool
    {
        if (!$this->settings || !$this->settings->email_welcome_enabled) {
            return false;
        }

        $churchName = $this->settings->church_name ?? 'Our Church';
        $template = $this->settings->email_welcome_template
            ?? "Welcome to {$churchName}!\n\nThank you for subscribing to our newsletter. You'll receive updates about upcoming events, sermons, and more.";

        // Replace template variables
        $body = str_replace(
            ['{name}', '{church_name}', '{email}'],
            [$name ?? 'Friend', $churchName, $email],
            $template
        );

        if ($unsubscribeUrl) {
            $body .= "\n\n---\nTo unsubscribe, visit: " . $unsubscribeUrl;
        }

        return $this->send($email, "Welcome to {$churchName} Newsletter!", $body, $name);
    }

    /**
     * Send newsletter to a subscriber.
     */
    public function sendNewsletter(string $email, ?string $name, string $subject, string $body, ?string $unsubscribeUrl = null): bool
    {
        $fullBody = $body;
        if ($unsubscribeUrl) {
            $fullBody .= "\n\n---\nTo unsubscribe, visit: " . $unsubscribeUrl;
        }

        return $this->send($email, $subject, $fullBody, $name);
    }

    /**
     * Add subscriber to Mailchimp audience list.
     */
    public function addToMailchimpList(string $email, ?string $name = null): bool
    {
        if (!$this->settings || !$this->settings->mailchimp_api_key || !$this->settings->mailchimp_list_id) {
            return false;
        }

        $server = $this->settings->mailchimp_server_prefix ?? 'us1';
        $listId = $this->settings->mailchimp_list_id;
        $apiKey = $this->settings->mailchimp_api_key;

        $subscriberHash = md5(strtolower($email));
        $url = "https://{$server}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

        $data = [
            'email_address' => $email,
            'status_if_new' => 'subscribed',
            'status'        => 'subscribed',
        ];

        if ($name) {
            $parts = explode(' ', $name, 2);
            $data['merge_fields'] = [
                'FNAME' => $parts[0],
                'LNAME' => $parts[1] ?? '',
            ];
        }

        $response = Http::withBasicAuth('anystring', $apiKey)
            ->put($url, $data);

        if ($response->failed()) {
            Log::warning('Mailchimp subscription failed', [
                'email'    => $email,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Remove subscriber from Mailchimp audience list.
     */
    public function removeFromMailchimpList(string $email): bool
    {
        if (!$this->settings || !$this->settings->mailchimp_api_key || !$this->settings->mailchimp_list_id) {
            return false;
        }

        $server = $this->settings->mailchimp_server_prefix ?? 'us1';
        $listId = $this->settings->mailchimp_list_id;
        $apiKey = $this->settings->mailchimp_api_key;

        $subscriberHash = md5(strtolower($email));
        $url = "https://{$server}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

        $response = Http::withBasicAuth('anystring', $apiKey)
            ->patch($url, ['status' => 'unsubscribed']);

        return $response->successful();
    }

    /**
     * Send a test email to verify configuration.
     */
    public function sendTestEmail(string $to): bool
    {
        $provider = $this->settings->mail_provider ?? 'smtp';
        $churchName = $this->settings->church_name ?? 'Church Platform';

        $body = "This is a test email from {$churchName}.\n\n"
            . "Your email configuration is working correctly!\n\n"
            . "Provider: " . strtoupper($provider) . "\n"
            . "Sent at: " . now()->toDateTimeString();

        return $this->send($to, "[{$churchName}] Test Email - Configuration Verified", $body);
    }

    /**
     * Check if email sending is properly configured.
     */
    public function isConfigured(): bool
    {
        if (!$this->settings) {
            return false;
        }

        $provider = $this->settings->mail_provider ?? 'smtp';

        return match ($provider) {
            'smtp'     => !empty($this->settings->smtp_host),
            'mailchimp' => !empty($this->settings->mailchimp_api_key),
            'sendgrid' => !empty($this->settings->sendgrid_api_key),
            'mailgun'  => !empty($this->settings->mailgun_domain) && !empty($this->settings->mailgun_secret),
            default    => false,
        };
    }
}
