<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations;

use App\Filament\Resources\MerchantLocations\Pages\EditMerchantLocation;
use App\Filament\Resources\MerchantLocations\Pages\ListMerchantLocations;
use App\Filament\Resources\MerchantLocations\Pages\ViewMerchantLocation;
use App\Filament\Resources\MerchantLocations\Schemas\MerchantLocationForm;
use App\Filament\Resources\MerchantLocations\Schemas\MerchantLocationInfolist;
use App\Filament\Resources\MerchantLocations\Tables\MerchantLocationsTable;
use App\Models\MerchantLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantLocationResource extends Resource
{
    protected static ?string $model = MerchantLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MerchantLocationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantLocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantLocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchantLocations::route('/'),
            'view' => ViewMerchantLocation::route('/{record}'),
            'edit' => EditMerchantLocation::route('/{record}/edit'),
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
