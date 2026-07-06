<?php

declare(strict_types=1);

namespace App\Enums;

enum ClawbackStatus: string
{
    case PENDING = 'pending';
    case RECOVERED = 'recovered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RECOVERED => 'Recovered',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::RECOVERED => 'success',
        };
    }
}
