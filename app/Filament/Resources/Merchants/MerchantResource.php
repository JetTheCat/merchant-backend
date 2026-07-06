<?php

declare(strict_types=1);

namespace App\Filament\Resources\Merchants;

use App\Filament\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Resources\Merchants\RelationManagers\CreditPackagesRelationManager;
use App\Filament\Resources\Merchants\RelationManagers\MerchantLocationsRelationManager;
use App\Filament\Resources\Merchants\RelationManagers\MerchantUsersRelationManager;
use App\Filament\Resources\Merchants\RelationManagers\SettlementClawbacksRelationManager;
use App\Filament\Resources\Merchants\RelationManagers\SettlementsRelationManager;
use App\Filament\Resources\Merchants\Schemas\MerchantForm;
use App\Filament\Resources\Merchants\Schemas\MerchantInfolist;
use App\Filament\Resources\Merchants\Tables\MerchantsTable;
use App\Models\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MerchantUsersRelationManager::class,
            CreditPackagesRelationManager::class,
            SettlementsRelationManager::class,
            SettlementClawbacksRelationManager::class,
            MerchantLocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchants::route('/'),
            'create' => CreateMerchant::route('/create'),
            'view' => ViewMerchant::route('/{record}'),
            'edit' => EditMerchant::route('/{record}/edit'),
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
