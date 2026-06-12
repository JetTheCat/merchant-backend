<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\RedemptionCompleted;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;
use Tests\TestCase;

class RedemptionCompletedTest extends TestCase
{
    public function testBroadcastsOnCorrectPrivateChannel(): void
    {
        $transaction = CreditTransaction::factory()->make([
            'merchant_location_id' => Str::uuid7()->toString(),
        ]);

        $channel = (new RedemptionCompleted($transaction))->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals("private-merchant-location.{$transaction->merchant_location_id}", $channel->name);
    }

    public function testBroadcastsWithCorrectEventName(): void
    {
        $transaction = CreditTransaction::factory()->make();

        $this->assertEquals('redemption.completed', (new RedemptionCompleted($transaction))->broadcastAs());
    }

    public function testBroadcastsWithCorrectPayload(): void
    {
        $user = User::factory()->make(['first_name' => 'Jet', 'last_name' => 'Lim']);

        $transaction = CreditTransaction::factory()->make([
            'amount' => 25.00,
            'redeemed_at' => now(),
        ]);

        $transaction->setRelation('user', $user);

        $payload = (new RedemptionCompleted($transaction))->broadcastWith();

        $this->assertEquals($transaction->id, $payload['transactionId']);
        $this->assertEquals('Jet Lim', $payload['customerName']);
        $this->assertEquals($transaction->amount, $payload['amount']);
        $this->assertNotNull($payload['redeemedAt']);
    }
}
