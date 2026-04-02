<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCredit extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'balance',
        'total_purchased',
        'total_redeemed',
        'last_purchase_at',
        'last_redemption_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_purchased' => 'decimal:2',
        'total_redeemed' => 'decimal:2',
        'last_purchase_at' => 'datetime',
        'last_redemption_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    #[Scope]
    protected function hasBalance(Builder $query): void
    {
        $query->where('balance', '>', 0);
    }

    #[Scope]
    protected function byMerchant(Builder $query, string $merchantId): void
    {
        $query->where('merchant_id', $merchantId);
    }
}
