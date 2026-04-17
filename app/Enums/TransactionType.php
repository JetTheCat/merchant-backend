<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case CREDIT_PURCHASE = 'credit_purchase';
    case CREDIT_REDEMPTION = 'credit_redemption';
    case CREDIT_REFUND = 'credit_refund';
    case CREDIT_EXPIRY = 'credit_expiry';
    case CREDIT_ADJUSTMENT = 'credit_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_PURCHASE => 'Credit Purchase',
            self::CREDIT_REDEMPTION => 'Credit Redemption',
            self::CREDIT_REFUND => 'Credit Refund',
            self::CREDIT_EXPIRY => 'Credit Expiry',
            self::CREDIT_ADJUSTMENT => 'Credit Adjustment',
        };
    }
}
