<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\User;
use App\UserStatus;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SuspendUserAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'suspendUser';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Suspend')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Suspend User')
            ->modalDescription('Are you sure you want to suspend this user? They will no longer be able to access the app.')
            ->visible(fn (User $record) => $record->isActive())
            ->action(fn (User $record) => $record->update(['status' => UserStatus::SUSPENDED]));
    }
}
