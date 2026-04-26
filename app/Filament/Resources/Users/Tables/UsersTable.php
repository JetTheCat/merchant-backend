<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\ActivateUserAction;
use App\Filament\Actions\SuspendUserAction;
use App\UserStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state) => $state->label())
                    ->color(fn (UserStatus $state) => $state->color()),

                TextColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->dateTime('d M Y')
                    ->placeholder('Not verified')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(UserStatus::options()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                SuspendUserAction::make(),
                ActivateUserAction::make(),
            ]);
    }
}
