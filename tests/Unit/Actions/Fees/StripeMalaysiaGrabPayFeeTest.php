<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fees;

use App\Actions\Fees\StripeMalaysiaGrabPayFee;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class StripeMalaysiaGrabPayFeeTest extends TestCase
{
    public function testAmountForComputesPercentageFeeOnly(): void
    {
        Config::set('stripe.fees.my.grabpay.percentage', '0.03');

        $fee = (new StripeMalaysiaGrabPayFee)->amountFor('100');

        $this->assertEquals('3.00', $fee);
    }

    public function testAmountForThrowsWhenPercentageIsMissing(): void
    {
        Config::set('stripe.fees.my.grabpay.percentage', null);

        $this->expectException(RuntimeException::class);

        (new StripeMalaysiaGrabPayFee)->amountFor('100');
    }
}
