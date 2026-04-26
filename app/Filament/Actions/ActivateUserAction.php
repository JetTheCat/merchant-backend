<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\User;
use App\UserStatus;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ActivateUserAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activateUser';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Activate')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Activate User')
            ->modalDescription('Are you sure you want to activate this user?')
            ->visible(fn (User $record) => $record->isSuspended())
            ->action(fn (User $record) => $record->update(['status' => UserStatus::ACTIVE]));
    }
}
