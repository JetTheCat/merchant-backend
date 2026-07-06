<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettlementClawbacks\Pages;

use App\Filament\Resources\SettlementClawbacks\SettlementClawbackResource;
use Filament\Resources\Pages\ListRecords;

class ListSettlementClawbacks extends ListRecords
{
    protected static string $resource = SettlementClawbackResource::class;
}
