<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MerchantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class MerchantUserPasswordResetNotification extends Notification
{
    use Queueable;

    public function via(object $_notifiable): array
    {
        return ['mail'];
    }

    public function toMail(MerchantUser $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.merchant-reset-password', [
                'merchantUser' => $notifiable,
                'resetUrl' => $this->resetUrl($notifiable),
            ]);
    }

    protected function resetUrl(MerchantUser $notifiable): string
    {
        $frontendUrl = config('app.merchant_portal_url');

        $userId = $notifiable->getKey();

        $temporarySignedUrl = URL::temporarySignedRoute(
            'merchant.password.reset',
            Carbon::now()->addHour(),
            [
                'id' => $userId,
            ]
        );

        $parsedUrl = parse_url($temporarySignedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        return sprintf(
            '%s/password/reset?id=%s&expires=%s&signature=%s',
            $frontendUrl,
            $userId,
            $queryParams['expires'] ?? '',
            $queryParams['signature'] ?? '',
        );
    }
}
