<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'user_credit_id',
        'credit_package_id',
        'merchant_user_id',
        'category',
        'type',
        'reference_code',
        'status',
        'amount',
        'paid_amount',
        'balance_before',
        'balance_after',
        'qr_code',
        'qr_expires_at',
        'redemption_code',
        'redeemed_at',
        'expires_at',
        'expired_at',
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
        'qr_expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'expired_at' => 'datetime',
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
        return $this->belongsTo(MerchantUser::class, 'merchant_user_id');
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

    public static function generateReferenceCode(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 6));

        return "TXN-{$date}-{$random}";
    }
}
