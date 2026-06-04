<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations\Pages;

use App\Filament\Resources\MerchantLocations\MerchantLocationResource;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMerchantLocation extends EditRecord
{
    protected static string $resource = MerchantLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            RestoreAction::make(),
        ];
    }
}
