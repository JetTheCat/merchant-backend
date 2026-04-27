<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\UserStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                DateTimePicker::make('phone_verified_at'),
                Select::make('status')
                    ->options(UserStatus::class)
                    ->default('active')
                    ->required(),
            ]);
    }
}
