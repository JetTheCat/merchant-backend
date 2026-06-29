<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\DataTransferObjects\ChargeResult;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Jobs\ProcessStripeWebhook;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Services\CreditPurchaseService;
use App\Services\StripeService;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessStripeWebhookTest extends TestCase
{
    public function testItHandlesSucceededEvent(): void
    {
        $payment = Payment::factory()->create([
            'stripe_payment_intent_id' => 'pi_test_succeeded',
            'payment_method' => PaymentType::CARD,
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

        $this->mock(StripeService::class)
            ->shouldReceive('retrieveCharge')
            ->once()
            ->with($chargeId)
            ->andReturn(new ChargeResult(chargeId: $chargeId, paymentMethodType: 'card'));

        $this->mock(CreditPurchaseService::class)
            ->shouldReceive('grantCredits')
            ->once()
            ->withArgs(fn (Payment $p) => $p->id === $payment->id);

        ProcessStripeWebhook::dispatchSync($event);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::SUCCEEDED, $payment->status);
        $this->assertEquals($chargeId, $payment->stripe_charge_id);
        $this->assertEquals(PaymentType::CARD, $payment->payment_method);
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

        $this->mock(StripeService::class);

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
