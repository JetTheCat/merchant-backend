<?php

declare(strict_types=1);

namespace App\Actions\Fees;

interface ProcessingFee
{
    public function amountFor(string $paidAmount): string;
}
