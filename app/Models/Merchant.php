<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantStatus;
use App\Enums\SettlementCycle;
use App\Observers\MerchantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([MerchantObserver::class])]
class Merchant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'business_name',
        'business_registration_number',
        'category_id',
        'description',
        'logo_url',
        'cover_image_url',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postcode',
        'latitude',
        'longitude',
        'platform_fee_rate',
        'settlement_cycle',
        'status',
        'credit_sales_paused_at',
        'liability_alerted_at',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected $casts = [
        'platform_fee_rate' => 'decimal:4',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'settlement_cycle' => SettlementCycle::class,
        'status' => MerchantStatus::class,
        'credit_sales_paused_at' => 'datetime',
        'liability_alerted_at' => 'datetime',
        'business_registration_number' => 'encrypted',
        'bank_account_number' => 'encrypted',
        'bank_account_name' => 'encrypted',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchantUsers(): HasMany
    {
        return $this->hasMany(MerchantUser::class);
    }

    public function creditPackages(): HasMany
    {
        return $this->hasMany(CreditPackage::class);
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(MerchantUser::class)
            ->where('role', 'owner');
    }

    public function merchantLocations(): HasMany
    {
        return $this->hasMany(MerchantLocation::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', MerchantStatus::ACTIVE->value);
    }

    public function scopeNotPaused($query)
    {
        return $query->whereNull('credit_sales_paused_at');
    }

    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeNearLocation($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $radiusMeters = $radiusKm * 1000;

        return $query
            ->selectRaw(
                '*, ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000 AS distance',
                [$longitude, $latitude]
            )
            ->whereRaw(
                'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$longitude, $latitude, $radiusMeters]
            )
            ->orderBy('distance');
    }

    public function getFormattedPlatformFeeRateAttribute(): string
    {
        return number_format($this->platform_fee_rate * 100, 2).'%';
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->postcode} {$this->city}, {$this->state}";
    }

    public function isActive(): bool
    {
        return $this->status === MerchantStatus::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === MerchantStatus::SUSPENDED;
    }

    public function isCreditSalesPaused(): bool
    {
        return $this->credit_sales_paused_at !== null;
    }
}
