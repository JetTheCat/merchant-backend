<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantUsers\Schemas;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Models\MerchantUser;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantUserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('name')
                            ->label('Name'),

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
                        TextEntry::make('email')
                            ->label('Email')
                            ->visible(fn (MerchantUser $record) => $record->isOwner())
                            ->default(Placeholder::EMPTY),

                        TextEntry::make('username')
                            ->label('Username')
                            ->visible(fn (MerchantUser $record) => ! $record->isOwner())
                            ->default(Placeholder::EMPTY),

                        TextEntry::make('phone')
                            ->label('Phone')
                            ->default(Placeholder::EMPTY),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Not verified')
                            ->visible(fn (MerchantUser $record) => $record->isOwner()),
                    ]),

                Section::make('Activity')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Never'),

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
}
