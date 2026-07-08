<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformFeeReport extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'period_start',
        'period_end',
        'file_path',
        'total_gmv',
        'total_platform_fees',
        'total_processing_costs',
        'net_earnings',
        'status',
        'payment_reference',
        'paid_at',
        'generated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_gmv' => 'decimal:2',
        'total_platform_fees' => 'decimal:2',
        'total_processing_costs' => 'decimal:2',
        'net_earnings' => 'decimal:2',
        'status' => FeeReportStatus::class,
        'paid_at' => 'datetime',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', FeeReportStatus::PENDING);
    }

    public function scopeOverlapping(Builder $query, string $periodStart, string $periodEnd): Builder
    {
        return $query->where('period_start', '<=', $periodEnd)
            ->where('period_end', '>=', $periodStart);
    }
}
