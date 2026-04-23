<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SettlementStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SettlementService
{
    public function generateForMerchant(Merchant $merchant, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): Settlement
    {
        if (empty($merchant->bank_name) || empty($merchant->bank_account_number) || empty($merchant->bank_account_name)) {
            throw new InvalidArgumentException("Merchant [{$merchant->id}] does not have banking details on file.");
        }

        return DB::transaction(function () use ($merchant, $periodStart, $periodEnd) {
            $transactions = CreditTransaction::where('merchant_id', $merchant->id)
                ->where('type', TransactionType::CREDIT_PURCHASE)
                ->where('status', TransactionStatus::COMPLETED)
                ->whereNull('settlement_id')
                ->whereBetween('created_at', [$periodStart->startOfDay(), $periodEnd->endOfDay()])
                ->lockForUpdate()
                ->select(['id', 'paid_amount', 'platform_fee'])
                ->get();

            if ($transactions->isEmpty()) {
                throw new InvalidArgumentException("No unsettled credit purchases found for merchant [{$merchant->id}] in the given period.");
            }

            $totalSales = $transactions->sum(fn ($t) => (float) $t->paid_amount);
            $totalFees = $transactions->sum(fn ($t) => (float) $t->platform_fee);
            $netAmount = bcsub((string) $totalSales, (string) $totalFees, 2);

            $settlement = Settlement::create([
                'merchant_id' => $merchant->id,
                'reference_code' => $this->generateUniqueReferenceCode(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_sales' => $totalSales,
                'total_fees' => $totalFees,
                'net_amount' => $netAmount,
                'transaction_count' => $transactions->count(),
                'status' => SettlementStatus::PENDING,
                'bank_name' => $merchant->bank_name,
                'bank_account_number' => $merchant->bank_account_number,
                'bank_account_name' => $merchant->bank_account_name,
            ]);

            CreditTransaction::whereIn('id', $transactions->pluck('id'))
                ->update(['settlement_id' => $settlement->id]);

            return $settlement;
        });
    }

    public function markAsPaid(Settlement $settlement, string $paymentReference): Settlement
    {
        $settlement->update([
            'status' => SettlementStatus::COMPLETED,
            'payment_reference' => $paymentReference,
            'paid_at' => now(),
        ]);

        return $settlement->fresh();
    }

    private function generateReferenceCode(): string
    {
        $date = now()->format('Ymd');
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $random = implode('', array_map(fn () => $chars[random_int(0, 35)], range(0, 5)));

        return "STL-{$date}-{$random}";
    }

    private function generateUniqueReferenceCode(int $maxAttempts = 5): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = $this->generateReferenceCode();

            if (! Settlement::where('reference_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException("Failed to generate a unique settlement reference code after {$maxAttempts} attempts.");
    }
}
