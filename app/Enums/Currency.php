<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case MYR = 'MYR';

    public function label(): string
    {
        return match ($this) {
            self::MYR => 'Malaysian Ringgit',
        };
    }
}
