<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantUsers\Services;

use App\Jobs\SendMerchantUserInvitationNotificationJob;
use App\Models\MerchantUser;

class MerchantUserService
{
    public static function sendInvitationIfEligible(MerchantUser $merchantUser): void
    {
        if ($merchantUser->isPendingVerification() && $merchantUser->isOwner()) {
            SendMerchantUserInvitationNotificationJob::dispatch($merchantUser);
        }
    }
}
