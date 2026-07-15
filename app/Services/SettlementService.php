<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ClawbackStatus;
use App\Enums\SettlementStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\SettlementClawback;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SettlementService
{
    /**
     * Only the Y-m-d portion of $periodStart/$periodEnd is used, treated as a calendar
     * day in reporting_timezone — the instant/timezone each value carries is otherwise ignored.
     */
    public function generateForMerchant(Merchant $merchant, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): Settlement
    {
        if (empty($merchant->bank_name) || empty($merchant->bank_account_number) || empty($merchant->bank_account_name)) {
            throw new InvalidArgumentException("Merchant [{$merchant->id}] does not have banking details on file.");
        }

        $reportingTimezone = config('app.reporting_timezone');

        // periodStart/periodEnd are calendar days in reporting_timezone; queryFrom/queryTo are
        // the same instants in UTC, for comparing against created_at.
        $queryFrom = CarbonImmutable::parse($periodStart->format('Y-m-d'), $reportingTimezone)->startOfDay()->utc();
        $queryTo = CarbonImmutable::parse($periodEnd->format('Y-m-d'), $reportingTimezone)->endOfDay()->utc();

        return DB::transaction(function () use ($merchant, $periodStart, $periodEnd, $queryFrom, $queryTo) {
            $transactions = CreditTransaction::where('merchant_id', $merchant->id)
                ->where('type', TransactionType::CREDIT_REDEMPTION)
                ->where('status', TransactionStatus::COMPLETED)
                ->whereNull('settlement_id')
                ->whereBetween('created_at', [$queryFrom, $queryTo])
                ->lockForUpdate()
                ->select(['id', 'settlement_amount'])
                ->get();

            if ($transactions->isEmpty()) {
                throw new InvalidArgumentException("No unsettled credit redemptions found for merchant [{$merchant->id}] in the given period.");
            }

            $totalSales = $transactions->sum(fn ($t) => (float) $t->settlement_amount);

            $settlement = Settlement::create([
                'merchant_id' => $merchant->id,
                'reference_code' => $this->generateUniqueReferenceCode(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_sales' => $totalSales,
                'net_amount' => $totalSales,
                'transaction_count' => $transactions->count(),
                'status' => SettlementStatus::PENDING,
                'bank_name' => $merchant->bank_name,
                'bank_account_number' => $merchant->bank_account_number,
                'bank_account_name' => $merchant->bank_account_name,
            ]);

            CreditTransaction::whereIn('id', $transactions->pluck('id'))
                ->update(['settlement_id' => $settlement->id]);

            $this->applyClawbacks($settlement, $totalSales);

            return $settlement->fresh();
        });
    }

    private function applyClawbacks(Settlement $settlement, float $totalSales): void
    {
        // whole-record-only, oldest-first: only deduct a clawback if its full amount
        // fits within the remaining deductible; skip if it doesn't, carry to next cycle
        $clawbacks = SettlementClawback::where('merchant_id', $settlement->merchant_id)
            ->where('status', ClawbackStatus::PENDING)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $remaining = $totalSales;
        $clawbackAmount = 0.0;
        $recoveredIds = [];

        foreach ($clawbacks as $clawback) {
            if ((float) $clawback->amount > $remaining) {
                continue;
            }

            $remaining -= (float) $clawback->amount;
            $clawbackAmount += (float) $clawback->amount;
            $recoveredIds[] = $clawback->id;
        }

        if (empty($recoveredIds)) {
            return;
        }

        SettlementClawback::whereIn('id', $recoveredIds)
            ->update([
                'status' => ClawbackStatus::RECOVERED,
                'settlement_id' => $settlement->id,
            ]);

        $settlement->update([
            'clawback_amount' => $clawbackAmount,
            'net_amount' => $totalSales - $clawbackAmount,
        ]);
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
