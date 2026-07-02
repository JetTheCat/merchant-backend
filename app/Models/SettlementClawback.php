<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClawbackStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementClawback extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'original_redemption_id',
        'refund_transaction_id',
        'amount',
        'status',
        'settlement_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => ClawbackStatus::class,
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function originalRedemption(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_redemption_id');
    }

    public function refundTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'refund_transaction_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
