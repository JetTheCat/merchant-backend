<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\MerchantUserRoles;
use App\Enums\MerchantUserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MerchantUser extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'status' => MerchantUserStatus::class,
        'password' => 'hashed',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', MerchantUserStatus::ACTIVE->value);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', MerchantUserStatus::PENDING_VERIFICATION->value);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOwners($query)
    {
        return $query->where('role', MerchantUserRoles::OWNER);
    }

    public function scopeStaff($query)
    {
        return $query->where('role', MerchantUserRoles::STAFF);
    }

    public function isActive(): bool
    {
        return $this->status === MerchantUserStatus::ACTIVE;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === MerchantUserStatus::PENDING_VERIFICATION;
    }

    public function isSuspended(): bool
    {
        return $this->status === MerchantUserStatus::SUSPENDED;
    }

    public function isOwner(): bool
    {
        return $this->role === MerchantUserRoles::OWNER;
    }

    public function isStaff(): bool
    {
        return $this->role === MerchantUserRoles::STAFF;
    }

    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}
