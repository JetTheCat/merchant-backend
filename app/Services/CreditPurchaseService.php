<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\PaymentIntentResult;
use App\Enums\Currency;
use App\Enums\PaymentStatus;
use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCredit;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreditPurchaseService
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    public function processPurchase(User $user, CreditPackage $package): PaymentIntentResult
    {
        // TODO: Move Stripe call outside the DB transaction to reduce lock contention on
        // limited packages. Requires a compensation strategy for Stripe failures after commit
        // (cancel orphaned records or expire stale PENDING records via a cleanup job).
        return DB::transaction(function () use ($user, $package) {
            $package = CreditPackage::with('merchant')
                ->where('id', $package->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($package->merchant->isCreditSalesPaused()) {
                throw ValidationException::withMessages([
                    'merchant' => ['This merchant is not currently accepting new credit purchases.'],
                ]);
            }

            if ($package->total_quantity !== null) {
                $activeCount = CreditTransaction::where('credit_package_id', $package->id)
                    ->whereIn('status', [TransactionStatus::PENDING, TransactionStatus::COMPLETED])
                    ->count();

                if ($activeCount >= $package->total_quantity) {
                    throw ValidationException::withMessages([
                        'package' => ['This package is no longer available.'],
                    ]);
                }
            }

            if ($package->purchase_limit_per_user !== null) {
                $purchaseCount = CreditTransaction::where('user_id', $user->id)
                    ->where('credit_package_id', $package->id)
                    ->whereIn('status', [TransactionStatus::PENDING, TransactionStatus::COMPLETED])
                    ->count();

                if ($purchaseCount >= $package->purchase_limit_per_user) {
                    throw ValidationException::withMessages([
                        'package' => ['You have reached the purchase limit for this package.'],
                    ]);
                }
            }

            $userCredit = UserCredit::firstOrCreate(
                ['user_id' => $user->id, 'merchant_id' => $package->merchant_id],
                ['balance' => 0, 'total_purchased' => 0, 'total_redeemed' => 0],
            );

            if ($this->purchaseWouldExceedWalletLimit($userCredit, $package)) {
                throw ValidationException::withMessages([
                    'wallet' => ['This purchase would exceed the maximum wallet balance allowed for this merchant.'],
                ]);
            }

            $balanceBefore = $userCredit->balance;
            $balanceAfter = add((string) $balanceBefore, (string) $package->credit_amount);

            $transaction = CreditTransaction::create([
                'user_id' => $user->id,
                'merchant_id' => $package->merchant_id,
                'user_credit_id' => $userCredit->id,
                'credit_package_id' => $package->id,
                'category' => TransactionCategory::CREDIT,
                'type' => TransactionType::CREDIT_PURCHASE,
                'reference_code' => Transaction::generateUniqueReferenceCode(),
                'status' => TransactionStatus::PENDING,
                'amount' => $package->credit_amount,
                'paid_amount' => $package->selling_price,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'amount' => $package->selling_price,
                'currency' => Currency::MYR,
                'status' => PaymentStatus::REQUIRES_PAYMENT_METHOD,
            ]);

            $transaction->update(['payment_id' => $payment->id]);

            $result = $this->stripeService->createPaymentIntent($payment);

            $payment->update(['stripe_payment_intent_id' => $result->paymentIntentId]);

            return $result;
        });
    }

    public function grantCredits(Payment $payment): void
    {
        $transaction = CreditTransaction::with('creditPackage.merchant')
            ->where('payment_id', $payment->id)
            ->first();

        if ($transaction === null) {
            Log::warning('Transaction not found for payment', ['payment_id' => $payment->id]);

            return;
        }

        $userCredit = UserCredit::lockForUpdate()->find($transaction->user_credit_id);

        $balanceBefore = $userCredit->balance;
        $balanceAfter = bcadd((string) $balanceBefore, (string) $transaction->amount, 2);

        $feeRate = $transaction->creditPackage->merchant->platform_fee_rate;
        $fee = bcmul((string) $transaction->paid_amount, (string) $feeRate, 2);

        $transaction->update([
            'status' => TransactionStatus::COMPLETED,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'platform_fee_rate' => $feeRate,
            'platform_fee' => $fee,
        ]);

        $userCredit->update([
            'balance' => $balanceAfter,
            'total_purchased' => bcadd((string) $userCredit->total_purchased, (string) $transaction->amount, 2),
            'last_purchase_at' => now(),
        ]);

        $transaction->creditPackage->increment('sold_quantity');
    }

    public function failPurchase(Payment $payment): void
    {
        $transaction = CreditTransaction::where('payment_id', $payment->id)->first();

        if ($transaction === null) {
            Log::warning('Transaction not found for payment', ['payment_id' => $payment->id]);

            return;
        }

        $transaction->update(['status' => TransactionStatus::FAILED]);
    }

    private function purchaseWouldExceedWalletLimit(UserCredit $userCredit, CreditPackage $package): bool
    {
        $total = add($userCredit->balance, $package->credit_amount);

        return gt($total, config('wallet.limit'));
    }
}
