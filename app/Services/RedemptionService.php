<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\MerchantUser;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedemptionService
{
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
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_REDEMPTION,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::PENDING,
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'fallback_code' => $this->generateShortCode(),
                'expires_at' => now()->addMinutes(config('app.fallback_code_expiry_minutes'))
            ]);
        });
    }

    public function redeemWithFallbackCode(string $code, User $user): CreditTransaction
    {
        return DB::transaction(function () use ($code, $user) {
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

            if (bccomp((string) $userCredit->balance, (string) $transaction->amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'code' => ['Insufficient balance.'],
                ]);
            }

            $balanceBefore = $userCredit->balance;
            $balanceAfter = bcsub((string) $balanceBefore, (string) $transaction->amount, 2);

            $transaction->update([
                'user_id' => $user->id,
                'user_credit_id' => $userCredit->id,
                'status' => TransactionStatus::COMPLETED,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'redeemed_at' => now(),
            ]);

            $userCredit->update([
                'balance' => $balanceAfter,
                'total_redeemed' => bcadd((string) $userCredit->total_redeemed, (string) $transaction->amount, 2),
                'last_redemption_at' => now(),
            ]);

            return $transaction->fresh();
        });
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
            $balanceAfter = bcadd((string) $balanceBefore, (string) $redemption->amount, 2);

            $userCredit->update([
                'balance' => $balanceAfter,
                'total_redeemed' => bcsub((string) $userCredit->total_redeemed, (string) $redemption->amount, 2),
            ]);

            return CreditTransaction::create([
                'user_id' => $redemption->user_id,
                'merchant_id' => $byUser->merchant_id,
                'merchant_user_id' => $byUser->id,
                'user_credit_id' => $redemption->user_credit_id,
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_REFUND,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::COMPLETED,
                'amount' => $redemption->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'notes' => $notes,
            ]);
        });
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
