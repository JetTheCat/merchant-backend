<?php

namespace App\Filament\Resources\MerchantUsers;

use App\Filament\Resources\MerchantUsers\Pages\EditMerchantUser;
use App\Filament\Resources\MerchantUsers\Pages\ListMerchantUsers;
use App\Filament\Resources\MerchantUsers\Pages\ViewMerchantUser;
use App\Filament\Resources\MerchantUsers\Schemas\MerchantUserForm;
use App\Filament\Resources\MerchantUsers\Schemas\MerchantUserInfolist;
use App\Filament\Resources\MerchantUsers\Tables\MerchantUsersTable;
use App\Models\MerchantUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantUserResource extends Resource
{
    protected static ?string $model = MerchantUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MerchantUserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchantUserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantUsersTable::configure($table);
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
            'index' => ListMerchantUsers::route('/'),
            'view'  => ViewMerchantUser::route('/{record}'),
            'edit'  => EditMerchantUser::route('/{record}/edit'),
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
