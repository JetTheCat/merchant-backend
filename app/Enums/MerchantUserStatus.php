<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantUserStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
            self::PENDING_VERIFICATION => 'warning',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isPendingVerification(): bool
    {
        return $this === self::PENDING_VERIFICATION;
    }
}
