<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'merchant_id',
        'reference_code',
        'period_start',
        'period_end',
        'total_sales',
        'total_fees',
        'net_amount',
        'transaction_count',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'payment_reference',
        'paid_at',
        'payment_proof_url',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_sales' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => SettlementStatus::class,
        'paid_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', SettlementStatus::PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SettlementStatus::COMPLETED);
    }

    public function scopeForMerchant($query, string $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }
}
