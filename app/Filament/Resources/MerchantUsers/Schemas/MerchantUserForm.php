<?php

namespace App\Filament\Resources\MerchantUsers\Schemas;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MerchantUserForm
{
    public static function configure(Schema $schema): Schema
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
                    ->required(false)
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
}
