<?php

declare(strict_types=1);

namespace Tests\Api\v1\Payments;

use App\DataTransferObjects\WebhookEventResult;
use App\Jobs\ProcessStripeWebhook;
use App\Models\StripeEvent;
use App\Services\StripeService;
use Illuminate\Support\Facades\Bus;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    private string $endpoint = '/api/v1/payments/stripe/webhook';

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake(ProcessStripeWebhook::class);
    }

    public function testInvalidSignatureReturnsUnauthorized(): void
    {
        $this->mock(StripeService::class)
            ->shouldReceive('constructWebhookEvent')
            ->andThrow(SignatureVerificationException::factory('Invalid signature.'));

        $this->postJson($this->endpoint, [], ['Stripe-Signature' => 'invalid'])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Bus::assertNothingDispatched();
    }

    public function testDuplicateEventIsIgnored(): void
    {
        $eventId = 'evt_test_duplicate';

        StripeEvent::factory()->create(['stripe_event_id' => $eventId]);

        $this->mock(StripeService::class)
            ->shouldReceive('constructWebhookEvent')
            ->andReturn(new WebhookEventResult(
                eventId: $eventId,
                type: 'payment_intent.succeeded',
                payload: [],
            ));

        $this->postJson($this->endpoint, [], ['Stripe-Signature' => 'valid'])
            ->assertStatus(Response::HTTP_OK);

        Bus::assertNothingDispatched();
        $this->assertDatabaseCount('stripe_events', 1);
    }

    public function testValidEventStoresEventAndDispatchesJob(): void
    {
        $mockResult = new WebhookEventResult(
            eventId: 'evt_test_123',
            type: 'payment_intent.succeeded',
            payload: ['data' => ['object' => ['id' => 'pi_test_123']]],
        );

        $this->mock(StripeService::class)
            ->shouldReceive('constructWebhookEvent')
            ->andReturn($mockResult);

        $this->postJson($this->endpoint, [], ['Stripe-Signature' => 'valid'])
            ->assertStatus(Response::HTTP_OK);

        $stripeEvent = StripeEvent::where('stripe_event_id', $mockResult->eventId)->first();

        $this->assertNotNull($stripeEvent);
        $this->assertEquals($mockResult->type, $stripeEvent->type);
        $this->assertEquals($mockResult->payload, $stripeEvent->payload);
        Bus::assertDispatched(ProcessStripeWebhook::class);
    }
}
