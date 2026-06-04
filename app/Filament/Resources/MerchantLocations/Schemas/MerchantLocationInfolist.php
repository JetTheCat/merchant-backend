<?php

declare(strict_types=1);

namespace App\Filament\Resources\MerchantLocations\Schemas;

use App\Models\MerchantLocation;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantLocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('name'),

                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('street')
                            ->columnSpanFull(),

                        TextEntry::make('city'),

                        TextEntry::make('postcode'),

                        TextEntry::make('state')
                            ->badge(),
                    ]),

                Section::make('QR Code')
                    ->schema([
                        TextEntry::make('redemption_code')
                            ->label('Redemption Code')
                            ->copyable()
                            ->columnSpanFull()
                            ->helperText('Encoded in the static QR URL for this location. Permanent — do not share publicly.'),
                    ]),

                Section::make('Activity')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime('d M Y, H:i'),

                        TextEntry::make('updated_at')
                            ->dateTime('d M Y, H:i'),

                        TextEntry::make('deleted_at')
                            ->dateTime('d M Y, H:i')
                            ->placeholder(Placeholder::EMPTY)
                            ->visible(fn (MerchantLocation $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
