<?php

declare(strict_types=1);

namespace App\Enums;

enum SettlementCycle: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
