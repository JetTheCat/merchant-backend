<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_method',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'amount',
        'currency',
        'status',
        'stripe_payload',
        'failure_reason',
        'paid_at',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'stripe_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    #[Scope]
    protected function succeeded(Builder $query): void
    {
        $query->where('status', PaymentStatus::SUCCEEDED);
    }

    #[Scope]
    protected function processing(Builder $query): void
    {
        $query->where('status', PaymentStatus::PROCESSING);
    }

    #[Scope]
    protected function canceled(Builder $query): void
    {
        $query->where('status', PaymentStatus::CANCELED);
    }

    public function markAsSucceeded(string $chargeId, array $payload): bool
    {
        return $this->update([
            'status' => PaymentStatus::SUCCEEDED,
            'stripe_charge_id' => $chargeId,
            'stripe_payload' => $payload,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, array $payload): bool
    {
        return $this->update([
            'status' => PaymentStatus::CANCELED,
            'failure_reason' => $reason,
            'stripe_payload' => $payload,
        ]);
    }
}
