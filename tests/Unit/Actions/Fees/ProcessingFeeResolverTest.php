<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fees;

use App\Actions\Fees\ProcessingFeeResolver;
use App\Actions\Fees\StripeMalaysiaCardFee;
use App\Actions\Fees\StripeMalaysiaFpxFee;
use App\Actions\Fees\StripeMalaysiaGrabPayFee;
use App\Enums\PaymentType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProcessingFeeResolverTest extends TestCase
{
    #[DataProvider('providesPaymentTypeToFeeClass')]
    public function testResolveReturnsFeeClassForPaymentType(PaymentType $paymentType, string $expectedFeeClass): void
    {
        $fee = app(ProcessingFeeResolver::class)->resolve($paymentType);

        $this->assertInstanceOf($expectedFeeClass, $fee);
    }

    public static function providesPaymentTypeToFeeClass(): array
    {
        return [
            'card resolves to card fee' => [
                'paymentType' => PaymentType::CARD,
                'expectedFeeClass' => StripeMalaysiaCardFee::class,
            ],
            'fpx resolves to fpx fee' => [
                'paymentType' => PaymentType::FPX,
                'expectedFeeClass' => StripeMalaysiaFpxFee::class,
            ],
            'grabpay resolves to grabpay fee' => [
                'paymentType' => PaymentType::GRABPAY,
                'expectedFeeClass' => StripeMalaysiaGrabPayFee::class,
            ],
        ];
    }
}
