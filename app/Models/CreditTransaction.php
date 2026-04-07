<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionCategory;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class CreditTransaction extends Transaction
{
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope(
            fn (Builder $query) => $query->where('category', TransactionCategory::CREDIT)
        );
    }

    #[Scope]
    protected function purchases(Builder $query): void
    {
        $query->where('type', TransactionType::CREDIT_PURCHASE);
    }

    #[Scope]
    protected function redemptions(Builder $query): void
    {
        $query->where('type', TransactionType::CREDIT_REDEMPTION);
    }
}
