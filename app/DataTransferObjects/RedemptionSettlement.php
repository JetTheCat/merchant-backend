<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class RedemptionSettlement
{
    public function __construct(
        public string $balanceBefore,
        public string $balanceAfter,
        public string $settlementAmount,
        public string $grossSettlementAmount,
    ) {}
}
