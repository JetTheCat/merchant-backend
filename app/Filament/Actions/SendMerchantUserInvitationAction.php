<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Filament\Resources\MerchantUsers\Services\MerchantUserService;
use App\Models\MerchantUser;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SendMerchantUserInvitationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendInvitation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Send Invitation')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription('This will send a setup email to the merchant owner with a link to set their password.')
            ->visible(fn (MerchantUser $record) => $record->isPendingVerification() && $record->isOwner())
            ->action(fn (MerchantUser $record) => MerchantUserService::sendInvitationIfEligible($record));
    }
}
