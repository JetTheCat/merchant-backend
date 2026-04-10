<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\PaymentIntentResult;
use App\DataTransferObjects\WebhookEventResult;
use App\Models\Payment;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
    ) {}

    public function createPaymentIntent(Payment $payment): PaymentIntentResult
    {
        $intent = $this->stripeClient->paymentIntents->create([
            'amount' => (int) bcmul((string) $payment->amount, '100'),
            'currency' => strtolower($payment->currency),
            'payment_method_types' => ['card', 'fpx', 'grabpay'],
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
            ],
        ]);

        return new PaymentIntentResult(
            paymentIntentId: $intent->id,
            clientSecret: $intent->client_secret,
        );
    }

    public function constructWebhookEvent(string $payload, string $signature): WebhookEventResult
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            config('stripe.webhook_secret'),
        );

        return new WebhookEventResult(
            eventId: $event->id,
            type: $event->type,
            payload: $event->toArray(),
        );
    }
}
