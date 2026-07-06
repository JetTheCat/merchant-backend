<?php

declare(strict_types=1);

namespace App\Enums;

use Stripe\PaymentMethod;
use ValueError;

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

    public static function fromStripeType(string $stripeType): self
    {
        return match ($stripeType) {
            PaymentMethod::TYPE_CARD => self::CARD,
            PaymentMethod::TYPE_FPX => self::FPX,
            PaymentMethod::TYPE_GRABPAY => self::GRABPAY,
            default => throw new ValueError("Unsupported Stripe payment method type [{$stripeType}]."),
        };
    }
}
