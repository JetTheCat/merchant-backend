<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('business_name')
                    ->label('Business Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),

                TextColumn::make('city')
                    ->sortable(),

                TextColumn::make('phone'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MerchantStatus $state) => $state->label())
                    ->color(fn (MerchantStatus $state) => $state->color()),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(MerchantStatus::options()),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Merchant')
                    ->modalDescription('Are you sure you want to suspend this merchant? They will no longer be visible to customers.')
                    ->visible(fn (Merchant $record) => $record->isActive())
                    ->action(fn (Merchant $record) => $record->update(['status' => MerchantStatus::SUSPENDED])),

                Action::make('activate')
                    ->label('Activate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Merchant')
                    ->modalDescription('Are you sure you want to activate this merchant?')
                    ->visible(fn (Merchant $record) => $record->isSuspended())
                    ->action(fn (Merchant $record) => $record->update(['status' => MerchantStatus::ACTIVE])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
