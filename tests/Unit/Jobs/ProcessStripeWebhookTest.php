<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessStripeWebhook;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Services\CreditPurchaseService;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessStripeWebhookTest extends TestCase
{
    public function testItHandlesSucceededEvent(): void
    {
        $payment = Payment::factory()->create([
            'stripe_payment_intent_id' => 'pi_test_succeeded',
            'status' => PaymentStatus::PROCESSING,
        ]);

        $chargeId = Str::random(24);

        $event = StripeEvent::factory()->create([
            'stripe_event_id' => 'evt_test_succeeded',
            'type' => 'payment_intent.succeeded',
            'payload' => [
                'data' => [
                    'object' => [
                        'id' => $payment->stripe_payment_intent_id,
                        'latest_charge' => $chargeId,
                    ],
                ],
            ],
        ]);

        $this->mock(CreditPurchaseService::class)
            ->shouldReceive('grantCredits')
            ->once()
            ->withArgs(fn (Payment $p) => $p->id === $payment->id);

        ProcessStripeWebhook::dispatchSync($event);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::SUCCEEDED, $payment->status);
        $this->assertEquals($chargeId, $payment->stripe_charge_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function testItHandlesFailedEvent(): void
    {
        $payment = Payment::factory()->create([
            'stripe_payment_intent_id' => 'pi_test_failed',
            'status' => PaymentStatus::PROCESSING,
        ]);

        $event = StripeEvent::factory()->create([
            'stripe_event_id' => 'evt_test_failed',
            'type' => 'payment_intent.payment_failed',
            'payload' => [
                'data' => [
                    'object' => [
                        'id' => $payment->stripe_payment_intent_id,
                        'last_payment_error' => [
                            'message' => 'Your card was declined.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->mock(CreditPurchaseService::class)
            ->shouldReceive('failPurchase')
            ->once()
            ->withArgs(fn (Payment $p) => $p->id === $payment->id);

        ProcessStripeWebhook::dispatchSync($event);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::PAYMENT_FAILED, $payment->status);
        $this->assertEquals('Your card was declined.', $payment->failure_reason);
        $this->assertNotNull($event->fresh()->processed_at);
    }
}
