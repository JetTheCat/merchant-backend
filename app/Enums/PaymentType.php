<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentType: string
{
    case CARD = 'card';
    case FPX = 'fpx';
    case GRABPAY = 'grabpay';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Card',
            self::FPX => 'FPX',
            self::GRABPAY => 'GrabPay',
        };
    }
}
