<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->view('emails.verify-email', [
                'verificationUrl' => $verificationUrl,
                'user' => $notifiable,
            ]);
    }

    protected function verificationUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url');
        
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.email_verification.expire')),
            [
                'id' => $id,
                'hash' => $hash,
            ]
        );

        $parsedUrl = parse_url($temporarySignedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        return sprintf(
            '%s/verify-email?id=%s&hash=%s&expires=%s&signature=%s',
            $frontendUrl,
            $id,
            $hash,
            $queryParams['expires'] ?? '',
            $queryParams['signature'] ?? '',
        );
    }
}
