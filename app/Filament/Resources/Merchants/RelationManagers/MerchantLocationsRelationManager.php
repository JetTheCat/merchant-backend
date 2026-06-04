<?php

declare(strict_types=1);

namespace App\Filament\Resources\Merchants\RelationManagers;

use App\Enums\MalaysianState;
use App\Filament\Actions\RemoveMerchantLocationAction;
use App\Filament\Resources\MerchantLocations\Schemas\MerchantLocationForm;
use App\Filament\Resources\MerchantLocations\Schemas\MerchantLocationInfolist;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'merchantLocations';

    protected static ?string $title = 'Locations';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return MerchantLocationForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return MerchantLocationInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('street')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('city')
                    ->searchable(),

                TextColumn::make('postcode')
                    ->searchable(),

                TextColumn::make('state')
                    ->badge()
                    ->sortable(),

                TextColumn::make('redemption_code')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),

                SelectFilter::make('state')
                    ->options(MalaysianState::class),

                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RemoveMerchantLocationAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
