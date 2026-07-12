<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformFeeReports;

use App\Filament\Resources\PlatformFeeReports\Pages\ListPlatformFeeReports;
use App\Filament\Resources\PlatformFeeReports\Pages\ViewPlatformFeeReport;
use App\Filament\Resources\PlatformFeeReports\Schemas\PlatformFeeReportInfolist;
use App\Filament\Resources\PlatformFeeReports\Tables\PlatformFeeReportsTable;
use App\Models\PlatformFeeReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlatformFeeReportResource extends Resource
{
    protected static ?string $model = PlatformFeeReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function infolist(Schema $schema): Schema
    {
        return PlatformFeeReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformFeeReportsTable::configure($table);
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
            'index' => ListPlatformFeeReports::route('/'),
            'view' => ViewPlatformFeeReport::route('/{record}'),
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
