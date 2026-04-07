<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionCategory: string
{
    case CREDIT = 'credit';
    case PAYMENT = 'payment';
    case SETTLEMENT = 'settlement';

    public function label(): string
    {
        return match($this) {
            self::CREDIT => 'Credit',
            self::PAYMENT => 'Payment',
            self::SETTLEMENT => 'Settlement',
        };
    }
}
