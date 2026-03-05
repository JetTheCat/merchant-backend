<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::REJECTED => 'Rejected',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }
}
