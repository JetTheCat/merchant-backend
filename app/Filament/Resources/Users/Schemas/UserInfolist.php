<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\UserStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Name'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (UserStatus $state) => $state->label())
                            ->color(fn (UserStatus $state) => $state->color()),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not verified'),

                        TextEntry::make('phone')
                            ->label('Phone'),

                        TextEntry::make('created_at')
                            ->label('Joined')
                            ->dateTime('d M Y, h:i A'),
                    ]),

                Section::make('Danger Zone')
                    ->schema([
                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->dateTime('d M Y, h:i A')
                            ->visible(fn (User $record): bool => $record->trashed()),
                    ])
                    ->visible(fn (User $record): bool => $record->trashed()),
            ]);
    }
}
