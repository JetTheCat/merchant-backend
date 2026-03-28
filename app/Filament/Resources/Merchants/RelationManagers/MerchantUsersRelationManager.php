<?php

namespace App\Filament\Resources\Merchants\RelationManagers;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Filament\Actions\SendMerchantUserInvitationAction;
use App\Models\MerchantUser;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MerchantUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'merchantUsers';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->saved(fn (?string $state) => filled($state)),
                Select::make('role')
                    ->options(MerchantUserRole::options())
                    ->default(MerchantUserRole::STAFF->value)
                    ->required(),
                Select::make('status')
                    ->options(MerchantUserStatus::options())
                    ->default(MerchantUserStatus::PENDING_VERIFICATION->value)
                    ->required(),
                DateTimePicker::make('email_verified_at'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),

                        TextEntry::make('role')
                            ->badge()
                            ->formatStateUsing(fn (MerchantUserRole $state) => $state->label())
                            ->color('info'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (MerchantUserStatus $state) => $state->label())
                            ->color(fn (MerchantUserStatus $state) => $state->color()),
                    ]),

                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email'),

                        TextEntry::make('phone')
                            ->default('—'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('d M Y, H:i')
                            ->default('Not verified'),
                    ]),

                Section::make('Activity')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime('d M Y, H:i')
                            ->default('Never'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d M Y'),

                        TextEntry::make('deleted_at')
                            ->label('Deleted')
                            ->dateTime('d M Y, H:i')
                            ->visible(fn (MerchantUser $record): bool => $record->trashed()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
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
            ->headerActions([
                CreateAction::make(),
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

                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
