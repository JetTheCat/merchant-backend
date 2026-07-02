<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'merchant_id',
        'user_credit_id',
        'credit_package_id',
        'merchant_user_id',
        'merchant_location_id',
        'settlement_id',
        'payment_id',
        'category',
        'type',
        'reference_code',
        'status',
        'amount',
        'paid_amount',
        'balance_before',
        'balance_after',
        'platform_fee_rate',
        'platform_fee',
        'settlement_amount',
        'gross_settlement_amount',
        'parent_transaction_id',
        'fallback_code',
        'redeemed_at',
        'expires_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'category' => TransactionCategory::class,
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'platform_fee_rate' => 'decimal:4',
        'platform_fee' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'gross_settlement_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function userCredit(): BelongsTo
    {
        return $this->belongsTo(UserCredit::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }

    public function merchantUser(): BelongsTo
    {
        return $this->belongsTo(MerchantUser::class);
    }

    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function childTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('status', TransactionStatus::COMPLETED);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', TransactionStatus::PENDING);
    }

    #[Scope]
    protected function byType(Builder $query, TransactionType $type): void
    {
        $query->where('type', $type);
    }

    public function markAsCompleted(): bool
    {
        return $this->update(['status' => TransactionStatus::COMPLETED]);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => TransactionStatus::FAILED]);
    }

    public function markAsCancelled(): bool
    {
        return $this->update(['status' => TransactionStatus::CANCELLED]);
    }

    public static function generateReferenceCode(): string
    {
        $date = now()->format('Ymd');
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $random = implode('', array_map(fn () => $chars[random_int(0, 35)], range(0, 5)));

        return "TXN-{$date}-{$random}";
    }

    public static function generateUniqueReferenceCode(int $maxAttempts = 5): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = static::generateReferenceCode();

            if (! static::withTrashed()->where('reference_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException("Failed to generate a unique reference code after {$maxAttempts} attempts.");
    }
}
