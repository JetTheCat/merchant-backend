<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations\Pages;

use App\Filament\Resources\MerchantLocations\MerchantLocationResource;
use Filament\Resources\Pages\ListRecords;

class ListMerchantLocations extends ListRecords
{
    protected static string $resource = MerchantLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
