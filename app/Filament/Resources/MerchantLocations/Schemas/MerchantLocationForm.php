<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations\Schemas;

use App\Enums\MalaysianState;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MerchantLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->maxLength(100)
                    ->required(),

                Select::make('is_active')
                    ->label('Status')
                    ->boolean(trueLabel: 'Active', falseLabel: 'Inactive')
                    ->required()
                    ->hidden(fn (string $operation): bool => $operation !== 'edit'),

                TextInput::make('street')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('city')
                    ->maxLength(100)
                    ->required(),

                TextInput::make('postcode')
                    ->required(),

                Select::make('state')
                    ->options(MalaysianState::class)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
