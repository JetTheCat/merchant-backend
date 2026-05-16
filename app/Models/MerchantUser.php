<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Notifications\MerchantUserInvitationNotification;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
        'username',
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
        'role' => MerchantUserRole::class,
        'status' => MerchantUserStatus::class,
        'password' => 'hashed',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', MerchantUserStatus::ACTIVE->value);
    }

    #[Scope]
    protected function pendingVerification(Builder $query): void
    {
        $query->where('status', MerchantUserStatus::PENDING_VERIFICATION->value);
    }

    #[Scope]
    protected function byRole(Builder $query, string $role): void
    {
        $query->where('role', $role);
    }

    #[Scope]
    protected function owner(Builder $query): void
    {
        $query->where('role', MerchantUserRole::OWNER);
    }

    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->where('role', MerchantUserRole::STAFF);
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
        return $this->role === MerchantUserRole::OWNER;
    }

    public function hasUsername(): bool
    {
        return $this->username !== null;
    }

    public function isStaff(): bool
    {
        return $this->role === MerchantUserRole::STAFF;
    }

    public function canLogin(): bool
    {
        return $this->status->canLogin();
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function sendMerchantUserInvitationNotification(): void
    {
        $this->notify(new MerchantUserInvitationNotification);
    }
}
