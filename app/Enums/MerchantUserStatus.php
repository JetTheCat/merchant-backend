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

    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isPendingVerification(): bool
    {
        return $this === self::PENDING_VERIFICATION;
    }
}
