<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CreditTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RedemptionCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CreditTransaction $transaction,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('merchant-location.' . $this->transaction->merchant_location_id);
    }

    public function broadcastAs(): string
    {
        return 'redemption.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'transactionId' => $this->transaction->id,
            'customerName' => $this->transaction->user->full_name,
            'amount' => $this->transaction->amount,
            'redeemedAt' => $this->transaction->redeemed_at,
        ];
    }
}
