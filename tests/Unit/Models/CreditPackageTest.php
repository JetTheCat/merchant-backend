<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CreditPackage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreditPackageTest extends TestCase
{
    #[DataProvider('discountScenarios')]
    public function testDiscountPercentageIsComputedCorrectly(
        float $creditAmount,
        float $sellingPrice,
        float $expectedDiscount,
    ): void {
        $package = new CreditPackage([
            'credit_amount' => $creditAmount,
            'selling_price' => $sellingPrice,
        ]);

        $this->assertEquals($expectedDiscount, $package->discount_percentage);
    }

    public static function discountScenarios(): array
    {
        return [
            '10% discount' => [100.00, 90.00, 10.00],
            '50% discount' => [100.00, 50.00, 50.00],
            'no discount' => [100.00, 100.00, 0.00],
            'fractional discount' => [300.00, 250.00, 16.67],
        ];
    }

    public function testDiscountPercentageIsZeroWhenCreditAmountIsZero(): void
    {
        $package = new CreditPackage([
            'credit_amount' => 0,
            'selling_price' => 90.00,
        ]);

        $this->assertEquals(0.0, $package->discount_percentage);
    }
}
