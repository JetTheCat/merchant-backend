<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class ChargeResult
{
    public function __construct(
        public string $chargeId,
        public string $paymentMethodType,
    ) {}
}
