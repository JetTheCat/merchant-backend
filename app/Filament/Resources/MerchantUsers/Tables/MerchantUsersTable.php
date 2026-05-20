<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantUsers\Tables;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Filament\Actions\SendMerchantUserInvitationAction;
use App\Models\MerchantUser;
use App\Support\Filament\Placeholder;
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

class MerchantUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->placeholder(Placeholder::EMPTY),
                TextColumn::make('username')
                    ->searchable()
                    ->placeholder(Placeholder::EMPTY),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (MerchantUserRole $state) => $state->label())
                    ->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MerchantUserStatus $state) => $state->label())
                    ->color(fn (MerchantUserStatus $state) => $state->color())
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(MerchantUserStatus::options()),

                SelectFilter::make('role')
                    ->options(MerchantUserRole::options()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                SendMerchantUserInvitationAction::make(),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MerchantUser $record) => $record->isActive())
                    ->action(fn (MerchantUser $record) => $record->update(['status' => MerchantUserStatus::SUSPENDED])),

                Action::make('activate')
                    ->label('Activate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MerchantUser $record) => $record->isSuspended())
                    ->action(fn (MerchantUser $record) => $record->update(['status' => MerchantUserStatus::ACTIVE])),
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
