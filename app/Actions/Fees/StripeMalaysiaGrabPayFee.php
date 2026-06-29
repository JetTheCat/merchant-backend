<?php

declare(strict_types=1);

namespace App\Actions\Fees;

use RuntimeException;

class StripeMalaysiaGrabPayFee implements ProcessingFee
{
    public function amountFor(string $paidAmount): string
    {
        $percentage = config('stripe.fees.my.grabpay.percentage');

        if ($percentage === null) {
            throw new RuntimeException('Stripe GrabPay fee rate is not configured. Set STRIPE_FEE_MY_GRABPAY_PERCENTAGE.');
        }

        return mul($paidAmount, $percentage);
    }
}
