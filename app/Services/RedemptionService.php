<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RedemptionSettlement;
use App\Enums\ClawbackStatus;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\RedemptionCompleted;
use App\Models\CreditTransaction;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\SettlementClawback;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedemptionService
{
    public function redeemWithStaticQr(string $code, float $amount, User $user): CreditTransaction
    {
        $transaction = DB::transaction(function () use ($code, $amount, $user) {
            $location = MerchantLocation::where('redemption_code', $code)
                ->where('is_active', true)
                ->first();

            if ($location === null) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid QR code.'],
                ]);
            }

            $userCredit = UserCredit::where('user_id', $user->id)
                ->where('merchant_id', $location->merchant_id)
                ->lockForUpdate()
                ->first();

            if ($userCredit === null) {
                throw ValidationException::withMessages([
                    'code' => ['You do not have a wallet for this merchant.'],
                ]);
            }

            if (compare((string) $userCredit->balance, (string) $amount) < 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Insufficient balance.'],
                ]);
            }

            $settlement = $this->settleRedemption($userCredit, (string) $amount);

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'merchant_id' => $location->merchant_id,
                'merchant_location_id' => $location->id,
                'user_credit_id' => $userCredit->id,
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_REDEMPTION,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::COMPLETED,
                'amount' => $amount,
                'balance_before' => $settlement->balanceBefore,
                'balance_after' => $settlement->balanceAfter,
                'settlement_amount' => $settlement->settlementAmount,
                'gross_settlement_amount' => $settlement->grossSettlementAmount,
                'redeemed_at' => now(),
            ]);

            return $transaction->fresh();
        });

        RedemptionCompleted::dispatch($transaction);

        return $transaction;
    }

    public function generateFallbackCode(MerchantUser $merchantUser, float $amount): CreditTransaction
    {
        return DB::transaction(function () use ($merchantUser, $amount) {
            CreditTransaction::pending()
                ->where('merchant_user_id', $merchantUser->id)
                ->where('type', TransactionType::CREDIT_REDEMPTION)
                ->update(['status' => TransactionStatus::CANCELLED]);

            return CreditTransaction::create([
                'merchant_id' => $merchantUser->merchant_id,
                'merchant_user_id' => $merchantUser->id,
                'merchant_location_id' => $merchantUser->merchant_location_id,
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_REDEMPTION,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::PENDING,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'fallback_code' => $this->generateShortCode(),
                'expires_at' => now()->addMinutes(config('app.fallback_code_expiry_minutes')),
            ]);
        });
    }

    public function redeemWithFallbackCode(string $code, User $user): CreditTransaction
    {
        $transaction = DB::transaction(function () use ($code, $user) {
            $transaction = CreditTransaction::pending()
                ->where('fallback_code', $code)
                ->where('type', TransactionType::CREDIT_REDEMPTION)
                ->first();

            if ($transaction === null) {
                throw ValidationException::withMessages([
                    'code' => ['Invalid code.'],
                ]);
            }

            if ($transaction->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'code' => ['This code has expired.'],
                ]);
            }

            $userCredit = UserCredit::where('user_id', $user->id)
                ->where('merchant_id', $transaction->merchant_id)
                ->lockForUpdate()
                ->first();

            if ($userCredit === null) {
                throw ValidationException::withMessages([
                    'code' => ['You do not have a wallet for this merchant.'],
                ]);
            }

            if (compare((string) $userCredit->balance, (string) $transaction->amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'code' => ['Insufficient balance.'],
                ]);
            }

            $settlement = $this->settleRedemption($userCredit, (string) $transaction->amount);

            $transaction->update([
                'user_id' => $user->id,
                'user_credit_id' => $userCredit->id,
                'status' => TransactionStatus::COMPLETED,
                'balance_before' => $settlement->balanceBefore,
                'balance_after' => $settlement->balanceAfter,
                'settlement_amount' => $settlement->settlementAmount,
                'gross_settlement_amount' => $settlement->grossSettlementAmount,
                'redeemed_at' => now(),
            ]);

            return $transaction->fresh();
        });

        if ($transaction->merchant_location_id !== null) {
            RedemptionCompleted::dispatch($transaction);
        }

        return $transaction;
    }

    public function refundRedemption(CreditTransaction $redemption, MerchantUser $byUser, ?string $notes = null): CreditTransaction
    {
        return DB::transaction(function () use ($redemption, $byUser, $notes) {
            $redemption = CreditTransaction::where('id', $redemption->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($redemption->type !== TransactionType::CREDIT_REDEMPTION) {
                throw ValidationException::withMessages([
                    'redemption' => ['Only redemption transactions can be refunded.'],
                ]);
            }

            if ($redemption->status !== TransactionStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'redemption' => ['Only completed redemptions can be refunded.'],
                ]);
            }

            if (is_null($redemption->user_credit_id)) {
                throw ValidationException::withMessages([
                    'redemption' => ['This redemption has no associated user credit and cannot be refunded.'],
                ]);
            }

            $redemption->update(['status' => TransactionStatus::REVERSED]);

            $userCredit = UserCredit::where('id', $redemption->user_credit_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = $userCredit->balance;
            $balanceAfter = add((string) $balanceBefore, (string) $redemption->amount);

            $userCredit->update([
                'balance' => $balanceAfter,
                'cash_balance' => add((string) $userCredit->cash_balance, (string) $redemption->settlement_amount),
                'gross_paid_balance' => add((string) $userCredit->gross_paid_balance, (string) $redemption->gross_settlement_amount),
                'total_redeemed' => sub((string) $userCredit->total_redeemed, (string) $redemption->amount),
            ]);

            $refund = CreditTransaction::create([
                'user_id' => $redemption->user_id,
                'merchant_id' => $byUser->merchant_id,
                'merchant_user_id' => $byUser->id,
                'user_credit_id' => $redemption->user_credit_id,
                'parent_transaction_id' => $redemption->id,
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_REFUND,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::COMPLETED,
                'amount' => $redemption->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'notes' => $notes,
            ]);

            if ($redemption->settlement_id !== null) {
                SettlementClawback::create([
                    'merchant_id' => $byUser->merchant_id,
                    'original_redemption_id' => $redemption->id,
                    'refund_transaction_id' => $refund->id,
                    'amount' => $redemption->settlement_amount,
                    'status' => ClawbackStatus::PENDING,
                ]);
            }

            return $refund;
        });
    }

    private function settleRedemption(UserCredit $userCredit, string $amount): RedemptionSettlement
    {
        $balanceBefore = (string) $userCredit->balance;
        $balanceAfter = sub($balanceBefore, $amount);

        $netRatio = div((string) $userCredit->cash_balance, $balanceBefore, 5);
        $settlementAmount = mul($amount, $netRatio);

        $grossRatio = div((string) $userCredit->gross_paid_balance, $balanceBefore, 5);
        $grossSettlementAmount = mul($amount, $grossRatio);

        $userCredit->update([
            'balance' => $balanceAfter,
            'cash_balance' => sub((string) $userCredit->cash_balance, $settlementAmount),
            'gross_paid_balance' => sub((string) $userCredit->gross_paid_balance, $grossSettlementAmount),
            'total_redeemed' => add((string) $userCredit->total_redeemed, $amount),
            'last_redemption_at' => now(),
        ]);

        return new RedemptionSettlement(
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            settlementAmount: $settlementAmount,
            grossSettlementAmount: $grossSettlementAmount,
        );
    }

    private function generateShortCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = implode('', array_map(fn () => $chars[random_int(0, 31)], range(0, 5)));
        } while (CreditTransaction::pending()->where('fallback_code', $code)->exists());

        return $code;
    }
}
