<?php

namespace App\Filament\Resources\MerchantUsers\Pages;

use App\Filament\Resources\MerchantUsers\MerchantUserResource;
use Filament\Resources\Pages\ListRecords;

class ListMerchantUsers extends ListRecords
{
    protected static string $resource = MerchantUserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
