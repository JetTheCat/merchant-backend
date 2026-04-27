<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditPackages;

use App\Filament\Resources\CreditPackages\Pages\ListCreditPackages;
use App\Filament\Resources\CreditPackages\Pages\ViewCreditPackage;
use App\Filament\Resources\CreditPackages\Schemas\CreditPackageInfolist;
use App\Filament\Resources\CreditPackages\Tables\CreditPackagesTable;
use App\Models\CreditPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CreditPackageResource extends Resource
{
    protected static ?string $model = CreditPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGiftTop;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return CreditPackageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CreditPackagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditPackages::route('/'),
            'view' => ViewCreditPackage::route('/{record}'),
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
