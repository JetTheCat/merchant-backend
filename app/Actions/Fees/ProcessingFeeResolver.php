<?php

declare(strict_types=1);

namespace App\Actions\Fees;

use App\Enums\PaymentType;
use Illuminate\Contracts\Container\Container;

class ProcessingFeeResolver
{
    private const array PROCESSING_FEE_CLASSES = [
        PaymentType::CARD->value => StripeMalaysiaCardFee::class,
        PaymentType::FPX->value => StripeMalaysiaFpxFee::class,
        PaymentType::GRABPAY->value => StripeMalaysiaGrabPayFee::class,
    ];

    public function __construct(
        private readonly Container $container,
    ) {}

    public function resolve(PaymentType $paymentType): ProcessingFee
    {
        return $this->container->make(self::PROCESSING_FEE_CLASSES[$paymentType->value]);
    }
}
