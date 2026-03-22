<?php

namespace App\Filament\Resources\MerchantUsers\Pages;

use App\Filament\Resources\MerchantUsers\MerchantUserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchantUser extends EditRecord
{
    protected static string $resource = MerchantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
