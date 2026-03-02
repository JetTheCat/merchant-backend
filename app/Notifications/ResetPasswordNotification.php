<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.reset-password', [
                'user' => $notifiable,
                'resetUrl' => $this->resetUrl($notifiable),
            ]);
    }

    protected function resetUrl($notifiable): string
    {
        $frontendUrl = config('app.frontend_url');

        $userId = $notifiable->getKey();

        $temporarySignedUrl = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addHour(),
            [
                'id' => $userId,
            ]
        );

        $parsedUrl = parse_url($temporarySignedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        return sprintf(
            '%s/reset-password?id=%s&expires=%s&signature=%s',
            $frontendUrl,
            $userId,
            $queryParams['expires'] ?? '',
            $queryParams['signature'] ?? '',
        );
    }
}
