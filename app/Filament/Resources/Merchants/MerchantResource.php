<?php

namespace App\Filament\Resources\Merchants;

use App\Filament\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Resources\Merchants\Pages\ViewMerchant;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Merchant';

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
            //
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
