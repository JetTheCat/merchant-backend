<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MerchantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class MerchantUserInvitationNotification extends Notification
{
    use Queueable;

    public function via(object $_notifiable): array
    {
        return ['mail'];
    }

    public function toMail(MerchantUser $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You\'ve Been Invited to '.$notifiable->merchant->name)
            ->view('emails.merchant-invitation', [
                'merchantUser' => $notifiable,
                'setupUrl' => $this->setupUrl($notifiable),
            ]);
    }

    protected function setupUrl(MerchantUser $notifiable): string
    {
        $frontendUrl = config('app.merchant_portal_url');

        $hashedEmail = sha1($notifiable->email);

        $temporarySignedUrl = URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->addHours(72),
            [
                'id' => $notifiable->getKey(),
                'hash' => $hashedEmail,
            ]
        );

        $parsedUrl = parse_url($temporarySignedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);

        return sprintf(
            '%s/invitation/setup?id=%s&hash=%s&expires=%s&signature=%s',
            $frontendUrl,
            $notifiable->getKey(),
            $hashedEmail,
            $queryParams['expires'] ?? '',
            $queryParams['signature'] ?? '',
        );
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
