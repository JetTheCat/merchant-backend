<?php

declare(strict_types=1);

namespace App\Constants;

class MerchantUserRoles
{
    public const OWNER = 'owner';
    public const STAFF = 'staff';

    public static function all(): array
    {
        return [
            self::OWNER,
            self::STAFF,
        ];
    }

    public static function labels(): array
    {
        return [
            self::OWNER => 'Owner',
            self::STAFF => 'Staff',
        ];
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? $role;
    }
}
