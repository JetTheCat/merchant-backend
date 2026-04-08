<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StripeEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    #[Scope]
    protected function unprocessed(Builder $query): void
    {
        $query->whereNull('processed_at');
    }

    public function markAsProcessed(): bool
    {
        return $this->update(['processed_at' => now()]);
    }
}
