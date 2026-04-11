<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class PaymentIntentResult
{
    public function __construct(
        public string $paymentId,
        public string $paymentIntentId,
        public string $clientSecret,
    ) {}
}
