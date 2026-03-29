<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case MERCHANT = 'merchant';
    case GENERAL = 'general';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANT => 'Merchant',
            self::GENERAL => 'General',
        };
    }
}
