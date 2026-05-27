<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\MerchantUser;

/**
 * @extends TransactionFactory<CreditTransaction>
 */
class CreditTransactionFactory extends TransactionFactory
{
    protected $model = CreditTransaction::class;

    public function purchase(): static
    {
        return $this->state(function (array $attributes) {
            $amount = abs((float) $attributes['amount']);

            return [
                'type' => TransactionType::CREDIT_PURCHASE,
                'credit_package_id' => $attributes['credit_package_id'] ?? CreditPackage::factory(),
                'merchant_user_id' => null,
                'amount' => $amount,
                'paid_amount' => $amount,
                'balance_after' => round((float) $attributes['balance_before'] + $amount, 2),
            ];
        });
    }

    public function redemption(): static
    {
        return $this->state(function (array $attributes) {
            $amount = abs((float) $attributes['amount']);

            return [
                'type' => TransactionType::CREDIT_REDEMPTION,
                'credit_package_id' => null,
                'merchant_user_id' => $attributes['merchant_user_id'] ?? MerchantUser::factory(),
                'amount' => $amount,
                'paid_amount' => null,
                'balance_after' => round((float) $attributes['balance_before'] - $amount, 2),
                'expires_at' => now()->addMinutes(config('app.fallback_code_expiry_minutes', 5)),
            ];
        });
    }

    public function refund(): static
    {
        return $this->state(function (array $attributes) {
            $amount = abs((float) $attributes['amount']);

            return [
                'type' => TransactionType::CREDIT_REFUND,
                'credit_package_id' => null,
                'merchant_user_id' => null,
                'amount' => $amount,
                'paid_amount' => null,
                'balance_after' => round((float) $attributes['balance_before'] + $amount, 2),
            ];
        });
    }
}
