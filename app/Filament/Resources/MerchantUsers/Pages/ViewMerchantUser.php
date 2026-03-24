<?php

namespace App\Filament\Resources\MerchantUsers\Pages;

use App\Filament\Actions\SendMerchantUserInvitationAction;
use App\Filament\Resources\MerchantUsers\MerchantUserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchantUser extends ViewRecord
{
    protected static string $resource = MerchantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SendMerchantUserInvitationAction::make(),
            EditAction::make(),
        ];
    }
}
