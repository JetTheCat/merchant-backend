<?php

declare(strict_types=1);

namespace App\Actions\Fees;

use RuntimeException;

class StripeMalaysiaCardFee implements ProcessingFee
{
    public function amountFor(string $paidAmount): string
    {
        $percentage = config('stripe.fees.my.card.percentage');
        $fixed = config('stripe.fees.my.card.fixed');

        if ($percentage === null || $fixed === null) {
            throw new RuntimeException('Stripe card fee rate is not configured. Set STRIPE_FEE_MY_CARD_PERCENTAGE and STRIPE_FEE_MY_CARD_FIXED.');
        }

        return add(mul($paidAmount, $percentage), $fixed);
    }
}
