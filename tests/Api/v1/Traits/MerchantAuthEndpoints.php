<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

use App\Models\MerchantUser;
use Illuminate\Support\Facades\URL;

trait MerchantAuthEndpoints
{
    protected function acceptMerchantInvitationUrl(MerchantUser $merchantUser): string
    {
        return URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->addHours(72),
            [
                'id' => $merchantUser->getKey(),
                'hash' => sha1($merchantUser->email),
            ]
        );
    }
}
