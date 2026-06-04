<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations\Pages;

use App\Filament\Resources\MerchantLocations\MerchantLocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchantLocation extends ViewRecord
{
    protected static string $resource = MerchantLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
