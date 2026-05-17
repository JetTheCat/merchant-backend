<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantUsers\Schemas;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MerchantUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                Select::make('role')
                    ->options([
                        MerchantUserRole::OWNER->value => MerchantUserRole::OWNER->label(),
                        MerchantUserRole::STAFF->value => MerchantUserRole::STAFF->label(),
                    ])
                    ->default(MerchantUserRole::STAFF->value)
                    ->required()
                    ->live(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(fn (Get $get) => $get('role') === MerchantUserRole::OWNER->value)
                    ->hidden(fn (Get $get) => $get('role') !== MerchantUserRole::OWNER->value),

                TextInput::make('username')
                    ->required(fn (Get $get) => $get('role') !== MerchantUserRole::OWNER->value)
                    ->hidden(fn (Get $get) => $get('role') === MerchantUserRole::OWNER->value)
                    ->helperText('Staff login credential will be username@merchantcode'),

                TextInput::make('phone')
                    ->tel(),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->saved(fn (?string $state) => filled($state)),

                Select::make('status')
                    ->options(MerchantUserStatus::options())
                    ->default(MerchantUserStatus::PENDING_VERIFICATION->value)
                    ->required(),

                DateTimePicker::make('email_verified_at')
                    ->hidden(fn (Get $get) => $get('role') !== MerchantUserRole::OWNER->value),
            ]);
    }
}
