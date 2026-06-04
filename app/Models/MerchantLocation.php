<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MalaysianState;
use App\Observers\MerchantLocationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([MerchantLocationObserver::class])]
class MerchantLocation extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'redemption_code',
        'street',
        'city',
        'postcode',
        'state',
        'is_active',
    ];

    protected $casts = [
        'state' => MalaysianState::class,
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantUsers(): HasMany
    {
        return $this->hasMany(MerchantUser::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
