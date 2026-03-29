<?php

declare(strict_types=1);

namespace App\Enums;

enum MerchantUserRole: string
{
    case OWNER = 'owner';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::STAFF => 'Staff',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    public function isStaff(): bool
    {
        return $this === self::STAFF;
    }
}
