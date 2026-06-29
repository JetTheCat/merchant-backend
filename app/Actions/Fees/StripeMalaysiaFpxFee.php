<?php

declare(strict_types=1);

namespace App\Actions\Fees;

use RuntimeException;

class StripeMalaysiaFpxFee implements ProcessingFee
{
    public function amountFor(string $paidAmount): string
    {
        $percentage = config('stripe.fees.my.fpx.percentage');
        $fixed = config('stripe.fees.my.fpx.fixed');

        if ($percentage === null || $fixed === null) {
            throw new RuntimeException('Stripe FPX fee rate is not configured. Set STRIPE_FEE_MY_FPX_PERCENTAGE and STRIPE_FEE_MY_FPX_FIXED.');
        }

        return add(mul($paidAmount, $percentage), $fixed);
    }
}
