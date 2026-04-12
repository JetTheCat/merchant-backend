<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Transaction;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    public function testGenerateReferenceCodeHasCorrectFormat(): void
    {
        $code = Transaction::generateReferenceCode();

        $this->assertMatchesRegularExpression('/^TXN-\d{8}-[A-Z0-9]{6}$/', $code);
    }

    public function testGenerateReferenceCodeIsUnique(): void
    {
        $codes = array_map(
            fn () => Transaction::generateReferenceCode(),
            range(1, 100),
        );

        $this->assertCount(100, array_unique($codes));
    }
}
