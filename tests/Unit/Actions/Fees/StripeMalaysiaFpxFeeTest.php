<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fees;

use App\Actions\Fees\StripeMalaysiaFpxFee;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class StripeMalaysiaFpxFeeTest extends TestCase
{
    public function testAmountForComputesPercentagePlusFixedFee(): void
    {
        Config::set('stripe.fees.my.fpx.percentage', '0.03');
        Config::set('stripe.fees.my.fpx.fixed', '1.00');

        $fee = (new StripeMalaysiaFpxFee)->amountFor('100');

        $this->assertEquals('4.00', $fee);
    }

    public function testAmountForThrowsWhenPercentageIsMissing(): void
    {
        Config::set('stripe.fees.my.fpx.percentage', null);
        Config::set('stripe.fees.my.fpx.fixed', '1.00');

        $this->expectException(RuntimeException::class);

        (new StripeMalaysiaFpxFee)->amountFor('100');
    }

    public function testAmountForThrowsWhenFixedIsMissing(): void
    {
        Config::set('stripe.fees.my.fpx.percentage', '0.03');
        Config::set('stripe.fees.my.fpx.fixed', null);

        $this->expectException(RuntimeException::class);

        (new StripeMalaysiaFpxFee)->amountFor('100');
    }
}
