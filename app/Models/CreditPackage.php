<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditPackage extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'credit_amount',
        'selling_price',
        'is_active',
        'purchase_limit_per_user',
        'total_quantity',
        'sold_quantity',
    ];

    protected $casts = [
        'credit_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->credit_amount > 0
                ? round((1 - (float) $this->selling_price / (float) $this->credit_amount) * 100, 2)
                : 0.0,
        );
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function byMerchant(Builder $query, string $merchantId): void
    {
        $query->where('merchant_id', $merchantId);
    }

    #[Scope]
    protected function notSoldOut(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('total_quantity')
                ->orWhereColumn('sold_quantity', '<', 'total_quantity');
        });
    }

    #[Scope]
    protected function notAtUserPurchaseLimit(Builder $query, User $user): void
    {
        $query->where(function (Builder $q) use ($user) {
            $q->whereNull('purchase_limit_per_user')
                ->orWhereRaw(
                    '(
                        SELECT COUNT(*) FROM transactions
                        WHERE transactions.credit_package_id = credit_packages.id
                        AND transactions.user_id = ?
                        AND transactions.type = ?
                        AND transactions.status IN (?, ?)
                    ) < credit_packages.purchase_limit_per_user',
                    [
                        $user->id,
                        TransactionType::CREDIT_PURCHASE->value,
                        ...self::activePurchaseStatuses()
                    ]
                );
        });
    }

    public static function activePurchaseStatuses(): array
    {
        return [
            TransactionStatus::PENDING->value,
            TransactionStatus::COMPLETED->value,
        ];
    }
}
