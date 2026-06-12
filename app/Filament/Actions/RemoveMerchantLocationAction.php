<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\MerchantLocation;
use App\Services\MerchantLocationService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class RemoveMerchantLocationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'removeMerchantLocation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $service = app(MerchantLocationService::class);

        $this->label('Remove')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Remove Location')
            ->modalDescription('If this location has no transaction history it will be permanently deleted. Otherwise it will be archived.')
            ->visible(fn (MerchantLocation $record) => ! $record->trashed() && ! $record->is_active)
            ->action(fn (MerchantLocation $record) => $service->remove($record));
    }
}
