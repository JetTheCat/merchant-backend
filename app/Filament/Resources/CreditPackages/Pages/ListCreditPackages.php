<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditPackages\Pages;

use App\Filament\Resources\CreditPackages\CreditPackageResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditPackages extends ListRecords
{
    protected static string $resource = CreditPackageResource::class;
}
