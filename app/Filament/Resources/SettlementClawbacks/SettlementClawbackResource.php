<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettlementClawbacks;

use App\Filament\Resources\SettlementClawbacks\Pages\ListSettlementClawbacks;
use App\Filament\Resources\SettlementClawbacks\Pages\ViewSettlementClawback;
use App\Filament\Resources\SettlementClawbacks\Schemas\SettlementClawbackInfolist;
use App\Filament\Resources\SettlementClawbacks\Tables\SettlementClawbacksTable;
use App\Models\SettlementClawback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettlementClawbackResource extends Resource
{
    protected static ?string $model = SettlementClawback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?string $navigationLabel = 'Clawbacks';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return SettlementClawbackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementClawbacksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettlementClawbacks::route('/'),
            'view' => ViewSettlementClawback::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
