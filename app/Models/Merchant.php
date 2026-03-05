<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantStatus;
use App\Enums\SettlementCycle;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
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
        'rejection_reason',
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
        'business_registration_number' => 'encrypted',
        'bank_account_number' => 'encrypted',
        'bank_account_name' => 'encrypted',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', MerchantStatus::ACTIVE->value);
    }

    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeNearLocation($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $earthRadiusKm = 6371;

        return $query->selectRaw(
            "*, (
                {$earthRadiusKm} * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance",
            [$latitude, $longitude, $latitude]
        )
            ->having('distance', '<=', $radiusKm)
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

    public function isPending(): bool
    {
        return $this->status === MerchantStatus::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === MerchantStatus::SUSPENDED;
    }

    public function isRejected(): bool
    {
        return $this->status === MerchantStatus::REJECTED;
    }
}
